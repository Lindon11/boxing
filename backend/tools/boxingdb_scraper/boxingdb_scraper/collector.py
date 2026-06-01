from __future__ import annotations

from pathlib import Path
from typing import Any

from bs4 import BeautifulSoup

from .browser import SeleniumBrowser
from .parsing import (
    LABEL_FIELD_MAP,
    clean_text,
    first_non_empty,
    jsonld_by_type,
    labelled_values,
    normalize_weight_class,
    now_iso,
    parse_date,
    parse_int,
    parse_measure_cm,
    parse_record,
    selector_value,
    slugify,
    split_name,
)


def collect_sources(
    browser: SeleniumBrowser,
    sources: list[dict[str, Any]],
    raw_dir: Path | None = None,
    screenshot_dir: Path | None = None,
    limit: int | None = None,
) -> dict[str, Any]:
    payload: dict[str, Any] = {
        "schema": "boxingdb.scrape.v1",
        "scraped_at": now_iso(),
        "sources": [],
        "fighters": [],
        "events": [],
        "rankings": [],
        "belts": [],
        "broadcasters": [],
        "event_broadcasts": [],
        "media": [],
    }

    for index, source in enumerate(sources[:limit] if limit else sources, start=1):
        url = source["url"]
        name = source.get("name") or f"source-{index}"
        kind = source.get("kind", "fighter")
        slug = slugify(name) or f"source-{index}"
        screenshot_path = screenshot_dir / f"{index:03d}-{slug}.png" if screenshot_dir else None

        html = browser.fetch(
            url,
            wait_for=source.get("wait_for", "body"),
            scroll=source.get("scroll", True),
            screenshot_path=screenshot_path,
        )

        if raw_dir is not None:
            raw_dir.mkdir(parents=True, exist_ok=True)
            (raw_dir / f"{index:03d}-{slug}.html").write_text(html, encoding="utf-8")

        soup = BeautifulSoup(html, "lxml")
        parsed = parse_page(kind, soup, source, url)
        merge_payload(payload, parsed)

        payload["sources"].append({
            "name": name,
            "kind": kind,
            "url": url,
            "scraped_at": now_iso(),
        })

    return payload


def parse_page(kind: str, soup: BeautifulSoup, source: dict[str, Any], url: str) -> dict[str, Any]:
    if kind == "event":
        return {"events": [parse_event(soup, source, url)]}
    if kind == "rankings":
        return {"rankings": [parse_rankings(soup, source, url)]}
    if kind == "media":
        return {"media": parse_media_page(soup, source, url)}
    return {"fighters": [parse_fighter(soup, source, url)]}


def parse_fighter(soup: BeautifulSoup, source: dict[str, Any], url: str) -> dict[str, Any]:
    selectors = source.get("selectors", {})
    person = jsonld_by_type(soup, "Person") or {}
    labels = labelled_values(soup)

    display_name = first_non_empty(
        selector_value(soup, selectors.get("display_name"), url),
        selector_value(soup, selectors.get("name"), url),
        person.get("name"),
        selector_value(soup, "h1", url),
    )
    first_name, last_name = split_name(display_name)

    record_text = first_non_empty(
        selector_value(soup, selectors.get("record"), url),
        labels.get("record"),
        labels.get("boxing record"),
    )
    record = parse_record(record_text)

    fighter = {
        "source_url": url,
        "slug": selector_value(soup, selectors.get("slug"), url) or slugify(display_name),
        "first_name": selector_value(soup, selectors.get("first_name"), url) or first_name,
        "last_name": selector_value(soup, selectors.get("last_name"), url) or last_name,
        "display_name": display_name,
        "ring_name": first_non_empty(selector_value(soup, selectors.get("ring_name"), url), labels.get("alias")),
        "country": first_non_empty(
            selector_value(soup, selectors.get("country"), url),
            selector_value(soup, selectors.get("nationality"), url),
            labels.get("nationality"),
            country_from_jsonld(person.get("nationality")),
        ),
        "stance": first_non_empty(selector_value(soup, selectors.get("stance"), url), labels.get("stance")),
        "weight_class": normalize_weight_class(first_non_empty(
            selector_value(soup, selectors.get("weight_class"), url),
            labels.get("weight class"),
            labels.get("division"),
            labels.get("weight"),
        )),
        "birth_date": parse_date(first_non_empty(
            selector_value(soup, selectors.get("birth_date"), url),
            person.get("birthDate"),
            labels.get("birth date"),
            labels.get("born"),
        )),
        "birth_place": first_non_empty(selector_value(soup, selectors.get("birth_place"), url), labels.get("birth place")),
        "residence": first_non_empty(selector_value(soup, selectors.get("residence"), url), labels.get("residence")),
        "height_cm": parse_measure_cm(first_non_empty(selector_value(soup, selectors.get("height"), url), person.get("height"), labels.get("height"))),
        "reach_cm": parse_measure_cm(first_non_empty(selector_value(soup, selectors.get("reach"), url), labels.get("reach"))),
        "debut_date": parse_date(first_non_empty(selector_value(soup, selectors.get("debut_date"), url), labels.get("debut"))),
        "photo_url": first_non_empty(
            selector_value(soup, selectors.get("photo_url"), url),
            image_from_jsonld(person.get("image")),
            selector_value(soup, "img@src", url),
        ),
        "bio": selector_value(soup, selectors.get("bio"), url),
        "aliases": selector_value(soup, selectors.get("aliases"), url) or [],
        "record": record,
    }

    for label, field in LABEL_FIELD_MAP.items():
        if label in labels and field in {"wins", "losses", "draws", "no_contests", "knockouts"}:
            fighter["record"][field] = parse_int(labels[label]) or fighter["record"].get(field, 0)

    return strip_empty(fighter)


def parse_event(soup: BeautifulSoup, source: dict[str, Any], url: str) -> dict[str, Any]:
    selectors = source.get("selectors", {})
    event_ld = jsonld_by_type(soup, "Event") or {}
    location = event_ld.get("location") if isinstance(event_ld.get("location"), dict) else {}
    organizer = event_ld.get("organizer") if isinstance(event_ld.get("organizer"), dict) else {}

    name = first_non_empty(selector_value(soup, selectors.get("name"), url), event_ld.get("name"), selector_value(soup, "h1", url))
    event = {
        "source_url": url,
        "name": name,
        "slug": selector_value(soup, selectors.get("slug"), url) or slugify(name),
        "subtitle": selector_value(soup, selectors.get("subtitle"), url),
        "event_date": first_non_empty(selector_value(soup, selectors.get("event_date"), url), event_ld.get("startDate")),
        "ring_walks_at": selector_value(soup, selectors.get("ring_walks_at"), url),
        "status": source.get("status", "upcoming"),
        "poster_url": first_non_empty(selector_value(soup, selectors.get("poster_url"), url), image_from_jsonld(event_ld.get("image"))),
        "hero_image_url": selector_value(soup, selectors.get("hero_image_url"), url),
        "broadcast_notes": selector_value(soup, selectors.get("broadcast_notes"), url),
        "ticket_url": first_non_empty(selector_value(soup, selectors.get("ticket_url"), url), event_ld.get("url")),
        "venue": {
            "name": first_non_empty(selector_value(soup, selectors.get("venue"), url), location.get("name")),
            "city": selector_value(soup, selectors.get("city"), url),
            "country": selector_value(soup, selectors.get("venue_country"), url),
        },
        "promoter": {
            "name": first_non_empty(selector_value(soup, selectors.get("promoter"), url), organizer.get("name")),
        },
        "fights": parse_fight_rows(soup, selectors.get("fights"), url),
    }

    return strip_empty(event)


def parse_fight_rows(soup: BeautifulSoup, config: Any, url: str) -> list[dict[str, Any]]:
    if not isinstance(config, dict) or not config.get("rows"):
        return []

    fights: list[dict[str, Any]] = []
    fields = config.get("fields", {})

    for index, row in enumerate(soup.select(config["rows"]), start=1):
        red = first_non_empty(selector_value(row, fields.get("red_corner"), url), selector_value(row, fields.get("fighter_a"), url))
        blue = first_non_empty(selector_value(row, fields.get("blue_corner"), url), selector_value(row, fields.get("fighter_b"), url))
        if not red or not blue:
            continue

        fight = {
            "red_corner": red,
            "blue_corner": blue,
            "winner": selector_value(row, fields.get("winner"), url),
            "method": selector_value(row, fields.get("method"), url),
            "weight_class": normalize_weight_class(selector_value(row, fields.get("weight_class"), url)),
            "title": selector_value(row, fields.get("title"), url),
            "billing": config.get("default_billing", "undercard"),
            "bout_order": parse_int(selector_value(row, fields.get("bout_order"), url)) or index,
            "scheduled_rounds": parse_int(selector_value(row, fields.get("scheduled_rounds"), url)) or config.get("default_rounds", 12),
            "completed_rounds": parse_int(selector_value(row, fields.get("completed_rounds"), url)),
            "result_time": selector_value(row, fields.get("result_time"), url),
            "status": config.get("default_status", "scheduled"),
            "is_title_fight": bool(selector_value(row, fields.get("is_title_fight"), url) or fields.get("is_title_fight") is True),
            "result_notes": selector_value(row, fields.get("result_notes"), url),
        }
        fights.append(strip_empty(fight))

    if fights:
        fights[0]["billing"] = config.get("main_event_billing", "main_event")
    if len(fights) > 1:
        fights[1]["billing"] = config.get("co_main_billing", "co_main_event")

    return fights


def parse_rankings(soup: BeautifulSoup, source: dict[str, Any], url: str) -> dict[str, Any]:
    selectors = source.get("selectors", {})
    config = selectors.get("entries", {})
    rows = soup.select(config.get("rows", "")) if isinstance(config, dict) else []
    fields = config.get("fields", {}) if isinstance(config, dict) else {}

    entries = []
    for index, row in enumerate(rows, start=1):
        fighter = selector_value(row, fields.get("fighter"), url)
        if not fighter:
            continue
        entries.append(strip_empty({
            "rank": parse_int(selector_value(row, fields.get("rank"), url)) or index,
            "fighter": fighter,
            "points": parse_int(selector_value(row, fields.get("points"), url)) or 0,
        }))

    return strip_empty({
        "source_url": url,
        "organisation": first_non_empty(source.get("organisation"), selector_value(soup, selectors.get("organisation"), url)),
        "weight_class": normalize_weight_class(first_non_empty(source.get("weight_class"), selector_value(soup, selectors.get("weight_class"), url))),
        "ranked_on": parse_date(first_non_empty(source.get("ranked_on"), selector_value(soup, selectors.get("ranked_on"), url))) or now_iso()[:10],
        "entries": entries,
    })


def parse_media_page(soup: BeautifulSoup, source: dict[str, Any], url: str) -> list[dict[str, Any]]:
    selectors = source.get("selectors", {})
    image_selector = selectors.get("images", "img@src")
    values = selector_value(soup, {"selector": image_selector.split("@")[0], "attr": image_selector.split("@")[1] if "@" in image_selector else "src", "all": True}, url)
    return [
        strip_empty({
            "source_url": url,
            "type": source.get("type", "event"),
            "parent": source.get("parent"),
            "collection": source.get("collection", "gallery"),
            "url": value,
            "credit": source.get("credit"),
        })
        for value in values or []
    ]


def country_from_jsonld(value: Any) -> str | None:
    if isinstance(value, dict):
        return clean_text(value.get("name"))
    return clean_text(value)


def image_from_jsonld(value: Any) -> str | None:
    if isinstance(value, list):
        return clean_text(value[0]) if value else None
    if isinstance(value, dict):
        return clean_text(value.get("url"))
    return clean_text(value)


def strip_empty(data: dict[str, Any]) -> dict[str, Any]:
    return {
        key: value
        for key, value in data.items()
        if value not in (None, "", [], {}) and not (isinstance(value, dict) and len(value) == 0)
    }


def merge_payload(payload: dict[str, Any], parsed: dict[str, Any]) -> None:
    for key, value in parsed.items():
        if key not in payload:
            payload[key] = []
        if isinstance(value, list):
            payload[key].extend(value)
        elif value:
            payload[key].append(value)
