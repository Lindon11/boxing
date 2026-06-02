<template>
  <div class="mb-3 flex items-center justify-between gap-3">
    <div>
      <p v-if="eyebrow" class="bd-kicker">{{ eyebrow }}</p>
      <h2 class="text-base font-black text-white md:text-lg">{{ title }}</h2>
    </div>
    <RouterLink v-if="to" :to="to" class="rounded-lg px-2.5 py-1 text-xs font-black text-red-400 transition hover:bg-red-500/10 hover:text-red-300">
      {{ action || 'View all' }}
    </RouterLink>
  </div>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router'

defineProps<{
  title: string
  eyebrow?: string
  to?: string
  action?: string
}>()
</script>
