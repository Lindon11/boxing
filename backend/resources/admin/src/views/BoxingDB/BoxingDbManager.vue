<template>
  <div class="space-y-6">
    <div class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-6">
      <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <p class="text-sm font-semibold text-amber-400">BoxingDB Admin</p>
          <h1 class="text-2xl font-bold text-white mt-1">{{ config.label }}</h1>
          <p class="text-sm text-slate-400 mt-1">{{ config.description }}</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
            <input
              v-model="search"
              type="text"
              :placeholder="`Search ${config.label.toLowerCase()}...`"
              class="w-full sm:w-80 pl-10 pr-4 py-3 bg-slate-900/60 border border-slate-700/60 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50"
              @input="queueLoad"
            >
          </div>
          <button
            class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-semibold hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg shadow-amber-500/20"
            @click="openCreate"
          >
            <PlusIcon class="w-5 h-5" />
            Add {{ config.singular }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-8">
      <div class="flex items-center justify-center gap-3 text-slate-400">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-500"></div>
        Loading {{ config.label.toLowerCase() }}...
      </div>
    </div>

    <div v-else-if="error" class="rounded-2xl bg-red-500/10 border border-red-500/30 p-6">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-red-300 font-medium">{{ error }}</p>
        <button class="px-4 py-2 rounded-xl bg-red-500/20 text-red-200 hover:bg-red-500/30" @click="loadItems">
          Retry
        </button>
      </div>
    </div>

    <div v-else class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-900/70 border-b border-slate-700/60">
            <tr>
              <th v-for="column in config.columns" :key="column.key" class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                {{ column.label }}
              </th>
              <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wide text-slate-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/50">
            <tr v-for="item in items" :key="item.id" class="hover:bg-slate-700/25 transition-colors">
              <td v-for="column in config.columns" :key="column.key" class="px-5 py-4 text-sm text-slate-300 align-top">
                <span v-if="column.badge" class="inline-flex px-2 py-1 rounded-lg bg-amber-500/15 text-amber-300 text-xs font-bold">
                  {{ displayValue(item, column) }}
                </span>
                <span v-else>{{ displayValue(item, column) }}</span>
              </td>
              <td class="px-5 py-4">
                <div class="flex items-center justify-end gap-2">
                  <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-500/15 text-amber-300 hover:bg-amber-500/25" @click="openEdit(item)">
                    Edit
                  </button>
                  <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-500/15 text-red-300 hover:bg-red-500/25" @click="deleteItem(item)">
                    Delete
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="items.length === 0">
              <td :colspan="config.columns.length + 1" class="px-5 py-10 text-center text-slate-500">
                No {{ config.label.toLowerCase() }} found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="pagination" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-sm text-slate-400">
        Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total }} results
      </p>
      <div class="flex items-center gap-2">
        <button :disabled="pagination.current_page === 1" class="pager-btn" @click="goToPage(pagination.current_page - 1)">Previous</button>
        <span class="text-sm text-slate-400 px-3">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
        <button :disabled="pagination.current_page === pagination.last_page" class="pager-btn" @click="goToPage(pagination.current_page + 1)">Next</button>
      </div>
    </div>

    <div v-if="modalOpen" class="fixed inset-0 z-[80] overflow-y-auto">
      <div class="min-h-screen p-4 flex items-start justify-center lg:items-center">
        <button class="fixed inset-0 bg-black/70 backdrop-blur-sm" type="button" @click="closeModal" />
        <form class="relative w-full max-w-6xl rounded-2xl bg-slate-900 border border-slate-700 shadow-2xl overflow-hidden" @submit.prevent="saveItem">
          <div class="sticky top-0 z-10 flex items-center justify-between gap-4 border-b border-slate-700 bg-slate-900/95 p-5">
            <div>
              <h2 class="text-xl font-bold text-white">{{ editingItem ? 'Edit' : 'Create' }} {{ config.singular }}</h2>
              <p class="text-sm text-slate-400">Required fields are validated by the Laravel admin API.</p>
            </div>
            <button class="text-slate-400 hover:text-white" type="button" @click="closeModal">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>

          <div class="p-5 space-y-6 max-h-[75vh] overflow-y-auto">
            <div v-if="formError" class="rounded-xl bg-red-500/10 border border-red-500/30 p-4 text-sm text-red-200">
              {{ formError }}
            </div>
            <div v-if="Object.keys(validationErrors).length" class="rounded-xl bg-red-500/10 border border-red-500/30 p-4 text-sm text-red-200">
              <p class="font-bold mb-2">Please fix these fields:</p>
              <ul class="list-disc pl-5 space-y-1">
                <li v-for="(messages, key) in validationErrors" :key="key">{{ messages[0] }}</li>
              </ul>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
              <div v-for="field in config.fields" :key="field.key" :class="field.type === 'textarea' ? 'md:col-span-2 xl:col-span-3' : ''">
                <label class="block text-sm font-semibold text-slate-300 mb-2">{{ field.label }}</label>

                <textarea
                  v-if="field.type === 'textarea'"
                  v-model="form[field.key]"
                  rows="4"
                  :placeholder="field.placeholder"
                  class="form-control"
                />

                <select
                  v-else-if="field.type === 'select'"
                  v-model="form[field.key]"
                  class="form-control"
                >
                  <option :value="null">{{ field.placeholder || 'Select...' }}</option>
                  <option v-for="option in fieldOptions(field)" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>

                <label v-else-if="field.type === 'checkbox'" class="flex items-center gap-3 h-12 px-4 rounded-xl bg-slate-950/50 border border-slate-700/60">
                  <input v-model="form[field.key]" type="checkbox" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-amber-500">
                  <span class="text-sm text-slate-300">{{ field.help || 'Enabled' }}</span>
                </label>

                <input
                  v-else
                  v-model="form[field.key]"
                  :type="field.type || 'text'"
                  :placeholder="field.placeholder"
                  class="form-control"
                >
              </div>
            </div>

            <section v-if="resource === 'events'" class="rounded-2xl border border-slate-700/60 bg-slate-950/40 p-4">
              <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                  <h3 class="text-lg font-bold text-white">Fight Card</h3>
                  <p class="text-sm text-slate-400">Add, edit, or reorder bouts for this event.</p>
                </div>
                <button type="button" class="px-3 py-2 rounded-xl bg-amber-500/15 text-amber-300 text-sm font-semibold hover:bg-amber-500/25" @click="addFightRow">
                  Add Bout
                </button>
              </div>

              <div class="space-y-3">
                <div v-for="(fight, index) in eventFights" :key="fight.local_key" class="rounded-xl border border-slate-700/60 bg-slate-900/80 p-4">
                  <div class="flex items-center justify-between gap-3 mb-4">
                    <p class="font-semibold text-white">Bout {{ index + 1 }}</p>
                    <button type="button" class="text-sm text-red-300 hover:text-red-200" @click="removeFightRow(index)">Remove</button>
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                    <AdminSelect v-model="fight.red_corner_fighter_id" label="Fighter A" :options="selectOptions.fighters" />
                    <AdminSelect v-model="fight.blue_corner_fighter_id" label="Fighter B" :options="selectOptions.fighters" />
                    <AdminSelect v-model="fight.winner_fighter_id" label="Winner" :options="selectOptions.fighters" />
                    <AdminSelect v-model="fight.weight_class_id" label="Weight" :options="selectOptions.weight_classes" />
                    <AdminSelect v-model="fight.result_method_id" label="Method" :options="selectOptions.result_methods" />
                    <AdminSelect v-model="fight.billing" label="Billing" :options="selectOptions.fight_billings" />
                    <AdminSelect v-model="fight.status" label="Status" :options="selectOptions.fight_statuses" />
                    <AdminInput v-model="fight.title" label="Title / Stakes" />
                    <AdminInput v-model="fight.bout_order" label="Order" type="number" />
                    <AdminInput v-model="fight.scheduled_rounds" label="Rounds" type="number" />
                    <AdminInput v-model="fight.completed_rounds" label="Result Round" type="number" />
                    <AdminInput v-model="fight.result_time" label="Result Time" placeholder="1:23" />
                    <label class="flex items-center gap-3 h-12 px-4 rounded-xl bg-slate-950/50 border border-slate-700/60">
                      <input v-model="fight.is_title_fight" type="checkbox" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-amber-500">
                      <span class="text-sm text-slate-300">Title fight</span>
                    </label>
                    <div class="md:col-span-2 xl:col-span-3">
                      <AdminInput v-model="fight.result_notes" label="Result Notes" />
                    </div>
                  </div>
                </div>
                <p v-if="eventFights.length === 0" class="text-sm text-slate-500">No bouts added yet.</p>
              </div>
            </section>
          </div>

          <div class="sticky bottom-0 flex items-center justify-end gap-3 border-t border-slate-700 bg-slate-900/95 p-5">
            <button type="button" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700" @click="closeModal">
              Cancel
            </button>
            <button type="submit" :disabled="saving" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-semibold hover:from-amber-600 hover:to-orange-700 disabled:opacity-60">
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  MagnifyingGlassIcon,
  PlusIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const toast = useToast()

const resource = computed(() => route.params.resource || 'fighters')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const formError = ref('')
const search = ref('')
const items = ref([])
const pagination = ref(null)
const page = ref(1)
const modalOpen = ref(false)
const editingItem = ref(null)
const validationErrors = ref({})
const form = reactive({})
const eventFights = ref([])
const options = ref({})
let searchTimer = null

const AdminInput = defineComponent({
  props: ['modelValue', 'label', 'type', 'placeholder'],
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    return () => h('label', { class: 'block' }, [
      h('span', { class: 'block text-sm font-semibold text-slate-300 mb-2' }, props.label),
      h('input', {
        value: props.modelValue ?? '',
        type: props.type || 'text',
        placeholder: props.placeholder || '',
        class: 'form-control',
        onInput: event => emit('update:modelValue', event.target.value),
      }),
    ])
  },
})

const AdminSelect = defineComponent({
  props: ['modelValue', 'label', 'options'],
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    return () => h('label', { class: 'block' }, [
      h('span', { class: 'block text-sm font-semibold text-slate-300 mb-2' }, props.label),
      h('select', {
        value: props.modelValue ?? '',
        class: 'form-control',
        onChange: event => emit('update:modelValue', event.target.value ? Number(event.target.value) || event.target.value : null),
      }, [
        h('option', { value: '' }, 'Select...'),
        ...(props.options || []).map(option => h('option', { value: option.value }, option.label)),
      ]),
    ])
  },
})

const resources = {
  fighters: {
    label: 'Fighters',
    singular: 'Fighter',
    description: 'Manage boxer profiles, records, photos, stance, country, and biography.',
    columns: [
      { key: 'display_name', label: 'Name' },
      { key: 'country.code', label: 'Country', badge: true },
      { key: 'weight_class.name', label: 'Weight' },
      { key: 'record', label: 'Record' },
      { key: 'knockouts', label: 'KOs' },
    ],
    fields: [
      field('first_name', 'First Name'), field('last_name', 'Last Name'), field('display_name', 'Display Name'),
      field('slug', 'Slug'), field('ring_name', 'Ring Name'), selectField('country_id', 'Country', 'countries'),
      selectField('stance_id', 'Stance', 'stances'), selectField('weight_class_id', 'Weight Class', 'weight_classes'),
      field('birth_date', 'Birth Date', 'date'), field('birth_place', 'Birth Place'), field('residence', 'Residence'),
      field('height_cm', 'Height CM', 'number'), field('reach_cm', 'Reach CM', 'number'),
      field('wins', 'Wins', 'number'), field('losses', 'Losses', 'number'), field('draws', 'Draws', 'number'),
      field('no_contests', 'No Contests', 'number'), field('knockouts', 'Knockouts', 'number'),
      field('debut_date', 'Debut Date', 'date'), field('photo_url', 'Photo URL', 'url'), checkboxField('active', 'Active'),
      field('bio', 'Bio', 'textarea'),
    ],
    defaults: { wins: 0, losses: 0, draws: 0, no_contests: 0, knockouts: 0, active: true },
  },
  events: {
    label: 'Events',
    singular: 'Event',
    description: 'Manage fight-night details, posters, broadcasts, venues, promoters, and embedded fight cards.',
    columns: [
      { key: 'name', label: 'Event' },
      { key: 'status', label: 'Status', badge: true },
      { key: 'event_date', label: 'Date', type: 'date' },
      { key: 'venue.name', label: 'Venue' },
      { key: 'promoter.name', label: 'Promoter' },
    ],
    fields: [
      field('name', 'Name'), field('slug', 'Slug'), field('subtitle', 'Subtitle'),
      selectField('status', 'Status', 'event_statuses'), field('event_date', 'Event Date', 'datetime-local'),
      field('ring_walks_at', 'Ring Walks At', 'datetime-local'), selectField('venue_id', 'Venue', 'venues'),
      selectField('promoter_id', 'Promoter', 'promoters'), field('poster_url', 'Poster URL', 'url'),
      field('hero_image_url', 'Hero Image URL', 'url'), field('broadcast_notes', 'Broadcast Notes'),
      field('ticket_url', 'Ticket URL', 'url'),
    ],
    defaults: { status: 'upcoming' },
  },
  fights: {
    label: 'Fights',
    singular: 'Fight',
    description: 'Manage individual bouts, results, title-fight status, rounds, and result time.',
    columns: [
      { key: 'event.name', label: 'Event' },
      { key: 'red_corner.display_name', label: 'Fighter A' },
      { key: 'blue_corner.display_name', label: 'Fighter B' },
      { key: 'winner.display_name', label: 'Winner' },
      { key: 'status', label: 'Status', badge: true },
    ],
    fields: fightFields(),
    defaults: { billing: 'undercard', bout_order: 1, scheduled_rounds: 12, status: 'scheduled', is_title_fight: false },
  },
  promoters: simpleResource('Promotions', 'Promotion', 'Manage promoters and their websites.', [
    selectField('country_id', 'Country', 'countries'), field('name', 'Name'), field('slug', 'Slug'), field('website_url', 'Website URL', 'url'),
  ], [{ key: 'name', label: 'Name' }, { key: 'country.code', label: 'Country', badge: true }, { key: 'website_url', label: 'Website' }]),
  venues: simpleResource('Venues', 'Venue', 'Manage arenas, cities, countries, and capacity.', [
    selectField('country_id', 'Country', 'countries'), field('name', 'Name'), field('slug', 'Slug'), field('city', 'City'),
    field('region', 'Region'), field('address', 'Address'), field('capacity', 'Capacity', 'number'),
  ], [{ key: 'name', label: 'Name' }, { key: 'city', label: 'City' }, { key: 'country.code', label: 'Country', badge: true }, { key: 'capacity', label: 'Capacity' }]),
  'weight-classes': simpleResource('Weight Classes', 'Weight Class', 'Manage boxing divisions and limits.', [
    field('name', 'Name'), field('slug', 'Slug'), field('limit_pounds', 'Limit Pounds', 'number'), field('limit_kg', 'Limit KG', 'number'), field('sort_order', 'Sort Order', 'number'),
  ], [{ key: 'name', label: 'Name' }, { key: 'limit_pounds', label: 'Lbs' }, { key: 'limit_kg', label: 'KG' }, { key: 'sort_order', label: 'Sort' }], { sort_order: 0 }),
  organisations: simpleResource('Organisations', 'Organisation', 'Manage WBC, WBA, IBF, WBO, The Ring, and other bodies.', [
    field('name', 'Name'), field('abbreviation', 'Abbreviation'), field('slug', 'Slug'), field('logo_url', 'Logo URL', 'url'),
  ], [{ key: 'abbreviation', label: 'Abbr', badge: true }, { key: 'name', label: 'Name' }, { key: 'logo_url', label: 'Logo' }]),
  belts: simpleResource('Belts', 'Belt', 'Manage titles, current champions, and active reigns.', [
    selectField('organisation_id', 'Organisation', 'organisations'), selectField('weight_class_id', 'Weight Class', 'weight_classes'),
    field('name', 'Name'), field('slug', 'Slug'), checkboxField('active', 'Active'),
    selectField('current_champion_id', 'Current Champion', 'fighters'), field('reign_started_on', 'Reign Started', 'date'), field('reign_result', 'Reign Note'),
  ], [{ key: 'organisation.abbreviation', label: 'Org', badge: true }, { key: 'weight_class.name', label: 'Weight' }, { key: 'name', label: 'Title' }, { key: 'current_reign.0.fighter.display_name', label: 'Champion' }], { active: true }),
  'belt-history': simpleResource('Belt History', 'Belt History Entry', 'Manage current, former, and vacated title reigns.', [
    selectField('belt_id', 'Belt', 'belts'), selectField('fighter_id', 'Champion', 'fighters'), selectField('event_id', 'Event', 'events'),
    selectField('fight_id', 'Fight', 'fights'), field('reign_started_on', 'Started', 'date'), field('reign_ended_on', 'Ended', 'date'),
    selectField('status', 'Status', 'belt_statuses'), field('result', 'Result / Note'),
  ], [{ key: 'belt.name', label: 'Belt' }, { key: 'fighter.display_name', label: 'Fighter' }, { key: 'status', label: 'Status', badge: true }, { key: 'reign_started_on', label: 'Started', type: 'date' }], { status: 'current' }),
  rankings: simpleResource('Rankings', 'Ranking', 'Manage rankings by organisation, weight class, date, and points.', [
    selectField('organisation_id', 'Organisation', 'organisations'), selectField('weight_class_id', 'Weight Class', 'weight_classes'),
    selectField('fighter_id', 'Fighter', 'fighters'), field('rank', 'Rank', 'number'), field('points', 'Points', 'number'), field('ranked_on', 'Ranked On', 'date'),
  ], [{ key: 'rank', label: '#' }, { key: 'organisation.abbreviation', label: 'Org', badge: true }, { key: 'weight_class.name', label: 'Weight' }, { key: 'fighter.display_name', label: 'Fighter' }, { key: 'points', label: 'Points' }], { rank: 1, points: 0, ranked_on: new Date().toISOString().slice(0, 10) }),
  broadcasters: simpleResource('Broadcasters', 'Broadcaster', 'Manage broadcast partners and stream/TV links.', [
    selectField('country_id', 'Country', 'countries'), field('name', 'Name'), field('slug', 'Slug'), field('logo_url', 'Logo URL', 'url'), field('website_url', 'Website URL', 'url'),
  ], [{ key: 'name', label: 'Name' }, { key: 'country.code', label: 'Country', badge: true }, { key: 'website_url', label: 'Website' }]),
  'event-broadcasts': simpleResource('Event Broadcasts', 'Event Broadcast', 'Manage which broadcasters carry each event by region/platform.', [
    selectField('event_id', 'Event', 'events'), selectField('broadcaster_id', 'Broadcaster', 'broadcasters'), field('region', 'Region'),
    field('platform', 'Platform'), checkboxField('is_ppv', 'PPV'), field('details', 'Details'),
  ], [{ key: 'event.name', label: 'Event' }, { key: 'broadcaster.name', label: 'Broadcaster' }, { key: 'region', label: 'Region', badge: true }, { key: 'platform', label: 'Platform' }], { region: 'Global', is_ppv: false }),
  media: simpleResource('Media & Posters', 'Media Item', 'Attach posters, galleries, and images to fighters or events.', [
    selectField('mediable_type', 'Type', 'media_types'), selectField('mediable_id', 'Parent', 'mediable'), field('collection', 'Collection'),
    field('title', 'Title'), field('url', 'Image URL', 'url'), field('credit', 'Credit'), field('sort_order', 'Sort Order', 'number'),
  ], [{ key: 'collection', label: 'Collection', badge: true }, { key: 'title', label: 'Title' }, { key: 'mediable_label', label: 'Attached To' }, { key: 'url', label: 'URL' }], { collection: 'gallery', sort_order: 0 }),
}

const config = computed(() => resources[resource.value] || resources.fighters)

const selectOptions = computed(() => ({
  countries: makeOptions(options.value.countries, 'name'),
  stances: makeOptions(options.value.stances, 'name'),
  fighters: makeOptions(options.value.fighters, 'display_name'),
  events: makeOptions(options.value.events, 'name'),
  fights: makeOptions(options.value.fights, 'name'),
  weight_classes: makeOptions(options.value.weight_classes, 'name'),
  organisations: makeOptions(options.value.organisations, row => `${row.abbreviation} - ${row.name}`),
  promoters: makeOptions(options.value.promoters, 'name'),
  venues: makeOptions(options.value.venues, row => `${row.name} (${row.city})`),
  result_methods: makeOptions(options.value.result_methods, row => `${row.abbreviation} - ${row.name}`),
  broadcasters: makeOptions(options.value.broadcasters, 'name'),
  belts: makeOptions(options.value.belts, row => row.label || row.name),
  media_types: options.value.media_types || [],
  event_statuses: valueOptions(options.value.event_statuses),
  fight_statuses: valueOptions(options.value.fight_statuses),
  fight_billings: valueOptions(options.value.fight_billings),
  belt_statuses: valueOptions(['current', 'former', 'vacated']),
  mediable: mediableOptions(),
}))

function field(key, label, type = 'text', placeholder = '') {
  return { key, label, type, placeholder }
}

function selectField(key, label, source, placeholder = 'Select...') {
  return { key, label, type: 'select', source, placeholder }
}

function checkboxField(key, label, help = '') {
  return { key, label, type: 'checkbox', help: help || label }
}

function fightFields() {
  return [
    selectField('event_id', 'Event', 'events'), selectField('red_corner_fighter_id', 'Fighter A', 'fighters'),
    selectField('blue_corner_fighter_id', 'Fighter B', 'fighters'), selectField('winner_fighter_id', 'Winner', 'fighters'),
    selectField('weight_class_id', 'Weight Class', 'weight_classes'), selectField('result_method_id', 'Result Method', 'result_methods'),
    field('title', 'Title / Stakes'), selectField('billing', 'Billing', 'fight_billings'), field('bout_order', 'Bout Order', 'number'),
    field('scheduled_rounds', 'Scheduled Rounds', 'number'), field('completed_rounds', 'Result Round', 'number'), field('result_time', 'Result Time'),
    checkboxField('is_title_fight', 'Title Fight'), selectField('status', 'Status', 'fight_statuses'), field('fight_date', 'Fight Date', 'datetime-local'),
    field('result_notes', 'Result Notes', 'textarea'),
  ]
}

function simpleResource(label, singular, description, fields, columns, defaults = {}) {
  return { label, singular, description, fields, columns, defaults }
}

function makeOptions(rows = [], labelKey = 'name') {
  return rows.map(row => ({
    value: row.id,
    label: typeof labelKey === 'function' ? labelKey(row) : row[labelKey],
  }))
}

function valueOptions(values = []) {
  return values.map(value => ({ value, label: String(value).replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()) }))
}

function mediableOptions() {
  if (form.mediable_type === 'event') return makeOptions(options.value.events, 'name')
  return makeOptions(options.value.fighters, 'display_name')
}

function fieldOptions(field) {
  return selectOptions.value[field.source] || []
}

function getValue(item, path) {
  return path.split('.').reduce((value, key) => value?.[key], item)
}

function displayValue(item, column) {
  const value = getValue(item, column.key)
  if (column.type === 'date' && value) return new Date(value).toLocaleDateString()
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (value === null || value === undefined || value === '') return '-'
  return value
}

function normalizeInputValue(value, type) {
  if (value === null || value === undefined) return null
  if (type === 'datetime-local' && typeof value === 'string') return value.slice(0, 16)
  if (type === 'date' && typeof value === 'string') return value.slice(0, 10)
  return value
}

function resetForm(item = null) {
  Object.keys(form).forEach(key => delete form[key])
  const defaults = config.value.defaults || {}

  config.value.fields.forEach(field => {
    form[field.key] = normalizeInputValue(item?.[field.key] ?? defaults[field.key] ?? (field.type === 'checkbox' ? false : null), field.type)
  })

  if (resource.value === 'events') {
    eventFights.value = (item?.fights || []).map(fight => ({
      ...fightDefaults(),
      ...fight,
      result_time: fight.result_time || null,
      local_key: fight.id || crypto.randomUUID(),
    }))
  } else {
    eventFights.value = []
  }
}

function fightDefaults() {
  return {
    local_key: crypto.randomUUID(),
    red_corner_fighter_id: null,
    blue_corner_fighter_id: null,
    winner_fighter_id: null,
    weight_class_id: null,
    result_method_id: null,
    title: '',
    billing: 'undercard',
    bout_order: eventFights.value.length + 1,
    scheduled_rounds: 12,
    completed_rounds: null,
    result_time: '',
    is_title_fight: false,
    status: 'scheduled',
    result_notes: '',
  }
}

async function loadOptions() {
  const response = await api.get('/admin/boxingdb/options')
  options.value = response.data
}

async function loadItems() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get(`/admin/boxingdb/${resource.value}`, {
      params: { page: page.value, q: search.value },
    })
    items.value = response.data.items.data
    pagination.value = response.data.items
  } catch {
    error.value = `Could not load ${config.value.label.toLowerCase()}.`
  } finally {
    loading.value = false
  }
}

function queueLoad() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    loadItems()
  }, 250)
}

function goToPage(nextPage) {
  page.value = nextPage
  loadItems()
}

function openCreate() {
  editingItem.value = null
  validationErrors.value = {}
  formError.value = ''
  resetForm()
  modalOpen.value = true
}

function openEdit(item) {
  editingItem.value = item
  validationErrors.value = {}
  formError.value = ''
  resetForm(item)
  modalOpen.value = true
}

function closeModal() {
  modalOpen.value = false
}

function addFightRow() {
  eventFights.value.push(fightDefaults())
}

function removeFightRow(index) {
  eventFights.value.splice(index, 1)
}

function cleanPayload() {
  const payload = {}
  config.value.fields.forEach(field => {
    let value = form[field.key]
    if (value === '') value = null
    payload[field.key] = value
  })

  if (resource.value === 'events') {
    payload.fights = eventFights.value.map(({ local_key, red_corner, blue_corner, winner, weight_class, result_method, ...fight }) => fight)
  }

  return payload
}

async function saveItem() {
  saving.value = true
  validationErrors.value = {}
  formError.value = ''

  try {
    const payload = cleanPayload()
    if (editingItem.value) {
      await api.put(`/admin/boxingdb/${resource.value}/${editingItem.value.id}`, payload)
    } else {
      await api.post(`/admin/boxingdb/${resource.value}`, payload)
    }
    toast.success(`${config.value.singular} saved.`)
    modalOpen.value = false
    await loadOptions()
    await loadItems()
  } catch (err) {
    validationErrors.value = err.response?.data?.errors || {}
    formError.value = err.response?.data?.message || `Could not save ${config.value.singular.toLowerCase()}.`
  } finally {
    saving.value = false
  }
}

async function deleteItem(item) {
  if (!window.confirm(`Delete ${config.value.singular.toLowerCase()} #${item.id}?`)) return

  try {
    await api.delete(`/admin/boxingdb/${resource.value}/${item.id}`)
    toast.success(`${config.value.singular} deleted.`)
    await loadOptions()
    await loadItems()
  } catch (err) {
    toast.error(err.response?.data?.message || `Could not delete ${config.value.singular.toLowerCase()}.`)
  }
}

watch(resource, async () => {
  page.value = 1
  search.value = ''
  modalOpen.value = false
  await loadOptions()
  await loadItems()
})

onMounted(async () => {
  await loadOptions()
  await loadItems()
})
</script>

<style scoped>
.form-control {
  @apply w-full px-4 py-3 bg-slate-950/50 border border-slate-700/60 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all;
}

.pager-btn {
  @apply px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-700 text-slate-300 hover:bg-slate-600 disabled:bg-slate-800 disabled:text-slate-600 disabled:cursor-not-allowed transition-colors;
}
</style>
