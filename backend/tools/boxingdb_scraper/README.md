# BoxingDB Selenium Scraper

This collector is built for boxing pages that render data with JavaScript. Selenium opens the page, waits for the rendered DOM, scrolls the page, then normalizes the result into JSON that Laravel can import.

## Install

```bash
cd backend/tools/boxingdb_scraper
python -m venv .venv
source .venv/bin/activate
python -m pip install -r requirements.txt
```

Selenium Manager will try to locate or download a matching Chrome/Firefox driver automatically.

## Configure Sources

Copy `sources.example.json` and replace the URLs/selectors with the boxing sites you want to collect from.

Each source needs:

- `kind`: `fighter`, `event`, `rankings`, or `media`
- `url`: the page to render
- `wait_for`: CSS selector that proves the JS app has rendered
- `selectors`: CSS selectors for the fields you want

Selectors support `@attr`, for example `img@src` or `a@href`.

## Scrape

```bash
cd backend/tools/boxingdb_scraper
source .venv/bin/activate
python -m boxingdb_scraper collect \
  --sources sources.local.json \
  --out ../../storage/app/boxingdb/imports/scraped.json \
  --screenshot-dir ../../storage/app/boxingdb/screenshots \
  --raw-dir ../../storage/app/boxingdb/raw
```

Use `--headed` while tuning selectors so you can see the browser.

## Import Into Laravel

From the backend directory:

```bash
php artisan boxingdb:import-scraped storage/app/boxingdb/imports/scraped.json --dry-run
php artisan boxingdb:import-scraped storage/app/boxingdb/imports/scraped.json
```

Use `--replace-event-fights` when an event page should become the source of truth for that event's fight card.
