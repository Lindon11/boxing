<template>
  <section v-if="loading" class="bd-panel p-4">
    <div class="space-y-3">
      <div v-for="i in 6" :key="i" class="animate-pulse h-24 rounded-lg bg-zinc-800" />
    </div>
  </section>
  <ErrorState
    v-else-if="error"
    title="Could not load fight card"
    :message="error"
    :retry="loadFightCard"
  />
  <EmptyState v-else-if="!event" title="Fight card not found" />

  <div v-else class="space-y-4">
    <nav class="flex items-center gap-2 text-sm text-zinc-500">
      <RouterLink to="/events" class="font-semibold text-zinc-400 transition hover:text-red-400">Events</RouterLink>
      <span class="text-zinc-600">/</span>
      <RouterLink :to="`/events/${event.slug}`" class="font-semibold text-zinc-400 transition hover:text-red-400 truncate max-w-[200px]">{{ event.name }}</RouterLink>
      <span class="text-zinc-600">/</span>
      <span class="font-semibold text-white">Fight Card</span>
    </nav>

    <section class="bd-panel p-5">
      <h1 class="bd-page-title">Fight Card</h1>
      <p class="mt-1 text-zinc-400">{{ event.name }} - {{ formatDate(event.event_date) }} - {{ event.venue?.name }}</p>
      <nav class="mt-5 flex gap-2 border-b border-white/10">
        <button class="border-b-2 border-red-500 px-4 py-3 text-sm font-black text-white" type="button">Main Card</button>
        <button class="px-4 py-3 text-sm font-bold text-zinc-500" type="button">Undercard</button>
      </nav>
    </section>

    <section class="bd-panel p-4">
      <div class="space-y-3">
        <FightRow v-for="fight in fights" :key="fight.id" :fight="fight" />
      </div>
      <p class="mt-4 text-xs text-zinc-500">* Fight card subject to change</p>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import EmptyState from '@/components/boxing/EmptyState.vue'
import ErrorState from '@/components/boxing/ErrorState.vue'
import FightRow from '@/components/boxing/FightRow.vue'
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
