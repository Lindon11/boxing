from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any

from .browser import SeleniumBrowser, SeleniumSettings


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description="Collect JavaScript-rendered boxing data for BoxingDB.")
    subparsers = parser.add_subparsers(dest="command", required=True)

    collect = subparsers.add_parser("collect", help="Scrape configured sources with Selenium.")
    collect.add_argument("--sources", required=True, help="Path to a JSON source configuration file.")
    collect.add_argument("--out", required=True, help="Path to write normalized BoxingDB JSON.")
    collect.add_argument("--browser", choices=["chrome", "firefox"], default="chrome")
    collect.add_argument("--headed", action="store_true", help="Show the browser window while scraping.")
    collect.add_argument("--wait-seconds", type=int, default=15)
    collect.add_argument("--page-load-timeout", type=int, default=45)
    collect.add_argument("--slow-mo", type=float, default=0.0, help="Extra seconds to wait after each page load.")
    collect.add_argument("--user-agent")
    collect.add_argument("--raw-dir", help="Optional directory to save rendered HTML snapshots.")
    collect.add_argument("--screenshot-dir", help="Optional directory to save rendered screenshots.")
    collect.add_argument("--limit", type=int, help="Only scrape the first N configured sources.")
    collect.add_argument("--compact", action="store_true", help="Write compact JSON instead of pretty JSON.")

    args = parser.parse_args(argv)

    if args.command == "collect":
        return collect_command(args)

    return 1


def collect_command(args: argparse.Namespace) -> int:
    try:
        from .collector import collect_sources
    except ImportError as exc:
        print(
            "Missing scraper dependency. Run `python -m pip install -r requirements.txt` "
            "inside backend/tools/boxingdb_scraper."
        )
        raise SystemExit(2) from exc

    sources = load_sources(Path(args.sources))
    settings = SeleniumSettings(
        browser=args.browser,
        headless=not args.headed,
        wait_seconds=args.wait_seconds,
        page_load_timeout=args.page_load_timeout,
        user_agent=args.user_agent,
        slow_mo=args.slow_mo,
    )

    with SeleniumBrowser(settings) as browser:
        payload = collect_sources(
            browser,
            sources,
            raw_dir=Path(args.raw_dir) if args.raw_dir else None,
            screenshot_dir=Path(args.screenshot_dir) if args.screenshot_dir else None,
            limit=args.limit,
        )

    out = Path(args.out)
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(
        json.dumps(payload, indent=None if args.compact else 2, ensure_ascii=False),
        encoding="utf-8",
    )

    counts = {key: len(payload.get(key, [])) for key in ["fighters", "events", "rankings", "belts", "media"]}
    print(f"Wrote {out}")
    print("Counts: " + ", ".join(f"{key}={value}" for key, value in counts.items()))
    return 0


def load_sources(path: Path) -> list[dict[str, Any]]:
    data = json.loads(path.read_text(encoding="utf-8"))
    sources = data.get("sources", data) if isinstance(data, dict) else data
    if not isinstance(sources, list):
        raise ValueError("Source config must be a list or an object with a `sources` list.")

    for index, source in enumerate(sources, start=1):
        if not isinstance(source, dict) or not source.get("url"):
            raise ValueError(f"Source #{index} must be an object with a `url`.")

    return sources
