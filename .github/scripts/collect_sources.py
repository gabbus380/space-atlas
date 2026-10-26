#!/usr/bin/env python3
"""Read the standing source list and turn it into a packet.

RSS and Atom feeds yield headlines, links, dates, and the feed's own summary.
A newsroom page with no feed yields the headlines on that page, then the text
of a few of those articles. Nothing here opens a browser.
"""

from __future__ import annotations

import argparse
import email.utils
import json
import re
import ssl
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timedelta, timezone
from html.parser import HTMLParser
from urllib.parse import urljoin, urlparse
from urllib.request import Request, urlopen
from xml.etree import ElementTree

UA = {"User-Agent": "Mozilla/5.0 (compatible; ATLAS-feed-reader/1.0)"}
SKIP = re.compile(
    r"\b(cookie|privacy|subscribe|sign in|log in|newsletter|facebook|linkedin|instagram|menu|search)\b",
    re.I,
)


def local(tag: str) -> str:
    return tag.rsplit("}", 1)[-1].lower()


def text_of(node) -> str:
    return re.sub(r"\s+", " ", "".join(node.itertext())).strip()


def strip_html(value: str) -> str:
    return re.sub(r"<[^>]+>", " ", value or "")


def clean(value: str, limit: int = 500) -> str:
    value = re.sub(r"\s+", " ", strip_html(value)).strip()
    return value[:limit].rstrip()


def parse_date(value: str | None) -> datetime | None:
    if not value:
        return None
    value = value.strip()
    try:
        when = email.utils.parsedate_to_datetime(value)
        if when.tzinfo is None:
            when = when.replace(tzinfo=timezone.utc)
        return when.astimezone(timezone.utc)
    except (TypeError, ValueError, IndexError):
        pass
    try:
        when = datetime.fromisoformat(value.replace("Z", "+00:00"))
        if when.tzinfo is None:
            when = when.replace(tzinfo=timezone.utc)
        return when.astimezone(timezone.utc)
    except ValueError:
        return None


def sources_in(data: dict) -> list[dict]:
    found = []

    def walk(node, desk=None, iso3=None):
        if isinstance(node, dict) and "url" in node and "role" in node:
            row = dict(node)
            row["desk"] = desk
            row["iso3"] = iso3
            found.append(row)
            return
        if isinstance(node, dict):
            for key, value in node.items():
                if key == "desks" and isinstance(value, dict):
                    for desk_id, desk_body in value.items():
                        walk(desk_body, desk_id, iso3)
                elif key == "countries" and isinstance(value, list):
                    for country in value:
                        walk(country.get("sources") or [], desk, country.get("iso3"))
                else:
                    walk(value, desk, iso3)
        elif isinstance(node, list):
            for value in node:
                walk(value, desk, iso3)

    walk(data)
    return found


def fetch(url: str, timeout: int) -> tuple[int, bytes]:
    request = Request(url, headers=UA)
    context = ssl.create_default_context()
    with urlopen(request, timeout=timeout, context=context) as response:
        return response.status, response.read(1_500_000)


def parse_feed(body: bytes, page_url: str) -> list[dict]:
    root = ElementTree.fromstring(body)
    items = []
    for node in root.iter():
        if local(node.tag) not in {"item", "entry"}:
            continue
        title = link = summary = when = ""
        for child in list(node):
            name = local(child.tag)
            if name == "title" and not title:
                title = text_of(child)
            elif name == "link" and not link:
                link = child.attrib.get("href") or text_of(child)
            elif name in {"description", "summary", "encoded"} and not summary:
                summary = text_of(child)
            elif name in {"pubdate", "published", "updated", "date"} and not when:
                when = text_of(child)
        if title:
            items.append({
                "title": clean(title, 240),
                "url": urljoin(page_url, link) if link else page_url,
                "date": when,
                "summary": clean(summary, 500),
            })
    return items


class LinkParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.links = []
        self._href = None
        self._parts = []

    def handle_starttag(self, tag, attrs):
        if tag == "a":
            self._href = dict(attrs).get("href")
            self._parts = []

    def handle_data(self, data):
        if self._href is not None:
            self._parts.append(data)

    def handle_endtag(self, tag):
        if tag == "a" and self._href is not None:
            text = re.sub(r"\s+", " ", " ".join(self._parts)).strip()
            self.links.append((self._href, text))
            self._href = None


def parse_listing(body: bytes, page_url: str) -> list[dict]:
    parser = LinkParser()
    parser.feed(body.decode("utf-8", "replace"))
    host = urlparse(page_url).netloc
    seen = set()
    items = []
    for href, title in parser.links:
        if not href or len(title) < 40 or len(title) > 200 or SKIP.search(title):
            continue
        absolute = urljoin(page_url, href.split("#")[0])
        parsed = urlparse(absolute)
        if parsed.scheme not in {"http", "https"} or parsed.netloc != host:
            continue
        if absolute in seen or absolute.rstrip("/") == page_url.rstrip("/"):
            continue
        seen.add(absolute)
        items.append({"title": title, "url": absolute, "date": "", "summary": ""})
        if len(items) == 12:
            break
    return items


def article_text(url: str, timeout: int) -> str:
    try:
        _status, body = fetch(url, timeout)
    except Exception:
        return ""
    html = re.sub(r"(?is)<(script|style|nav|footer|header).*?>.*?</\1>", " ", body.decode("utf-8", "replace"))
    text = clean(html, 900)
    return text


def recent(items: list[dict], cutoff: datetime) -> list[dict]:
    kept = []
    for item in items:
        when = parse_date(item.get("date"))
        if when is not None and when < cutoff:
            continue
        kept.append(item)
    return kept or items[:8]


def read_source(source: dict, cutoff: datetime, timeout: int, articles: int) -> dict:
    result = {
        "name": source.get("name"),
        "url": source.get("url"),
        "role": source.get("role"),
        "language": source.get("language"),
        "desk": source.get("desk"),
        "iso3": source.get("iso3"),
        "kind": source.get("kind"),
        "ok": False,
        "error": "",
        "items": [],
    }
    if source.get("reachable") is False:
        result["error"] = "marked unreachable"
        return result
    try:
        _status, body = fetch(source["url"], timeout)
    except Exception as error:
        result["error"] = type(error).__name__
        return result
    head = body[:400].lower()
    try:
        if b"<rss" in head or b"<feed" in head or b"rdf:rdf" in head:
            items = parse_feed(body, source["url"])
            result["kind"] = "feed"
        else:
            items = parse_listing(body, source["url"])
            result["kind"] = "newsroom"
            for item in items[:articles]:
                item["summary"] = article_text(item["url"], timeout)
    except ElementTree.ParseError:
        items = parse_listing(body, source["url"])
        result["kind"] = "newsroom"
    result["items"] = recent(items, cutoff)[:20]
    result["ok"] = True
    return result


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--sources", required=True)
    parser.add_argument("--out", required=True)
    parser.add_argument("--days", type=int, default=5)
    parser.add_argument("--timeout", type=int, default=12)
    parser.add_argument("--articles", type=int, default=2)
    parser.add_argument("--workers", type=int, default=12)
    args = parser.parse_args()
    data = json.loads(open(args.sources, encoding="utf-8").read())
    rows = sources_in(data)
    cutoff = datetime.now(timezone.utc) - timedelta(days=args.days)
    done = []
    with ThreadPoolExecutor(args.workers) as pool:
        futures = [pool.submit(read_source, row, cutoff, args.timeout, args.articles) for row in rows]
        for future in as_completed(futures):
            done.append(future.result())
    done.sort(key=lambda row: (row.get("desk") or "", row.get("name") or ""))
    packet = {
        "collected_at": datetime.now(timezone.utc).replace(microsecond=0).isoformat(),
        "window_days": args.days,
        "sources": done,
    }
    with open(args.out, "w", encoding="utf-8") as handle:
        json.dump(packet, handle, ensure_ascii=False)
    ok = [row for row in done if row["ok"]]
    items = sum(len(row["items"]) for row in ok)
    print(f"sources {len(done)} read {len(ok)} failed {len(done) - len(ok)} items {items}")
    for row in sorted(done, key=lambda item: item.get("error") or ""):
        if not row["ok"]:
            print(f"FAIL {row['error']:16} {row['name']}")


if __name__ == "__main__":
    main()
