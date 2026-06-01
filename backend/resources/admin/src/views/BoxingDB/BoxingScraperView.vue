<template>
  <div class="space-y-6">
    <section class="rounded-2xl border border-slate-700/60 bg-slate-900/80 p-5 shadow-xl shadow-black/20">
      <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-red-400">BoxingDB Automation</p>
          <h1 class="mt-2 text-2xl font-bold text-white">Selenium Scraper</h1>
          <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-400">
            Collect JavaScript-rendered fighter, event, ranking, media, and broadcast data into normalized JSON, then import it into the BoxingDB tables.
          </p>
        </div>
        <div class="grid gap-2 sm:grid-cols-2 xl:min-w-[460px]">
          <div class="rounded-xl border border-slate-700/60 bg-slate-950/70 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Python CLI</p>
            <p :class="['mt-1 text-sm font-semibold', status?.python?.ok ? 'text-emerald-300' : 'text-red-300']">
              {{ status?.python?.ok ? 'Ready' : 'Needs setup' }}
            </p>
          </div>
          <div class="rounded-xl border border-slate-700/60 bg-slate-950/70 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Latest Imports</p>
            <p class="mt-1 text-sm font-semibold text-white">{{ status?.latest_imports?.length || 0 }} files</p>
          </div>
        </div>
      </div>

      <div v-if="status?.python && !status.python.ok" class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-100">
        <p class="font-semibold">Python scraper dependencies are not ready.</p>
        <pre class="mt-2 whitespace-pre-wrap text-xs text-amber-100/80">{{ status.python.message }}</pre>
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
      <div class="rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
        <div class="flex flex-col gap-3 border-b border-slate-700/60 p-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-white">Source Configuration</h2>
            <p class="mt-1 text-sm text-slate-400">Tune selectors here, then run a Selenium collect.</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-lg border border-slate-600 px-3 py-2 text-sm font-medium text-slate-200 hover:border-slate-500 hover:bg-slate-800"
              @click="loadTemplate"
            >
              Load Template
            </button>
            <button
              type="button"
              class="rounded-lg border border-slate-600 px-3 py-2 text-sm font-medium text-slate-200 hover:border-slate-500 hover:bg-slate-800"
              @click="formatSources"
            >
              Format JSON
            </button>
          </div>
        </div>

        <div class="space-y-4 p-4">
          <textarea
            v-model="sourcesJson"
            spellcheck="false"
            class="min-h-[420px] w-full rounded-xl border border-slate-700 bg-slate-950 p-4 font-mono text-sm leading-6 text-slate-100 outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-500/20"
          />

          <div class="grid gap-3 lg:grid-cols-4">
            <label class="space-y-1">
              <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Browser</span>
              <select v-model="form.browser" class="field">
                <option value="chrome">Chrome</option>
                <option value="firefox">Firefox</option>
              </select>
            </label>
            <label class="space-y-1">
              <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Wait Seconds</span>
              <input v-model.number="form.wait_seconds" type="number" min="1" max="120" class="field" />
            </label>
            <label class="space-y-1">
              <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Limit</span>
              <input v-model.number="form.limit" type="number" min="1" placeholder="All" class="field" />
            </label>
            <label class="space-y-1">
              <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Slow Mo</span>
              <input v-model.number="form.slow_mo" type="number" min="0" step="0.5" class="field" />
            </label>
          </div>

          <div class="flex flex-wrap gap-4 text-sm text-slate-300">
            <label class="inline-flex items-center gap-2">
              <input v-model="form.save_raw" type="checkbox" class="rounded border-slate-600 bg-slate-950 text-red-600 focus:ring-red-500" />
              Save rendered HTML
            </label>
            <label class="inline-flex items-center gap-2">
              <input v-model="form.save_screenshots" type="checkbox" class="rounded border-slate-600 bg-slate-950 text-red-600 focus:ring-red-500" />
              Save screenshots
            </label>
            <label class="inline-flex items-center gap-2">
              <input v-model="form.replace_event_fights" type="checkbox" class="rounded border-slate-600 bg-slate-950 text-red-600 focus:ring-red-500" />
              Replace event fight cards on import
            </label>
          </div>

          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-red-950/30 hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="collecting"
              @click="collect"
            >
              {{ collecting ? 'Collecting...' : 'Run Selenium Collect' }}
            </button>
            <button
              type="button"
              class="rounded-lg border border-emerald-500/50 px-4 py-2 text-sm font-semibold text-emerald-200 hover:bg-emerald-500/10 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="importing || !lastOutputPath"
              @click="importLatest(true)"
            >
              Dry Run Import
            </button>
            <button
              type="button"
              class="rounded-lg border border-slate-600 px-4 py-2 text-sm font-semibold text-slate-100 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="importing || !lastOutputPath"
              @click="importLatest(false)"
            >
              Import Latest
            </button>
          </div>
        </div>
      </div>

      <aside class="space-y-6">
        <div class="rounded-2xl border border-slate-700/60 bg-slate-900/80 p-4 shadow-xl shadow-black/20">
          <h2 class="text-lg font-semibold text-white">Automation</h2>
          <p class="mt-2 text-sm leading-6 text-slate-400">
            Scheduled scraping is handled by Laravel. Create a real source file, then enable the env flags below.
          </p>
          <pre class="mt-4 overflow-x-auto rounded-xl border border-slate-700 bg-slate-950 p-3 text-xs leading-5 text-slate-200">{{ automationSnippet }}</pre>
        </div>

        <div class="rounded-2xl border border-slate-700/60 bg-slate-900/80 p-4 shadow-xl shadow-black/20">
          <h2 class="text-lg font-semibold text-white">Latest Files</h2>
          <div class="mt-4 space-y-3">
            <button
              v-for="file in status?.latest_imports || []"
              :key="file.path"
              type="button"
              class="w-full rounded-xl border border-slate-700 bg-slate-950/80 p-3 text-left hover:border-red-500/60"
              @click="selectImport(file.path)"
            >
              <p class="truncate text-sm font-medium text-white">{{ file.name }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ file.path }}</p>
            </button>
            <p v-if="!status?.latest_imports?.length" class="rounded-xl border border-dashed border-slate-700 p-4 text-sm text-slate-500">
              No generated scrape files yet.
            </p>
          </div>
        </div>
      </aside>
    </section>

    <section v-if="result" class="rounded-2xl border border-slate-700/60 bg-slate-900/80 p-4 shadow-xl shadow-black/20">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-white">Last Run</h2>
          <p class="mt-1 text-sm text-slate-400">{{ result.output_path || result.path || 'No output path' }}</p>
        </div>
        <span :class="['rounded-full px-3 py-1 text-xs font-semibold', result.ok ? 'bg-emerald-500/10 text-emerald-300' : 'bg-red-500/10 text-red-300']">
          {{ result.ok ? 'Success' : 'Failed' }}
        </span>
      </div>

      <div v-if="result.summary" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="(count, key) in result.summary" :key="key" class="rounded-xl border border-slate-700 bg-slate-950/70 p-3">
          <p class="text-xs uppercase tracking-wide text-slate-500">{{ key }}</p>
          <p class="mt-1 text-xl font-bold text-white">{{ count }}</p>
        </div>
      </div>

      <pre v-if="result.stdout || result.output" class="mt-4 max-h-96 overflow-auto rounded-xl border border-slate-700 bg-slate-950 p-4 text-xs leading-5 text-slate-200">{{ result.stdout || result.output }}</pre>
      <pre v-if="result.stderr" class="mt-4 max-h-96 overflow-auto rounded-xl border border-red-500/30 bg-red-950/30 p-4 text-xs leading-5 text-red-100">{{ result.stderr }}</pre>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const status = ref(null)
const sourcesJson = ref('')
const result = ref(null)
const lastOutputPath = ref('')
const collecting = ref(false)
const importing = ref(false)

const form = reactive({
  browser: 'chrome',
  wait_seconds: 15,
  page_load_timeout: 45,
  slow_mo: 0,
  limit: null,
  save_raw: true,
  save_screenshots: true,
  replace_event_fights: false,
})

const automationSnippet = computed(() => [
  'BOXINGDB_SCRAPER_ENABLED=true',
  'BOXINGDB_SCRAPER_SOURCES_PATH=tools/boxingdb_scraper/sources.local.json',
  'BOXINGDB_SCRAPER_SCHEDULE=daily',
  'BOXINGDB_SCRAPER_IMPORT=true',
  '',
  'php artisan schedule:run',
  'php artisan boxingdb:scrape --import',
].join('\n'))

onMounted(() => {
  refresh()
})

async function refresh() {
  try {
    const response = await api.get('/admin/boxingdb/scraper/status')
    status.value = response.data
    if (!sourcesJson.value && response.data.template_sources) {
      sourcesJson.value = JSON.stringify(response.data.template_sources, null, 2)
    }
    lastOutputPath.value = response.data.latest_imports?.[0]?.path || lastOutputPath.value
  } catch (error) {
    toast.error(error.response?.data?.message || 'Could not load scraper status.')
  }
}

function loadTemplate() {
  sourcesJson.value = JSON.stringify(status.value?.template_sources || { sources: [] }, null, 2)
}

function formatSources() {
  try {
    sourcesJson.value = JSON.stringify(JSON.parse(sourcesJson.value), null, 2)
  } catch {
    toast.error('Sources JSON is not valid.')
  }
}

async function collect() {
  collecting.value = true
  result.value = null
  try {
    const response = await api.post('/admin/boxingdb/scraper/collect', {
      sources_json: sourcesJson.value,
      browser: form.browser,
      wait_seconds: form.wait_seconds,
      page_load_timeout: form.page_load_timeout,
      slow_mo: form.slow_mo,
      limit: form.limit || null,
      save_raw: form.save_raw,
      save_screenshots: form.save_screenshots,
    })
    result.value = response.data
    lastOutputPath.value = response.data.output_path
    toast.success('Selenium collect completed.')
    await refresh()
  } catch (error) {
    result.value = error.response?.data || { ok: false, stderr: error.message }
    toast.error(error.response?.data?.message || 'Selenium collect failed.')
  } finally {
    collecting.value = false
  }
}

async function importLatest(dryRun) {
  if (!lastOutputPath.value) {
    toast.warning('Run a collect first or choose an import file.')
    return
  }

  importing.value = true
  result.value = null
  try {
    const response = await api.post('/admin/boxingdb/scraper/import', {
      path: lastOutputPath.value,
      dry_run: dryRun,
      replace_event_fights: form.replace_event_fights,
    })
    result.value = response.data
    toast.success(dryRun ? 'Dry-run import completed.' : 'Scraped data imported.')
    await refresh()
  } catch (error) {
    result.value = error.response?.data || { ok: false, output: error.message }
    toast.error(error.response?.data?.message || 'Import failed.')
  } finally {
    importing.value = false
  }
}

function selectImport(path) {
  lastOutputPath.value = path
  toast.info(`Selected ${path}`)
}
</script>

<style scoped>
.field {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid rgb(51 65 85);
  background: rgb(2 6 23 / 0.85);
  padding: 0.625rem 0.75rem;
  color: white;
  outline: none;
}

.field:focus {
  border-color: rgb(239 68 68);
  box-shadow: 0 0 0 2px rgb(239 68 68 / 0.2);
}
</style>
