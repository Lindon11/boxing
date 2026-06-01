<template>
  <div class="grid gap-3 rounded-lg border border-white/10 bg-[#090f15] p-3 shadow-lg shadow-black/10 transition hover:border-red-500/40 md:grid-cols-[1fr_auto_1fr_auto] md:items-center">
    <RouterLink
      v-if="fight.red_corner"
      :to="`/fighters/${fight.red_corner.slug}`"
      class="flex items-center gap-3"
    >
      <img
        v-if="fight.red_corner.photo_url"
        :src="fight.red_corner.photo_url"
        :alt="fight.red_corner.display_name"
        class="size-12 rounded-lg object-cover"
      >
      <div class="min-w-0">
        <p class="truncate font-black text-white">{{ fight.red_corner.display_name }}</p>
        <p class="text-xs text-zinc-400">{{ fight.red_corner.record }} ({{ fight.red_corner.knockouts }} KO)</p>
      </div>
    </RouterLink>

    <div class="flex justify-center">
      <span class="rounded-full border border-white/10 bg-black/35 px-3 py-2 text-xs font-black text-red-400">VS</span>
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
        class="size-12 rounded-lg object-cover"
      >
      <div class="min-w-0">
        <p class="truncate font-black text-white">{{ fight.blue_corner.display_name }}</p>
        <p class="text-xs text-zinc-400">{{ fight.blue_corner.record }} ({{ fight.blue_corner.knockouts }} KO)</p>
      </div>
    </RouterLink>

    <div class="text-left text-xs text-zinc-400 md:text-right">
      <p class="font-bold uppercase text-red-300">{{ label }}</p>
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
