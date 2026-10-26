#!/usr/bin/env python3
"""Record a briefing when the drop has a script and no mp4.

The GitHub connector used by the morning agent can commit the JSON and
cannot commit the recording. This reads audio_sections (or audio_script)
and writes audio/brief_YYYY-MM-DD.mp4 in the Kokoro voice the site already
uses, plus the sentence timings the read-along view expects.
"""

from __future__ import annotations

import argparse
import json
import re
import shutil
import subprocess
import sys
import wave
from datetime import date, datetime, timedelta, timezone
from pathlib import Path

DATE = re.compile(r"^\d{4}-\d{2}-\d{2}$")
VOICE = "af_heart"
SENTENCE_GAP = 0.16
SECTION_GAP = 0.32


def split_sent(paragraph: str) -> list[str]:
    """Same sentence split the read-along view uses."""
    text = re.sub(r"\s+", " ", paragraph).strip()
    text = re.sub(r"\b(Dr|Mr|Ms|Mrs|St|No|vs)\.", r"\1<DOT>", text)
    parts = re.split(r"(?<=[.!?])\s+(?=[A-Z0-9\"'“])", text)
    return [part.replace("<DOT>", ".").strip() for part in parts if part.strip()]


def lines_for(payload: dict) -> list[dict]:
    sections = payload.get("audio_sections")
    if isinstance(sections, list) and sections and all(isinstance(s, dict) and str(s.get("text") or "").strip() for s in sections):
        rows = []
        for index, section in enumerate(sections):
            paragraphs = [p.strip() for p in re.split(r"\n\s*\n", str(section["text"])) if p.strip()]
            for paragraph_index, paragraph in enumerate(paragraphs):
                for sentence in split_sent(paragraph):
                    rows.append({
                        "s": index,
                        "p": paragraph_index,
                        "text": sentence,
                        "id": section.get("id") or f"s{index}",
                        "title": section.get("title") or "",
                    })
        if rows:
            return rows
    script = str(payload.get("audio_script") or "").strip()
    if not script:
        return []
    rows = []
    paragraphs = [p.strip() for p in re.split(r"\n\s*\n", script) if p.strip()]
    for paragraph_index, paragraph in enumerate(paragraphs):
        for sentence in split_sent(paragraph):
            rows.append({"s": 0, "p": paragraph_index, "text": sentence, "id": "brief", "title": "Briefing"})
    return rows


def needs_recording(payload: dict, mp4: Path) -> bool:
    if mp4.is_file() and not mp4.is_symlink() and mp4.stat().st_size >= 1000:
        return False
    return bool(lines_for(payload))


def write_wav(path: Path, samples, sample_rate: int) -> None:
    import numpy as np

    pcm = (np.clip(samples, -1, 1) * 32767).astype(np.int16)
    with wave.open(str(path), "wb") as handle:
        handle.setnchannels(1)
        handle.setsampwidth(2)
        handle.setframerate(sample_rate)
        handle.writeframes(pcm.tobytes())


def to_mp4(wav: Path, mp4: Path) -> None:
    ffmpeg = shutil.which("ffmpeg")
    if not ffmpeg:
        raise SystemExit("ffmpeg is not installed, so the narration cannot be encoded.")
    subprocess.run(
        [ffmpeg, "-y", "-loglevel", "error", "-i", str(wav), "-c:a", "aac", "-b:a", "96k", "-ac", "1", "-ar", "24000", "-movflags", "+faststart", str(mp4)],
        check=True,
    )


def record(payload: dict, mp4: Path, model: Path, voices: Path) -> dict:
    import numpy as np
    from kokoro_onnx import Kokoro

    rows = lines_for(payload)
    if not rows:
        raise SystemExit("The edition has no narration script.")
    voice = Kokoro(str(model), str(voices))
    if VOICE not in voice.get_voices():
        raise SystemExit(f"The Kokoro voice {VOICE} is not in the voice file.")

    pieces = []
    segments = []
    section_starts: dict[int, float] = {}
    cursor = 0.0
    sample_rate = 24000
    previous_section = None
    for row in rows:
        samples, sample_rate = voice.create(row["text"], voice=VOICE, speed=1.0, lang="en-us")
        samples = np.asarray(samples, dtype=np.float32)
        if samples.size < sample_rate // 20:
            raise SystemExit(f"The narration produced silence for: {row['text'][:80]}")
        gap = SECTION_GAP if previous_section not in (None, row["s"]) else (0.0 if previous_section is None else SENTENCE_GAP)
        if gap:
            pieces.append(np.zeros(int(gap * sample_rate), dtype=np.float32))
            cursor += gap
        if row["s"] not in section_starts:
            section_starts[row["s"]] = round(cursor, 2)
        start = cursor
        pieces.append(samples)
        cursor += len(samples) / sample_rate
        segments.append({
            "p": row["p"],
            "s": row["s"],
            "t0": round(start, 2),
            "t1": round(cursor, 2),
            "text": row["text"],
        })
        previous_section = row["s"]

    wav = mp4.with_suffix(".wav")
    mp4.parent.mkdir(parents=True, exist_ok=True)
    write_wav(wav, np.concatenate(pieces), sample_rate)
    try:
        to_mp4(wav, mp4)
    finally:
        wav.unlink(missing_ok=True)

    seen = []
    for row in rows:
        if row["s"] not in {item["s"] for item in seen}:
            seen.append(row)
    return {
        "engine": "Kokoro neural TTS",
        "voice": VOICE,
        "style": "reader",
        "generated_at": datetime.now(timezone.utc).replace(microsecond=0).isoformat(),
        "seconds": round(cursor, 1),
        "url": f"app/audio/brief_{payload['date']}.mp4",
        "sections": [{"id": row["id"], "title": row["title"], "t0": section_starts[row["s"]]} for row in seen],
        "segments": segments,
    }


def narrate(editions: Path, model: Path, voices: Path) -> list[str]:
    folder = editions / "briefings"
    if not folder.is_dir():
        return []
    done = []
    for path in sorted(folder.glob("*.json")):
        if path.is_symlink() or not DATE.match(path.stem):
            continue
        payload = json.loads(path.read_text(encoding="utf-8"))
        if not isinstance(payload, dict) or payload.get("date") != path.stem:
            continue
        today = datetime.now(timezone.utc).date()
        try:
            filed = date.fromisoformat(path.stem)
        except ValueError:
            continue
        if not (today - timedelta(days=3) <= filed <= today + timedelta(days=1)):
            continue
        mp4 = editions / "audio" / f"brief_{path.stem}.mp4"
        if not needs_recording(payload, mp4):
            continue
        payload["audio"] = record(payload, mp4, model, voices)
        path.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        done.append(f"{path.stem} {payload['audio']['seconds']}s")
    return done


def self_test() -> None:
    payload = {
        "date": "2026-10-26",
        "audio_sections": [
            {"id": "opening", "title": "Opening", "text": "Good morning.\n\nDr. Kim spoke. She was brief."},
        ],
    }
    rows = lines_for(payload)
    assert [row["text"] for row in rows] == ["Good morning.", "Dr. Kim spoke.", "She was brief."], rows
    assert rows[0]["p"] == 0 and rows[1]["p"] == 1
    assert needs_recording(payload, Path("/no/such/file.mp4"))
    print("self-test ok")


def main() -> None:
    if "--self-test" in sys.argv:
        self_test()
        return
    parser = argparse.ArgumentParser()
    parser.add_argument("--editions", type=Path, required=True)
    parser.add_argument("--model", type=Path, required=True)
    parser.add_argument("--voices", type=Path, required=True)
    args = parser.parse_args()
    done = narrate(args.editions, args.model, args.voices)
    print("narrated " + ", ".join(done) if done else "no narration needed")


if __name__ == "__main__":
    main()
