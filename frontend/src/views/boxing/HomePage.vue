<template>
  <LoadingPanel v-if="loading" />
  <ErrorState
    v-else-if="error"
    title="Could not load the BoxingDB homepage"
    :message="error"
    :retry="loadHome"
  />

  <div v-else-if="home" class="space-y-4">
    <section
      v-if="home.featured_event"
      class="relative min-h-[520px] overflow-hidden rounded-lg border border-white/10 bg-zinc-950 shadow-2xl shadow-black/30"
    >
      <img
        v-if="home.featured_event.hero_image_url"
        :src="home.featured_event.hero_image_url"
        :alt="home.featured_event.name"
        class="absolute inset-0 h-full w-full object-cover opacity-55"
      >
      <div class="absolute inset-0 bg-gradient-to-r from-black via-black/75 to-black/20" />
      <div class="relative grid min-h-[520px] gap-6 p-6 md:grid-cols-[1.12fr_0.88fr] md:p-10">
        <div class="flex max-w-2xl flex-col justify-center">
          <p class="text-sm font-black uppercase tracking-[0.22em] text-yellow-300">{{ home.featured_event.subtitle }}</p>
          <h1 class="mt-4 text-5xl font-black uppercase leading-none text-white md:text-7xl">
            {{ home.featured_event.name }}
          </h1>
          <form class="mt-7 max-w-xl" @submit.prevent="submitHeroSearch">
            <label class="relative block">
              <SearchIcon class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-red-400" />
              <input
                v-model="heroSearch"
                class="h-14 w-full rounded-lg border border-white/15 bg-black/45 pl-12 pr-28 text-sm font-bold text-white shadow-2xl shadow-black/30 outline-none backdrop-blur placeholder:text-zinc-500 focus:border-red-500"
                placeholder="Search fighters, events, titles..."
                type="search"
              >
              <button class="absolute right-1.5 top-1.5 h-11 rounded-lg bg-red-600 px-5 text-sm font-black text-white transition hover:bg-red-500" type="submit">
                Search
              </button>
            </label>
          </form>
          <div class="mt-6 grid gap-2 text-sm font-medium text-zinc-200">
            <p class="flex items-center gap-2"><CalendarIcon class="size-4 text-red-400" /> {{ formatDateTime(home.featured_event.event_date) }}</p>
            <p class="flex items-center gap-2"><MapPinIcon class="size-4 text-red-400" /> {{ venueLine(home.featured_event) }}</p>
            <p class="flex items-center gap-2"><RadioIcon class="size-4 text-red-400" /> {{ home.featured_event.broadcast_notes || 'Broadcast TBC' }}</p>
          </div>
          <div class="mt-7 flex flex-wrap gap-3">
            <RouterLink
              :to="`/events/${home.featured_event.slug}`"
              class="rounded-lg bg-red-600 px-5 py-3 text-sm font-black text-white transition hover:bg-red-500"
            >
              View Event Details
            </RouterLink>
            <RouterLink
              :to="`/events/${home.featured_event.slug}/fight-card`"
              class="rounded-lg border border-white/20 bg-white/[0.04] px-5 py-3 text-sm font-black text-white transition hover:border-red-500/60"
            >
              Full Fight Card
            </RouterLink>
          </div>
        </div>

        <div v-if="home.featured_event.main_fight" class="flex items-center">
          <div class="w-full rounded-lg border border-white/10 bg-black/55 p-5 shadow-2xl shadow-black/40 backdrop-blur">
            <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">
              <p class="text-xs font-black uppercase tracking-[0.18em] text-zinc-400">Featured Fight</p>
              <span class="rounded bg-red-600/20 px-2 py-1 text-xs font-black text-red-300">Main Event</span>
            </div>
            <div class="mt-6 grid grid-cols-[1fr_auto_1fr] items-center gap-4">
              <FighterMini :fighter="home.featured_event.main_fight.red_corner" />
              <span class="rounded-lg border border-white/10 bg-white/[0.05] px-3 py-2 text-xl font-black text-red-500">VS</span>
              <FighterMini :fighter="home.featured_event.main_fight.blue_corner" align="right" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(380px,0.6fr)]">
      <section class="rounded-lg border border-white/10 bg-[#070c12] p-4 shadow-xl shadow-black/10">
        <SectionHeader title="Upcoming Events" to="/events" />
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
          <EventCard v-for="event in home.upcoming_events" :key="event.slug" :event="event" />
        </div>
      </section>

      <section class="rounded-lg border border-white/10 bg-[#070c12] p-4 shadow-xl shadow-black/10">
        <SectionHeader title="Latest News" action="View all news" to="/news" />
        <div class="divide-y divide-white/10">
          <article v-for="item in home.news" :key="item.title" class="py-4 first:pt-0 last:pb-0">
            <p class="font-bold text-white">{{ item.title }}</p>
            <p class="mt-1 text-sm text-zinc-500">{{ item.timestamp }}</p>
          </article>
        </div>
      </section>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
      <section class="rounded-lg border border-white/10 bg-[#070c12] p-4 shadow-xl shadow-black/10">
        <SectionHeader title="Rankings" to="/rankings" />
        <div class="overflow-hidden rounded-lg border border-white/10">
          <table class="w-full text-left text-sm">
            <thead class="bg-white/[0.04] text-xs uppercase text-zinc-500">
              <tr>
                <th class="px-4 py-3">#</th>
                <th class="px-4 py-3">Fighter</th>
                <th class="px-4 py-3">Record</th>
                <th class="px-4 py-3 text-right">Points</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
              <tr v-for="ranking in home.rankings" :key="ranking.fighter?.slug" class="text-zinc-200">
                <td class="px-4 py-3 font-black text-zinc-500">{{ ranking.rank }}</td>
                <td class="px-4 py-3">
                  <RouterLink v-if="ranking.fighter" :to="`/fighters/${ranking.fighter.slug}`" class="font-bold text-white hover:text-red-400">
                    {{ ranking.fighter.country?.code }} {{ ranking.fighter.display_name }}
                  </RouterLink>
                </td>
                <td class="px-4 py-3">{{ ranking.fighter?.record }}</td>
                <td class="px-4 py-3 text-right">{{ ranking.points }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="rounded-lg border border-white/10 bg-[#070c12] p-4 shadow-xl shadow-black/10">
        <SectionHeader title="Recent Results" to="/events?status=completed" />
        <div class="space-y-3">
          <RouterLink
            v-for="fight in home.latest_results"
            :key="fight.id"
            :to="fight.event ? `/events/${fight.event.slug}` : '/events'"
            class="grid grid-cols-[auto_1fr_auto] gap-3 rounded-lg border border-white/10 bg-white/[0.035] p-3 transition hover:border-red-500/50"
          >
            <span class="text-sm font-black text-zinc-500">{{ fight.bout_order }}</span>
            <span>
              <span class="block font-bold text-white">{{ fight.red_corner?.display_name }} vs {{ fight.blue_corner?.display_name }}</span>
              <span class="text-sm text-zinc-400">{{ fight.result_notes || fight.result_method?.name || 'Result pending' }}</span>
            </span>
            <span class="text-right text-xs font-bold uppercase text-zinc-500">{{ formatDate(fight.fight_date) }}</span>
          </RouterLink>
        </div>
      </section>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1fr_1fr]">
      <section class="rounded-lg border border-white/10 bg-[#070c12] p-4 shadow-xl shadow-black/10">
        <SectionHeader title="Upcoming Broadcasts" to="/watch" />
        <div class="space-y-3">
          <RouterLink
            v-for="item in home.broadcasts"
            :key="`${item.event.slug}-${item.broadcast.broadcaster}`"
            :to="`/events/${item.event.slug}`"
            class="flex items-center justify-between gap-4 rounded-lg border border-white/10 bg-white/[0.035] p-4 transition hover:border-red-500/50"
          >
            <span>
              <span class="block font-bold text-white">{{ item.event.name }}</span>
              <span class="text-sm text-zinc-400">{{ formatDate(item.event.event_date) }} - {{ item.broadcast.broadcaster }}</span>
            </span>
            <span class="rounded bg-white/[0.06] px-3 py-1 text-xs font-black text-zinc-300">{{ item.broadcast.platform }}</span>
          </RouterLink>
        </div>
      </section>

      <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <StatTile v-for="tile in statTiles" :key="tile.label" :icon="tile.icon" :label="tile.label" :value="tile.value" />
      </section>
    </div>
  </div>

  <EmptyState v-else title="No BoxingDB data yet" message="Run the boxing database seeder to populate the public site." />
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import {
  CalendarDays as CalendarIcon,
  Dumbbell as FighterIcon,
  MapPin as MapPinIcon,
  Radio as RadioIcon,
  Shield as PromoterIcon,
  Swords as FightIcon,
  Trophy as TrophyIcon,
  Map as VenueIcon,
  Search as SearchIcon,
} from '@lucide/vue'
import EmptyState from '@/components/boxing/EmptyState.vue'
import ErrorState from '@/components/boxing/ErrorState.vue'
import EventCard from '@/components/boxing/EventCard.vue'
import LoadingPanel from '@/components/boxing/LoadingPanel.vue'
import SectionHeader from '@/components/boxing/SectionHeader.vue'
import StatTile from '@/components/boxing/StatTile.vue'
import { boxingApi } from '@/services/boxing'
import type { EventSummary, FighterSummary, HomeResponse } from '@/types/boxing'
import { formatDate, formatDateTime, formatNumber } from '@/utils/boxing-format'

const home = ref<HomeResponse | null>(null)
const loading = ref(true)
const error = ref('')
const heroSearch = ref('')
const router = useRouter()

const FighterMini = defineComponent({
  props: {
    fighter: {
      type: Object as () => FighterSummary | null,
      default: null,
    },
    align: {
      type: String,
      default: 'left',
    },
  },
  setup(props) {
    return () => h('div', { class: props.align === 'right' ? 'text-right' : '' }, [
      props.fighter?.photo_url
        ? h('img', {
          src: props.fighter.photo_url,
          alt: props.fighter.display_name,
          class: `mb-3 inline-block h-28 w-28 rounded-lg object-cover ${props.align === 'right' ? 'ml-auto' : ''}`,
        })
        : null,
      h('p', { class: 'text-lg font-black text-white' }, props.fighter?.display_name || 'TBC'),
      h('p', { class: 'text-sm text-zinc-400' }, props.fighter ? `${props.fighter.record} (${props.fighter.knockouts} KO)` : ''),
    ])
  },
})

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

function venueLine(event: EventSummary) {
  if (!event.venue) return 'Venue TBC'
  return [event.venue.name, event.venue.city, event.venue.country].filter(Boolean).join(', ')
}

function submitHeroSearch() {
  router.push({ path: '/search', query: heroSearch.value ? { q: heroSearch.value } : {} })
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
