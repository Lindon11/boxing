<template>
  <div v-if="loading" class="space-y-4">
    <section class="relative grid gap-4 overflow-hidden rounded-lg border border-white/10 bg-[#070c12] p-4 shadow-2xl shadow-black/30 xl:grid-cols-[360px_minmax(0,1fr)_360px]">
      <div class="animate-pulse overflow-hidden rounded-lg border border-white/10 bg-black/30">
        <div class="aspect-[4/4] w-full bg-zinc-800" />
      </div>
      <div class="animate-pulse rounded-lg border border-white/10 bg-black/40 p-6 backdrop-blur">
        <div class="h-3 w-24 rounded bg-zinc-700" />
        <div class="mt-3 h-10 w-48 rounded bg-zinc-700" />
        <div class="mt-2 h-4 w-32 rounded bg-zinc-700" />
        <div class="mt-5 flex gap-2">
          <div v-for="i in 3" :key="i" class="h-6 w-20 rounded bg-zinc-700" />
        </div>
        <div class="mt-6 grid gap-3 sm:grid-cols-2">
          <div v-for="i in 6" :key="i" class="space-y-1 rounded-lg border border-white/10 bg-white/[0.04] p-3">
            <div class="h-3 w-16 rounded bg-zinc-700" />
            <div class="h-4 w-24 rounded bg-zinc-700" />
          </div>
        </div>
      </div>
      <div class="animate-pulse rounded-lg border border-white/10 bg-black/40 p-5 backdrop-blur">
        <div class="h-5 w-28 rounded bg-zinc-700" />
        <div class="mt-4 space-y-3">
          <div v-for="i in 3" :key="i" class="h-16 rounded-lg bg-zinc-800" />
        </div>
      </div>
    </section>
  </div>
  <ErrorState
    v-else-if="error"
    title="Could not load fighter profile"
    :message="error"
    :retry="loadFighter"
  />
  <EmptyState v-else-if="!fighter" title="Fighter not found" />

  <div v-else class="space-y-6">
    <nav class="flex items-center gap-2 text-sm text-zinc-500">
      <RouterLink to="/fighters" class="font-semibold text-zinc-400 transition hover:text-red-400">Fighters</RouterLink>
      <span class="text-zinc-600">/</span>
      <span class="font-semibold text-white truncate max-w-[300px]">{{ fighter.display_name }}</span>
    </nav>

    <section class="relative overflow-hidden rounded-lg border border-white/10 bg-gradient-to-br from-[#0f1724] via-[#0a0f1a] to-[#080c14] shadow-2xl shadow-black/40">
      <img
        v-if="fighter.photo_url"
        :src="fighter.photo_url"
        :alt="fighter.display_name"
        class="absolute inset-0 h-full w-full object-cover opacity-[0.07] blur-xl"
      >
      <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-transparent to-black/40" />

      <div class="relative grid gap-6 p-6 xl:grid-cols-[300px_minmax(0,1fr)_300px] xl:p-8">
        <div class="overflow-hidden rounded-lg border border-white/10 bg-gradient-to-b from-zinc-800 to-zinc-900 shadow-2xl shadow-black/40">
          <img
            v-if="fighter.photo_url"
            :src="fighter.photo_url"
            :alt="fighter.display_name"
            class="aspect-[4/5] w-full object-cover"
          >
        </div>

        <div class="rounded-lg border border-white/10 bg-black/40 p-6 backdrop-blur">
          <p class="bd-kicker">{{ fighter.weight_class?.name || 'Professional Boxer' }}</p>
          <h1 class="mt-2 text-4xl font-black leading-tight text-white md:text-5xl">{{ fighter.display_name }}</h1>
          <p v-if="fighter.ring_name" class="mt-1 text-lg text-zinc-400">"{{ fighter.ring_name }}"</p>

          <div class="mt-5 flex flex-wrap gap-2">
            <span class="bd-chip bd-chip-red">{{ fighter.active ? 'Active' : 'Inactive' }}</span>
            <span class="bd-chip">{{ fighter.record }}</span>
            <span class="bd-chip">{{ koRate(fighter.wins, fighter.knockouts) }} KO Rate</span>
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

        <div class="rounded-lg border border-white/10 bg-black/40 p-5 backdrop-blur">
          <h2 class="flex items-center gap-2 font-black text-white">
            <TrophyIcon class="size-4 text-yellow-400" />
            World Titles
          </h2>
          <div class="mt-4 space-y-3">
            <div v-for="title in fighter.titles" :key="title.belt.name" class="rounded-lg border border-yellow-500/15 bg-yellow-500/5 p-3">
              <p class="font-black text-white">{{ title.belt.organisation }}</p>
              <p class="text-sm text-zinc-400">{{ title.belt.weight_class }}</p>
              <p class="mt-1 text-xs text-zinc-500">Since {{ formatDate(title.reign_started_on) }}</p>
            </div>
            <p v-if="!fighter.titles.length" class="rounded-lg border border-dashed border-white/10 p-4 text-sm text-zinc-500">No active title data yet.</p>
          </div>
        </div>
      </div>
    </section>

    <nav class="bd-panel flex gap-2 overflow-x-auto p-2">
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
      <div class="bd-panel p-5">
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
      <div v-for="title in fighter.titles" :key="title.belt.name" class="bd-panel p-4">
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
    return () => h('div', { class: 'rounded-lg border border-white/10 bg-white/[0.04] p-3' }, [
      h('dt', { class: 'text-xs font-black uppercase text-zinc-500' }, props.label),
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

    return () => h('div', { class: 'bd-panel p-5' }, [
      h('h2', { class: 'font-black text-white' }, props.title),
      props.fight
        ? h(RouterLink, { to: href.value, class: 'bd-card-hover mt-4 grid grid-cols-[1fr_120px] gap-4 rounded-lg border border-white/10 bg-white/[0.04] p-3' }, () => [
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
