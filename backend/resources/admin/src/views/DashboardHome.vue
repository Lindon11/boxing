<template>
  <div class="space-y-6">
    <section class="admin-panel overflow-hidden">
      <div class="grid gap-6 p-5 xl:grid-cols-[minmax(0,1fr)_420px]">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.24em] text-red-400">BoxingDB Control Room</p>
          <h1 class="mt-2 text-3xl font-black leading-tight text-white md:text-4xl">Manage the boxing database</h1>
          <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-400">
            Add fighters, build events, maintain rankings, attach posters, and run Selenium scraping from one focused admin area.
          </p>
          <div class="mt-5 flex flex-wrap gap-3">
            <button class="admin-primary px-4 py-3" type="button" @click="router.push('/boxingdb/fighters')">
              <UsersIcon class="h-5 w-5" />
              Manage Fighters
            </button>
            <button class="admin-secondary px-4 py-3" type="button" @click="router.push('/boxingdb/events')">
              <CalendarIcon class="h-5 w-5" />
              Build Events
            </button>
            <button class="admin-secondary px-4 py-3" type="button" @click="router.push('/boxingdb/scraper')">
              <CloudArrowDownIcon class="h-5 w-5" />
              Run Scraper
            </button>
          </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
          <div v-for="stat in stats" :key="stat.label" class="rounded-lg border border-slate-700/70 bg-slate-950/60 p-4">
            <component :is="stat.icon" class="h-6 w-6 text-red-400" />
            <p class="mt-3 text-2xl font-black text-white">{{ stat.value }}</p>
            <p class="text-sm text-slate-400">{{ stat.label }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_380px]">
      <div class="admin-panel p-4">
        <div class="mb-4 flex items-center justify-between gap-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-red-400">Data Entry</p>
            <h2 class="text-xl font-black text-white">Core Tables</h2>
          </div>
        </div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <button
            v-for="item in primaryResources"
            :key="item.path"
            class="group rounded-lg border border-slate-700/70 bg-slate-950/55 p-4 text-left transition hover:border-red-500/50 hover:bg-slate-900"
            type="button"
            @click="router.push(item.path)"
          >
            <component :is="item.icon" class="h-6 w-6 text-slate-400 transition group-hover:text-red-400" />
            <p class="mt-3 font-bold text-white">{{ item.label }}</p>
            <p class="mt-1 text-xs leading-5 text-slate-500">{{ item.description }}</p>
          </button>
        </div>
      </div>

      <aside class="space-y-4">
        <div class="admin-panel p-4">
          <h2 class="text-lg font-black text-white">Automation</h2>
          <p class="mt-2 text-sm leading-6 text-slate-400">Use Selenium scraping to collect JavaScript-rendered boxing data, then import it into the same CRUD tables.</p>
          <button class="admin-primary mt-4 w-full px-4 py-3" type="button" @click="router.push('/boxingdb/scraper')">
            <CloudArrowDownIcon class="h-5 w-5" />
            Open Scraper
          </button>
        </div>

        <div class="admin-panel p-4">
          <h2 class="text-lg font-black text-white">Publishing Checklist</h2>
          <div class="mt-4 space-y-3">
            <div v-for="item in checklist" :key="item" class="flex items-center gap-3 rounded-lg border border-slate-700/70 bg-slate-950/55 p-3">
              <CheckCircleIcon class="h-5 w-5 text-emerald-400" />
              <span class="text-sm text-slate-300">{{ item }}</span>
            </div>
          </div>
        </div>
      </aside>
    </section>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import {
  CalendarIcon,
  CheckCircleIcon,
  CloudArrowDownIcon,
  PhotoIcon,
  RadioIcon,
  ScaleIcon,
  ShieldCheckIcon,
  TrophyIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'

const router = useRouter()

const stats = [
  { label: 'Fighters table', value: 'CRUD', icon: UsersIcon },
  { label: 'Event builder', value: 'Cards', icon: CalendarIcon },
  { label: 'Rankings manager', value: 'Live', icon: ScaleIcon },
  { label: 'Scraper automation', value: 'Selenium', icon: CloudArrowDownIcon },
]

const primaryResources = [
  { label: 'Fighters', path: '/boxingdb/fighters', icon: UsersIcon, description: 'Profiles, records, stances, countries, photos, and bios.' },
  { label: 'Events', path: '/boxingdb/events', icon: CalendarIcon, description: 'Fight nights, venues, posters, broadcasts, and fight cards.' },
  { label: 'Fights', path: '/boxingdb/fights', icon: TrophyIcon, description: 'Bouts, winners, methods, rounds, times, and title-fight status.' },
  { label: 'Rankings', path: '/boxingdb/rankings', icon: ScaleIcon, description: 'Manage organisation and weight-class rankings.' },
  { label: 'Belts', path: '/boxingdb/belts', icon: ShieldCheckIcon, description: 'Current champions, title names, and active reigns.' },
  { label: 'Media', path: '/boxingdb/media', icon: PhotoIcon, description: 'Poster URLs, fighter galleries, image credits, and ordering.' },
  { label: 'Broadcasters', path: '/boxingdb/broadcasters', icon: RadioIcon, description: 'Networks, streaming platforms, regions, and event coverage.' },
]

const checklist = [
  'Add fighter records and profile photos',
  'Build event fight cards before publishing',
  'Attach poster and hero image URLs',
  'Update rankings and title history',
]
</script>
