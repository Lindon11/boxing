<template>
  <div class="rounded-lg border border-white/10 bg-white/[0.035] p-8 text-center">
    <p class="text-lg font-black text-white">{{ title }}</p>
    <p v-if="message" class="mt-2 text-sm text-zinc-400">{{ message }}</p>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  message?: string
}>()
</script>
