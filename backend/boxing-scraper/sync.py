"""
Pull all boxers + images + honours + events from TheSportsDB API.
Usage: python3 sync.py --storage storage/app/public --output data/
"""

import argparse, json, os, time, requests, re
from urllib3.exceptions import InsecureRequestWarning
requests.packages.urllib3.disable_warnings(InsecureRequestWarning)

API_KEY = "4029125286"
V1 = f"https://www.thesportsdb.com/api/v1/json/{API_KEY}"
V2 = "https://www.thesportsdb.com/api/v2/json"
V2_HEADERS = {"X-API-KEY": API_KEY}

WEIGHT_CLASS_TEAMS = {
    151144: "Heavyweight",
    151153: "Cruiserweight",
    151146: "Middleweight",
    151147: "Welterweight",
    151148: "Lightweight",
    151149: "Featherweight",
    151150: "Bantamweight",
    151151: "Flyweight",
    151161: "Mini Flyweight",
}

FIGHTER_IMAGES = ["thumb", "cutout", "poster", "cartoon", "render", "banner", "fanart1", "fanart2", "fanart3"]
API_IMG_MAP = {"thumb":"strThumb","cutout":"strCutout","poster":"strPoster","cartoon":"strCartoon",
               "render":"strRender","banner":"strBanner","fanart1":"strFanart1",
               "fanart2":"strFanart2","fanart3":"strFanart3"}


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
        return None


def v2_get(path):
    try:
        r = requests.get(f"{V2}{path}", headers=V2_HEADERS, timeout=20)
        if r.status_code == 200 and r.text.strip():
            return r.json()
    except:
        pass
    return None


def fetch_team_players(team_id):
    r = requests.get(f"{V1}/lookup_all_players.php?id={team_id}", timeout=30)
    r.raise_for_status()
    return r.json().get("player", [])


def run(storage_dir, output_dir):
    fighters_dir = os.path.join(storage_dir, "fighters")
    events_dir = os.path.join(storage_dir, "events")
    honours_dir = os.path.join(storage_dir, "honours")
    os.makedirs(fighters_dir, exist_ok=True)
    os.makedirs(events_dir, exist_ok=True)
    os.makedirs(honours_dir, exist_ok=True)
    os.makedirs(output_dir, exist_ok=True)

    all_fighters = []
    all_events = {}
    all_honours = []

    for team_id, weight in WEIGHT_CLASS_TEAMS.items():
        print(f"\n=== {weight} ===")
        try:
            players = fetch_team_players(team_id)
        except Exception as e:
            print(f"  X API error: {e}")
            continue

        for p in players:
            name = p.get("strPlayer", "")
            pid = p.get("idPlayer")
            if not name or not pid:
                continue
            slug = slugify(name)
            fdir = os.path.join(fighters_dir, slug)
            os.makedirs(fdir, exist_ok=True)

            # Download fighter images
            local_imgs = {}
            for it, ak in API_IMG_MAP.items():
                url = p.get(ak)
                if url:
                    saved = download(url, os.path.join(fdir, it))
                    if saved:
                        local_imgs[it] = f"/storage/fighters/{slug}/{it}.{saved.split('.')[-1]}"

            primary = local_imgs.get("cutout") or local_imgs.get("poster") or local_imgs.get("render") or local_imgs.get("thumb")
            name_parts = name.split(" ")

            fighter = {
                "source_id": pid,
                "display_name": name,
                "first_name": name_parts[0],
                "last_name": " ".join(name_parts[1:]) if len(name_parts) > 1 else "",
                "birth_date": p.get("dateBorn", ""),
                "country": p.get("strNationality", ""),
                "weight_class": weight,
                "bio": (p.get("strDescriptionEN") or "")[:5000],
                "photo_url": primary,
                "images": local_imgs,
            }
            all_fighters.append(fighter)
            print(f"  {name}")

            # Fetch honours
            print(f"    Honours...", end=" ")
            data = v2_get(f"/lookup/player_honours/{pid}")
            if data:
                honours = data.get("lookup", [])
                for h in honours:
                    hid = h.get("idHonour")
                    trophy_url = h.get("strHonourTrophy")
                    logo_url = h.get("strHonourLogo")
                    local_trophy = None
                    local_logo = None
                    if trophy_url:
                        hdir = os.path.join(honours_dir, hid)
                        os.makedirs(hdir, exist_ok=True)
                        saved = download(trophy_url, os.path.join(hdir, "trophy"))
                        if saved:
                            local_trophy = f"/storage/honours/{hid}/trophy.{saved.split('.')[-1]}"
                    if logo_url and hid:
                        saved = download(logo_url, os.path.join(hdir, "logo"))
                        if saved:
                            local_logo = f"/storage/honours/{hid}/logo.{saved.split('.')[-1]}"
                    all_honours.append({
                        "id": hid,
                        "player_id": pid,
                        "player_name": name,
                        "honour": h.get("strHonour"),
                        "team": h.get("strTeam"),
                        "season": h.get("strSeason"),
                        "trophy_url": local_trophy or trophy_url,
                        "logo_url": local_logo or logo_url,
                        "team_badge": h.get("strTeamBadge"),
                    })
                print(f"{len(honours)} found")
            else:
                print("none")

            # Fetch results (gives us event IDs)
            print(f"    Results...", end=" ")
            data = v2_get(f"/lookup/player_results/{pid}")
            if data:
                results = data.get("lookup", [])
                for res in results:
                    eid = res.get("idEvent")
                    if eid and eid not in all_events:
                        all_events[eid] = {"id": eid, "fighter_results": []}
                    if eid:
                        all_events[eid]["fighter_results"].append({
                            "player_id": pid,
                            "player": name,
                            "result": res.get("strResult"),
                            "detail": res.get("strDetail"),
                        })
                print(f"{len(results)} found")
            else:
                print("none")

            time.sleep(0.3)

    # Fetch event details + posters
    print(f"\n=== Events ({len(all_events)}) ===")
    event_list = []
    for eid in sorted(all_events.keys()):
        print(f"  Event {eid}...", end=" ")
        data = v2_get(f"/lookup/event/{eid}")
        if data:
            ev = data.get("lookup", [{}])[0]
            slug = slugify(ev.get("strEvent", f"event-{eid}"))
            edir = os.path.join(events_dir, slug)
            os.makedirs(edir, exist_ok=True)

            # Download event images
            ev_imgs = {}
            for img_type in ["poster", "thumb", "square", "fanart", "banner"]:
                key = f"str{img_type.capitalize()}"
                if img_type == "square":
                    key = "strSquare"
                url = ev.get(key)
                if url:
                    saved = download(url, os.path.join(edir, img_type))
                    if saved:
                        ev_imgs[img_type] = f"/storage/events/{slug}/{img_type}.{saved.split('.')[-1]}"
                    else:
                        ev_imgs[img_type] = url

            event_list.append({
                "id": eid,
                "name": ev.get("strEvent"),
                "date": ev.get("dateEvent"),
                "venue": ev.get("strVenue"),
                "city": ev.get("strCity"),
                "country": ev.get("strCountry"),
                "status": ev.get("strStatus"),
                "season": ev.get("strSeason"),
                "round": ev.get("intRound"),
                "result": ev.get("strResult"),
                "description": (ev.get("strDescriptionEN") or "")[:3000],
                "video": ev.get("strVideo"),
                "images": ev_imgs,
            })
            print("OK")
        else:
            print("no data")
        time.sleep(0.3)

    # Save all data
    output = {
        "fighters": all_fighters,
        "events": event_list,
        "honours": all_honours,
        "_meta": {
            "fighter_count": len(all_fighters),
            "event_count": len(event_list),
            "honour_count": len(all_honours),
        }
    }

    path = os.path.join(output_dir, "boxingdb.json")
    with open(path, "w") as f:
        json.dump(output, f, indent=2)

    print(f"\n{'='*50}")
    print(f"Fighters: {len(all_fighters)}")
    print(f"Events:   {len(event_list)}")
    print(f"Honours:  {len(all_honours)}")
    print(f"Output:   {os.path.abspath(path)}")
    print(f"Storage:  {os.path.abspath(storage_dir)}/")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--storage", default="storage/app/public")
    parser.add_argument("--output", default="data")
    args = parser.parse_args()
    run(os.path.abspath(args.storage), os.path.abspath(args.output))
