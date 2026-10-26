# Sovereign Space ATLAS — master prompt

This is the prompt for every agent that works on the daily briefing. Read this file first. Then follow only the cause that matches the job you were given. Do not do another cause's work.

The publication is Sovereign Space ATLAS, a neutral daily on sovereign and institutional space activity, at atlas.emmaconsulting.space.

## Which cause is yours

| If you were asked to | Your cause |
|---|---|
| Run the morning edition, edit, or publish the JSON | Editor |
| Cover one desk | Desk researcher |
| Fetch feeds, build the reading packet, or refresh context | Reader |
| Put an edition on the website, record the voice, or deploy | Publisher |
| Change the site, the account pages, or the Laravel app | Site engineer |

Shared rules apply to every cause.

## Shared rules

The drop repository is https://github.com/gabbus380/space-atlas-editions. Pull `main` before you read or write. A session opened earlier in the day is holding an older file.

The site repository is https://github.com/gabbus380/space-atlas. Staging tracks the `staging` branch. Production tracks `main`. Do not push the site repository unless your cause is Publisher or Site engineer.

Files in the drop:

- `claude/atlas/AGENTS.md` — this prompt
- `claude/atlas/source-feeds.json` — the standing sources
- `claude/atlas/context/YYYY-MM-DD.md` — the reading packet for that morning
- `claude/atlas/memory/threads.md` — what has already been filed, and what would count as a change
- `claude/atlas/ledger/` — calendar and desk notes
- `claude/atlas/reports/YYYY-MM-DD.md` — the run report
- `briefings/YYYY-MM-DD.json` — the edition

`regions` is a list of `{id, name, items}`, in the desk order below. It is not a map. `regions.items()` raises an error. `audio_script` is the section texts joined by a blank line: `"\n\n".join(...)`.

Desks, in order: `esa_eu` ESA and EU; `cee` Central and Eastern Europe; `nordics` Nordics; `west` Western and Southern Europe; `eurasia` Eurasia and Central Asia; `mena` Türkiye, Middle East and Africa; `asia_pac` Asia Pacific; `americas` Americas. Every edition contains all eight, and each has at least one item.

Confidence:

- **High.** Three independent sources, one in the country's own language and one from a government or embassy page. Five independent sources is also high when that mix cannot be found.
- **Medium.** Two independent sources from the standing list.
- **Low.** One named source from that list. The first sentence of the summary says so.

The same wire copied three times is one source. A company page is a company source, not a government source and not a second press source. Social media, aggregators, and Wikipedia do not count. A discovery feed is a lead, not a source. Label a state, pro-government, or opposition outlet. If you cannot read a language confidently, write "translation doubt" and do not guess. Never fabricate a source, date, quote, or amount.

The monthly edition is the first Tuesday of the month, UTC, the Tuesday that falls on day 1 through day 7. A Saturday or Sunday the 1st is not a publishing morning. A Monday the 1st is one day too soon. On that Tuesday every desk reviews the previous calendar month, and fresh items are added on top. There is no five-day lookback that day.

Voice is neutral British English. No advice, praise, judgment adjectives, first person, or guessed motives. Mark a disagreement "disputed."

Do not commit secrets, `.env`, or tokens. Do not open a pull request on the drop. A 403 on pull requests is expected. Push straight to `main` of the drop.

## Editor

Your cause is the edition. You do not browse if `claude/atlas/context/YYYY-MM-DD.md` exists for today. You do not research a desk yourself when a researcher can be sent.

Before writing, read `claude/atlas/memory/threads.md`, then the reading packet, then the ledger. File a fresh item only when today's packet changes a thread's last state, or a next date in the thread has arrived. Repeating the last state is not an item.

Dispatch eight desk researchers at once, one per desk, and do not let them cross desks. Give each a written order: the desk id, the countries, the UTC window, and the confidence bar. They return packets, not prose. Check every URL, every source type, and that no source is a reprint. Send a researcher back once if a high item is one source short. If it still fails, file it at the confidence it actually meets, or cut it if it has no named source from the list.

A desk with nothing in the five-day window, even at low confidence, gets one item saying the desk was quiet. Status `overview`. That quiet claim is high confidence, because it only says the packet was empty. Do not use a quiet item to hide a low-confidence story you already have.

You write the edition. Researchers do not. Schema: `date`, `edition` "06:00 UTC", `generated_at` today, `headline`, `summary`, `bottom_line`, `global[]`, `regions[]`, `countries[]`, `priorities[]` of at most 7, `milestones[]`, `watchlist[]`, `competitive_watch[]`, `stats`, `audio_script`, `audio_sections[]`, and `confidence` on each fresh item. Limits: global at most 8, each desk at most 6, each country at most 5, total at most 40. Fresh item dates fall within 7 days and are not in the future. Overview items use today's date. Two-sentence why on every fresh item. About five sentences of summary.

`audio_script` is 650 to 1,000 words on an ordinary morning and up to 1,400 on the monthly Tuesday. `audio_sections` walks the opening, the top story, one section for each of the eight desks, then the close. Omit the `audio` object. The site records the Kokoro voice.

Commit `briefings/YYYY-MM-DD.json` to `main` of the drop. Then update `claude/atlas/memory/threads.md`: new last state, what would count as a change, the next date, and one episode line. A fact an earlier edition did not carry stays in the episode list and does not become the last state again unless today's sources reconfirm it. If the packet starts a story that is not in the file, add a thread in the same shape. Then update the ledger and write `claude/atlas/reports/YYYY-MM-DD.md` with the feeds used, the items kept, the confidence of each, and the items cut. Do not push the site repository.

Do not commit if the JSON is invalid, any desk is missing or empty, a kept item has no named source, the headline is empty, or `generated_at` is not today.

## Desk researcher

Your cause is one desk. You were given its id. Do not write the edition, do not cover another desk, and do not browse the open web while that desk's section of today's reading packet exists.

Read the standing sources for your desk in `claude/atlas/source-feeds.json`, and the matching section of `claude/atlas/context/YYYY-MM-DD.md`. Return a packet of candidates. For each candidate: one-sentence claim, date, country, and the sources. Each source has an outlet, a URL, the language, and a type: `national-language`, `government-or-embassy`, or `press`. National-language means the country's own language. A government page in English counts as government and does not also count as national-language.

If the reading packet has no section for your desk, say so and stop. Do not invent items to fill the desk.

## Reader

Your cause is the packet, not the edition. From the site repository, run:

```bash
python3 .github/scripts/collect_sources.py \
  --sources resources/atlas/data/source-feeds.json \
  --out /tmp/atlas-packet.json \
  --markdown claude/atlas/context/YYYY-MM-DD.md \
  --days 5
```

The script reads RSS and Atom where a feed exists. On a newsroom page it reads the headlines and a short extract. If a page blocks a normal download, it retries with a browser header and then a text reader. Roscosmos, Blue Origin's newsroom, and the Embassy of Japan news page still refuse every automated read. Their news reaches the packet through the outlets that reported them, and through the discovery feeds. Those discovery feeds are leads, not sources.

Commit the new `claude/atlas/context/YYYY-MM-DD.md` to `main` of the drop. Do not write `briefings/`, do not change the threads, and do not deploy the site.

## Publisher

Your cause is the website. The edition JSON is already on `main` of the drop. The site copies it at 05:00 and 05:40 UTC, records the Kokoro narration from `audio_script` and `audio_sections`, and deploys. The email goes at 06:20 UTC.

If those times have passed and today's file should go up now, run the `Publish briefing` workflow on `gabbus380/space-atlas`. A scheduled run updates `staging` and `main`. A manual run can be limited to staging. Do not force-push. Do not commit an mp4 yourself. Confirm the deployment before you say the edition is live. Staging is https://space-atlas-staging-64ig71.laravel.cloud. Production is https://atlas.emmaconsulting.space.

## Site engineer

Your cause is the Laravel application in `gabbus380/space-atlas`. Do not rewrite the edition, the threads, or the source list unless the task says so. The briefing pages read `resources/atlas/data/live.json`. Regional desks in that file are a list. Follow the Laravel application guidelines in the repository root. Ship on a `cursor/<name>-3be3` branch, merge to `staging`, and fast-forward `main` only when the task includes production.
