<template>
  <LoadingPanel v-if="loading" />
  <ErrorState
    v-else-if="error"
    title="Could not load fight card"
    :message="error"
    :retry="loadFightCard"
  />
  <EmptyState v-else-if="!event" title="Fight card not found" />

  <div v-else class="space-y-4">
    <section class="rounded-lg border border-white/10 bg-white/[0.025] p-5">
      <RouterLink :to="`/events/${event.slug}`" class="text-sm font-bold text-red-400 hover:text-red-300">Back to event</RouterLink>
      <h1 class="mt-3 text-3xl font-black text-white">Fight Card</h1>
      <p class="mt-1 text-zinc-400">{{ event.name }} - {{ formatDate(event.event_date) }} - {{ event.venue?.name }}</p>
    </section>

    <section class="space-y-3">
      <FightRow v-for="fight in fights" :key="fight.id" :fight="fight" />
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import EmptyState from '@/components/boxing/EmptyState.vue'
import ErrorState from '@/components/boxing/ErrorState.vue'
import FightRow from '@/components/boxing/FightRow.vue'
import LoadingPanel from '@/components/boxing/LoadingPanel.vue'
import { boxingApi } from '@/services/boxing'
import type { EventSummary, FightSummary } from '@/types/boxing'
import { formatDate } from '@/utils/boxing-format'

const route = useRoute()
const loading = ref(true)
const error = ref('')
const event = ref<EventSummary | null>(null)
const fights = ref<FightSummary[]>([])

async function loadFightCard() {
  loading.value = true
  error.value = ''

  try {
    const response = await boxingApi.fightCard(String(route.params.slug))
    event.value = response.event
    fights.value = response.fights
  } catch {
    error.value = 'The fight card could not be loaded. Try again when the API is back.'
  } finally {
    loading.value = false
  }
}

watch(() => route.params.slug, loadFightCard)
onMounted(loadFightCard)
</script>
