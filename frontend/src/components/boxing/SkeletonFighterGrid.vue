<template>
  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
    <div v-for="i in count" :key="i" class="animate-pulse overflow-hidden rounded-lg border border-white/10 bg-[#0f1724] shadow-xl shadow-black/20">
      <div class="relative aspect-[4/3] bg-zinc-800">
        <div class="absolute bottom-3 left-3 h-5 w-20 rounded bg-zinc-700" />
      </div>
      <div class="p-4">
        <div class="flex items-start justify-between gap-3">
          <div class="flex-1">
            <div class="h-5 w-32 rounded bg-zinc-700" />
            <div class="mt-1 h-3 w-20 rounded bg-zinc-700" />
          </div>
          <div class="h-5 w-10 rounded bg-zinc-700" />
        </div>
        <div class="mt-4 grid grid-cols-3 gap-2">
          <div v-for="j in 3" :key="j" class="space-y-1 rounded bg-black/25 p-2">
            <div class="mx-auto h-3 w-10 rounded bg-zinc-700" />
            <div class="mx-auto h-4 w-8 rounded bg-zinc-700" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  count?: number
}>(), {
  count: 8,
})
</script>
