<template>
  <div class="space-y-4">
    <section class="bd-panel p-5">
      <p class="bd-kicker">Championships</p>
      <h1 class="bd-page-title">Titles and Belts</h1>
    </section>

    <section v-if="loading" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <div v-for="i in 6" :key="i" class="animate-pulse rounded-lg border border-white/10 bg-white/[0.035] p-5">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="h-7 w-16 rounded bg-zinc-700" />
            <div class="mt-2 h-4 w-28 rounded bg-zinc-700" />
          </div>
          <div class="h-5 w-16 rounded bg-zinc-700" />
        </div>
        <div class="mt-5 flex items-center gap-3 rounded-lg bg-white/[0.04] p-3">
          <div class="size-14 rounded-lg bg-zinc-700" />
          <div class="flex-1 space-y-2">
            <div class="h-5 w-36 rounded bg-zinc-700" />
            <div class="h-3 w-24 rounded bg-zinc-700" />
          </div>
        </div>
        <div class="mt-4 h-4 w-48 rounded bg-zinc-700" />
      </div>
    </section>
    <ErrorState
      v-else-if="error"
      title="Could not load titles"
      :message="error"
      :retry="loadTitles"
    />
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="title in titles" :key="title.slug" class="bd-card-hover rounded-lg border border-white/10 bg-gradient-to-b from-[#0f1724] to-[#0a0f1a] p-5 shadow-xl shadow-black/15 bd-fade-in">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-2xl font-black text-white">{{ title.organisation.abbreviation }}</p>
            <p class="mt-1 text-sm text-zinc-400">{{ title.weight_class.name }}</p>
          </div>
          <span class="bd-chip bd-chip-gold">
            <TrophyIcon class="size-3" />
            Champion
          </span>
        </div>

        <RouterLink
          v-if="title.champion"
          :to="`/fighters/${title.champion.slug}`"
          class="mt-5 flex items-center gap-3 rounded-lg bg-white/[0.04] p-3 transition hover:bg-white/[0.07]"
        >
          <img v-if="title.champion.photo_url" :src="title.champion.photo_url" :alt="title.champion.display_name" class="size-14 rounded-lg object-cover ring-1 ring-white/10">
          <span>
            <span class="block font-black text-white">{{ title.champion.display_name }}</span>
            <span class="text-sm text-zinc-400">{{ title.champion.record }} &middot; since {{ formatDate(title.reign_started_on) }}</span>
          </span>
        </RouterLink>

        <p class="mt-4 text-sm font-semibold text-zinc-300">{{ title.name }}</p>
      </article>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { Trophy as TrophyIcon } from '@lucide/vue'
import ErrorState from '@/components/boxing/ErrorState.vue'
import { boxingApi } from '@/services/boxing'
import type { TitleSummary } from '@/types/boxing'
import { formatDate } from '@/utils/boxing-format'

const loading = ref(true)
const error = ref('')
const titles = ref<TitleSummary[]>([])

async function loadTitles() {
  loading.value = true
  error.value = ''

  try {
    titles.value = (await boxingApi.titles()).titles
  } catch {
    error.value = 'The title-holder list could not be loaded. Check the API and try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadTitles)
</script>
