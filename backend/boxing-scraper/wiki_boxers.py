"""
Wikipedia Boxing Scraper — Discover ALL boxers by weight class.
Finds boxers via Wikipedia category pages, then searches Flashscore for their IDs.

Usage:
    python3 wiki_boxers.py --weight Heavyweight --limit 50
    python3 wiki_boxers.py --all-weights --output data/
    python3 wiki_boxers.py --all-weights --find-flashscore --headless
"""

import argparse, json, os, re, sys, time
from datetime import datetime
from typing import Optional

import requests
from bs4 import BeautifulSoup


WIKI_BASE = "https://en.wikipedia.org"
USER_AGENT = "BoxingDB/1.0 (boxingdb.com) scraper"

WEIGHT_CLASSES = [
    ("Heavyweight", "Heavyweight"),
    ("Bridgerweight", "Bridgerweight"),
    ("Cruiserweight", "Cruiserweight"),
    ("Light heavyweight", "Light-heavyweight"),
    ("Super middleweight", "Super-middleweight"),
    ("Middleweight", "Middleweight"),
    ("Super welterweight", "Light-middleweight"),
    ("Welterweight", "Welterweight"),
    ("Super lightweight", "Light-welterweight"),
    ("Lightweight", "Lightweight"),
    ("Super featherweight", "Super-featherweight"),
    ("Featherweight", "Featherweight"),
    ("Super bantamweight", "Super-bantamweight"),
    ("Bantamweight", "Bantamweight"),
    ("Super flyweight", "Super-flyweight"),
    ("Flyweight", "Flyweight"),
    ("Light flyweight", "Light-flyweight"),
    ("Minimumweight", "Minimumweight"),
]


def wiki_get(path: str) -> BeautifulSoup:
    url = f"{WIKI_BASE}{path}"
    r = requests.get(url, headers={"User-Agent": USER_AGENT}, timeout=30)
    r.raise_for_status()
    return BeautifulSoup(r.text, "lxml")


def get_category_page(category: str) -> list[dict]:
    """Get all boxers from a Wikipedia category page (e.g. 'Heavyweight boxers')."""
    path = f"/wiki/Category:{category.replace(' ', '_')}_boxers"
    soup = wiki_get(path)
    
    boxers = []
    for li in soup.select("div.mw-category-group li"):
        a = li.find("a")
        if not a:
            continue
        href = a.get("href", "")
        name = a.text.strip()
        if not name or ":" in href or "List of" in name:
            continue
        # Filter out subcategories, list pages, and non-fighters
        if any(kw in name.lower() for kw in ["weight", "boxer", "list of", "category:", "template"]):
            continue
        if not href.startswith("/wiki/"):
            continue
        boxers.append({
            "name": name,
            "wiki_url": f"{WIKI_BASE}{href}",
            "wiki_title": href.replace("/wiki/", ""),
        })
    
    return boxers


def get_all_pages(category: str) -> list[dict]:
    """Get ALL boxers from a category, handling pagination."""
    boxers = get_category_page(category)
    page_path = f"/w/index.php?title=Category:{category.replace(' ', '_')}_boxers&pagefrom="
    
    # Follow "next page" links
    while True:
        soup = wiki_get(f"{page_path}&subcatcontinue=0")
        next_link = soup.select_one("a:has(span:contains('next page'))")
        if not next_link:
            break
        more = get_category_page(f"{category}?pagefrom=")
        if not more:
            break
        existing = {b["wiki_title"] for b in boxers}
        for b in more:
            if b["wiki_title"] not in existing:
                boxers.append(b)
        break  # one page is enough for most weight classes
    
    return boxers


def search_flashscore(browser, name: str):
    """Search Flashscore for a boxer name. Requires an active browser."""
    try:
        browser.driver.execute_script("""
            document.querySelector('[data-testid="wcl-icon-action-icon-search"]').parentElement.click();
        """)
        time.sleep(1.5)
        inp = browser.find("input.searchInput__input")
        inp.clear()
        inp.send_keys(name)
        time.sleep(4)
        
        names = browser.find_all("div.searchResult__participantName")
        for n in names:
            try:
                cat = n.find_element(By.XPATH, "./following-sibling::div[contains(@class,'searchResult__participantCategory')]")
                if "boxing" not in cat.text.lower():
                    continue
            except:
                continue
            try:
                parent = n.find_element(By.XPATH, "./ancestor::a")
                text = n.text.strip()
                parent_href = parent.get_attribute("href") or ""
                # Click to resolve the redirect URL
                browser.driver.execute_script("arguments[0].click();", parent)
                time.sleep(3)
                current = browser.driver.current_url
                m = re.search(r"/player/([^/]+)/([^/?#]+)", current)
                if m:
                    return {"name": text, "slug": m.group(1), "id": m.group(2), "url": current}
            except:
                continue
    except Exception as e:
        pass
    return None


def main():
    parser = argparse.ArgumentParser(description="Wikipedia Boxing Scraper")
    parser.add_argument("--weight", help=f"Weight class ({', '.join(w[0] for w in WEIGHT_CLASSES)})")
    parser.add_argument("--all-weights", action="store_true", help="Scrape all weight classes")
    parser.add_argument("--limit", type=int, default=0, help="Max fighters per weight class")
    parser.add_argument("--find-flashscore", action="store_true", help="Search Flashscore for each fighter")
    parser.add_argument("--output", default="data/")
    parser.add_argument("--headless", action="store_true")
    args = parser.parse_args()

    if not args.weight and not args.all_weights:
        parser.print_help()
        sys.exit(1)

    os.makedirs(args.output, exist_ok=True)
    weights = WEIGHT_CLASSES if args.all_weights else [(args.weight, args.weight)]

    all_boxers = []
    for display_name, wiki_cat in weights:
        print(f"\n=== {display_name} ===")
        try:
            boxers = get_all_pages(wiki_cat)
            if args.limit:
                boxers = boxers[:args.limit]
            for b in boxers:
                b["weight_class"] = display_name
            all_boxers.extend(boxers)
            print(f"  Found {len(boxers)} boxers")
        except Exception as e:
            print(f"  Error: {e}")
        time.sleep(1)

    # Save boxer list
    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    path = os.path.join(args.output, f"wikipedia_boxers_{ts}.json")
    with open(path, "w") as f:
        json.dump(all_boxers, f, indent=2)
    print(f"\nTotal: {len(all_boxers)} boxers saved to {path}")

    # Find Flashscore IDs (requires Selenium)
    if args.find_flashscore and all_boxers:
        from selenium import webdriver
        from selenium.webdriver.chrome.options import Options
        from selenium.webdriver.common.by import By
        from selenium.webdriver.common.keys import Keys
        from selenium.webdriver.support.ui import WebDriverWait
        from selenium.webdriver.support import expected_conditions as EC
        from webdriver_manager.chrome import ChromeDriverManager
        from selenium.webdriver.chrome.service import Service

        opts = Options()
        chrome_paths = ["/usr/bin/google-chrome", "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"]
        for p in chrome_paths:
            if os.path.exists(p):
                opts.binary_location = p
                break
        if args.headless:
            opts.add_argument("--headless=new")
        opts.add_argument("--no-sandbox")
        opts.add_argument("--disable-dev-shm-usage")
        driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=opts)
        driver.get("https://www.flashscore.co.uk/")
        time.sleep(3)

        from types import SimpleNamespace
        browser = SimpleNamespace(driver=driver, find=lambda s: driver.find_element(By.CSS_SELECTOR, s))

        for boxer in all_boxers:
            if boxer.get("flashscore_id"):
                continue
            print(f"  Searching: {boxer['name']}...", end=" ")
            result = search_flashscore(browser, boxer["name"])
            if result:
                boxer["flashscore_slug"] = result["slug"]
                boxer["flashscore_id"] = result["id"]
                boxer["flashscore_name"] = result["name"]
                print(f"found -> {result['slug']}/{result['id']}")
            else:
                print("not found")
            time.sleep(1)

        driver.quit()

        # Save updated list with Flashscore IDs
        fspath = os.path.join(args.output, f"wikipedia_boxers_with_fsid_{ts}.json")
        with open(fspath, "w") as f:
            json.dump(all_boxers, f, indent=2)
        print(f"\nUpdated: {fspath}")

    # Summary by weight
    if not args.all_weights:
        return
    print(f"\n=== Summary ===")
    for display_name, _ in WEIGHT_CLASSES:
        count = sum(1 for b in all_boxers if b.get("weight_class") == display_name)
        with_fs = sum(1 for b in all_boxers if b.get("weight_class") == display_name and b.get("flashscore_id"))
        print(f"  {display_name:25s}: {count:4d} fighters ({with_fs} with Flashscore ID)")


if __name__ == "__main__":
    main()
