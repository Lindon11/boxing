<template>
  <div class="min-h-screen bg-[#05070a] text-zinc-100">
    <header class="sticky top-0 z-40 border-b border-white/10 bg-[#070b10]/95 shadow-2xl shadow-black/30 backdrop-blur">
      <div class="flex min-h-20 items-center gap-4 px-4 lg:px-7">
        <RouterLink to="/" class="flex shrink-0 items-center gap-1 text-2xl font-black tracking-tight">
          <span>Boxing</span><span class="text-red-500">DB</span>
        </RouterLink>

        <form class="hidden max-w-2xl flex-1 md:block" @submit.prevent="submitSearch">
          <label class="relative block">
            <SearchIcon class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-zinc-500" />
            <input
              v-model="search"
              class="h-12 w-full rounded-lg border border-white/10 bg-[#0d1218] pl-11 pr-4 text-sm font-semibold text-zinc-100 shadow-inner outline-none transition placeholder:text-zinc-500 focus:border-red-500/70 focus:bg-[#111820]"
              placeholder="Search fighters, events, promoters..."
              type="search"
            >
          </label>
        </form>

        <nav class="ml-auto hidden items-center gap-6 text-sm font-bold text-zinc-200 xl:flex">
          <RouterLink v-for="item in topNav" :key="item.to" :to="item.to" class="relative py-2 transition hover:text-red-400">
            {{ item.label }}
          </RouterLink>
        </nav>

        <RouterLink
          to="/search"
          class="inline-flex size-10 items-center justify-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-red-500/50 hover:text-red-400 md:hidden"
          aria-label="Open search"
        >
          <SearchIcon class="size-4" />
        </RouterLink>
      </div>

      <nav class="flex gap-1 overflow-x-auto border-t border-white/10 px-3 py-2 lg:hidden">
        <RouterLink
          v-for="item in sideNav"
          :key="item.to"
          :to="item.to"
          class="inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-zinc-300"
          :class="isActive(item.to) ? 'bg-red-600 text-white' : 'bg-white/[0.035]'"
        >
          <component :is="item.icon" class="size-4" />
          {{ item.label }}
        </RouterLink>
      </nav>
    </header>

    <div class="mx-auto grid max-w-[1680px] grid-cols-1 gap-4 px-3 py-4 lg:grid-cols-[280px_minmax(0,1fr)] lg:px-5">
      <aside class="hidden lg:block">
        <div class="sticky top-24 space-y-4">
          <nav class="rounded-lg border border-white/10 bg-[#080d13]/90 p-3 shadow-2xl shadow-black/20">
            <RouterLink
              v-for="item in sideNav"
              :key="item.to"
              :to="item.to"
              class="mb-1 flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-bold text-zinc-300 transition hover:bg-white/[0.06] hover:text-white"
              :class="{ 'bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg shadow-red-950/30': isActive(item.to) }"
            >
              <component :is="item.icon" class="size-4" />
              <span>{{ item.label }}</span>
            </RouterLink>
          </nav>

          <section class="overflow-hidden rounded-lg border border-red-500/40 bg-gradient-to-br from-red-950/50 via-[#12090b] to-white/[0.03] p-4">
            <p class="text-sm font-black text-white">BoxingDB API</p>
            <p class="mt-2 text-xs leading-5 text-zinc-400">Power your app with structured boxing data for fighters, fights, titles, and events.</p>
            <a class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-sm font-bold text-white transition hover:bg-red-500" href="#">
              <CodeIcon class="size-4" />
              API Plans
            </a>
          </section>
        </div>
      </aside>

      <main class="min-w-0">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import {
  CalendarDays as CalendarIcon,
  Code as CodeIcon,
  Dumbbell as FighterIcon,
  Home as HomeIcon,
  MapPin as VenueIcon,
  Radio as BroadcastIcon,
  Search as SearchIcon,
  Shield as PromotionIcon,
  Trophy as TrophyIcon,
  Newspaper as NewsIcon,
  ListOrdered as RankingIcon,
} from '@lucide/vue'

const route = useRoute()
const router = useRouter()
const search = ref(String(route.query.q ?? ''))

const topNav = [
  { label: 'Fighters', to: '/fighters' },
  { label: 'Events', to: '/events' },
  { label: 'Rankings', to: '/rankings' },
  { label: 'Titles', to: '/titles' },
  { label: 'News', to: '/news' },
  { label: 'Watch', to: '/watch' },
  { label: 'API', to: '/api-access' },
]

const sideNav = [
  { label: 'Home', to: '/', icon: HomeIcon },
  { label: 'Fighters', to: '/fighters', icon: FighterIcon },
  { label: 'Events', to: '/events', icon: CalendarIcon },
  { label: 'Rankings', to: '/rankings', icon: RankingIcon },
  { label: 'Titles', to: '/titles', icon: TrophyIcon },
  { label: 'Promotions', to: '/promotions', icon: PromotionIcon },
  { label: 'News', to: '/news', icon: NewsIcon },
  { label: 'Venues', to: '/venues', icon: VenueIcon },
  { label: 'Broadcasts', to: '/watch', icon: BroadcastIcon },
  { label: 'Search', to: '/search', icon: SearchIcon },
]

const activePath = computed(() => route.path)

function isActive(path: string) {
  if (path === '/') {
    return activePath.value === '/'
  }

  return activePath.value.startsWith(path)
}

function submitSearch() {
  router.push({ path: '/search', query: search.value ? { q: search.value } : {} })
}
</script>
