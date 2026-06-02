<template>
  <div class="flex min-h-20 items-center gap-3 rounded-lg border border-white/10 bg-white/[0.04] p-3 shadow-lg shadow-black/10">
    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white/[0.06] text-red-400">
      <component :is="icon" class="size-4" />
    </div>
    <div class="min-w-0">
      <p class="text-xl font-black leading-none text-white">{{ value }}</p>
      <p class="mt-1 text-sm leading-none text-zinc-500">{{ label }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue'

defineProps<{
  icon: Component
  label: string
  value: string | number
}>()
</script>
