"""
BoxingDB Scraper — Main orchestrator.

Usage:
    python scraper.py --fighters --limit 200
    python scraper.py --titles
    python scraper.py --all --output data/
"""

import argparse
import json
import os
import time
from datetime import datetime

import wikidata


def save_json(data: list, name: str, output_dir: str):
    path = os.path.join(output_dir, f"{name}.json")
    serialized = []
    for item in data:
        if hasattr(item, 'to_dict'):
            serialized.append(item.to_dict())
        elif isinstance(item, dict):
            serialized.append(item)
        else:
            serialized.append(item)
    with open(path, "w") as f:
        json.dump(serialized, f, indent=2, default=str)
    print(f"  Saved {len(serialized)} records → {path}")
    return path


def scrape_fighters(limit: int = 500, offset: int = 0, output_dir: str = "data"):
    print(f"\nScraping fighters (limit={limit}, offset={offset})...")
    boxers = wikidata.fetch_boxers(limit=limit, offset=offset)
    # Generate slugs
    for b in boxers:
        b.slug = b.display_name.lower().replace(" ", "-").replace(".", "")
    save_json(boxers, "fighters", output_dir)
    return boxers


def scrape_titles(output_dir: str = "data"):
    print("\nScraping title holders...")
    titles = wikidata.fetch_title_holders()
    save_json(titles, "titles", output_dir)
    return titles


def scrape_weight_classes(output_dir: str = "data"):
    print("\nScraping weight classes...")
    weights = wikidata.fetch_weight_classes()
    save_json(weights, "weight_classes", output_dir)
    return weights


def scrape_events(limit: int = 100, output_dir: str = "data"):
    print(f"\nScraping events (limit={limit})...")
    events = wikidata.fetch_events(limit=limit)
    for e in events:
        e.slug = e.name.lower().replace(" ", "-").replace(".", "").replace(":", "")[:80]
    save_json(events, "events", output_dir)
    return events


def scrape_all(limit_fighters: int = 500, limit_events: int = 100, output_dir: str = "data"):
    os.makedirs(output_dir, exist_ok=True)

    t0 = time.time()

    fighters = scrape_fighters(limit=limit_fighters, output_dir=output_dir)
    time.sleep(0.5)

    titles = scrape_titles(output_dir=output_dir)
    time.sleep(0.5)

    weights = scrape_weight_classes(output_dir=output_dir)
    time.sleep(0.5)

    events = scrape_events(limit=limit_events, output_dir=output_dir)

    elapsed = time.time() - t0
    print(f"\n{'='*50}")
    print(f"Done in {elapsed:.1f}s")
    print(f"  Fighters: {len(fighters)}")
    print(f"  Titles: {len(titles)}")
    print(f"  Weight classes: {len(weights)}")
    print(f"  Events: {len(events)}")
    print(f"  Output: {output_dir}/")
    print(f"{'='*50}")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="BoxingDB Data Scraper")
    parser.add_argument("--fighters", action="store_true", help="Scrape fighters")
    parser.add_argument("--titles", action="store_true", help="Scrape title holders")
    parser.add_argument("--weight-classes", action="store_true", help="Scrape weight classes")
    parser.add_argument("--events", action="store_true", help="Scrape events")
    parser.add_argument("--all", action="store_true", help="Scrape everything")
    parser.add_argument("--limit", type=int, default=500, help="Max records per entity")
    parser.add_argument("--offset", type=int, default=0, help="Offset for pagination")
    parser.add_argument("--output", default="data", help="Output directory")

    args = parser.parse_args()

    anything = args.fighters or args.titles or args.weight_classes or args.events or args.all
    if not anything:
        parser.print_help()
        exit(1)

    os.makedirs(args.output, exist_ok=True)

    if args.fighters or args.all:
        scrape_fighters(limit=args.limit, offset=args.offset, output_dir=args.output)
    if args.titles or args.all:
        scrape_titles(output_dir=args.output)
    if args.weight_classes or args.all:
        scrape_weight_classes(output_dir=args.output)
    if args.events or args.all:
        scrape_events(limit=args.limit, output_dir=args.output)

    print(f"\nOutput directory: {os.path.abspath(args.output)}/")
