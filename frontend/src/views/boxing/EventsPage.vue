<template>
  <div class="space-y-4">
    <section class="rounded-lg border border-white/10 bg-white/[0.025] p-5">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.18em] text-red-400">Schedule</p>
          <h1 class="mt-2 text-3xl font-black text-white">Events</h1>
        </div>

        <div class="grid gap-3 md:grid-cols-3 lg:w-[680px]">
          <select v-model="filters.status" class="h-11 rounded-lg border border-white/10 bg-zinc-950 px-3 text-sm outline-none focus:border-red-500">
            <option value="">All events</option>
            <option value="upcoming">Upcoming</option>
            <option value="completed">Past results</option>
          </select>
          <select v-model="filters.weight_class" class="h-11 rounded-lg border border-white/10 bg-zinc-950 px-3 text-sm outline-none focus:border-red-500">
            <option value="">All weights</option>
            <option v-for="weight in data?.filters.weight_classes" :key="weight.slug" :value="weight.slug">{{ weight.name }}</option>
          </select>
          <button class="rounded-lg bg-red-600 px-4 text-sm font-black text-white transition hover:bg-red-500" @click="loadEvents">Apply Filters</button>
        </div>
      </div>
    </section>

    <LoadingPanel v-if="loading" />
    <ErrorState
      v-else-if="error"
      title="Could not load events"
      :message="error"
      :retry="loadEvents"
    />
    <EmptyState v-else-if="!data?.events.data.length" title="No events found" message="Try a different date, status, promoter, venue, or weight class." />

    <section v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
      <EventCard v-for="event in data.events.data" :key="event.slug" :event="event" />
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import EmptyState from '@/components/boxing/EmptyState.vue'
import ErrorState from '@/components/boxing/ErrorState.vue'
import EventCard from '@/components/boxing/EventCard.vue'
import LoadingPanel from '@/components/boxing/LoadingPanel.vue'
import { boxingApi } from '@/services/boxing'
import type { BoxingFilters, EventSummary, PaginatedResponse } from '@/types/boxing'

interface EventsResponse {
  events: PaginatedResponse<EventSummary>
  filters: BoxingFilters
}

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const error = ref('')
const data = ref<EventsResponse | null>(null)
const filters = reactive({
  status: String(route.query.status ?? ''),
  weight_class: String(route.query.weight_class ?? ''),
})

async function loadEvents() {
  loading.value = true
  error.value = ''
  router.replace({ query: { status: filters.status || undefined, weight_class: filters.weight_class || undefined } })

  try {
    data.value = await boxingApi.events(filters)
  } catch {
    error.value = 'The event schedule could not be loaded. Check the API and try again.'
  } finally {
    loading.value = false
  }
}

watch(() => [filters.status, filters.weight_class], loadEvents)
onMounted(loadEvents)
</script>
