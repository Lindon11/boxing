<template>
  <RouterLink
    :to="`/fighters/${fighter.slug}`"
    class="bd-card-hover group overflow-hidden rounded-lg border border-white/10 bg-[#0f1724] shadow-xl shadow-black/15"
  >
    <div class="relative aspect-[4/3] overflow-hidden bg-zinc-900">
      <img
        v-if="fighter.photo_url"
        :src="fighter.photo_url"
        :alt="fighter.display_name"
        class="h-full w-full object-cover opacity-80 transition duration-300 group-hover:scale-105 group-hover:opacity-100"
      >
      <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent" />
      <span class="bd-chip bd-chip-red absolute bottom-3 left-3">{{ fighter.weight_class?.name || 'Boxer' }}</span>
    </div>
    <div class="p-4">
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <p class="truncate text-lg font-black leading-tight text-white">{{ fighter.display_name }}</p>
          <p class="truncate text-sm text-zinc-400">{{ fighter.ring_name || fighter.stance || 'Professional boxer' }}</p>
        </div>
        <span class="rounded-lg bg-white/[0.06] px-3 py-1 text-xs font-black text-zinc-200">{{ fighter.country?.code || 'INT' }}</span>
      </div>
      <dl class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
        <div class="rounded-lg bg-black/25 p-2">
          <dt class="text-zinc-500">Record</dt>
          <dd class="font-bold text-white">{{ fighter.record }}</dd>
        </div>
        <div class="rounded-lg bg-black/25 p-2">
          <dt class="text-zinc-500">KOs</dt>
          <dd class="font-bold text-white">{{ fighter.knockouts }}</dd>
        </div>
        <div class="rounded-lg bg-black/25 p-2">
          <dt class="text-zinc-500">Stance</dt>
          <dd class="truncate font-bold text-white">{{ fighter.stance || '-' }}</dd>
        </div>
      </dl>
    </div>
  </RouterLink>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { FighterSummary } from '@/types/boxing'

defineProps<{
  fighter: FighterSummary
}>()
</script>
