<template>
  <div class="bd-card-hover grid gap-3 rounded-lg border border-white/10 bg-[#0f1724] p-4 shadow-lg shadow-black/10 md:grid-cols-[1fr_auto_1fr_auto] md:items-center">
    <RouterLink
      v-if="fight.red_corner"
      :to="`/fighters/${fight.red_corner.slug}`"
      class="flex items-center gap-3"
    >
      <img
        v-if="fight.red_corner.photo_url"
        :src="fight.red_corner.photo_url"
        :alt="fight.red_corner.display_name"
        class="size-14 rounded-lg object-cover ring-1 ring-white/10"
      >
      <div class="min-w-0">
        <p class="truncate font-black text-white">{{ fight.red_corner.display_name }}</p>
        <p class="text-xs text-zinc-400">{{ fight.red_corner.record }} ({{ fight.red_corner.knockouts }} KO)</p>
      </div>
    </RouterLink>

    <div class="flex justify-center">
      <span class="flex size-10 items-center justify-center rounded-lg bg-gradient-to-br from-red-600 to-red-800 text-xs font-black text-white shadow-lg shadow-red-800/30">VS</span>
    </div>

    <RouterLink
      v-if="fight.blue_corner"
      :to="`/fighters/${fight.blue_corner.slug}`"
      class="flex items-center gap-3 md:flex-row-reverse md:text-right"
    >
      <img
        v-if="fight.blue_corner.photo_url"
        :src="fight.blue_corner.photo_url"
        :alt="fight.blue_corner.display_name"
        class="size-14 rounded-lg object-cover ring-1 ring-white/10"
      >
      <div class="min-w-0">
        <p class="truncate font-black text-white">{{ fight.blue_corner.display_name }}</p>
        <p class="text-xs text-zinc-400">{{ fight.blue_corner.record }} ({{ fight.blue_corner.knockouts }} KO)</p>
      </div>
    </RouterLink>

    <div class="rounded-lg border border-white/10 bg-black/20 p-3 text-left text-xs text-zinc-400 md:text-right">
      <p class="font-black uppercase text-red-300">{{ label }}</p>
      <p>{{ fight.weight_class?.name || 'Weight TBC' }}</p>
      <p>{{ fight.scheduled_rounds }} rounds</p>
      <p v-if="fight.result_notes" class="mt-1 text-zinc-200">{{ fight.result_notes }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { FightSummary } from '@/types/boxing'

const props = defineProps<{
  fight: FightSummary
}>()

const label = computed(() => props.fight.billing.replace(/_/g, ' '))
</script>
