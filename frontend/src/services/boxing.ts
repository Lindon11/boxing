import api from './api'
import type {
  BoxingFilters,
  EventDetail,
  EventSummary,
  FightSummary,
  FighterDetail,
  FighterSummary,
  HomeResponse,
  PaginatedResponse,
  RankingSummary,
  SearchResponse,
  TitleSummary,
} from '@/types/boxing'

interface FighterIndexResponse {
  fighters: PaginatedResponse<FighterSummary>
  filters: BoxingFilters
}

interface EventIndexResponse {
  events: PaginatedResponse<EventSummary>
  filters: BoxingFilters
}

interface FighterShowResponse {
  fighter: FighterDetail
}

interface EventShowResponse {
  event: EventDetail
}

interface FightCardResponse {
  event: EventSummary
  fights: FightSummary[]
}

interface RankingsResponse {
  rankings: RankingSummary[]
  filters: BoxingFilters
  selected: {
    organisation: string
    weight_class: string
  }
}

interface TitlesResponse {
  titles: TitleSummary[]
}

const toQuery = (params: Record<string, string | number | undefined | null>) => {
  const query = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      query.set(key, String(value))
    }
  })

  const queryString = query.toString()
  return queryString ? `?${queryString}` : ''
}

export const boxingApi = {
  async home() {
    const response = await api.get<HomeResponse>('/api/v1/boxing/home')
    return response.data
  },

  async fighters(params: Record<string, string | number | undefined | null> = {}) {
    const response = await api.get<FighterIndexResponse>(`/api/v1/boxing/fighters${toQuery(params)}`)
    return response.data
  },

  async fighter(slug: string) {
    const response = await api.get<FighterShowResponse>(`/api/v1/boxing/fighters/${slug}`)
    return response.data.fighter
  },

  async events(params: Record<string, string | number | undefined | null> = {}) {
    const response = await api.get<EventIndexResponse>(`/api/v1/boxing/events${toQuery(params)}`)
    return response.data
  },

  async event(slug: string) {
    const response = await api.get<EventShowResponse>(`/api/v1/boxing/events/${slug}`)
    return response.data.event
  },

  async fightCard(slug: string) {
    const response = await api.get<FightCardResponse>(`/api/v1/boxing/events/${slug}/fight-card`)
    return response.data
  },

  async rankings(params: Record<string, string | number | undefined | null> = {}) {
    const response = await api.get<RankingsResponse>(`/api/v1/boxing/rankings${toQuery(params)}`)
    return response.data
  },

  async titles() {
    const response = await api.get<TitlesResponse>('/api/v1/boxing/titles')
    return response.data
  },

  async search(query: string) {
    const response = await api.get<SearchResponse>(`/api/v1/boxing/search${toQuery({ q: query })}`)
    return response.data
  },
}
