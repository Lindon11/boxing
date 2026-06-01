<template>
  <div class="space-y-4">
    <section class="rounded-lg border border-white/10 bg-white/[0.025] p-5">
      <form class="flex flex-col gap-3 md:flex-row" @submit.prevent="submitSearch">
        <label class="relative flex-1">
          <SearchIcon class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-zinc-500" />
          <input
            v-model="query"
            class="h-12 w-full rounded-lg border border-white/10 bg-white/[0.04] pl-11 pr-4 text-sm outline-none transition focus:border-red-500"
            placeholder="Search BoxingDB"
            type="search"
          >
        </label>
        <button class="inline-flex h-12 items-center justify-center gap-2 rounded-lg bg-red-600 px-6 text-sm font-black text-white transition hover:bg-red-500">
          <SearchIcon class="size-4" />
          Search
        </button>
      </form>
    </section>

    <LoadingPanel v-if="loading" />
    <ErrorState
      v-else-if="error"
      title="Search failed"
      :message="error"
      :retry="submitSearch"
    />

    <div v-else-if="results" class="space-y-4">
      <section v-if="results.fighters.length" class="rounded-lg border border-white/10 bg-white/[0.025] p-4">
        <SectionHeader title="Fighters" action="View all fighters" to="/fighters" />
        <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-4">
          <FighterCard v-for="fighter in results.fighters" :key="fighter.slug" :fighter="fighter" />
        </div>
      </section>

      <section v-if="results.events.length" class="rounded-lg border border-white/10 bg-white/[0.025] p-4">
        <SectionHeader title="Events" to="/events" />
        <div class="space-y-3">
          <RouterLink
            v-for="event in results.events"
            :key="event.slug"
            :to="`/events/${event.slug}`"
            class="flex items-center gap-4 rounded-lg border border-white/10 bg-white/[0.035] p-3 transition hover:border-red-500/50"
          >
            <img v-if="event.poster_url" :src="event.poster_url" :alt="event.name" class="h-20 w-28 rounded-lg object-cover">
            <span>
              <span class="block font-black text-white">{{ event.name }}</span>
              <span class="text-sm text-zinc-400">{{ formatDate(event.event_date) }} - {{ event.venue?.name }}</span>
            </span>
          </RouterLink>
        </div>
      </section>

      <section v-if="results.titles.length" class="rounded-lg border border-white/10 bg-white/[0.025] p-4">
        <SectionHeader title="Titles" to="/titles" />
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <RouterLink
            v-for="title in results.titles"
            :key="title.slug"
            :to="title.champion ? `/fighters/${title.champion.slug}` : '/titles'"
            class="rounded-lg border border-white/10 bg-white/[0.035] p-4 transition hover:border-red-500/50"
          >
            <p class="font-black text-white">{{ title.name }}</p>
            <p class="mt-1 text-sm text-zinc-400">{{ title.champion?.display_name || 'Vacant' }}</p>
          </RouterLink>
        </div>
      </section>

      <EmptyState
        v-if="!hasResults"
        title="No results found"
        message="Try a fighter surname, event name, promoter, venue, or title organisation."
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { Search as SearchIcon } from '@lucide/vue'
import EmptyState from '@/components/boxing/EmptyState.vue'
import ErrorState from '@/components/boxing/ErrorState.vue'
import FighterCard from '@/components/boxing/FighterCard.vue'
import LoadingPanel from '@/components/boxing/LoadingPanel.vue'
import SectionHeader from '@/components/boxing/SectionHeader.vue'
import { boxingApi } from '@/services/boxing'
import type { SearchResponse } from '@/types/boxing'
import { formatDate } from '@/utils/boxing-format'

const route = useRoute()
const router = useRouter()
const query = ref(String(route.query.q ?? ''))
const loading = ref(false)
const error = ref('')
const results = ref<SearchResponse | null>(null)

const hasResults = computed(() => {
  if (!results.value) return false
  return results.value.fighters.length > 0 || results.value.events.length > 0 || results.value.titles.length > 0
})

async function runSearch(updateRoute = true) {
  error.value = ''

  if (updateRoute) {
    router.replace({ query: query.value ? { q: query.value } : {} })
  }

  if (!query.value.trim()) {
    results.value = null
    return
  }

  loading.value = true

  try {
    results.value = await boxingApi.search(query.value)
  } catch {
    error.value = 'Search is unavailable right now. Try again once the API is reachable.'
  } finally {
    loading.value = false
  }
}

function submitSearch() {
  runSearch(true)
}

watch(() => route.query.q, () => {
  query.value = String(route.query.q ?? '')
  runSearch(false)
})

onMounted(runSearch)
</script>
