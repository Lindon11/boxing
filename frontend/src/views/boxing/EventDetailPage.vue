<template>
  <LoadingPanel v-if="loading" />
  <ErrorState
    v-else-if="error"
    title="Could not load event"
    :message="error"
    :retry="loadEvent"
  />
  <EmptyState v-else-if="!event" title="Event not found" />

  <div v-else class="space-y-4">
    <section class="relative overflow-hidden rounded-lg border border-white/10 bg-zinc-950 shadow-2xl shadow-black/30">
      <img
        v-if="event.hero_image_url"
        :src="event.hero_image_url"
        :alt="event.name"
        class="absolute inset-0 h-full w-full object-cover opacity-45"
      >
      <div class="absolute inset-0 bg-gradient-to-r from-black via-black/85 to-black/30" />
      <div class="relative grid min-h-[520px] gap-6 p-6 xl:grid-cols-[minmax(0,1fr)_340px] xl:p-8">
        <div class="flex flex-col justify-center">
          <div class="flex flex-wrap items-center gap-2">
            <span class="w-max rounded bg-red-600 px-3 py-1 text-xs font-black uppercase text-white">{{ event.status }}</span>
            <span class="w-max rounded border border-white/10 bg-white/[0.05] px-3 py-1 text-xs font-black uppercase text-zinc-300">{{ event.promoter?.name || 'Promoter TBC' }}</span>
          </div>
          <h1 class="mt-4 text-4xl font-black text-white md:text-6xl">{{ event.name }}</h1>
          <p class="mt-2 text-lg text-zinc-300">{{ event.subtitle }}</p>
          <div class="mt-6 grid gap-2 text-sm font-semibold text-zinc-200">
            <p class="flex items-center gap-2"><CalendarIcon class="size-4 text-red-400" /> {{ formatDateTime(event.event_date) }}</p>
            <p class="flex items-center gap-2"><MapPinIcon class="size-4 text-red-400" /> {{ venueLine }}</p>
            <p class="flex items-center gap-2"><RadioIcon class="size-4 text-red-400" /> {{ event.broadcast_notes || 'Broadcast TBC' }}</p>
          </div>
          <div class="mt-7 flex flex-wrap gap-3">
            <RouterLink :to="`/events/${event.slug}/fight-card`" class="rounded-lg bg-red-600 px-5 py-3 text-sm font-black text-white transition hover:bg-red-500">
              View Fight Card
            </RouterLink>
            <a v-if="event.ticket_url" :href="event.ticket_url" class="rounded-lg border border-white/20 bg-white/[0.04] px-5 py-3 text-sm font-black text-white transition hover:border-red-500/60">
              Tickets
            </a>
          </div>
          <div class="mt-8 grid max-w-2xl gap-3 sm:grid-cols-3">
            <div class="rounded-lg border border-white/10 bg-black/40 p-3 backdrop-blur">
              <p class="text-xs uppercase text-zinc-500">Bouts</p>
              <p class="mt-1 text-2xl font-black text-white">{{ event.fights.length }}</p>
            </div>
            <div class="rounded-lg border border-white/10 bg-black/40 p-3 backdrop-blur">
              <p class="text-xs uppercase text-zinc-500">Broadcasts</p>
              <p class="mt-1 text-2xl font-black text-white">{{ event.broadcasts.length }}</p>
            </div>
            <div class="rounded-lg border border-white/10 bg-black/40 p-3 backdrop-blur">
              <p class="text-xs uppercase text-zinc-500">Venue</p>
              <p class="mt-1 truncate text-lg font-black text-white">{{ event.venue?.name || 'TBC' }}</p>
            </div>
          </div>
        </div>

        <div class="hidden xl:block">
          <img
            v-if="event.poster_url"
            :src="event.poster_url"
            :alt="`${event.name} poster`"
            class="aspect-[3/4] w-full rounded-lg border border-white/10 object-cover shadow-2xl shadow-black/60"
          >
        </div>
      </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
      <section class="rounded-lg border border-white/10 bg-[#070c12] p-5 shadow-xl shadow-black/10">
        <SectionHeader title="Fight Card" :to="`/events/${event.slug}/fight-card`" />
        <div class="space-y-3">
          <FightRow v-for="fight in event.fights" :key="fight.id" :fight="fight" />
        </div>
      </section>

      <aside class="space-y-4">
        <section class="rounded-lg border border-white/10 bg-[#070c12] p-5 shadow-xl shadow-black/10">
          <h2 class="font-black text-white">Event Details</h2>
          <dl class="mt-4 space-y-3 text-sm">
            <DetailRow label="Date" :value="formatDate(event.event_date)" />
            <DetailRow label="Venue" :value="event.venue?.name || 'TBC'" />
            <DetailRow label="Location" :value="venueLine" />
            <DetailRow label="Promoter" :value="event.promoter?.name || 'TBC'" />
            <DetailRow label="Ring walks" :value="formatDateTime(event.ring_walks_at)" />
          </dl>
        </section>

        <section class="rounded-lg border border-white/10 bg-[#070c12] p-5 shadow-xl shadow-black/10">
          <h2 class="font-black text-white">Where To Watch</h2>
          <div class="mt-4 space-y-3">
            <a
              v-for="broadcast in event.broadcasts"
              :key="`${broadcast.region}-${broadcast.broadcaster.slug}`"
              :href="broadcast.broadcaster.website_url || '#'"
              class="block rounded-lg bg-white/[0.04] p-3 transition hover:bg-white/[0.07]"
            >
              <p class="font-black text-white">{{ broadcast.broadcaster.name }}</p>
              <p class="text-sm text-zinc-400">{{ broadcast.region }} - {{ broadcast.platform }}</p>
              <p v-if="broadcast.details" class="mt-1 text-xs text-zinc-500">{{ broadcast.details }}</p>
            </a>
          </div>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  CalendarDays as CalendarIcon,
  MapPin as MapPinIcon,
  Radio as RadioIcon,
} from '@lucide/vue'
import EmptyState from '@/components/boxing/EmptyState.vue'
import ErrorState from '@/components/boxing/ErrorState.vue'
import FightRow from '@/components/boxing/FightRow.vue'
import LoadingPanel from '@/components/boxing/LoadingPanel.vue'
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

async function loadEvent() {
  loading.value = true
  error.value = ''

  try {
    event.value = await boxingApi.event(String(route.params.slug))
  } catch {
    error.value = 'Fight-night details could not be loaded. The event feed may be temporarily unavailable.'
  } finally {
    loading.value = false
  }
}

watch(() => route.params.slug, loadEvent)
onMounted(loadEvent)
</script>
