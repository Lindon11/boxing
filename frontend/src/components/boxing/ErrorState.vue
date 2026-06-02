<template>
  <div class="rounded-lg border border-red-500/30 bg-red-950/20 p-6 shadow-2xl shadow-black/20">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="text-sm font-black uppercase text-red-300">{{ eyebrow || 'Something went wrong' }}</p>
        <h2 class="mt-2 text-2xl font-black text-white">{{ title }}</h2>
        <p v-if="message" class="mt-2 max-w-2xl text-sm leading-6 text-zinc-400">{{ message }}</p>
      </div>
      <button
        v-if="retry"
        class="bd-button-primary"
        type="button"
        @click="retry"
      >
        Try Again
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  message?: string
  eyebrow?: string
  retry?: () => void
}>()
</script>
