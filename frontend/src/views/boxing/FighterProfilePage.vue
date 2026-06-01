<template>
  <LoadingPanel v-if="loading" />
  <ErrorState
    v-else-if="error"
    title="Could not load fighter profile"
    :message="error"
    :retry="loadFighter"
  />
  <EmptyState v-else-if="!fighter" title="Fighter not found" />

  <div v-else class="space-y-4">
    <section class="relative grid gap-4 overflow-hidden rounded-lg border border-white/10 bg-[#070c12] p-4 shadow-2xl shadow-black/30 xl:grid-cols-[360px_minmax(0,1fr)_360px]">
      <img
        v-if="fighter.photo_url"
        :src="fighter.photo_url"
        :alt="fighter.display_name"
        class="absolute inset-0 h-full w-full object-cover opacity-10 blur"
      >
      <div class="absolute inset-0 bg-gradient-to-r from-black via-[#070c12]/95 to-black/80" />

      <div class="relative overflow-hidden rounded-lg border border-white/10 bg-black/30">
        <img
          v-if="fighter.photo_url"
          :src="fighter.photo_url"
          :alt="fighter.display_name"
          class="aspect-[4/4] w-full object-cover"
        >
      </div>

      <div class="relative rounded-lg border border-white/10 bg-black/35 p-6 backdrop-blur">
        <p class="text-sm font-black uppercase tracking-[0.18em] text-red-400">{{ fighter.weight_class?.name }}</p>
        <h1 class="mt-2 text-4xl font-black text-white">{{ fighter.display_name }}</h1>
        <p v-if="fighter.ring_name" class="mt-1 text-lg text-zinc-400">{{ fighter.ring_name }}</p>

        <div class="mt-5 flex flex-wrap gap-2">
          <span class="rounded bg-red-600 px-3 py-1 text-xs font-black uppercase text-white">Active</span>
          <span class="rounded border border-white/10 bg-white/[0.04] px-3 py-1 text-xs font-black uppercase text-zinc-300">{{ fighter.record }}</span>
          <span class="rounded border border-white/10 bg-white/[0.04] px-3 py-1 text-xs font-black uppercase text-zinc-300">{{ koRate(fighter.wins, fighter.knockouts) }} KO Rate</span>
        </div>

        <dl class="mt-6 grid gap-3 sm:grid-cols-2">
          <InfoItem label="Country" :value="fighter.country ? `${fighter.country.name} (${fighter.country.code})` : 'TBC'" />
          <InfoItem label="Record" :value="`${fighter.record} (${fighter.knockouts} KO)`" />
          <InfoItem label="Stance" :value="fighter.stance || 'TBC'" />
          <InfoItem label="Height" :value="fighter.height_cm ? `${fighter.height_cm} cm` : 'TBC'" />
          <InfoItem label="Reach" :value="fighter.reach_cm ? `${fighter.reach_cm} cm` : 'TBC'" />
          <InfoItem label="Debut" :value="formatDate(fighter.debut_date)" />
        </dl>
      </div>

      <div class="relative rounded-lg border border-white/10 bg-black/35 p-5 backdrop-blur">
        <h2 class="font-black text-white">World Titles</h2>
        <div class="mt-4 space-y-3">
          <div v-for="title in fighter.titles" :key="title.belt.name" class="rounded-lg bg-white/[0.04] p-3">
            <p class="font-black text-white">{{ title.belt.organisation }}</p>
            <p class="text-sm text-zinc-400">{{ title.belt.weight_class }}</p>
            <p class="mt-1 text-xs text-zinc-500">Since {{ formatDate(title.reign_started_on) }}</p>
          </div>
        </div>
      </div>
    </section>

    <nav class="flex gap-2 overflow-x-auto rounded-lg border border-white/10 bg-[#070c12] p-2 shadow-xl shadow-black/10">
      <button
        v-for="tab in tabs"
        :key="tab"
        class="rounded-lg px-4 py-2 text-sm font-bold transition"
        :class="activeTab === tab ? 'bg-red-600 text-white' : 'text-zinc-400 hover:bg-white/[0.06] hover:text-white'"
        @click="activeTab = tab"
      >
        {{ tab }}
      </button>
    </nav>

    <section v-if="activeTab === 'Overview'" class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_420px]">
      <div class="rounded-lg border border-white/10 bg-[#070c12] p-5 shadow-xl shadow-black/10">
        <h2 class="font-black text-white">About</h2>
        <p class="mt-3 leading-7 text-zinc-300">{{ fighter.bio }}</p>
        <dl class="mt-6 grid gap-3 sm:grid-cols-2">
          <InfoItem label="Born" :value="formatDate(fighter.birth_date)" />
          <InfoItem label="Birthplace" :value="fighter.birth_place || 'TBC'" />
          <InfoItem label="Residence" :value="fighter.residence || 'TBC'" />
          <InfoItem label="KO Rate" :value="koRate(fighter.wins, fighter.knockouts)" />
        </dl>
      </div>

      <div class="space-y-4">
        <FightSnapshot title="Last Fight" :fight="fighter.last_fight" />
        <FightSnapshot title="Next Fight" :fight="fighter.upcoming_fight" />
      </div>
    </section>

    <section v-else-if="activeTab === 'Record' || activeTab === 'Fights'" class="space-y-3">
      <FightRow v-for="fight in fighter.fight_history" :key="fight.id" :fight="fight" />
    </section>

    <section v-else-if="activeTab === 'Stats'" class="grid gap-4 md:grid-cols-4">
      <StatTile :icon="TrophyIcon" label="Wins" :value="fighter.wins" />
      <StatTile :icon="XIcon" label="Losses" :value="fighter.losses" />
      <StatTile :icon="MinusIcon" label="Draws" :value="fighter.draws" />
      <StatTile :icon="ZapIcon" label="KO Rate" :value="koRate(fighter.wins, fighter.knockouts)" />
    </section>

    <section v-else-if="activeTab === 'Titles'" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
      <div v-for="title in fighter.titles" :key="title.belt.name" class="rounded-lg border border-white/10 bg-white/[0.035] p-4">
        <p class="text-xl font-black text-white">{{ title.belt.organisation }}</p>
        <p class="mt-1 text-zinc-300">{{ title.belt.name }}</p>
        <p class="mt-4 text-sm text-zinc-500">{{ title.result }}</p>
      </div>
    </section>

    <EmptyState v-else title="Coming soon" message="This tab is ready for editorial content and media once those feeds are connected." />
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  Minus as MinusIcon,
  Trophy as TrophyIcon,
  X as XIcon,
  Zap as ZapIcon,
} from '@lucide/vue'
import EmptyState from '@/components/boxing/EmptyState.vue'
import ErrorState from '@/components/boxing/ErrorState.vue'
import FightRow from '@/components/boxing/FightRow.vue'
import LoadingPanel from '@/components/boxing/LoadingPanel.vue'
import StatTile from '@/components/boxing/StatTile.vue'
import { boxingApi } from '@/services/boxing'
import type { FightSummary, FighterDetail } from '@/types/boxing'
import { formatDate, koRate } from '@/utils/boxing-format'

const route = useRoute()
const loading = ref(true)
const error = ref('')
const fighter = ref<FighterDetail | null>(null)
const activeTab = ref('Overview')
const tabs = ['Overview', 'Record', 'Stats', 'Fights', 'Titles', 'News', 'Gallery']

const InfoItem = defineComponent({
  props: {
    label: { type: String, required: true },
    value: { type: String, required: true },
  },
  setup(props) {
    return () => h('div', { class: 'rounded-lg bg-white/[0.04] p-3' }, [
      h('dt', { class: 'text-xs uppercase text-zinc-500' }, props.label),
      h('dd', { class: 'mt-1 font-bold text-white' }, props.value),
    ])
  },
})

const FightSnapshot = defineComponent({
  props: {
    title: { type: String, required: true },
    fight: { type: Object as () => FightSummary | null, default: null },
  },
  setup(props) {
    const href = computed(() => props.fight?.event ? `/events/${props.fight.event.slug}` : '/events')

    return () => h('div', { class: 'rounded-lg border border-white/10 bg-white/[0.025] p-5' }, [
      h('h2', { class: 'font-black text-white' }, props.title),
      props.fight
        ? h(RouterLink, { to: href.value, class: 'mt-4 grid grid-cols-[1fr_120px] gap-4 rounded-lg bg-white/[0.04] p-3 transition hover:bg-white/[0.07]' }, () => [
          h('div', [
            h('p', { class: 'font-black text-white' }, `${props.fight?.red_corner?.display_name} vs ${props.fight?.blue_corner?.display_name}`),
            h('p', { class: 'mt-1 text-sm text-zinc-400' }, props.fight?.event?.name || props.fight?.title || ''),
            h('p', { class: 'mt-2 text-xs text-zinc-500' }, formatDate(props.fight?.fight_date)),
          ]),
          props.fight?.red_corner?.photo_url
            ? h('img', { src: props.fight.red_corner.photo_url, alt: props.fight.red_corner.display_name, class: 'h-24 w-full rounded-lg object-cover' })
            : null,
        ])
        : h('p', { class: 'mt-3 text-sm text-zinc-500' }, 'No fight found.'),
    ])
  },
})

async function loadFighter() {
  loading.value = true
  error.value = ''

  try {
    fighter.value = await boxingApi.fighter(String(route.params.slug))
  } catch {
    error.value = 'This profile could not be loaded. Try again once the API is available.'
  } finally {
    loading.value = false
  }
}

watch(() => route.params.slug, loadFighter)
onMounted(loadFighter)
</script>
