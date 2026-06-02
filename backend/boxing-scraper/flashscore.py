"""
Flashscore Boxing Scraper — Selenium scraper for fighter results.

Scrapes fighter pages to get full fight history:
  date, opponent, result (W/L/D), method, rounds
Usage:
    python3 flashscore.py --player usyk-olexandr nqbF7L5K
    python3 flashscore.py --file fighters.json --output data/
"""

import argparse, json, os, re, sys, time
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
USER_AGENT = (
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) "
    "AppleWebKit/537.36 (KHTML, like Gecko) "
    "Chrome/120.0.0.0 Safari/537.36"
)


@dataclass
class FightResult:
    date: str
    opponent: str
    opponent_id: str
    result: str  # W, L, D
    method: str  # KO, TKO, UD, SD, MD, PTS
    rounds: str  # e.g. "12", "6"
    title: str
    match_url: str
    fighter_name: str


RESULT_CLASS_MAP = {
    "wcl-win": "W",
    "wcl-lose": "L",
    "wcl-draw": "D",
}


def extract_result(button_class: str) -> str:
    for cls, result in RESULT_CLASS_MAP.items():
        if cls in button_class:
            return result
    return ""


class BrowserManager:
    def __init__(self, headless=True):
        self.headless = headless
        self.driver = None

    def start(self):
        opts = Options()
        chrome_paths = [
            "/usr/bin/google-chrome",
            "/usr/bin/google-chrome-stable",
            "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
        ]
        for p in chrome_paths:
            if os.path.exists(p):
                opts.binary_location = p
                break
        if self.headless:
            opts.add_argument("--headless=new")
        opts.add_argument(f"user-agent={USER_AGENT}")
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

    def source(self):
        return self.driver.page_source

    def scroll_to(self, el):
        self.driver.execute_script("arguments[0].scrollIntoView(true);", el)

    def click(self, el):
        self.driver.execute_script("arguments[0].click();", el)

    def close(self):
        if self.driver:
            self.driver.quit()


def extract_opponent_id(match_url: str, fighter_slug: str) -> str:
    """Extract opponent ID from match URL."""
    # URL format: /match/boxing/{name}-{id}/{name}-{id}/
    parts = match_url.rstrip("/").split("/")
    for part in parts:
        if "-" in part and fighter_slug not in part:
            match = re.search(r"-([A-Za-z0-9]+)$", part)
            if match:
                return match.group(1)
    return ""


def scrape_player(browser: BrowserManager, player_slug: str, player_id: str, player_name: str = "") -> list[FightResult]:
    url = f"{BASE}/player/{player_slug}/{player_id}/results/"
    print(f"  Scraping: {url}")
    browser.get(url)
    time.sleep(4)

    try:
        WebDriverWait(browser.driver, 15).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "div.event__match"))
        )
    except TimeoutException:
        print(f"  Warning: No matches loaded")
        return []

    # Load all results by clicking "Show more matches"
    for i in range(25):
        try:
            more = browser.driver.find_element(By.CSS_SELECTOR, "a.event__more")
            if more and more.is_displayed():
                browser.click(more)
                time.sleep(2)
            else:
                break
        except (NoSuchElementException, TimeoutException):
            break

    time.sleep(1)

    matches = browser.find_all("div.event__match")
    fights = []

    for match in matches:
        try:
            # Get match URL
            link = match.find_element(By.CSS_SELECTOR, "a.eventRowLink")
            match_url = link.get_attribute("href") or ""

            # Date
            date_el = match.find_element(By.CSS_SELECTOR, "div.event__time")
            date = date_el.text.strip()

            # Other fighter (opponent)
            away = match.find_element(By.CSS_SELECTOR, "div.event__awayParticipant")
            opponent = away.text.strip()

            # Method / rounds
            rounds_el = match.find_element(By.CSS_SELECTOR, "div.event__rounds")
            rounds_text = rounds_el.text.strip()

            # Result button
            result_btn = match.find_element(By.CSS_SELECTOR, "button.formIcon__lastMatches")
            btn_class = result_btn.get_attribute("class") or ""
            result = extract_result(btn_class)

            opp_id = extract_opponent_id(match_url, player_slug)

            fights.append(FightResult(
                date=date,
                opponent=opponent,
                opponent_id=opp_id,
                result=result,
                method=rounds_text,
                rounds="",
                title="",
                match_url=match_url,
                fighter_name=player_name or f"{player_slug}/{player_id}",
            ))
        except Exception as e:
            continue

    # Calculate record
    wins = sum(1 for f in fights if f.result == "W")
    losses = sum(1 for f in fights if f.result == "L")
    draws = sum(1 for f in fights if f.result == "D")
    kos = sum(1 for f in fights if "KO" in f.method or "TKO" in f.method)

    print(f"  Found {len(fights)} fights (W:{wins} L:{losses} D:{draws} KO:{kos})")
    return fights


def main():
    parser = argparse.ArgumentParser(description="Flashscore Boxing Scraper")
    parser.add_argument("--player", nargs=2, metavar=("SLUG", "ID"),
                        help="Player slug and Flashscore ID (e.g. usyk-olexandr nqbF7L5K)")
    parser.add_argument("--file", help="JSON file with fighters containing flashscore_id fields")
    parser.add_argument("--output", default="data/", help="Output directory")
    parser.add_argument("--no-headless", action="store_true", help="Show browser window")
    args = parser.parse_args()

    if not args.player and not args.file:
        parser.print_help()
        sys.exit(1)

    os.makedirs(args.output, exist_ok=True)

    all_fights = []

    browser = BrowserManager(headless=not args.no_headless)
    browser.start()

    try:
        if args.player:
            slug, pid = args.player
            fights = scrape_player(browser, slug, pid, slug)
            all_fights.extend(fights)

        if args.file:
            fighters = json.load(open(args.file))
            for f in fighters:
                fs_id = f.get("flashscore_id") or f.get("source_id")
                if not fs_id:
                    continue
                name = f.get("display_name", "")
                slug = f.get("slug", name.lower().replace(" ", "-"))
                fights = scrape_player(browser, slug, fs_id, name)
                all_fights.extend(fights)
                time.sleep(2)

    finally:
        browser.close()

    # Save
    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    path = os.path.join(args.output, f"flashscore_results_{ts}.json")
    with open(path, "w") as f:
        json.dump([asdict(fi) for fi in all_fights], f, indent=2)

    print(f"\nTotal: {len(all_fights)} fights saved to {path}")
    total_w = sum(1 for f in all_fights if f.result == "W")
    total_l = sum(1 for f in all_fights if f.result == "L")
    total_d = sum(1 for f in all_fights if f.result == "D")
    total_ko = sum(1 for f in all_fights if "KO" in f.method or "TKO" in f.method)
    print(f"Record: {total_w}-{total_l}-{total_d} ({total_ko} KO)")


if __name__ == "__main__":
    main()
