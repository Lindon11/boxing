export interface CountrySummary {
  name: string
  code: string
}

export interface WeightClassSummary {
  name: string
  slug: string
}

export interface FighterSummary {
  id: number
  slug: string
  display_name: string
  ring_name: string | null
  record: string
  wins: number
  losses: number
  draws: number
  no_contests: number
  knockouts: number
  country: CountrySummary | null
  stance: string | null
  weight_class: WeightClassSummary | null
  photo_url: string | null
}

export interface FighterDetail extends FighterSummary {
  first_name: string
  last_name: string
  birth_date: string | null
  birth_place: string | null
  residence: string | null
  height_cm: number | null
  reach_cm: number | null
  debut_date: string | null
  active: boolean
  bio: string | null
  aliases: string[]
  titles: BeltHistorySummary[]
  rankings: RankingSummary[]
  fight_history: FightSummary[]
  upcoming_fight: FightSummary | null
  last_fight: FightSummary | null
}

export interface VenueSummary {
  name: string
  slug: string
  city: string
  region: string | null
  country: string | null
  country_code: string | null
  capacity: number | null
}

export interface PromoterSummary {
  name: string
  slug: string
}

export interface EventSummary {
  id: number
  slug: string
  name: string
  subtitle: string | null
  status: string
  event_date: string | null
  ring_walks_at: string | null
  poster_url: string | null
  hero_image_url: string | null
  broadcast_notes: string | null
  ticket_url: string | null
  venue: VenueSummary | null
  promoter: PromoterSummary | null
  main_fight: FightSummary | null
}

export interface BroadcastSummary {
  region: string
  platform: string | null
  is_ppv: boolean
  details: string | null
  broadcaster: {
    name: string
    slug: string
    website_url: string | null
  }
}

export interface EventDetail extends EventSummary {
  fights: FightSummary[]
  broadcasts: BroadcastSummary[]
}

export interface FightSummary {
  id: number
  title: string | null
  billing: string
  bout_order: number
  scheduled_rounds: number
  completed_rounds: number | null
  status: string
  fight_date: string | null
  is_title_fight: boolean
  result_notes: string | null
  result_method: {
    name: string
    abbreviation: string
  } | null
  weight_class: WeightClassSummary | null
  red_corner: FighterSummary | null
  blue_corner: FighterSummary | null
  winner: FighterSummary | null
  event?: EventSummary
}

export interface RankingSummary {
  rank: number
  points: number
  ranked_on: string | null
  fighter: FighterSummary | null
  organisation: {
    name: string
    abbreviation: string
    slug: string
  }
  weight_class: WeightClassSummary
}

export interface TitleSummary {
  id: number
  name: string
  slug: string
  organisation: {
    name: string
    abbreviation: string
    slug: string
  }
  weight_class: WeightClassSummary
  champion: FighterSummary | null
  reign_started_on: string | null
}

export interface BeltHistorySummary {
  status: string
  reign_started_on: string | null
  reign_ended_on: string | null
  result: string | null
  belt: {
    name: string
    organisation: string
    weight_class: string
  }
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface BoxingFilters {
  countries?: CountrySummary[]
  stances?: Array<{ name: string; slug: string }>
  weight_classes?: WeightClassSummary[]
  promoters?: PromoterSummary[]
  venues?: Array<{ name: string; slug: string; city: string }>
  organisations?: Array<{ name: string; abbreviation: string; slug: string }>
}

export interface HomeResponse {
  featured_event: EventDetail | null
  upcoming_events: EventSummary[]
  latest_results: FightSummary[]
  rankings: RankingSummary[]
  broadcasts: Array<{
    event: EventSummary
    broadcast: {
      region: string
      platform: string | null
      is_ppv: boolean
      broadcaster: string
    }
  }>
  news: Array<{ title: string; timestamp: string }>
  stats: Record<string, number>
}

export interface SearchResponse {
  query: string
  fighters: FighterSummary[]
  events: EventSummary[]
  promoters: Array<{ name: string; slug: string; website_url: string | null }>
  venues: Array<{ name: string; slug: string; city: string; country: string | null }>
  titles: TitleSummary[]
}
