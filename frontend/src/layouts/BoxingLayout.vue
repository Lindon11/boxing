<template>
  <div class="bd-shell min-h-screen text-zinc-100">
    <div class="bd-ambient" />

    <header class="sticky top-0 z-40 border-b border-white/[0.08] bg-[#0a0f1a]/92 shadow-lg shadow-black/20 backdrop-blur-2xl">
      <div class="mx-auto flex max-w-[1480px] items-center gap-3 px-4 py-3 lg:px-5">
        <button
          class="bd-focus-ring inline-flex size-10 items-center justify-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-red-500/50 hover:text-red-300 lg:hidden"
          aria-label="Open navigation menu"
          @click="drawerOpen = true"
        >
          <MenuIcon class="size-5" />
        </button>

        <RouterLink to="/" class="bd-focus-ring flex shrink-0 items-center gap-2 rounded-lg">
          <span class="flex size-9 items-center justify-center rounded-lg border border-red-500/30 bg-red-600/15 text-red-300">
            <TrophyIcon class="size-4" />
          </span>
          <span class="text-lg font-black text-white sm:text-xl">Boxing<span class="text-red-500">DB</span></span>
        </RouterLink>

        <div class="hidden h-5 w-px bg-white/10 md:block" />

        <form class="hidden max-w-[360px] flex-1 md:block" @submit.prevent="submitSearch">
          <label class="relative block">
            <SearchIcon class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-500" />
            <input
              v-model="search"
              class="bd-control bd-focus-ring h-10 pl-10 pr-4 text-sm font-semibold"
              placeholder="Search fighters, events, titles..."
              type="search"
            >
          </label>
        </form>

        <nav class="ml-auto hidden items-center gap-0.5 font-bold text-zinc-300 xl:flex">
          <RouterLink
            v-for="item in topNav"
            :key="item.to"
            :to="item.to"
            class="bd-focus-ring rounded-lg px-3 py-2 text-sm transition hover:bg-white/[0.05] hover:text-red-300"
            :class="isActive(item.to) ? 'bg-white/[0.06] text-white' : ''"
            :aria-current="isActive(item.to) ? 'page' : undefined"
          >
            {{ item.label }}
          </RouterLink>
        </nav>

        <RouterLink
          to="/search"
          class="bd-focus-ring ml-auto inline-flex size-10 items-center justify-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-red-500/50 hover:text-red-300 md:hidden"
          aria-label="Open search"
        >
          <SearchIcon class="size-5" />
        </RouterLink>
      </div>
    </header>

    <div class="bd-safe-bottom mx-auto max-w-[1480px] px-4 py-4 sm:px-5 lg:pr-5">
      <aside class="fixed bottom-0 left-0 top-[65px] hidden w-60 overflow-y-auto border-r border-white/[0.08] bg-[#0b111d]/95 backdrop-blur-xl lg:block">
        <div class="flex h-full flex-col justify-between py-4">
          <nav class="flex flex-col gap-1 px-3">
            <p class="mb-2 px-3 text-xs font-black uppercase text-zinc-500">Main</p>
            <RouterLink
              v-for="item in mainNav"
              :key="item.to"
              :to="item.to"
              class="bd-focus-ring relative flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-zinc-400 transition-all hover:bg-white/[0.05] hover:text-zinc-100"
              :class="{ 'text-white': isActive(item.to) }"
              :aria-current="isActive(item.to) ? 'page' : undefined"
            >
              <component :is="item.icon" class="size-4 shrink-0" :class="isActive(item.to) ? 'text-red-400' : 'text-zinc-500' " />
              <span>{{ item.label }}</span>
              <span v-if="isActive(item.to)" class="absolute left-0 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-full bg-red-500" />
            </RouterLink>

            <div class="my-3 border-t border-white/[0.04] pt-3">
              <p class="mb-2 px-3 text-xs font-black uppercase text-zinc-500">Discover</p>
            </div>
            <RouterLink
              v-for="item in discoverNav"
              :key="item.to"
              :to="item.to"
              class="bd-focus-ring relative flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-zinc-400 transition-all hover:bg-white/[0.05] hover:text-zinc-100"
              :class="{ 'text-white': isActive(item.to) }"
              :aria-current="isActive(item.to) ? 'page' : undefined"
            >
              <component :is="item.icon" class="size-4 shrink-0" :class="isActive(item.to) ? 'text-red-400' : 'text-zinc-500' " />
              <span>{{ item.label }}</span>
              <span v-if="isActive(item.to)" class="absolute left-0 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-full bg-red-500" />
            </RouterLink>
          </nav>

          <nav class="px-3">
            <div class="border-t border-white/[0.04] pt-2">
              <RouterLink
              v-for="item in bottomNav"
              :key="item.to"
              :to="item.to"
                class="bd-focus-ring relative flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-zinc-500 transition-all hover:bg-white/[0.05] hover:text-zinc-100"
                :class="{ 'text-white': isActive(item.to) }"
                :aria-current="isActive(item.to) ? 'page' : undefined"
              >
                <component :is="item.icon" class="size-4 shrink-0" :class="isActive(item.to) ? 'text-red-400' : 'text-zinc-500' " />
                <span>{{ item.label }}</span>
                <span v-if="isActive(item.to)" class="absolute left-0 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-full bg-red-500" />
              </RouterLink>
            </div>
          </nav>
        </div>
      </aside>

      <main class="min-w-0 lg:ml-64">
        <RouterView v-slot="{ Component }">
          <Transition name="page" mode="out-in">
            <component :is="Component" />
          </Transition>
        </RouterView>
      </main>
    </div>

    <nav class="fixed inset-x-3 bottom-2 z-40 rounded-lg border border-white/10 bg-[#0a0f1a]/95 px-2 py-1.5 shadow-2xl shadow-black/45 backdrop-blur-2xl lg:hidden">
      <div class="mx-auto grid max-w-md grid-cols-5 gap-1">
        <RouterLink
          v-for="item in mainNav"
          :key="item.to"
          :to="item.to"
          class="bd-focus-ring flex min-h-11 flex-col items-center justify-center gap-0.5 rounded-lg px-1 text-[10px] font-bold text-zinc-500 transition hover:bg-white/[0.05] hover:text-zinc-100"
          :class="isActive(item.to) ? 'bg-white/[0.06] text-white' : ''"
          :aria-label="item.label"
          :aria-current="isActive(item.to) ? 'page' : undefined"
        >
          <component :is="item.icon" class="size-4" :class="isActive(item.to) ? 'text-red-400' : 'text-zinc-500'" />
          <span class="truncate">{{ item.label }}</span>
        </RouterLink>
      </div>
    </nav>

    <Transition name="fade">
      <button
        v-if="showBackToTop"
        class="bd-focus-ring fixed bottom-24 right-5 z-50 flex size-11 items-center justify-center rounded-lg border border-white/10 bg-gradient-to-br from-red-600 to-red-800 text-white shadow-2xl shadow-black/50 transition hover:from-red-500 hover:to-red-700 lg:bottom-6"
        aria-label="Back to top"
        @click="scrollToTop"
      >
        <ArrowUpIcon class="size-5" />
      </button>
    </Transition>

    <Transition name="drawer">
      <div v-if="drawerOpen" class="fixed inset-0 z-50 lg:hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="drawerOpen = false" />
        <aside class="relative h-full w-80 max-w-[88vw] overflow-y-auto border-r border-white/10 bg-[#080d16] p-5 shadow-2xl shadow-black/60">
          <div class="mb-6 flex items-center justify-between">
            <span class="flex items-center gap-2 text-lg font-black text-white">
              <span class="flex size-9 items-center justify-center rounded-lg border border-red-500/30 bg-red-600/15 text-red-300">
                <TrophyIcon class="size-4" />
              </span>
              Boxing<span class="text-red-500">DB</span>
            </span>
            <button
              class="bd-focus-ring inline-flex size-9 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-white/[0.06] hover:text-white"
              aria-label="Close navigation menu"
              @click="drawerOpen = false"
            >
              <XIcon class="size-4" />
            </button>
          </div>
          <nav class="flex flex-col gap-1">
            <p class="mb-2 px-3 text-xs font-black uppercase text-zinc-500">Main</p>
            <RouterLink
              v-for="item in mainNav"
              :key="item.to"
              :to="item.to"
              class="bd-focus-ring relative flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-zinc-400 transition-all hover:bg-white/[0.05] hover:text-zinc-100"
              :class="{ 'text-white': isActive(item.to) }"
              :aria-current="isActive(item.to) ? 'page' : undefined"
              @click="drawerOpen = false"
            >
              <component :is="item.icon" class="size-4 shrink-0" :class="isActive(item.to) ? 'text-red-400' : 'text-zinc-500'" />
              <span>{{ item.label }}</span>
              <span v-if="isActive(item.to)" class="absolute left-0 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-full bg-red-500" />
            </RouterLink>

            <div class="my-3 border-t border-white/[0.04] pt-3">
              <p class="mb-2 px-3 text-xs font-black uppercase text-zinc-500">Discover</p>
            </div>
            <RouterLink
              v-for="item in discoverNav"
              :key="item.to"
              :to="item.to"
              class="bd-focus-ring relative flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-zinc-400 transition-all hover:bg-white/[0.05] hover:text-zinc-100"
              :class="{ 'text-white': isActive(item.to) }"
              :aria-current="isActive(item.to) ? 'page' : undefined"
              @click="drawerOpen = false"
            >
              <component :is="item.icon" class="size-4 shrink-0" :class="isActive(item.to) ? 'text-red-400' : 'text-zinc-500'" />
              <span>{{ item.label }}</span>
              <span v-if="isActive(item.to)" class="absolute left-0 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-full bg-red-500" />
            </RouterLink>

            <div class="my-3 border-t border-white/[0.04] pt-3">
              <p class="mb-2 px-3 text-xs font-black uppercase text-zinc-500">Utility</p>
            </div>
            <RouterLink
              v-for="item in bottomNav"
              :key="item.to"
              :to="item.to"
              class="bd-focus-ring relative flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-zinc-500 transition-all hover:bg-white/[0.05] hover:text-zinc-100"
              :class="{ 'text-white': isActive(item.to) }"
              :aria-current="isActive(item.to) ? 'page' : undefined"
              @click="drawerOpen = false"
            >
              <component :is="item.icon" class="size-4 shrink-0" :class="isActive(item.to) ? 'text-red-400' : 'text-zinc-500'" />
              <span>{{ item.label }}</span>
              <span v-if="isActive(item.to)" class="absolute left-0 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-full bg-red-500" />
            </RouterLink>
          </nav>
          <div class="mt-6 border-t border-white/10 pt-6">
            <form @submit.prevent="mobileSearch">
              <label class="relative block">
                <SearchIcon class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-500" />
                <input
                  v-model="mobileQuery"
                  class="bd-control bd-focus-ring h-11 pl-10 pr-4 text-sm"
                  placeholder="Search BoxingDB..."
                  type="search"
                >
              </label>
            </form>
          </div>
        </aside>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import {
  ArrowUp as ArrowUpIcon,
  CalendarDays as CalendarIcon,
  Dumbbell as FighterIcon,
  Home as HomeIcon,
  ListOrdered as RankingIcon,
  MapPin as VenueIcon,
  Menu as MenuIcon,
  Newspaper as NewsIcon,
  Radio as BroadcastIcon,
  Search as SearchIcon,
  Shield as PromotionIcon,
  Trophy as TrophyIcon,
  X as XIcon,
} from '@lucide/vue'

const route = useRoute()
const router = useRouter()
const search = ref(String(route.query.q ?? ''))
const mobileQuery = ref('')
const drawerOpen = ref(false)
const showBackToTop = ref(false)

const topNav = [
  { label: 'Fighters', to: '/fighters' },
  { label: 'Events', to: '/events' },
  { label: 'Rankings', to: '/rankings' },
  { label: 'Titles', to: '/titles' },
]

const mainNav = [
  { label: 'Home', to: '/', icon: HomeIcon },
  { label: 'Fighters', to: '/fighters', icon: FighterIcon },
  { label: 'Events', to: '/events', icon: CalendarIcon },
  { label: 'Rankings', to: '/rankings', icon: RankingIcon },
  { label: 'Titles', to: '/titles', icon: TrophyIcon },
]

const discoverNav = [
  { label: 'Promotions', to: '/promotions', icon: PromotionIcon },
  { label: 'News', to: '/news', icon: NewsIcon },
  { label: 'Venues', to: '/venues', icon: VenueIcon },
  { label: 'Broadcasts', to: '/watch', icon: BroadcastIcon },
]

const bottomNav = [
  { label: 'Results', to: '/events?status=completed', icon: CalendarIcon },
  { label: 'Search', to: '/search', icon: SearchIcon },
]

const activePath = computed(() => route.path)
const activeFullPath = computed(() => route.fullPath)
const activeQuery = computed(() => route.query)

function isActive(path: string) {
  if (path.includes('?')) {
    const base = path.split('?')[0] ?? ''
    return activeFullPath.value.startsWith(base) && activeQuery.value?.status === 'completed'
  }
  const cleanPath = path.split('?')[0] ?? ''
  if (cleanPath === '/') return activePath.value === '/'
  return activePath.value.startsWith(cleanPath)
}

function submitSearch() {
  router.push({ path: '/search', query: search.value ? { q: search.value } : {} })
}

function mobileSearch() {
  router.push({ path: '/search', query: mobileQuery.value ? { q: mobileQuery.value } : {} })
  drawerOpen.value = false
}

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

let scrollCleanup: (() => void) | null = null

onMounted(() => {
  const handler = () => { showBackToTop.value = window.scrollY > 400 }
  window.addEventListener('scroll', handler, { passive: true })
  scrollCleanup = () => window.removeEventListener('scroll', handler)
})

onUnmounted(() => { scrollCleanup?.() })
</script>

<style scoped>
.page-enter-active { transition: opacity 0.2s ease, transform 0.25s cubic-bezier(0.22, 1, 0.36, 1); }
.page-leave-active { transition: opacity 0.12s ease, transform 0.15s ease; }
.page-enter-from { opacity: 0; transform: translateY(12px); }
.page-leave-to { opacity: 0; transform: translateY(-6px); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.drawer-enter-active, .drawer-leave-active { transition: opacity 0.25s ease; }
.drawer-enter-active aside, .drawer-leave-active aside { transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1); }
.drawer-enter-from, .drawer-leave-to { opacity: 0; }
.drawer-enter-from aside, .drawer-leave-to aside { transform: translateX(-100%); }
</style>
