from __future__ import annotations

from datetime import datetime, timezone
import json
import re
from typing import Any
from urllib.parse import urljoin

from bs4 import BeautifulSoup

try:
    from dateutil import parser as date_parser
except ImportError:  # pragma: no cover - dependency is declared, this is a friendly fallback.
    date_parser = None


WEIGHT_CLASS_ALIASES = {
    "heavy": "Heavyweight",
    "heavyweight": "Heavyweight",
    "bridgerweight": "Bridgerweight",
    "cruiser": "Cruiserweight",
    "cruiserweight": "Cruiserweight",
    "light heavy": "Light Heavyweight",
    "light heavyweight": "Light Heavyweight",
    "super middle": "Super Middleweight",
    "super middleweight": "Super Middleweight",
    "middle": "Middleweight",
    "middleweight": "Middleweight",
    "super welter": "Super Welterweight",
    "super welterweight": "Super Welterweight",
    "light middleweight": "Super Welterweight",
    "welter": "Welterweight",
    "welterweight": "Welterweight",
    "super lightweight": "Super Lightweight",
    "junior welterweight": "Super Lightweight",
    "lightweight": "Lightweight",
    "super featherweight": "Super Featherweight",
    "junior lightweight": "Super Featherweight",
    "featherweight": "Featherweight",
    "super bantamweight": "Super Bantamweight",
    "junior featherweight": "Super Bantamweight",
    "bantamweight": "Bantamweight",
    "super flyweight": "Super Flyweight",
    "junior bantamweight": "Super Flyweight",
    "flyweight": "Flyweight",
    "light flyweight": "Light Flyweight",
    "minimumweight": "Minimumweight",
    "strawweight": "Minimumweight",
}

LABEL_FIELD_MAP = {
    "alias": "ring_name",
    "birth date": "birth_date",
    "born": "birth_date",
    "birth place": "birth_place",
    "debut": "debut_date",
    "division": "weight_class",
    "draws": "draws",
    "height": "height_cm",
    "ko": "knockouts",
    "kos": "knockouts",
    "knockouts": "knockouts",
    "losses": "losses",
    "nationality": "country",
    "no contests": "no_contests",
    "reach": "reach_cm",
    "record": "record_text",
    "residence": "residence",
    "stance": "stance",
    "total fights": "total_fights",
    "weight": "weight_class",
    "weight class": "weight_class",
    "wins": "wins",
    "wins by ko": "knockouts",
}


def now_iso() -> str:
    return datetime.now(timezone.utc).replace(microsecond=0).isoformat()


def clean_text(value: Any) -> str | None:
    if value is None:
        return None
    text = re.sub(r"\s+", " ", str(value)).strip()
    return text or None


def slugify(value: str | None) -> str | None:
    value = clean_text(value)
    if not value:
        return None
    slug = re.sub(r"[^a-z0-9]+", "-", value.lower()).strip("-")
    return slug or None


def first_non_empty(*values: Any) -> Any:
    for value in values:
        if isinstance(value, str):
            value = clean_text(value)
        if value not in (None, "", [], {}):
            return value
    return None


def selector_value(root: BeautifulSoup, spec: Any, base_url: str | None = None) -> Any:
    if not spec:
        return None

    if isinstance(spec, list):
        for item in spec:
            value = selector_value(root, item, base_url)
            if value not in (None, "", [], {}):
                return value
        return None

    if isinstance(spec, str):
        selector = spec
        attr = None
        if "@" in spec:
            selector, attr = spec.rsplit("@", 1)
        node = root.select_one(selector)
        if node is None:
            return None
        value = node.get(attr) if attr else node.get_text(" ", strip=True)
        return normalize_url(clean_text(value), base_url) if attr in {"href", "src"} else clean_text(value)

    if isinstance(spec, dict):
        selector = spec.get("selector")
        attr = spec.get("attr")
        regex = spec.get("regex")
        default = spec.get("default")
        all_values = bool(spec.get("all"))

        nodes = root.select(selector) if selector else []
        if not nodes:
            return default

        def value_from_node(node):
            raw = node.get(attr) if attr else node.get_text(" ", strip=True)
            value = normalize_url(clean_text(raw), base_url) if attr in {"href", "src"} else clean_text(raw)
            if value and regex:
                match = re.search(regex, value, re.IGNORECASE)
                value = match.group(1) if match and match.groups() else (match.group(0) if match else None)
            return value

        values = [value_from_node(node) for node in nodes]
        values = [value for value in values if value]
        return values if all_values else first_non_empty(*values, default)

    return None


def normalize_url(value: str | None, base_url: str | None) -> str | None:
    value = clean_text(value)
    if not value:
        return None
    return urljoin(base_url, value) if base_url else value


def jsonld_items(soup: BeautifulSoup) -> list[dict[str, Any]]:
    items: list[dict[str, Any]] = []
    for script in soup.select('script[type="application/ld+json"]'):
        raw = script.string or script.get_text()
        if not raw:
            continue
        try:
            data = json.loads(raw)
        except json.JSONDecodeError:
            continue
        items.extend(flatten_jsonld(data))
    return items


def flatten_jsonld(data: Any) -> list[dict[str, Any]]:
    if isinstance(data, list):
        return [item for entry in data for item in flatten_jsonld(entry)]
    if isinstance(data, dict):
        graph = data.get("@graph")
        if isinstance(graph, list):
            return [item for entry in graph for item in flatten_jsonld(entry)]
        return [data]
    return []


def jsonld_by_type(soup: BeautifulSoup, type_name: str) -> dict[str, Any] | None:
    for item in jsonld_items(soup):
        item_type = item.get("@type")
        if isinstance(item_type, list):
            matches = type_name in item_type
        else:
            matches = item_type == type_name
        if matches:
            return item
    return None


def labelled_values(soup: BeautifulSoup) -> dict[str, str]:
    values: dict[str, str] = {}

    for row in soup.select("tr"):
        cells = row.find_all(["th", "td"])
        if len(cells) >= 2:
            key = clean_label(cells[0].get_text(" ", strip=True))
            value = clean_text(cells[1].get_text(" ", strip=True))
            if key and value:
                values[key] = value

    for term in soup.select("dt"):
        value_node = term.find_next_sibling("dd")
        key = clean_label(term.get_text(" ", strip=True))
        value = clean_text(value_node.get_text(" ", strip=True)) if value_node else None
        if key and value:
            values[key] = value

    return values


def clean_label(value: str | None) -> str | None:
    value = clean_text(value)
    if not value:
        return None
    return re.sub(r"[^a-z0-9 ]+", "", value.lower()).strip()


def parse_record(value: str | dict[str, Any] | None) -> dict[str, int]:
    if isinstance(value, dict):
        return {
            "wins": int_or_zero(value.get("wins")),
            "losses": int_or_zero(value.get("losses")),
            "draws": int_or_zero(value.get("draws")),
            "no_contests": int_or_zero(value.get("no_contests")),
            "knockouts": int_or_zero(value.get("knockouts")),
        }

    text = clean_text(value) or ""
    ko_match = re.search(r"(\d+)\s*(?:ko|kos|knockouts)", text, re.IGNORECASE)
    numbers = [int(match) for match in re.findall(r"\d+", text)]

    wins = numbers[0] if len(numbers) >= 1 else 0
    losses = numbers[1] if len(numbers) >= 2 else 0
    draws = numbers[2] if len(numbers) >= 3 else 0
    no_contests = 0
    knockouts = int(ko_match.group(1)) if ko_match else 0

    nc_match = re.search(r"(\d+)\s*(?:nc|no contests?)", text, re.IGNORECASE)
    if nc_match:
        no_contests = int(nc_match.group(1))

    return {
        "wins": wins,
        "losses": losses,
        "draws": draws,
        "no_contests": no_contests,
        "knockouts": knockouts,
    }


def parse_int(value: Any) -> int | None:
    if value in (None, ""):
        return None
    match = re.search(r"\d+", str(value).replace(",", ""))
    return int(match.group(0)) if match else None


def int_or_zero(value: Any) -> int:
    parsed = parse_int(value)
    return parsed if parsed is not None else 0


def parse_measure_cm(value: Any) -> int | None:
    text = clean_text(value)
    if not text:
        return None

    cm_match = re.search(r"(\d{2,3})\s*cm", text, re.IGNORECASE)
    if cm_match:
        return int(cm_match.group(1))

    feet_match = re.search(r"(\d)\s*(?:ft|'|feet)\s*(\d{1,2})?", text, re.IGNORECASE)
    if feet_match:
        feet = int(feet_match.group(1))
        inches = int(feet_match.group(2) or 0)
        return round((feet * 12 + inches) * 2.54)

    inches_match = re.search(r"(\d{2,3})\s*(?:in|inches|\")", text, re.IGNORECASE)
    if inches_match:
        return round(int(inches_match.group(1)) * 2.54)

    return parse_int(text)


def parse_date(value: Any) -> str | None:
    text = clean_text(value)
    if not text:
        return None
    if date_parser:
        try:
            return date_parser.parse(text, fuzzy=True).date().isoformat()
        except (ValueError, OverflowError):
            return None

    for fmt in ("%Y-%m-%d", "%d/%m/%Y", "%d %B %Y", "%B %d, %Y"):
        try:
            return datetime.strptime(text, fmt).date().isoformat()
        except ValueError:
            continue
    return None


def normalize_weight_class(value: Any) -> str | None:
    text = clean_text(value)
    if not text:
        return None
    key = re.sub(r"[^a-z ]+", "", text.lower()).strip()
    return WEIGHT_CLASS_ALIASES.get(key, text.title())


def split_name(display_name: str | None) -> tuple[str, str]:
    name = clean_text(display_name) or "Unknown Fighter"
    parts = name.split()
    if len(parts) == 1:
        return parts[0], "Unknown"
    return " ".join(parts[:-1]), parts[-1]
