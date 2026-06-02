"""
Wikidata SPARQL queries for boxing data.
Uses direct requests for better User-Agent and rate-limit control.
"""

import time
from typing import Optional
import requests

from models import Boxer, Country, Stance, Organisation, Title, WeightClass, BoxingEvent

ENDPOINT = "https://query.wikidata.org/sparql"
USER_AGENT = "BoxingDB/1.0 (boxingdb.com) scraper for open boxing data collection"
_last_request = 0


def query(sparql: str, retries: int = 3) -> list[dict]:
    global _last_request
    elapsed = time.time() - _last_request
    if elapsed < 2.0:
        time.sleep(2.0 - elapsed)

    headers = {"User-Agent": USER_AGENT, "Accept": "application/sparql-results+json"}
    for attempt in range(retries):
        try:
            resp = requests.get(ENDPOINT, params={"format": "json", "query": sparql}, headers=headers, timeout=120)
            _last_request = time.time()
            if resp.status_code == 429:
                wait = 65
                print(f"  Rate limited, waiting {wait}s...")
                time.sleep(wait)
                continue
            resp.raise_for_status()
            return [
                {k: v.get("value") for k, v in r.items()}
                for r in resp.json().get("results", {}).get("bindings", [])
            ]
        except requests.exceptions.RequestException as e:
            print(f"  Attempt {attempt + 1}/{retries} failed: {e}")
            if attempt < retries - 1:
                time.sleep(5)
    return []


def fetch_boxers(limit: int = 500, offset: int = 0) -> list[Boxer]:
    sparql = f"""
    SELECT DISTINCT ?fighter ?fighterLabel ?record ?wins ?losses ?draws ?kos
           ?birthDate ?deathDate ?height ?reach ?stance ?stanceLabel
           ?country ?countryCode ?countryLabel ?weightClass ?weightClassLabel
           ?image ?debut ?description
    WHERE {{
      ?fighter wdt:P106 wd:Q11338576.
      OPTIONAL {{ ?fighter wdt:P2417 ?record. }}
      OPTIONAL {{ ?fighter wdt:P1092 ?wins. }}
      OPTIONAL {{ ?fighter wdt:P1093 ?losses. }}
      OPTIONAL {{ ?fighter wdt:P1140 ?draws. }}
      OPTIONAL {{ ?fighter wdt:P3666 ?kos. }}
      OPTIONAL {{ ?fighter wdt:P569 ?birthDate. }}
      OPTIONAL {{ ?fighter wdt:P570 ?deathDate. }}
      OPTIONAL {{ ?fighter wdt:P2048 ?height. }}
      OPTIONAL {{ ?fighter wdt:P2442 ?reach. }}
      OPTIONAL {{ ?fighter wdt:P1785 ?stance. }}
      OPTIONAL {{ ?fighter wdt:P27 ?country. }}
      OPTIONAL {{ ?fighter wdt:P18 ?image. }}
      OPTIONAL {{ ?fighter wdt:P2031 ?debut. }}
      OPTIONAL {{ ?fighter wdt:P10698 ?weightClass. }}
      OPTIONAL {{ ?fighter wdt:P27/wdt:P297 ?countryCode. }}
      SERVICE wikibase:label {{ bd:serviceParam wikibase:language "en". }}
    }}
    LIMIT {limit} OFFSET {offset}
    """
    rows = query(sparql)
    print(f"  Got {len(rows)} fighter rows from Wikidata")
    boxers = []
    for r in rows:
        boxers.append(Boxer(
            wikidata_id=r.get("fighter", "").split("/")[-1] if r.get("fighter") else "",
            display_name=r.get("fighterLabel", ""),
            birth_date=r.get("birthDate", ""),
            death_date=r.get("deathDate", ""),
            height_cm=_float(r.get("height")),
            reach_cm=_float(r.get("reach")),
            stance=Stance(name=r.get("stanceLabel", "")) if r.get("stanceLabel") else None,
            country=Country(
                name=r.get("countryLabel", ""),
                code=r.get("countryCode", ""),
            ) if r.get("country") else None,
            wins=_int(r.get("wins", 0)),
            losses=_int(r.get("losses", 0)),
            draws=_int(r.get("draws", 0)),
            knockouts=_int(r.get("kos", 0)),
            photo_url=r.get("image") if r.get("image", "").startswith("http") else None,
            debut_date=r.get("debut", ""),
            weight_class=r.get("weightClassLabel", ""),
            bio=r.get("description", ""),
        ))
    return boxers


def fetch_title_holders() -> list[Title]:
    sparql = """
    SELECT ?title ?titleLabel ?organisation ?organisationLabel ?orgAbbr
           ?weightClass ?weightClassLabel
           ?champion ?championLabel
           ?reignStart
    WHERE {
      ?title wdt:P31 wd:Q1144228.
      OPTIONAL { ?title wdt:P361 ?organisation. }
      OPTIONAL { ?title wdt:P10698 ?weightClass. }
      OPTIONAL { ?title wdt:P488 ?champion. }
      OPTIONAL { ?title wdt:P580 ?reignStart. }
      SERVICE wikibase:label { bd:serviceParam wikibase:language "en". }
      FILTER NOT EXISTS { ?title wdt:P582 ?reignEnd. }
    }
    ORDER BY ?organisationLabel ?weightClassLabel
    """
    rows = query(sparql)
    print(f"  Got {len(rows)} title rows from Wikidata")
    titles = []
    for r in rows:
        org = Organisation(
            name=r.get("organisationLabel", ""),
            abbreviation=r.get("orgAbbr", ""),
            wikidata_id=r.get("organisation", "").split("/")[-1] if r.get("organisation") else None,
        )
        champion = Boxer(
            wikidata_id=r.get("champion", "").split("/")[-1] if r.get("champion") else "",
            display_name=r.get("championLabel", ""),
        )
        titles.append(Title(
            name=r.get("titleLabel", ""),
            organisation=org,
            weight_class=r.get("weightClassLabel", ""),
            champion=champion if r.get("champion") else None,
            reign_started_on=r.get("reignStart", ""),
            wikidata_id=r.get("title", "").split("/")[-1] if r.get("title") else None,
        ))
    return titles


def fetch_weight_classes() -> list[WeightClass]:
    """Standard professional boxing weight classes (hardcoded — they don't change)."""
    return [
        WeightClass(name="Minimumweight", slug="minimumweight", min_weight_kg=0, max_weight_kg=47.627, order=1),
        WeightClass(name="Light Flyweight", slug="light-flyweight", min_weight_kg=47.627, max_weight_kg=48.988, order=2),
        WeightClass(name="Flyweight", slug="flyweight", min_weight_kg=48.988, max_weight_kg=50.802, order=3),
        WeightClass(name="Super Flyweight", slug="super-flyweight", min_weight_kg=50.802, max_weight_kg=52.163, order=4),
        WeightClass(name="Bantamweight", slug="bantamweight", min_weight_kg=52.163, max_weight_kg=53.525, order=5),
        WeightClass(name="Super Bantamweight", slug="super-bantamweight", min_weight_kg=53.525, max_weight_kg=55.338, order=6),
        WeightClass(name="Featherweight", slug="featherweight", min_weight_kg=55.338, max_weight_kg=57.153, order=7),
        WeightClass(name="Super Featherweight", slug="super-featherweight", min_weight_kg=57.153, max_weight_kg=58.967, order=8),
        WeightClass(name="Lightweight", slug="lightweight", min_weight_kg=58.967, max_weight_kg=61.235, order=9),
        WeightClass(name="Super Lightweight", slug="super-lightweight", min_weight_kg=61.235, max_weight_kg=63.503, order=10),
        WeightClass(name="Welterweight", slug="welterweight", min_weight_kg=63.503, max_weight_kg=66.678, order=11),
        WeightClass(name="Super Welterweight", slug="super-welterweight", min_weight_kg=66.678, max_weight_kg=69.853, order=12),
        WeightClass(name="Middleweight", slug="middleweight", min_weight_kg=69.853, max_weight_kg=72.574, order=13),
        WeightClass(name="Super Middleweight", slug="super-middleweight", min_weight_kg=72.574, max_weight_kg=76.363, order=14),
        WeightClass(name="Light Heavyweight", slug="light-heavyweight", min_weight_kg=76.363, max_weight_kg=79.378, order=15),
        WeightClass(name="Cruiserweight", slug="cruiserweight", min_weight_kg=79.378, max_weight_kg=90.719, order=16),
        WeightClass(name="Heavyweight", slug="heavyweight", min_weight_kg=90.719, max_weight_kg=999, order=17),
    ]


def fetch_events(limit: int = 100, offset: int = 0) -> list[BoxingEvent]:
    sparql = f"""
    SELECT ?event ?eventLabel ?date ?venue ?venueLabel ?venueCity ?venueCountry
           ?promoter ?promoterLabel ?image ?description
    WHERE {{
      ?event wdt:P31 wd:Q16510064.
      OPTIONAL {{ ?event wdt:P585 ?date. }}
      OPTIONAL {{ ?event wdt:P276 ?venue. }}
      OPTIONAL {{ ?event wdt:P664 ?promoter. }}
      OPTIONAL {{ ?event wdt:P18 ?image. }}
      SERVICE wikibase:label {{ bd:serviceParam wikibase:language "en". }}
    }}
    ORDER BY DESC(?date)
    LIMIT {limit} OFFSET {offset}
    """
    rows = query(sparql)
    print(f"  Got {len(rows)} event rows from Wikidata")
    events = []
    for r in rows:
        events.append(BoxingEvent(
            name=r.get("eventLabel", ""),
            event_date=r.get("date", ""),
            status="completed" if r.get("date") else "upcoming",
            wikidata_id=r.get("event", "").split("/")[-1] if r.get("event") else None,
            poster_url=r.get("image") if r.get("image", "").startswith("http") else None,
            subtitle=r.get("description", ""),
        ))
    return events


def _float(val) -> Optional[float]:
    if val is None:
        return None
    try:
        return float(val)
    except (ValueError, TypeError):
        return None


def _int(val) -> int:
    if val is None:
        return 0
    try:
        return int(val)
    except (ValueError, TypeError):
        return 0
