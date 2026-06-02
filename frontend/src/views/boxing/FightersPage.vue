<template>
  <div class="space-y-4">
    <section class="bd-panel p-4 sm:p-5">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="bd-kicker">Boxers</p>
          <h1 class="bd-page-title">Fighters</h1>
          <p class="mt-3 max-w-xl text-sm leading-6 text-zinc-400">Search active profiles by name, alias, country, stance, and division.</p>
        </div>

        <form class="grid gap-3 sm:grid-cols-2 lg:w-[760px] lg:grid-cols-4" @submit.prevent="loadFighters">
          <label class="sm:col-span-2">
            <span class="sr-only">Search fighters</span>
            <input
              v-model="filters.q"
              class="bd-control h-11 text-sm"
              placeholder="Search name or alias"
              type="search"
            >
          </label>
          <select v-model="filters.weight_class" class="bd-control h-11 text-sm">
            <option value="">All weights</option>
            <option v-for="weight in data?.filters.weight_classes" :key="weight.slug" :value="weight.slug">{{ weight.name }}</option>
          </select>
          <button class="bd-button-primary h-11 min-h-0">
            <SearchIcon class="size-4" />
            Search
          </button>
        </form>
      </div>
    </section>

    <SkeletonFighterGrid v-if="loading" />
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
import SkeletonFighterGrid from '@/components/boxing/SkeletonFighterGrid.vue'
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
