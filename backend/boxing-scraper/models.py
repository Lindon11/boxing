from dataclasses import dataclass, field, asdict
from typing import Optional


@dataclass
class Country:
    name: str
    code: str
    flag_emoji: Optional[str] = None


@dataclass
class Stance:
    name: str  # Orthodox, Southpaw, Switch


@dataclass
class Boxer:
    wikidata_id: str
    display_name: str
    first_name: Optional[str] = None
    last_name: Optional[str] = None
    ring_name: Optional[str] = None
    slug: Optional[str] = None
    photo_url: Optional[str] = None
    birth_date: Optional[str] = None
    death_date: Optional[str] = None
    country: Optional[Country] = None
    stance: Optional[Stance] = None
    height_cm: Optional[float] = None
    reach_cm: Optional[float] = None
    wins: int = 0
    losses: int = 0
    draws: int = 0
    knockouts: int = 0
    total_bouts: int = 0
    debut_date: Optional[str] = None
    weight_class: Optional[str] = None
    bio: Optional[str] = None
    active: bool = True

    @property
    def record(self) -> str:
        if self.draws > 0:
            return f"{self.wins}-{self.losses}-{self.draws}"
        return f"{self.wins}-{self.losses}"

    def to_dict(self):
        d = asdict(self)
        if self.country:
            d["country"] = asdict(self.country)
        if self.stance:
            d["stance"] = asdict(self.stance)
        return d


@dataclass
class Venue:
    name: str
    city: Optional[str] = None
    country: Optional[Country] = None
    capacity: Optional[int] = None
    wikidata_id: Optional[str] = None
    photo_url: Optional[str] = None


@dataclass
class Promoter:
    name: str
    wikidata_id: Optional[str] = None
    country: Optional[Country] = None


@dataclass
class Broadcaster:
    name: str
    country: Optional[Country] = None
    website_url: Optional[str] = None


@dataclass
class Organisation:
    name: str
    abbreviation: str
    wikidata_id: Optional[str] = None


@dataclass
class Title:
    name: str
    organisation: Organisation
    weight_class: str
    champion: Optional[Boxer] = None
    reign_started_on: Optional[str] = None
    wikidata_id: Optional[str] = None


@dataclass
class Billing:
    name: str  # main_event, co_main_event, undercard


@dataclass
class Bout:
    red_corner: Boxer
    blue_corner: Boxer
    weight_class: str
    scheduled_rounds: int = 12
    billing: str = "undercard"
    title_fight: bool = False
    title: Optional[Title] = None
    bout_order: Optional[int] = None
    result_method: Optional[str] = None
    result_notes: Optional[str] = None
    winner: Optional[str] = None  # red, blue, draw


@dataclass
class BoxingEvent:
    name: str
    slug: Optional[str] = None
    event_date: Optional[str] = None
    venue: Optional[Venue] = None
    promoter: Optional[Promoter] = None
    broadcaster: Optional[Broadcaster] = None
    bouts: list[Bout] = field(default_factory=list)
    status: str = "upcoming"  # upcoming, completed
    wikidata_id: Optional[str] = None
    poster_url: Optional[str] = None
    hero_image_url: Optional[str] = None
    subtitle: Optional[str] = None
    ticket_url: Optional[str] = None

    def to_dict(self):
        d = asdict(self)
        d["bouts"] = [b.to_dict() if hasattr(b, 'to_dict') else b for b in self.bouts]
        return d


@dataclass
class WeightClass:
    name: str
    slug: str
    min_weight_kg: Optional[float] = None
    max_weight_kg: Optional[float] = None
    order: int = 0


@dataclass
class Ranking:
    organisation: Organisation
    weight_class: WeightClass
    rank: int
    boxer: Boxer
    points: Optional[int] = None
