"""
Pull all boxers + images from TheSportsDB API and save locally.
Usage: python3 sync.py --storage ../../storage/app/public/fighters --output data/
"""

import argparse, json, os, time, requests, re

API_KEY = "4029125286"
BASE = f"https://www.thesportsdb.com/api/v1/json/{API_KEY}"

WEIGHT_CLASS_TEAMS = {
    151144: "Heavyweight",
    151145: "Cruiserweight",
    151146: "Middleweight",
    151147: "Welterweight",
    151148: "Lightweight",
    151149: "Featherweight",
    151150: "Bantamweight",
    151151: "Flyweight",
}

IMAGE_FIELDS = ["thumb", "cutout", "poster", "cartoon", "render", "banner", "fanart1", "fanart2", "fanart3"]

API_IMAGE_MAP = {
    "thumb": "strThumb",
    "cutout": "strCutout",
    "poster": "strPoster",
    "cartoon": "strCartoon",
    "render": "strRender",
    "banner": "strBanner",
    "fanart1": "strFanart1",
    "fanart2": "strFanart2",
    "fanart3": "strFanart3",
}


def slugify(name):
    s = name.lower().replace(" ", "-").replace(".", "").replace("'", "")
    return re.sub(r"[^a-z0-9-]", "", s)


def download(url, dest):
    if not url:
        return None
    try:
        r = requests.get(url, timeout=30)
        r.raise_for_status()
        ext = url.split(".")[-1].split("?")[0].split("@")[0]
        if ext not in ("jpg", "jpeg", "png", "gif", "webp"):
            ext = "jpg"
        path = f"{dest}.{ext}"
        with open(path, "wb") as f:
            f.write(r.content)
        return path
    except Exception as e:
        print(f"    X Download failed: {e}")
        return None


def fetch_team_players(team_id):
    r = requests.get(f"{BASE}/lookup_all_players.php?id={team_id}", timeout=30)
    r.raise_for_status()
    return r.json().get("player", [])


def run(storage_dir, output_dir):
    os.makedirs(storage_dir, exist_ok=True)
    os.makedirs(output_dir, exist_ok=True)
    all_fighters = []

    # storage_dir is absolute path to e.g. backend/storage/app/public/fighters/
    # We need public/ path for DB storage: /storage/fighters/{slug}/{type}.png
    # storage_dir = .../public/fighters/
    # Rel from public: fighters/

    parts = storage_dir.rstrip("/").split("/")
    public_root = "/".join(parts[:-2]) if len(parts) >= 2 else parts[0]
    rel_prefix = parts[-1] if parts[-2] == "public" else "/".join(parts[-2:])

    for team_id, weight in WEIGHT_CLASS_TEAMS.items():
        print(f"\n=== {weight} ===")
        try:
            players = fetch_team_players(team_id)
        except Exception as e:
            print(f"  X API error: {e}")
            continue

        for p in players:
            name = p.get("strPlayer", "")
            if not name:
                continue
            slug = slugify(name)
            fighter_dir = os.path.join(storage_dir, slug)
            os.makedirs(fighter_dir, exist_ok=True)

            print(f"  {name}")

            local_images = {}
            for img_type, api_key in API_IMAGE_MAP.items():
                url = p.get(api_key)
                if url:
                    dest = os.path.join(fighter_dir, img_type)
                    saved = download(url, dest)
                    if saved:
                        local_images[img_type] = f"/storage/fighters/{slug}/{img_type}.{saved.split('.')[-1]}"
                    else:
                        local_images[img_type] = None
                else:
                    local_images[img_type] = None

            primary = local_images.get("cutout") or local_images.get("poster") or local_images.get("render") or local_images.get("thumb")

            name_parts = name.split(" ")
            all_fighters.append({
                "source_id": p.get("idPlayer"),
                "display_name": name,
                "first_name": name_parts[0] if name_parts else "",
                "last_name": " ".join(name_parts[1:]) if len(name_parts) > 1 else "",
                "birth_date": p.get("dateBorn", ""),
                "country": p.get("strNationality", ""),
                "weight_class": weight,
                "bio": (p.get("strDescriptionEN") or "")[:5000],
                "photo_url": primary,
                "images": local_images,
            })

        time.sleep(0.5)

    path = os.path.join(output_dir, "fighters.json")
    with open(path, "w") as f:
        json.dump(all_fighters, f, indent=2)

    print(f"\n{'='*50}")
    print(f"Total: {len(all_fighters)} fighters")
    with_img = sum(1 for f in all_fighters if f.get("photo_url"))
    print(f"With downloaded images: {with_img}")
    print(f"Images: {os.path.abspath(storage_dir)}/")
    print(f"Data: {os.path.abspath(path)}")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--storage", default="storage/app/public/fighters")
    parser.add_argument("--output", default="data")
    args = parser.parse_args()
    storage = os.path.abspath(args.storage)
    output = os.path.abspath(args.output)
    run(storage, output)
