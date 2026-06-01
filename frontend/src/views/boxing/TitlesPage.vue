<template>
  <div class="space-y-4">
    <section class="rounded-lg border border-white/10 bg-white/[0.025] p-5">
      <p class="text-xs font-black uppercase tracking-[0.18em] text-red-400">Championships</p>
      <h1 class="mt-2 text-3xl font-black text-white">Titles and Belts</h1>
    </section>

    <LoadingPanel v-if="loading" />
    <ErrorState
      v-else-if="error"
      title="Could not load titles"
      :message="error"
      :retry="loadTitles"
    />
    <section v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="title in titles" :key="title.slug" class="rounded-lg border border-white/10 bg-white/[0.035] p-5">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-2xl font-black text-white">{{ title.organisation.abbreviation }}</p>
            <p class="mt-1 text-sm text-zinc-400">{{ title.weight_class.name }}</p>
          </div>
          <span class="rounded bg-red-600/20 px-2 py-1 text-xs font-black uppercase text-red-300">Current</span>
        </div>

        <RouterLink
          v-if="title.champion"
          :to="`/fighters/${title.champion.slug}`"
          class="mt-5 flex items-center gap-3 rounded-lg bg-white/[0.04] p-3 transition hover:bg-white/[0.07]"
        >
          <img v-if="title.champion.photo_url" :src="title.champion.photo_url" :alt="title.champion.display_name" class="size-14 rounded-lg object-cover">
          <span>
            <span class="block font-black text-white">{{ title.champion.display_name }}</span>
            <span class="text-sm text-zinc-400">{{ title.champion.record }} - since {{ formatDate(title.reign_started_on) }}</span>
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
import ErrorState from '@/components/boxing/ErrorState.vue'
import LoadingPanel from '@/components/boxing/LoadingPanel.vue'
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
