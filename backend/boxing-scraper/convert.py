"""
Convert boxingdb.json → import format for boxingdb:import-scraped.
Usage: python3 convert.py && php8.2 artisan boxingdb:import-scraped storage/import.json
"""

import json, os

SRC = "../backend/boxing-scraper/data/boxingdb.json"
DST = "../storage/app/boxingdb/imports/convert.json"

os.makedirs(os.path.dirname(DST), exist_ok=True)

raw = json.load(open(SRC))

output = {
    "fighters": [],
    "events": [],
    "belts": [],
}

# ── Convert fighters ──────────────────────────────────────────
for f in raw["fighters"]:
    output["fighters"].append({
        "source_id": f.get("source_id"),
        "display_name": f["display_name"],
        "first_name": f.get("first_name", ""),
        "last_name": f.get("last_name", ""),
        "birth_date": f.get("birth_date") or None,
        "country": {"name": f.get("country")} if f.get("country") else None,
        "weight_class": f.get("weight_class"),
        "bio": f.get("bio") or None,
        "photo_url": f.get("photo_url") or None,
    })

# ── Convert events ────────────────────────────────────────────
for ev in raw["events"]:
    imgs = ev.get("images", {})
    output["events"].append({
        "name": ev.get("name", "Unknown Event"),
        "slug": ev.get("name", "").lower().replace(" ", "-").replace(".", "")[:80],
        "event_date": ev.get("date"),
        "status": "completed" if ev.get("status") == "FT" else "upcoming",
        "subtitle": ev.get("description", "")[:200] if ev.get("description") else None,
        "poster_url": imgs.get("poster"),
        "hero_image_url": imgs.get("fanart") or imgs.get("banner"),
        "venue": {"name": ev.get("venue", "TBA")} if ev.get("venue") else None,
    })

# ── Convert honours → belts ───────────────────────────────────
for h in raw["honours"]:
    output["belts"].append({
        "name": h.get("honour", "Unknown Honour"),
        "current_champion_name": h.get("player_name"),
        "organisation_abbreviation": h.get("honour", "")[:10].upper(),
        "reign_started_on": h.get("season"),
        "active": True,
    })

# ── Save ──────────────────────────────────────────────────────
with open(DST, "w") as f:
    json.dump(output, f, indent=2)

print(f"Converted: {len(output['fighters'])} fighters, {len(output['events'])} events, {len(output['belts'])} belts")
print(f"Saved to: {DST}")
