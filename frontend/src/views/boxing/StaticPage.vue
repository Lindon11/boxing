<template>
  <section class="rounded-lg border border-white/10 bg-white/[0.025] p-8">
    <p class="bd-kicker">BoxingDB</p>
    <h1 class="mt-2 text-3xl font-black text-white">{{ title }}</h1>
    <p class="mt-4 max-w-2xl text-zinc-400">{{ message }}</p>
  </section>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  message: string
}>()
</script>
