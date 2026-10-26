#!/usr/bin/env python3
"""Copy a checked briefing from the editions drop into the site data.

The drop repository is the only place an outside publisher can write.
This script reads two kinds of file from it and ignores everything else:

  briefings/YYYY-MM-DD.json
  audio/brief_YYYY-MM-DD.mp4

A file that fails the checks is not copied. Run with --self-test to
exercise those checks without touching the real briefing archive.
"""

from __future__ import annotations

import argparse
import json
import re
import shutil
import sys
import tempfile
from datetime import date, datetime, timedelta, timezone
from pathlib import Path

DATE = re.compile(r"^\d{4}-\d{2}-\d{2}$")
AUDIO_NAME = re.compile(r"^brief_(\d{4}-\d{2}-\d{2})\.mp4$")
SECRET = re.compile(
    "|".join(
        [
            r"sk-ant-",
            r"sk_live_",
            r"sk_test_",
            r"-----BEGIN [A-Z ]*PRIVATE KEY-----",
            r"\bAKIA[0-9A-Z]{16}\b",
            r"\bgh[pousr]_[A-Za-z0-9]{20,}",
            r"\bgithub_pat_[A-Za-z0-9_]+",
            r"\bxox[baprs]-[A-Za-z0-9-]+",
            r"ANTHROPIC_API_KEY\s*=",
            r"PADDLE_(API_KEY|WEBHOOK_SECRET)\s*=",
        ]
    )
)
MAX_JSON = 1_500_000
MAX_AUDIO = 30_000_000
KEEP = 21


def fail(message: str) -> None:
    raise SystemExit(message)


def edition_files(root: Path) -> list[Path]:
    folder = root / "briefings"
    if not folder.is_dir():
        return []
    found = []
    for path in sorted(folder.glob("*.json")):
        if path.is_symlink() or not DATE.match(path.stem):
            continue
        if path.stat().st_size > MAX_JSON:
            fail(f"{path.name} is too large to publish.")
        found.append(path)
    return found


def in_window(day: str, today: date) -> bool:
    try:
        parsed = date.fromisoformat(day)
    except ValueError:
        return False
    return today - timedelta(days=3) <= parsed <= today + timedelta(days=1)


def check_edition(payload: dict, day: str) -> None:
    if payload.get("date") != day:
        fail(f"{day}: the date inside the file does not match its name.")
    headline = payload.get("headline")
    if not isinstance(headline, str) or not headline.strip() or len(headline) > 300:
        fail(f"{day}: a headline is required.")
    global_items = payload.get("global")
    if not isinstance(global_items, list) or not global_items:
        fail(f"{day}: at least one global item is required.")
    for item in global_items:
        if not isinstance(item, dict):
            fail(f"{day}: a global item is not an object.")
        if not str(item.get("title") or "").strip() or not str(item.get("summary") or "").strip():
            fail(f"{day}: every global item needs a title and a summary.")
    for key in ("regions", "countries"):
        if key in payload and not isinstance(payload[key], list):
            fail(f"{day}: {key} must be a list.")
    audio = payload.get("audio")
    if audio is not None:
        if not isinstance(audio, dict):
            fail(f"{day}: audio must be an object.")
        url = audio.get("url")
        expected = f"app/audio/brief_{day}.mp4"
        if url is not None and url != expected:
            fail(f"{day}: audio url must be {expected}.")
    text = json.dumps(payload, ensure_ascii=False)
    if SECRET.search(text):
        fail(f"{day}: the file contains a secret and was refused.")
    if re.search(r"<\s*script|javascript\s*:|onerror\s*=", text, re.I):
        fail(f"{day}: the file contains markup the site will not publish.")


def audio_source(root: Path, day: str) -> Path | None:
    path = root / "audio" / f"brief_{day}.mp4"
    if not path.exists():
        return None
    if path.is_symlink() or not path.is_file() or AUDIO_NAME.match(path.name) is None:
        fail(f"{day}: the audio file is not a plain mp4.")
    if path.stat().st_size > MAX_AUDIO or path.stat().st_size < 1000:
        fail(f"{day}: the audio file is an unexpected size.")
    return path


def newer(candidate: dict, current: dict | None) -> bool:
    if current is None:
        return True
    return str(candidate.get("generated_at") or "") > str(current.get("generated_at") or "")


def publish(editions: Path, live_path: Path, audio_dir: Path, today: date | None = None) -> list[str]:
    today = today or datetime.now(timezone.utc).date()
    raw = live_path.read_text(encoding="utf-8")
    live = json.loads(raw)
    briefings = live.setdefault("collections", {}).setdefault("briefings", {})
    if not isinstance(briefings, dict):
        fail("The briefing archive is not in the expected shape.")

    accepted: dict[str, dict] = {}
    clips: dict[str, Path] = {}
    for path in edition_files(editions):
        day = path.stem
        if not in_window(day, today):
            continue
        payload = json.loads(path.read_text(encoding="utf-8"))
        if not isinstance(payload, dict):
            fail(f"{day}: the briefing must be a JSON object.")
        check_edition(payload, day)
        current = briefings.get(day) if isinstance(briefings.get(day), dict) else None
        if not newer(payload, current):
            continue
        clip = audio_source(editions, day)
        if clip is not None:
            clips[day] = clip
            payload.setdefault("audio", {})["url"] = f"app/audio/brief_{day}.mp4"
        elif isinstance(payload.get("audio"), dict) and payload["audio"].get("url"):
            expected = audio_dir / f"brief_{day}.mp4"
            if not expected.is_file():
                fail(f"{day}: the briefing points at audio that was not provided.")
        accepted[day] = payload

    for day, clip in clips.items():
        audio_dir.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(clip, audio_dir / f"brief_{day}.mp4")

    if not accepted:
        return []

    merged = dict(sorted(accepted.items(), reverse=True))
    for day, payload in briefings.items():
        merged.setdefault(day, payload)
    kept = sorted(merged, reverse=True)[:KEEP]
    live["collections"]["briefings"] = {day: merged[day] for day in kept}
    live["generated_at"] = datetime.now(timezone.utc).replace(microsecond=0).isoformat()
    encoded = json.dumps(live, ensure_ascii=False, separators=(",", ":"))
    live_path.write_text(encoded, encoding="utf-8")
    return kept[: len(accepted)]


def self_test() -> None:
    with tempfile.TemporaryDirectory() as tmp:
        root = Path(tmp)
        editions = root / "editions"
        (editions / "briefings").mkdir(parents=True)
        (editions / "audio").mkdir()
        live = root / "live.json"
        archive = {
            "generated_at": "2026-10-23T17:45:57+00:00",
            "collections": {"briefings": {"2026-10-23": {"date": "2026-10-23", "headline": "Old", "generated_at": "2026-10-23T05:00:00+00:00", "global": [{"title": "Old", "summary": "Old"}]}}},
        }
        live.write_text(json.dumps(archive, ensure_ascii=False, separators=(",", ":")), encoding="utf-8")
        good = {
            "date": "2026-10-26",
            "generated_at": "2026-10-26T04:00:00+00:00",
            "headline": "A normal headline for the morning edition.",
            "global": [{"title": "One story", "summary": "What happened, and who said it."}],
            "regions": [],
            "countries": [],
        }
        (editions / "briefings" / "2026-10-26.json").write_text(json.dumps(good), encoding="utf-8")
        (editions / "notes.txt").write_text("ignored", encoding="utf-8")
        published = publish(editions, live, root / "audio", date(2026, 10, 26))
        assert published == ["2026-10-26"], published
        again = publish(editions, live, root / "audio", date(2026, 10, 26))
        assert again == [], again

        leaked = dict(good, generated_at="2026-10-26T05:00:00+00:00", summary=["sk-ant-this-must-not-publish"])
        (editions / "briefings" / "2026-10-26.json").write_text(json.dumps(leaked), encoding="utf-8")
        try:
            publish(editions, live, root / "audio", date(2026, 10, 26))
        except SystemExit as error:
            assert "secret" in str(error), error
        else:
            raise AssertionError("a secret was published")
    print("self-test ok")


def main() -> None:
    if "--self-test" in sys.argv:
        self_test()
        return
    parser = argparse.ArgumentParser()
    parser.add_argument("--editions", type=Path, required=True)
    parser.add_argument("--live", type=Path, required=True)
    parser.add_argument("--audio-dir", type=Path, required=True)
    args = parser.parse_args()
    published = publish(args.editions, args.live, args.audio_dir)
    if published:
        print("published " + ", ".join(published))
    else:
        print("no new edition")


if __name__ == "__main__":
    main()
