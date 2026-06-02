<template>
  <RouterLink
    :to="`/events/${event.slug}`"
    class="bd-card-hover group overflow-hidden rounded-lg border border-white/10 bg-[#0f1724] shadow-xl shadow-black/15"
  >
    <div class="relative aspect-[16/9] overflow-hidden bg-zinc-900">
      <img
        v-if="event.poster_url || event.hero_image_url"
        :src="event.poster_url || event.hero_image_url || ''"
        :alt="event.name"
        class="h-full w-full object-cover opacity-75 transition duration-300 group-hover:scale-105 group-hover:opacity-95"
      >
      <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent" />
      <div class="absolute left-3 top-3 rounded-lg bg-zinc-950/90 px-3 py-2 text-center shadow-xl">
        <p class="text-[10px] font-black uppercase text-zinc-400">{{ month }}</p>
        <p class="text-lg font-black leading-none text-white">{{ day }}</p>
      </div>
      <span class="bd-chip bd-chip-red absolute right-3 top-3 shadow-lg shadow-black/40">{{ event.status }}</span>
    </div>
    <div class="p-4">
      <p class="line-clamp-2 text-lg font-black leading-tight text-white">{{ event.name }}</p>
      <p class="mt-1 line-clamp-2 min-h-10 text-sm text-zinc-400">{{ event.subtitle || event.broadcast_notes || 'Event details available' }}</p>
      <div class="mt-4 border-t border-white/10 pt-3 text-sm">
        <p class="truncate font-semibold text-zinc-200">{{ event.venue?.name || 'Venue TBC' }}</p>
        <p class="mt-0.5 text-xs text-zinc-500">{{ event.broadcast_notes || event.promoter?.name || 'Broadcast TBC' }}</p>
      </div>
    </div>
  </RouterLink>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { EventSummary } from '@/types/boxing'

const props = defineProps<{
  event: EventSummary
}>()

const date = computed(() => props.event.event_date ? new Date(props.event.event_date) : null)
const month = computed(() => date.value ? date.value.toLocaleString(undefined, { month: 'short' }) : 'TBC')
const day = computed(() => date.value ? String(date.value.getDate()).padStart(2, '0') : '--')
</script>
