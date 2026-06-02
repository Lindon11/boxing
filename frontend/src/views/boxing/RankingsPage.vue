<template>
  <div class="space-y-4">
    <section class="bd-panel p-4 sm:p-5">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="bd-kicker">Sanctioning bodies</p>
          <h1 class="bd-page-title">Rankings</h1>
          <p class="mt-3 max-w-xl text-sm leading-6 text-zinc-400">Compare fighters by organisation, division, points and current rank.</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:w-[520px]">
          <select v-model="filters.organisation" class="bd-control h-11 text-sm">
            <option v-for="org in data?.filters.organisations" :key="org.slug" :value="org.slug">{{ org.abbreviation }}</option>
          </select>
          <select v-model="filters.weight_class" class="bd-control h-11 text-sm">
            <option v-for="weight in data?.filters.weight_classes" :key="weight.slug" :value="weight.slug">{{ weight.name }}</option>
          </select>
        </div>
      </div>
    </section>

    <section v-if="loading" class="bd-panel">
      <SkeletonTable :cols="5" :rows="10" />
    </section>
    <ErrorState
      v-else-if="error"
      title="Could not load rankings"
      :message="error"
      :retry="loadRankings"
    />
    <EmptyState v-else-if="!data?.rankings.length" title="No rankings found" message="Try a different organisation or weight class." />

    <section v-else class="bd-panel overflow-x-auto bd-fade-in">
      <table class="bd-table">
        <thead>
          <tr>
            <th class="px-4 py-3">#</th>
            <th class="px-4 py-3">Fighter</th>
            <th class="px-4 py-3">Country</th>
            <th class="px-4 py-3">Record</th>
            <th class="px-4 py-3 text-right">Points</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/10">
          <tr v-for="ranking in data.rankings" :key="`${ranking.organisation.slug}-${ranking.weight_class.slug}-${ranking.rank}`">
            <td class="px-4 py-4 text-lg font-black text-zinc-500">{{ ranking.rank }}</td>
            <td class="px-4 py-4">
              <RouterLink v-if="ranking.fighter" :to="`/fighters/${ranking.fighter.slug}`" class="flex items-center gap-3 font-black text-white hover:text-red-400">
                <img v-if="ranking.fighter.photo_url" :src="ranking.fighter.photo_url" :alt="ranking.fighter.display_name" class="size-10 rounded-lg object-cover">
                {{ ranking.fighter.display_name }}
              </RouterLink>
            </td>
            <td class="px-4 py-4 text-zinc-400">{{ ranking.fighter?.country?.code }}</td>
            <td class="px-4 py-4 text-zinc-300">{{ ranking.fighter?.record }}</td>
            <td class="px-4 py-4 text-right font-black text-white">{{ ranking.points }}</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import EmptyState from '@/components/boxing/EmptyState.vue'
import ErrorState from '@/components/boxing/ErrorState.vue'
import SkeletonTable from '@/components/boxing/SkeletonTable.vue'
import { boxingApi } from '@/services/boxing'
import type { BoxingFilters, RankingSummary } from '@/types/boxing'

interface RankingsResponse {
  rankings: RankingSummary[]
  filters: BoxingFilters
  selected: {
    organisation: string
    weight_class: string
  }
}

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const error = ref('')
const data = ref<RankingsResponse | null>(null)
const filters = reactive({
  organisation: String(route.query.organisation ?? 'wba'),
  weight_class: String(route.query.weight_class ?? 'heavyweight'),
})

async function loadRankings() {
  loading.value = true
  error.value = ''
  router.replace({ query: { organisation: filters.organisation, weight_class: filters.weight_class } })

  try {
    data.value = await boxingApi.rankings(filters)
  } catch {
    error.value = 'Rankings could not be loaded. Try again once the rankings API responds.'
  } finally {
    loading.value = false
  }
}

watch(() => [filters.organisation, filters.weight_class], loadRankings)
onMounted(loadRankings)
</script>
