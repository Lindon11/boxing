<template>
  <div class="space-y-4">
    <section class="rounded-lg border border-white/10 bg-white/[0.025] p-5">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.18em] text-red-400">Boxers</p>
          <h1 class="mt-2 text-3xl font-black text-white">Fighters</h1>
        </div>

        <form class="grid gap-3 md:grid-cols-4 lg:w-[760px]" @submit.prevent="loadFighters">
          <label class="md:col-span-2">
            <span class="sr-only">Search fighters</span>
            <input
              v-model="filters.q"
              class="h-11 w-full rounded-lg border border-white/10 bg-white/[0.04] px-4 text-sm outline-none transition focus:border-red-500"
              placeholder="Search name or alias"
              type="search"
            >
          </label>
          <select v-model="filters.weight_class" class="h-11 rounded-lg border border-white/10 bg-zinc-950 px-3 text-sm outline-none focus:border-red-500">
            <option value="">All weights</option>
            <option v-for="weight in data?.filters.weight_classes" :key="weight.slug" :value="weight.slug">{{ weight.name }}</option>
          </select>
          <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-red-600 px-4 text-sm font-black text-white transition hover:bg-red-500">
            <SearchIcon class="size-4" />
            Search
          </button>
        </form>
      </div>
    </section>

    <LoadingPanel v-if="loading" />
    <ErrorState
      v-else-if="error"
      title="Could not load fighters"
      :message="error"
      :retry="loadFighters"
    />
    <EmptyState v-else-if="!data?.fighters.data.length" title="No fighters found" message="Try a different name, country, stance, or weight class." />

    <section v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
      <FighterCard v-for="fighter in data.fighters.data" :key="fighter.slug" :fighter="fighter" />
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Search as SearchIcon } from '@lucide/vue'
import EmptyState from '@/components/boxing/EmptyState.vue'
import ErrorState from '@/components/boxing/ErrorState.vue'
import FighterCard from '@/components/boxing/FighterCard.vue'
import LoadingPanel from '@/components/boxing/LoadingPanel.vue'
import { boxingApi } from '@/services/boxing'
import type { BoxingFilters, FighterSummary, PaginatedResponse } from '@/types/boxing'

interface FightersResponse {
  fighters: PaginatedResponse<FighterSummary>
  filters: BoxingFilters
}

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const error = ref('')
const data = ref<FightersResponse | null>(null)
const filters = reactive({
  q: String(route.query.q ?? ''),
  weight_class: String(route.query.weight_class ?? ''),
})

async function loadFighters() {
  loading.value = true
  error.value = ''
  router.replace({ query: { ...route.query, q: filters.q || undefined, weight_class: filters.weight_class || undefined } })

  try {
    data.value = await boxingApi.fighters(filters)
  } catch {
    error.value = 'The fighter index could not be reached. The backend may be offline or still migrating.'
  } finally {
    loading.value = false
  }
}

watch(() => filters.weight_class, () => {
  loadFighters()
})

onMounted(loadFighters)
</script>
