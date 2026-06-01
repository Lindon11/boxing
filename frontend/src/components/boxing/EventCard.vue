<template>
  <RouterLink
    :to="`/events/${event.slug}`"
    class="group overflow-hidden rounded-lg border border-white/10 bg-[#090f15] shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-red-500/60 hover:bg-[#0c131b]"
  >
    <div class="relative aspect-[16/9] overflow-hidden bg-zinc-900">
      <img
        v-if="event.poster_url || event.hero_image_url"
        :src="event.poster_url || event.hero_image_url || ''"
        :alt="event.name"
        class="h-full w-full object-cover opacity-75 transition duration-300 group-hover:scale-105 group-hover:opacity-95"
      >
      <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent" />
      <div class="absolute left-3 top-3 rounded bg-zinc-950/85 px-2.5 py-2 text-center shadow-xl">
        <p class="text-[10px] font-black uppercase text-zinc-400">{{ month }}</p>
        <p class="text-lg font-black leading-none text-white">{{ day }}</p>
      </div>
      <span class="absolute right-3 top-3 rounded bg-red-600 px-2 py-1 text-[10px] font-black uppercase text-white shadow-lg shadow-black/40">{{ event.status }}</span>
    </div>
    <div class="p-4">
      <p class="truncate font-black text-white">{{ event.name }}</p>
      <p class="mt-1 text-sm text-zinc-400">{{ event.subtitle }}</p>
      <div class="mt-4 border-t border-white/10 pt-3">
        <p class="text-sm font-semibold text-zinc-300">{{ event.venue?.name || 'Venue TBC' }}</p>
        <p class="text-xs text-zinc-500">{{ event.broadcast_notes || event.promoter?.name || 'Broadcast TBC' }}</p>
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
