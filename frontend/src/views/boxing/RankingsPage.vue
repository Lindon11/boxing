<template>
  <div class="space-y-4">
    <section class="rounded-lg border border-white/10 bg-white/[0.025] p-5">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.18em] text-red-400">Sanctioning bodies</p>
          <h1 class="mt-2 text-3xl font-black text-white">Rankings</h1>
        </div>

        <div class="grid gap-3 md:grid-cols-2 lg:w-[520px]">
          <select v-model="filters.organisation" class="h-11 rounded-lg border border-white/10 bg-zinc-950 px-3 text-sm outline-none focus:border-red-500">
            <option v-for="org in data?.filters.organisations" :key="org.slug" :value="org.slug">{{ org.abbreviation }}</option>
          </select>
          <select v-model="filters.weight_class" class="h-11 rounded-lg border border-white/10 bg-zinc-950 px-3 text-sm outline-none focus:border-red-500">
            <option v-for="weight in data?.filters.weight_classes" :key="weight.slug" :value="weight.slug">{{ weight.name }}</option>
          </select>
        </div>
      </div>
    </section>

    <LoadingPanel v-if="loading" />
    <ErrorState
      v-else-if="error"
      title="Could not load rankings"
      :message="error"
      :retry="loadRankings"
    />
    <EmptyState v-else-if="!data?.rankings.length" title="No rankings found" message="Try a different organisation or weight class." />

    <section v-else class="overflow-hidden rounded-lg border border-white/10 bg-white/[0.025]">
      <table class="w-full text-left text-sm">
        <thead class="bg-white/[0.04] text-xs uppercase text-zinc-500">
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
import LoadingPanel from '@/components/boxing/LoadingPanel.vue'
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
