<template>
  <div class="mb-4 flex items-center justify-between gap-4">
    <div>
      <p v-if="eyebrow" class="text-xs font-black uppercase tracking-[0.18em] text-red-400">{{ eyebrow }}</p>
      <h2 class="text-xl font-black text-white md:text-2xl">{{ title }}</h2>
    </div>
    <RouterLink v-if="to" :to="to" class="text-sm font-bold text-red-400 transition hover:text-red-300">
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
