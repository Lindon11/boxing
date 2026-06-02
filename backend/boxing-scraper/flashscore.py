"""
Flashscore Boxing Scraper — Full fighter profiles + results.

Scrapes:
  - Fighter info (name, nationality, age, DOB)
  - Fight results with titles, weight class, method, opponent
  - Match detail (title, venue)
  - Records (W/L/D/KO)

Usage:
    python3 flashscore.py --player usyk-olexandr nqbF7L5K
    python3 flashscore.py --search "tyson fury"
    python3 flashscore.py --batch fighters.json --output data/
"""

import argparse, json, os, re, sys, time, requests
from datetime import datetime
from dataclasses import dataclass, asdict
from typing import Optional

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException


BASE = "https://www.flashscore.co.uk"
CHROME_PATHS = [
    "/usr/bin/google-chrome", "/usr/bin/google-chrome-stable",
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
]


@dataclass
class FighterInfo:
    name: str
    nationality: str
    age: str
    birth_date: str
    flashscore_slug: str
    flashscore_id: str


@dataclass
class FightResult:
    date: str
    opponent: str
    opponent_id: str
    result: str  # W, L, D
    method: str
    title: str  # title stake (e.g. "WBC/WBO/IBF/IBO/WBA Super Titles")
    weight_class: str  # e.g. "HEAVYWEIGHT - MEN"
    match_url: str
    fighter_slug: str
    fighter_id: str


class BrowserManager:
    def __init__(self, headless=True):
        self.headless = headless
        self.driver = None

    def start(self):
        opts = Options()
        for p in CHROME_PATHS:
            if os.path.exists(p):
                opts.binary_location = p
                break
        if self.headless:
            opts.add_argument("--headless=new")
        opts.add_argument("--no-sandbox")
        opts.add_argument("--disable-dev-shm-usage")
        opts.add_argument("--disable-gpu")
        opts.add_argument("--window-size=1920,1080")
        opts.add_experimental_option("excludeSwitches", ["enable-automation"])
        from webdriver_manager.chrome import ChromeDriverManager
        svc = Service(ChromeDriverManager().install())
        self.driver = webdriver.Chrome(service=svc, options=opts)

    def get(self, url):
        self.driver.get(url)

    def find(self, selector):
        return self.driver.find_element(By.CSS_SELECTOR, selector)

    def find_all(self, selector):
        return self.driver.find_elements(By.CSS_SELECTOR, selector)

    def click(self, el):
        self.driver.execute_script("arguments[0].click();", el)

    def close(self):
        if self.driver:
            self.driver.quit()


RESULT_CLASS_MAP = {"wcl-win": "W", "wcl-lose": "L", "wcl-draw": "D"}


def extract_result(btn_class: str) -> str:
    for cls, r in RESULT_CLASS_MAP.items():
        if cls in btn_class:
            return r
    return ""


def scrape_fighter(browser: BrowserManager, slug: str, pid: str) -> tuple[FighterInfo, list[FightResult]]:
    """Scrape a fighter's profile page for info + full results with titles."""
    url = f"{BASE}/player/{slug}/{pid}/"
    print(f"  {url}")

    browser.get(url)
    time.sleep(4)

    try:
        WebDriverWait(browser.driver, 15).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "div.event__match"))
        )
    except TimeoutException:
        print(f"  Warning: No matches loaded")
        return FighterInfo("", "", "", "", slug, pid), []

    # Scroll to load more results
    for i in range(5):
        browser.driver.execute_script("window.scrollBy(0, 600);")
        time.sleep(0.5)

    # Extract fighter info from header
    info = FighterInfo("", "", "", "", slug, pid)
    try:
        header = browser.find("div[class*='playerPage__']").text
        lines = [l.strip() for l in header.split("\n") if l.strip()]
        for line in lines:
            if "Age:" in line:
                info.age = line.replace("Age:", "").strip()
            m = re.search(r"(\d{2}\.\d{2}\.\d{4})", line)
            if m:
                info.birth_date = m.group(1)
        # Name is first non-empty line
        name_match = re.search(r"^([A-Za-zÀ-ÿ\s.]+)\s*\(([^)]+)\)", lines[0] if lines else "")
        if name_match:
            info.name = name_match.group(1).strip()
            info.nationality = name_match.group(2).strip()
    except Exception:
        pass

    # Scroll to load ALL results by clicking "Show more matches"
    for i in range(30):
        try:
            more = browser.driver.find_element(By.CSS_SELECTOR, "a.event__more")
            if more and more.is_displayed():
                browser.click(more)
                time.sleep(1.5)
            else:
                break
        except (NoSuchElementException, TimeoutException):
            break

    time.sleep(1)

    matches = browser.find_all("div.event__match")
    fights = []
    current_title = ""
    current_weight = ""

    for match in matches:
        try:
            # Check if this is a title/weight header row
            text = match.text.strip()

            # Get match URL
            try:
                link = match.find_element(By.CSS_SELECTOR, "a.eventRowLink")
                match_url = link.get_attribute("href") or ""
            except:
                match_url = ""

            # Date
            try:
                date = match.find_element(By.CSS_SELECTOR, "div.event__time").text.strip()
            except:
                date = ""

            # Home participant (the fighter whose page we're on)
            try:
                home = match.find_element(By.CSS_SELECTOR, "div.event__homeParticipant").text.strip()
            except:
                home = ""

            # Away participant (opponent)
            try:
                away = match.find_element(By.CSS_SELECTOR, "div.event__awayParticipant").text.strip()
            except:
                away = ""

            # Method / rounds
            try:
                method = match.find_element(By.CSS_SELECTOR, "div.event__rounds").text.strip()
            except:
                method = ""

            # Result badge
            try:
                btn = match.find_element(By.CSS_SELECTOR, "button.formIcon__lastMatches")
                result = extract_result(btn.get_attribute("class") or "")
            except:
                result = ""

            # Extract opponent ID from match URL
            opp_id = ""
            if match_url:
                parts = match_url.rstrip("/").split("/")
                for part in parts:
                    if "-" in part and slug not in part:
                        m = re.search(r"-([A-Za-z0-9]+)$", part)
                        if m:
                            opp_id = m.group(1)

            # Track title/weight from header rows (divs with specific pattern)
            if not match_url and not date and not home:
                if any(kw in text.upper() for kw in ["TITLE", "WORLD", "BELT", "CHAMPION"]):
                    current_title = text.strip()
                elif any(kw in text.upper() for kw in ["HEAVYWEIGHT", "LIGHT", "WELTER", "MIDDLE", "FEATHER", "BANTAM", "FLY", "CRUISER"]):
                    current_weight = text.strip()

            if not match_url:
                # Check if this is a header/title row
                if text and not any(c.isdigit() for c in text[:5]):
                    if any(kw in text.upper() for kw in ["TITLE", "CHAMPIONSHIP", "BELT", "WORLD"]):
                        current_title = text.strip()
                    elif any(kw in text.upper() for kw in ["HEAVYWEIGHT", "LIGHT", "WELTER", "MIDDLE", "FEATHER"]):
                        current_weight = text.strip()
                continue

            if not home:
                continue

            fights.append(FightResult(
                date=date,
                opponent=away,
                opponent_id=opp_id,
                result=result,
                method=method,
                title=current_title,
                weight_class=current_weight,
                match_url=match_url,
                fighter_slug=slug,
                fighter_id=pid,
            ))

        except Exception:
            continue

    # Calculate record
    wins = sum(1 for f in fights if f.result == "W")
    losses = sum(1 for f in fights if f.result == "L")
    draws = sum(1 for f in fights if f.result == "D")
    kos = sum(1 for f in fights if "KO" in f.method or "TKO" in f.method)
    print(f"  {info.name or slug}: {len(fights)} fights ({wins}-{losses}-{draws}, {kos} KO)")

    return info, fights


def search_player(browser, query):
    """Search Flashscore for a player. Returns list of {name, slug, id}."""
    browser.get(f"{BASE}/search/?q={requests.utils.quote(query)}")
    time.sleep(5)
    try:
        browser.find("a[href*='/player/']")
    except:
        pass
    results = []
    for link in browser.find_all("a[href*='/player/']"):
        href = link.get_attribute("href") or ""
        m = re.search(r"/player/([^/]+)/([^/?#]+)", href)
        if m:
            results.append({"name": link.text.strip() or query, "slug": m.group(1), "id": m.group(2), "url": href})
    return results


def main():
    parser = argparse.ArgumentParser(description="Flashscore Boxing Scraper")
    parser.add_argument("--player", nargs=2, metavar=("SLUG", "ID"))
    parser.add_argument("--search", metavar="NAME")
    parser.add_argument("--batch", help="JSON file with [{flashscore_slug, flashscore_id}]")
    parser.add_argument("--output", default="data/")
    parser.add_argument("--no-headless", action="store_true")
    args = parser.parse_args()

    if not args.player and not args.search and not args.batch:
        parser.print_help()
        sys.exit(1)

    os.makedirs(args.output, exist_ok=True)
    all_fights = []
    all_fighters = []

    browser = BrowserManager(headless=not args.no_headless)
    browser.start()

    try:
        if args.search:
            results = search_player(browser, args.search)
            print(f"\nFound {len(results)} player(s):")
            for r in results:
                print(f"  {r['name']:40s} slug={r['slug']:30s} id={r['id']}")

        if args.player:
            slug, pid = args.player
            info, fights = scrape_fighter(browser, slug, pid)
            all_fighters.append(info)
            all_fights.extend(fights)

        if args.batch:
            fighters = json.load(open(args.batch))
            for f in fighters:
                slug = f.get("flashscore_slug", "")
                pid = f.get("flashscore_id", "")
                if not slug or not pid:
                    continue
                info, fights = scrape_fighter(browser, slug, pid)
                if info.name:
                    all_fighters.append(info)
                all_fights.extend(fights)
                time.sleep(2)

    finally:
        browser.close()

    # Save results
    if all_fights:
        ts = datetime.now().strftime("%Y%m%d_%H%M%S")
        path = os.path.join(args.output, f"flashscore_fights_{ts}.json")
        with open(path, "w") as f:
            json.dump([asdict(fi) for fi in all_fights], f, indent=2)
        print(f"\nFights saved: {len(all_fights)} to {path}")

    if all_fighters:
        fpath = os.path.join(args.output, f"flashscore_fighters_{ts}.json")
        with open(fpath, "w") as f:
            json.dump([asdict(fi) for fi in all_fighters], f, indent=2)
        print(f"Fighters saved: {len(all_fighters)} to {fpath}")

    # Summary
    if all_fights:
        total_w = sum(1 for f in all_fights if f.result == "W")
        total_l = sum(1 for f in all_fights if f.result == "L")
        total_d = sum(1 for f in all_fights if f.result == "D")
        total_ko = sum(1 for f in all_fights if "KO" in f.method or "TKO" in f.method)
        print(f"Overall: {total_w}-{total_l}-{total_d} ({total_ko} KO)")


if __name__ == "__main__":
    main()
