<template>
  <div class="rounded-lg border border-white/10 bg-white/[0.035] p-4">
    <div class="flex items-center gap-3">
      <div class="flex size-10 items-center justify-center rounded-lg bg-white/[0.06] text-red-400">
        <component :is="icon" class="size-5" />
      </div>
      <div>
        <p class="text-2xl font-black text-white">{{ value }}</p>
        <p class="text-sm text-zinc-400">{{ label }}</p>
      </div>
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
