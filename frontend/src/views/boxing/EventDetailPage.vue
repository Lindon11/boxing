<template>
  <div v-if="loading" class="space-y-4">
    <section class="animate-pulse relative overflow-hidden rounded-lg border border-white/10 bg-zinc-950 shadow-2xl shadow-black/30">
      <div class="relative min-h-[360px] p-5 xl:p-8">
        <div class="flex max-w-2xl flex-col justify-center">
          <div class="flex gap-2">
            <div v-for="i in 2" :key="i" class="h-6 w-20 rounded bg-zinc-700" />
          </div>
          <div class="mt-4 h-12 w-3/4 rounded bg-zinc-700" />
          <div class="mt-3 h-5 w-1/2 rounded bg-zinc-700" />
          <div class="mt-6 space-y-2">
            <div v-for="i in 3" :key="i" class="h-4 w-64 rounded bg-zinc-700" />
          </div>
          <div class="mt-7 flex gap-3">
            <div class="h-11 w-40 rounded-lg bg-zinc-700" />
            <div class="h-11 w-28 rounded-lg bg-zinc-700" />
          </div>
          <div class="mt-7 grid max-w-md grid-cols-4 overflow-hidden rounded-lg border border-white/10 bg-black/40">
            <div v-for="i in 4" :key="i" class="border-r border-white/10 p-3 text-center last:border-r-0">
              <div class="mx-auto h-7 w-10 rounded bg-zinc-700" />
              <div class="mx-auto mt-2 h-3 w-8 rounded bg-zinc-700" />
            </div>
          </div>
        </div>
      </div>
    </section>
    <div class="grid gap-4 xl:grid-cols-3">
      <div v-for="i in 3" :key="i" class="bd-panel p-5 animate-pulse">
        <div class="h-5 w-32 rounded bg-zinc-700" />
        <div class="mt-4 space-y-3">
          <div v-for="j in 4" :key="j" class="h-4 w-full rounded bg-zinc-700" />
        </div>
      </div>
    </div>
  </div>
  <ErrorState
    v-else-if="error"
    title="Could not load event"
    :message="error"
    :retry="loadEvent"
  />
  <EmptyState v-else-if="!event" title="Event not found" />

  <div v-else class="space-y-4">
    <nav class="flex items-center gap-2 text-sm text-zinc-500">
      <RouterLink to="/events" class="font-semibold text-zinc-400 transition hover:text-red-400">Events</RouterLink>
      <span class="text-zinc-600">/</span>
      <span class="font-semibold text-white truncate max-w-[300px]">{{ event.name }}</span>
    </nav>

    <section class="relative overflow-hidden rounded-lg border border-white/10 bg-zinc-950 shadow-2xl shadow-black/30">
      <img
        v-if="event.hero_image_url"
        :src="event.hero_image_url"
        :alt="event.name"
        class="absolute inset-0 h-full w-full object-cover opacity-45"
      >
      <div class="absolute inset-0 bg-gradient-to-r from-black via-black/85 to-black/30" />
      <div class="relative min-h-[360px] p-5 xl:p-8">
        <div class="flex max-w-2xl flex-col justify-center">
          <div class="flex flex-wrap items-center gap-2">
            <span class="bd-chip bd-chip-red">{{ event.status }}</span>
            <span class="bd-chip">{{ event.promoter?.name || 'Promoter TBC' }}</span>
          </div>
          <h1 class="mt-4 max-w-4xl text-4xl font-black leading-none text-white md:text-5xl">{{ event.name }}</h1>
          <p class="mt-2 text-lg text-zinc-300">{{ event.subtitle }}</p>
          <div class="mt-6 grid gap-2 text-sm font-semibold text-zinc-200">
            <p class="flex items-center gap-2"><CalendarIcon class="size-4 text-red-400" /> {{ formatDateTime(event.event_date) }}</p>
            <p class="flex items-center gap-2"><MapPinIcon class="size-4 text-red-400" /> {{ venueLine }}</p>
            <p class="flex items-center gap-2"><RadioIcon class="size-4 text-red-400" /> {{ event.broadcast_notes || 'Broadcast TBC' }}</p>
          </div>
          <div class="mt-7 flex flex-wrap gap-3">
            <RouterLink :to="`/events/${event.slug}/fight-card`" class="bd-button-primary">
              View Fight Card
            </RouterLink>
            <a v-if="event.ticket_url" :href="event.ticket_url" class="bd-button-secondary">
              Tickets
            </a>
          </div>
          <div v-if="event.status === 'upcoming'" class="mt-7 grid max-w-md grid-cols-4 overflow-hidden rounded-lg border border-white/10 bg-black/40 backdrop-blur">
            <div v-for="tile in countdownTiles" :key="tile.label" class="border-r border-white/10 p-3 text-center last:border-r-0">
              <p class="text-2xl font-black text-white">{{ tile.value }}</p>
              <p class="text-[10px] font-black uppercase text-zinc-500">{{ tile.label }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <nav class="flex gap-2 overflow-x-auto border-b border-white/10 bg-[#070c12] px-2">
      <RouterLink class="border-b-2 border-red-500 px-4 py-3 text-sm font-black text-white" :to="`/events/${event.slug}`">Overview</RouterLink>
      <RouterLink class="px-4 py-3 text-sm font-bold text-zinc-400 hover:text-white" :to="`/events/${event.slug}/fight-card`">Fight Card</RouterLink>
      <span class="px-4 py-3 text-sm font-bold text-zinc-500">News</span>
      <span class="px-4 py-3 text-sm font-bold text-zinc-500">Broadcasts</span>
      <span class="px-4 py-3 text-sm font-bold text-zinc-500">Venue</span>
      <span class="px-4 py-3 text-sm font-bold text-zinc-500">Tickets</span>
    </nav>

    <div class="grid gap-4 xl:grid-cols-3">
      <section class="bd-panel p-5">
        <h2 class="font-black text-white">About the Event</h2>
        <p class="mt-3 text-sm leading-6 text-zinc-300">
          {{ event.subtitle || 'Full fight-night details, broadcast information, venue data, and results will appear here as the event record is updated.' }}
        </p>
        <dl class="mt-5 space-y-3 text-sm">
          <DetailRow label="Promoter" :value="event.promoter?.name || 'TBC'" />
          <DetailRow label="Main card" :value="formatDateTime(event.ring_walks_at)" />
          <DetailRow label="Bouts" :value="String(event.fights.length)" />
        </dl>
      </section>

      <section class="bd-panel p-5">
        <h2 class="font-black text-white">Event Details</h2>
        <dl class="mt-4 space-y-3 text-sm">
          <DetailRow label="Date" :value="formatDate(event.event_date)" />
          <DetailRow label="Venue" :value="event.venue?.name || 'TBC'" />
          <DetailRow label="Location" :value="venueLine" />
          <DetailRow label="Broadcast" :value="event.broadcast_notes || 'TBC'" />
          <DetailRow label="Ring walks" :value="formatDateTime(event.ring_walks_at)" />
        </dl>
      </section>

      <section class="bd-panel p-5">
        <h2 class="font-black text-white">Where To Watch</h2>
        <div class="mt-4 space-y-3">
          <a
            v-for="broadcast in event.broadcasts"
            :key="`${broadcast.region}-${broadcast.broadcaster.slug}`"
            :href="broadcast.broadcaster.website_url || '#'"
            class="bd-card-hover block rounded-lg border border-white/10 bg-white/[0.04] p-3"
          >
            <p class="font-black text-white">{{ broadcast.broadcaster.name }}</p>
            <p class="text-sm text-zinc-400">{{ broadcast.region }} - {{ broadcast.platform }}</p>
            <p v-if="broadcast.details" class="mt-1 text-xs text-zinc-500">{{ broadcast.details }}</p>
          </a>
          <p v-if="!event.broadcasts.length" class="text-sm text-zinc-500">Broadcasts TBC.</p>
        </div>
      </section>
    </div>

    <section class="bd-panel p-5">
      <SectionHeader title="Fight Card" :to="`/events/${event.slug}/fight-card`" />
      <div class="space-y-3">
        <FightRow v-for="fight in event.fights" :key="fight.id" :fight="fight" />
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  CalendarDays as CalendarIcon,
  MapPin as MapPinIcon,
  Radio as RadioIcon,
} from '@lucide/vue'
import EmptyState from '@/components/boxing/EmptyState.vue'
import ErrorState from '@/components/boxing/ErrorState.vue'
import FightRow from '@/components/boxing/FightRow.vue'
import SectionHeader from '@/components/boxing/SectionHeader.vue'
import { boxingApi } from '@/services/boxing'
import type { EventDetail } from '@/types/boxing'
import { formatDate, formatDateTime } from '@/utils/boxing-format'

const route = useRoute()
const loading = ref(true)
const error = ref('')
const event = ref<EventDetail | null>(null)

const venueLine = computed(() => {
  if (!event.value?.venue) return 'Venue TBC'
  return [event.value.venue.name, event.value.venue.city, event.value.venue.country].filter(Boolean).join(', ')
})

const now = ref(Date.now())
let countdownTimer: ReturnType<typeof setInterval> | null = null

const countdownTiles = computed(() => {
  const target = event.value?.event_date ? new Date(event.value.event_date).getTime() : Date.now()
  const remaining = Math.max(0, target - now.value)
  const days = Math.floor(remaining / 86_400_000)
  const hours = Math.floor((remaining % 86_400_000) / 3_600_000)
  const mins = Math.floor((remaining % 3_600_000) / 60_000)
  const secs = Math.floor((remaining % 60_000) / 1000)

  return [
    { label: 'Days', value: String(days).padStart(2, '0') },
    { label: 'Hrs', value: String(hours).padStart(2, '0') },
    { label: 'Mins', value: String(mins).padStart(2, '0') },
    { label: 'Secs', value: String(secs).padStart(2, '0') },
  ]
})

const DetailRow = defineComponent({
  props: {
    label: { type: String, required: true },
    value: { type: String, required: true },
  },
  setup(props) {
    return () => h('div', { class: 'flex justify-between gap-4 border-b border-white/10 pb-3 last:border-b-0 last:pb-0' }, [
      h('dt', { class: 'text-zinc-500' }, props.label),
      h('dd', { class: 'text-right font-semibold text-white' }, props.value),
    ])
  },
})

function startCountdown() {
  countdownTimer = setInterval(() => {
    now.value = Date.now()
  }, 1000)
}

function stopCountdown() {
  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
}

async function loadEvent() {
  stopCountdown()
  loading.value = true
  error.value = ''

  try {
    event.value = await boxingApi.event(String(route.params.slug))
    startCountdown()
  } catch {
    error.value = 'Fight-night details could not be loaded. The event feed may be temporarily unavailable.'
  } finally {
    loading.value = false
  }
}

watch(() => route.params.slug, () => {
  loadEvent()
})
onMounted(loadEvent)
onUnmounted(stopCountdown)
</script>
