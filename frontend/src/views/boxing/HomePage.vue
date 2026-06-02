<template>
  <div v-if="loading" class="flex h-[calc(100vh-180px)] items-center justify-center">
    <div class="text-center">
      <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-red-500 border-t-transparent" />
      <p class="mt-3 text-xs font-bold uppercase text-zinc-500">Loading</p>
    </div>
  </div>

  <ErrorState
    v-else-if="error"
    title="Could not load the BoxingDB homepage"
    :message="error"
    :retry="loadHome"
  />

  <div v-else-if="home" class="space-y-4 md:space-y-5">
    <section class="bd-panel">
      <div class="grid overflow-hidden lg:grid-cols-[minmax(0,1fr)_420px]">
        <div class="p-4 sm:p-6 lg:p-8">
          <p class="bd-kicker">Live Boxing Database</p>
          <h1 class="mt-2 max-w-3xl text-3xl font-black leading-none text-white sm:text-4xl md:text-5xl">
            Fighters, fight nights, rankings and titles.
          </h1>
          <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-400 sm:text-base sm:leading-7">
            Track the names, records, belts, results and broadcast details that shape the modern boxing calendar.
          </p>

          <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:gap-3">
            <RouterLink to="/events" class="bd-button-primary bd-focus-ring">
              <CalendarIcon class="size-4" />
              Browse Events
            </RouterLink>
            <RouterLink to="/fighters" class="bd-button-secondary bd-focus-ring">
              <FighterIcon class="size-4" />
              Explore Fighters
            </RouterLink>
          </div>

          <div v-if="home.featured_event?.main_fight" class="mt-5 rounded-lg border border-white/10 bg-black/20 p-3 sm:p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
              <span class="bd-chip bd-chip-red">Featured bout</span>
              <span class="text-sm font-semibold text-zinc-400">{{ formatDate(home.featured_event.event_date) }}</span>
            </div>
            <div class="grid gap-3 sm:grid-cols-[1fr_auto_1fr] sm:items-center">
              <div class="min-w-0">
                <p class="truncate text-base font-black text-white sm:text-lg">{{ home.featured_event.main_fight.red_corner?.display_name }}</p>
                <p class="text-sm text-zinc-500">{{ home.featured_event.main_fight.red_corner?.record }}</p>
              </div>
              <div class="flex size-9 items-center justify-center rounded-lg bg-gradient-to-br from-red-600 to-red-900 text-xs font-black text-white bd-vs sm:size-10">
                VS
              </div>
              <div class="min-w-0 sm:text-right">
                <p class="truncate text-base font-black text-white sm:text-lg">{{ home.featured_event.main_fight.blue_corner?.display_name }}</p>
                <p class="text-sm text-zinc-500">{{ home.featured_event.main_fight.blue_corner?.record }}</p>
              </div>
            </div>
            <div class="mt-4 flex flex-col gap-2 text-sm text-zinc-400 sm:flex-row sm:items-center sm:justify-between">
              <span class="inline-flex min-w-0 items-center gap-2">
                <MapPinIcon class="size-4 shrink-0 text-red-400" />
                <span class="truncate">{{ venueShort(home.featured_event) }}</span>
              </span>
              <RouterLink :to="`/events/${home.featured_event.slug}/fight-card`" class="font-black text-red-300 transition hover:text-red-200">
                View fight card
              </RouterLink>
            </div>
          </div>
        </div>

        <RouterLink
          v-if="home.featured_event"
          :to="`/events/${home.featured_event.slug}`"
          class="group relative min-h-72 overflow-hidden border-t border-white/10 bg-[#111827] lg:border-l lg:border-t-0"
        >
          <img
            v-if="home.featured_event.poster_url || home.featured_event.hero_image_url"
            :src="home.featured_event.poster_url || home.featured_event.hero_image_url || ''"
            :alt="home.featured_event.name"
            class="absolute inset-0 h-full w-full object-cover opacity-75 transition duration-300 group-hover:scale-105 group-hover:opacity-95"
          >
          <div class="absolute inset-0 bg-gradient-to-t from-black via-black/45 to-transparent" />
          <div class="absolute inset-x-0 bottom-0 p-5">
            <span class="bd-chip bd-chip-gold">{{ home.featured_event.status }}</span>
            <p class="mt-3 text-2xl font-black leading-tight text-white">{{ home.featured_event.name }}</p>
            <p class="mt-1 text-sm text-zinc-300">{{ home.featured_event.subtitle || home.featured_event.broadcast_notes || 'Event details available' }}</p>
          </div>
        </RouterLink>
      </div>
    </section>

    <section class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
      <StatTile v-for="tile in statTiles" :key="tile.label" :icon="tile.icon" :label="tile.label" :value="tile.value" />
    </section>

    <section v-if="home.upcoming_events?.length" class="bd-panel p-4 sm:p-5">
      <div class="mb-4 flex items-center justify-between gap-3">
        <div>
          <p class="bd-kicker">Calendar</p>
          <h2 class="text-xl font-black text-white">Upcoming Events</h2>
        </div>
        <RouterLink to="/events" class="text-sm font-black text-red-300 transition hover:text-red-200">View all</RouterLink>
      </div>
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <RouterLink
          v-for="ev in home.upcoming_events.slice(0, 4)"
          :key="ev.slug"
          :to="`/events/${ev.slug}`"
          class="bd-card-hover rounded-lg border border-white/10 bg-white/[0.035] p-4"
        >
          <p class="text-sm font-black text-white">{{ ev.name }}</p>
          <div class="mt-3 flex items-center gap-2 text-sm text-zinc-400">
            <CalendarIcon class="size-4 text-red-400" />
            <span>{{ formatDate(ev.event_date) }}</span>
          </div>
          <p class="mt-1 truncate text-sm text-zinc-500">{{ venueShort(ev) }}</p>
        </RouterLink>
      </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_380px]">
      <div class="grid gap-4 lg:grid-cols-2">
        <section class="bd-panel p-4 sm:p-5">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <p class="bd-kicker">Form Guide</p>
              <h2 class="text-xl font-black text-white">Rankings</h2>
            </div>
            <RouterLink to="/rankings" class="text-sm font-black text-red-300 transition hover:text-red-200">View all</RouterLink>
          </div>

          <div class="space-y-2 sm:hidden">
            <RouterLink
              v-for="r in home.rankings.slice(0, 8)"
              :key="r.rank"
              :to="r.fighter ? `/fighters/${r.fighter.slug}` : '/rankings'"
              class="flex items-center gap-3 rounded-lg border border-white/10 bg-black/15 p-3"
            >
              <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/[0.06] font-black text-zinc-300">{{ r.rank }}</span>
              <span class="min-w-0 flex-1">
                <span class="block truncate font-black text-white">{{ r.fighter?.display_name || 'TBC' }}</span>
                <span class="text-sm text-zinc-500">{{ r.fighter?.record || '-' }}</span>
              </span>
              <span class="text-sm font-black text-white">{{ r.points }}</span>
            </RouterLink>
          </div>

          <div class="hidden overflow-x-auto sm:block">
            <table class="bd-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Fighter</th>
                  <th>Record</th>
                  <th class="text-right">Pts</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in home.rankings.slice(0, 10)" :key="r.rank">
                  <td class="font-black text-zinc-500">{{ r.rank }}</td>
                  <td>
                    <RouterLink v-if="r.fighter" :to="`/fighters/${r.fighter.slug}`" class="font-black text-white hover:text-red-300">
                      {{ r.fighter.display_name }}
                    </RouterLink>
                  </td>
                  <td class="text-zinc-500">{{ r.fighter?.record }}</td>
                  <td class="text-right font-black text-white">{{ r.points }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="bd-panel p-4 sm:p-5">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <p class="bd-kicker">Scorecards</p>
              <h2 class="text-xl font-black text-white">Latest Results</h2>
            </div>
            <RouterLink to="/events?status=completed" class="text-sm font-black text-red-300 transition hover:text-red-200">View all</RouterLink>
          </div>

          <div class="space-y-2">
            <RouterLink
              v-for="fight in home.latest_results.slice(0, 8)"
              :key="fight.id"
              :to="fight.event ? `/events/${fight.event.slug}` : '/events'"
              class="bd-card-hover flex items-center gap-3 rounded-lg border border-white/10 bg-black/15 p-3"
            >
              <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/[0.06] text-sm font-black text-zinc-400">{{ fight.bout_order }}</span>
              <span class="min-w-0 flex-1">
                <span class="block truncate font-black text-white">
                  {{ fight.red_corner?.display_name }} <span class="text-zinc-600">vs</span> {{ fight.blue_corner?.display_name }}
                </span>
                <span class="text-sm text-zinc-500">{{ fight.result_method?.abbreviation || fight.status }} - {{ formatDate(fight.fight_date) }}</span>
              </span>
            </RouterLink>
          </div>
        </section>
      </div>

      <aside class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
        <section class="bd-panel p-4 sm:p-5">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <p class="bd-kicker">Updates</p>
              <h2 class="text-xl font-black text-white">News</h2>
            </div>
            <RouterLink to="/news" class="text-sm font-black text-red-300 transition hover:text-red-200">View all</RouterLink>
          </div>
          <div class="space-y-2">
            <article v-for="item in home.news.slice(0, 6)" :key="item.title" class="rounded-lg border border-white/10 bg-black/15 p-3">
              <p class="font-black leading-snug text-white">{{ item.title }}</p>
              <p class="mt-1 text-sm text-zinc-500">{{ item.timestamp }}</p>
            </article>
          </div>
        </section>

        <section class="bd-panel p-4 sm:p-5">
          <div class="mb-4">
            <p class="bd-kicker">Viewing</p>
            <h2 class="text-xl font-black text-white">Broadcasts</h2>
          </div>
          <div class="space-y-2">
            <RouterLink
              v-for="item in home.broadcasts.slice(0, 6)"
              :key="`${item.event.slug}-${item.broadcast.broadcaster}`"
              :to="`/events/${item.event.slug}`"
              class="bd-card-hover flex items-center justify-between gap-3 rounded-lg border border-white/10 bg-black/15 p-3"
            >
              <span class="min-w-0 flex-1">
                <span class="block truncate font-black text-white">{{ item.event.name }}</span>
                <span class="text-sm text-zinc-500">{{ item.broadcast.broadcaster }}</span>
              </span>
              <span class="bd-chip">{{ item.broadcast.platform || 'TV' }}</span>
            </RouterLink>
          </div>
        </section>
      </aside>
    </div>
  </div>

  <EmptyState v-else title="No BoxingDB data yet" message="Run the boxing database seeder to populate the public site." />
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  CalendarDays as CalendarIcon,
  Dumbbell as FighterIcon,
  MapPin as MapPinIcon,
  Shield as PromoterIcon,
  Swords as FightIcon,
  Trophy as TrophyIcon,
  Map as VenueIcon,
} from '@lucide/vue'
import EmptyState from '@/components/boxing/EmptyState.vue'
import ErrorState from '@/components/boxing/ErrorState.vue'
import StatTile from '@/components/boxing/StatTile.vue'
import { boxingApi } from '@/services/boxing'
import type { EventSummary, HomeResponse } from '@/types/boxing'
import { formatDate, formatNumber } from '@/utils/boxing-format'

const home = ref<HomeResponse | null>(null)
const loading = ref(true)
const error = ref('')

const statTiles = computed(() => {
  const stats = home.value?.stats || {}
  return [
    { label: 'Fighters', value: formatNumber(stats.fighters), icon: FighterIcon },
    { label: 'Events', value: formatNumber(stats.events), icon: CalendarIcon },
    { label: 'Fights', value: formatNumber(stats.fights), icon: FightIcon },
    { label: 'Promoters', value: formatNumber(stats.promoters), icon: PromoterIcon },
    { label: 'Titles', value: formatNumber(stats.titles), icon: TrophyIcon },
    { label: 'Venues', value: formatNumber(stats.venues), icon: VenueIcon },
  ]
})

function venueShort(event: EventSummary) {
  if (!event.venue) return 'TBC'
  return [event.venue.city, event.venue.country].filter(Boolean).join(', ')
}

async function loadHome() {
  loading.value = true
  error.value = ''
  try {
    home.value = await boxingApi.home()
  } catch {
    error.value = 'The live boxing feed is unavailable right now. Check the API service and try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadHome)
</script>
