<template>
  <div class="bd-panel p-10 text-center">
    <div class="mx-auto mb-4 h-1 w-16 rounded-full bg-red-600" />
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
