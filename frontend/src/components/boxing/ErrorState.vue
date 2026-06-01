<template>
  <div class="rounded-lg border border-red-500/30 bg-red-950/20 p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="text-sm font-black uppercase tracking-[0.18em] text-red-300">{{ eyebrow || 'Something went wrong' }}</p>
        <h2 class="mt-2 text-2xl font-black text-white">{{ title }}</h2>
        <p v-if="message" class="mt-2 max-w-2xl text-sm leading-6 text-zinc-400">{{ message }}</p>
      </div>
      <button
        v-if="retry"
        class="inline-flex h-11 items-center justify-center rounded-lg bg-red-600 px-5 text-sm font-black text-white transition hover:bg-red-500"
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
