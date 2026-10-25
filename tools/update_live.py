"""Build resources/atlas/data/live.json (and resources/atlas/audio/) for the hosted Sovereign Space ATLAS.

Usage: python3 update_live.py <export_dir> <audio_dir> <app_dir>
  export_dir  one sub-folder per collection, one <doc_id>.json per document
              (the layout ArtifactData list/query writes with out_dir)
  audio_dir   briefing audio files named <asset id>.mp4 (Artifact read with path=<asset id>)
  app_dir     the Laravel app folder. The server filters this data by plan, so none of it is public.
Exit code 2 when the leak scan finds internal material. Nothing is written in that case."""
import json, os, re, shutil, sys, datetime

EXPORT, AUDIO, SITE = sys.argv[1:4]
PUBLIC = ["briefings", "country", "states", "hubs", "feeds", "blackhat", "orgs", "projects"]   # never: notes, config, lens
KEEP_BRIEFS = 21
DROP_FIELDS = {"blackhat": ["threat", "axiom_angle"], "briefings": ["implications"]}
LEAKS = [r"\bGab[oó]r\b", r"Magyari", r"this desk", r"lens note", r"axiom_angle", r"for Axiom\b", r"Axiom's position",
         r"\bODCs?\b", r"orbital data cent", r"Marketplace", r"Ferencz"]

def clean(v, key=""):
    if isinstance(v, dict): return {k: clean(x, k) for k, x in v.items()}
    if isinstance(v, list): return [clean(x, key) for x in v]
    if isinstance(v, str) and not re.match(r"^(https?:|/|data/)", v) and "url" not in key.lower():
        v = re.sub(r"(\d)\s*–\s*(\d)", r"\1 to \2", v)
        v = re.sub(r"\s*[—–]\s*", ", ", v)
    return v

coll = {}
for name in PUBLIC:
    d = os.path.join(EXPORT, name)
    if not os.path.isdir(d): continue
    docs = {}
    for f in sorted(os.listdir(d)):
        if not f.endswith(".json") or f.startswith("_"): continue
        doc = json.load(open(os.path.join(d, f)))
        if isinstance(doc, dict) and doc.get("deleted"): continue
        for k in DROP_FIELDS.get(name, []): doc.pop(k, None)
        docs[f[:-5]] = doc
    if docs: coll[name] = docs

# briefings: newest KEEP_BRIEFS, audio served to subscribers from app/audio/
DATA = os.path.join(SITE, "resources", "atlas", "data"); AUD = os.path.join(SITE, "resources", "atlas", "audio")
os.makedirs(DATA, exist_ok=True); os.makedirs(AUD, exist_ok=True)
keep_audio = set()
if "briefings" in coll:
    ids = sorted(coll["briefings"], key=lambda i: coll["briefings"][i].get("date", i), reverse=True)[:KEEP_BRIEFS]
    coll["briefings"] = {i: coll["briefings"][i] for i in ids}
    for i, b in coll["briefings"].items():
        b["edition"] = "06:00 UTC"
        a = b.get("audio") or {}
        src = os.path.join(AUDIO, str(a.get("id", "")) + ".mp4")
        if a.get("id") and os.path.exists(src):
            name = "brief_" + b.get("date", i) + ".mp4"
            dst = os.path.join(AUD, name)
            if not os.path.exists(dst) or os.path.getsize(dst) != os.path.getsize(src): shutil.copyfile(src, dst)
            a["url"] = "app/audio/" + name; a.pop("id", None); keep_audio.add(name)
        else:
            b.pop("audio", None)

coll = clean(coll)
blob = json.dumps(coll, ensure_ascii=False)
hits = sorted({m.group(0) for p in LEAKS for m in re.finditer(p, blob)})
if hits:
    print("LEAK SCAN FAILED, nothing written:", hits); sys.exit(2)

out = {"generated_at": datetime.datetime.now(datetime.timezone.utc).replace(microsecond=0).isoformat(),
       "source": "Sovereign Space ATLAS, EMMA Aerospace Consulting", "collections": coll}
json.dump(out, open(os.path.join(DATA, "live.json"), "w"), ensure_ascii=False, separators=(",", ":"))
for f in os.listdir(AUD):
    if f not in keep_audio: os.remove(os.path.join(AUD, f))
print("live.json", os.path.getsize(os.path.join(DATA, "live.json")), {k: len(v) for k, v in coll.items()}, "audio", sorted(keep_audio))
