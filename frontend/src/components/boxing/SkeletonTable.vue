<template>
  <div class="animate-pulse overflow-hidden rounded-lg border border-white/10">
    <table class="bd-table">
      <thead>
        <tr>
          <th v-for="i in cols" :key="i" class="px-4 py-3">
            <div class="h-3 w-16 rounded bg-zinc-700" />
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row" class="border-t border-white/10">
          <td v-for="col in cols" :key="col" class="px-4 py-4">
            <div class="h-4 w-24 rounded bg-zinc-700" :style="col === cols ? 'margin-left: auto' : ''" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  rows?: number
  cols?: number
}>(), {
  rows: 5,
  cols: 4,
})
</script>
