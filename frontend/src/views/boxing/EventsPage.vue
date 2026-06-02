<template>
  <div class="space-y-4">
    <section class="bd-panel p-4 sm:p-5">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="bd-kicker">Schedule</p>
          <h1 class="bd-page-title">Events</h1>
          <p class="mt-3 max-w-xl text-sm leading-6 text-zinc-400">Filter upcoming fight nights, completed cards, and division-specific events.</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:w-[680px] lg:grid-cols-3">
          <select v-model="filters.status" class="bd-control h-11 text-sm">
            <option value="">All events</option>
            <option value="upcoming">Upcoming</option>
            <option value="completed">Past results</option>
          </select>
          <select v-model="filters.weight_class" class="bd-control h-11 text-sm">
            <option value="">All weights</option>
            <option v-for="weight in data?.filters.weight_classes" :key="weight.slug" :value="weight.slug">{{ weight.name }}</option>
          </select>
          <button class="bd-button-primary h-11 min-h-0 px-4 sm:col-span-2 lg:col-span-1" @click="loadEvents">Apply Filters</button>
        </div>
      </div>
    </section>

    <section v-if="loading" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
      <SkeletonCard v-for="i in 8" :key="i" />
    </section>
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
import SkeletonCard from '@/components/boxing/SkeletonCard.vue'
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
