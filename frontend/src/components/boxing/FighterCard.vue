<template>
  <RouterLink
    :to="`/fighters/${fighter.slug}`"
    class="group overflow-hidden rounded-lg border border-white/10 bg-[#090f15] shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-red-500/60 hover:bg-[#0c131b]"
  >
    <div class="aspect-[4/3] overflow-hidden bg-zinc-900">
      <img
        v-if="fighter.photo_url"
        :src="fighter.photo_url"
        :alt="fighter.display_name"
        class="h-full w-full object-cover opacity-80 transition duration-300 group-hover:scale-105 group-hover:opacity-100"
      >
    </div>
    <div class="p-4">
      <div class="flex items-start justify-between gap-3">
        <div>
          <p class="font-black text-white">{{ fighter.display_name }}</p>
          <p class="text-xs text-zinc-400">{{ fighter.ring_name || fighter.weight_class?.name || 'Boxer' }}</p>
        </div>
      <span class="rounded bg-red-600/20 px-2 py-1 text-xs font-black text-red-300 ring-1 ring-red-500/20">{{ fighter.country?.code || 'INT' }}</span>
      </div>
      <dl class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
        <div class="rounded bg-black/25 p-2">
          <dt class="text-zinc-500">Record</dt>
          <dd class="font-bold text-white">{{ fighter.record }}</dd>
        </div>
        <div class="rounded bg-black/25 p-2">
          <dt class="text-zinc-500">KOs</dt>
          <dd class="font-bold text-white">{{ fighter.knockouts }}</dd>
        </div>
        <div class="rounded bg-black/25 p-2">
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
