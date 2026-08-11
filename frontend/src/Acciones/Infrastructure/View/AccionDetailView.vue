<template>
  <div>
    <!-- Header -->
    <div class="page__header">
      <div class="header-left">
        <button class="back-btn" @click="$router.back()"><i class="pi pi-arrow-left" /> Volver</button>
        <div v-if="accion.symbol">
          <div class="title-row">
            <h1 class="page__title">{{ accion.symbol }}</h1>
            <Tag :value="accion.type" severity="secondary" />
            <span class="exchange-badge">{{ accion.exchange }}</span>
          </div>
          <span class="page__subtitle">{{ accion.name }}</span>
        </div>
      </div>
      <div class="header-right">
        <Button
          label="Análisis fundamental"
          icon="pi pi-chart-bar"
          size="small"
          severity="secondary"
          outlined
          @click="$router.push(`/acciones/${route.params.uuid}/analisis`)"
        />
        <Button
          label="Earnings"
          icon="pi pi-megaphone"
          size="small"
          severity="secondary"
          outlined
          @click="$router.push(`/acciones/${route.params.uuid}/earnings`)"
        />
        <!-- Pertenece a un portafolio -->
        <div v-if="portafolio" class="portafolio-chip">
          <i class="pi pi-briefcase" />
          <RouterLink :to="`/portafolio/${portafolio.uuid}`" class="portafolio-link">{{ portafolio.nombre }}</RouterLink>
          <Tag :value="portafolio.status" severity="secondary" style="font-size:0.7rem" />
          <button class="change-btn" @click="openChangePortafolio" v-tooltip.bottom="'Cambiar portafolio'">
            <i class="pi pi-pencil" />
          </button>
        </div>
        <!-- Sin portafolio -->
        <Button v-else label="Añadir a portafolio" icon="pi pi-briefcase" size="small" outlined @click="addToPortafolio" />
      </div>
    </div>

    <!-- Dialog cambiar portafolio -->
    <Dialog v-model:visible="changeDialog" header="Cambiar portafolio" modal style="width: 380px">
      <div class="form-fields">
        <div class="field">
          <label>Portafolio</label>
          <Select v-model="selectedPortafolioUuid" :options="portafolioOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="changeDialog = false" />
        <Button label="Mover" :loading="changeSaving" @click="savePortafolioChange" />
      </template>
    </Dialog>

    <div v-if="loading" class="loading-msg"><i class="pi pi-spin pi-spinner" /> Cargando...</div>

    <div v-else class="detail-layout">

      <!-- SECCIÓN 1: Evolución de precio -->
      <section class="detail-section">
        <h2 class="section-title"><i class="pi pi-chart-line" /> Evolución del precio</h2>
        <div v-if="historyError" class="section-empty">{{ historyError }}</div>
        <div v-else-if="history.length" class="history-grid">
          <div
            v-for="p in history"
            :key="p.label"
            class="history-card"
            :class="p.change_pct == null ? 'today' : p.change_pct >= 0 ? 'up' : 'down'"
          >
            <div class="history-label">{{ p.label }}</div>
            <div class="history-price">${{ p.price?.toFixed(2) ?? '—' }}</div>
            <div class="history-date">{{ p.date }}</div>
            <div v-if="p.change_pct != null" class="history-pct">
              <i :class="p.change_pct >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" />
              {{ Math.abs(p.change_pct).toFixed(2) }}% vs hoy
            </div>
            <div v-else class="history-pct today-label">precio actual</div>
          </div>
        </div>
        <div v-else class="section-empty">Sin datos históricos.</div>
      </section>

      <!-- SECCIÓN 2: Descripción de la empresa -->
      <section class="detail-section">
        <h2 class="section-title"><i class="pi pi-building" /> Sobre la empresa</h2>
        <div v-if="descError" class="section-empty">{{ descError }}</div>
        <div v-else-if="description.name" class="desc-layout">
          <div class="desc-facts">
            <div class="fact" v-if="description.sector">
              <span class="fact-label">Sector</span>
              <span class="fact-value">{{ description.sector }}</span>
            </div>
            <div class="fact" v-if="description.industry">
              <span class="fact-label">Industry</span>
              <span class="fact-value">{{ description.industry }}</span>
            </div>
            <div class="fact" v-if="description.country">
              <span class="fact-label">País</span>
              <span class="fact-value">{{ description.city ? description.city + ', ' : '' }}{{ description.country }}</span>
            </div>
            <div class="fact" v-if="description.employees">
              <span class="fact-label">Empleados</span>
              <span class="fact-value">{{ description.employees?.toLocaleString() }}</span>
            </div>
            <div class="fact" v-if="description.market_cap">
              <span class="fact-label">Market cap</span>
              <span class="fact-value">
                {{ formatMarketCap(description.market_cap) }}
                <span
                  v-if="capSize(description.market_cap)"
                  class="cap-badge"
                  :style="{ color: capSize(description.market_cap).color, borderColor: capSize(description.market_cap).color }"
                >{{ capSize(description.market_cap).label }}</span>
              </span>
            </div>
            <div class="fact" v-if="description.website">
              <span class="fact-label">Web</span>
              <a :href="description.website" target="_blank" class="fact-link">{{ description.website }}</a>
            </div>
          </div>
          <p v-if="description.summary" class="desc-summary">{{ description.summary }}</p>
          <p v-else class="section-empty">Sin descripción disponible.</p>
        </div>
        <div v-else class="section-empty">Sin datos de empresa.</div>
      </section>

      <!-- SECCIÓN 3: Noticias -->
      <section class="detail-section">
        <h2 class="section-title"><i class="pi pi-bell" /> Noticias recientes</h2>
        <div v-if="news.length === 0" class="section-empty">Sin noticias.</div>
        <div v-else class="news-list">
          <div v-for="n in news" :key="n.url || n.title" class="news-item">
            <div class="news-meta">
              <span class="news-date">{{ n.date ?? n.pub_date ?? '—' }}</span>
              <span v-if="n.change_pct != null" class="news-movement" :class="n.change_pct >= 0 ? 'up' : 'down'">
                {{ n.change_pct >= 0 ? '+' : '' }}{{ n.change_pct?.toFixed(2) }}%
              </span>
            </div>
            <a v-if="n.url" :href="n.url" target="_blank" class="news-title">{{ n.title }}</a>
            <span v-else class="news-title no-link">{{ n.title }}</span>
            <p v-if="n.summary" class="news-summary">{{ n.summary }}</p>
            <p v-if="n.agent_analysis" class="news-analysis"><i class="pi pi-robot" /> {{ n.agent_analysis }}</p>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import { useToast } from 'primevue/usetoast'
import { GetAccionNoticiasUseCase } from '@/Acciones/Application/UseCase/GetAccionNoticias/GetAccionNoticiasUseCase'
import { GetAccionHistoryUseCase } from '@/Acciones/Application/UseCase/GetAccionHistory/GetAccionHistoryUseCase'
import { GetAccionDescriptionUseCase } from '@/Acciones/Application/UseCase/GetAccionDescription/GetAccionDescriptionUseCase'
import { GetAccionNewsLiveUseCase } from '@/Acciones/Application/UseCase/GetAccionNewsLive/GetAccionNewsLiveUseCase'
import { AddAccionToPortafolioUseCase } from '@/Acciones/Application/UseCase/AddAccionToPortafolio/AddAccionToPortafolioUseCase'
import { DeletePortafolioEntryUseCase } from '@/Acciones/Application/UseCase/DeletePortafolioEntry/DeletePortafolioEntryUseCase'
import { ListPortafolioUseCase } from '@/Acciones/Application/UseCase/ListPortafolio/ListPortafolioUseCase'

const route  = useRoute()
const router = useRouter()
const toast  = useToast()

const accion       = ref({})
const portafolio   = ref(null)
const history      = ref([])
const description  = ref({})
const news         = ref([])
const loading      = ref(true)
const historyError = ref(null)
const descError    = ref(null)

const changeDialog          = ref(false)
const changeSaving          = ref(false)
const selectedPortafolioUuid = ref(null)
const portafolioOptions     = ref([])
const allPortafolios        = ref([])

function formatMarketCap(v) {
  if (!v) return '—'
  if (v >= 1e12) return `$${(v / 1e12).toFixed(2)}T`
  if (v >= 1e9)  return `$${(v / 1e9).toFixed(2)}B`
  if (v >= 1e6)  return `$${(v / 1e6).toFixed(2)}M`
  return `$${v.toLocaleString()}`
}

function capSize(v) {
  if (!v) return null
  if (v >= 200e9) return { label: 'Mega cap',  color: '#bb9af7' }
  if (v >= 10e9)  return { label: 'Large cap', color: '#7aa2f7' }
  if (v >= 2e9)   return { label: 'Mid cap',   color: '#73daca' }
  if (v >= 300e6) return { label: 'Small cap', color: '#e0af68' }
  if (v >= 50e6)  return { label: 'Micro cap', color: '#f7768e' }
  return           { label: 'Nano cap',  color: '#9d8f8f' }
}

onMounted(async () => {
  const [dbData, portafoliosList] = await Promise.all([
    GetAccionNoticiasUseCase(route.params.uuid),
    ListPortafolioUseCase(),
  ])
  accion.value     = dbData.accion
  portafolio.value = dbData.portafolio ?? null
  allPortafolios.value   = portafoliosList
  portafolioOptions.value = portafoliosList.map(p => ({
    label: p.is_default ? `${p.nombre} (default)` : p.nombre,
    value: p.uuid,
  }))
  const symbol = accion.value.symbol

  const [histRes, descRes, liveNewsRes] = await Promise.allSettled([
    GetAccionHistoryUseCase(symbol),
    GetAccionDescriptionUseCase(symbol),
    GetAccionNewsLiveUseCase(symbol),
  ])

  if (histRes.status === 'fulfilled') {
    history.value = histRes.value.periods ?? []
  } else {
    historyError.value = 'No se pudo cargar el historial.'
  }

  if (descRes.status === 'fulfilled') {
    description.value = descRes.value
  } else {
    descError.value = 'No se pudo cargar la descripción.'
  }

  const liveNews = liveNewsRes.status === 'fulfilled'
    ? (liveNewsRes.value.articles ?? []).map(a => ({ ...a, source: 'live' }))
    : []
  const dbNews = dbData.noticias.map(n => ({ ...n, source: 'db' }))

  const seen = new Set()
  news.value = [...liveNews, ...dbNews]
    .filter(n => { if (seen.has(n.title)) return false; seen.add(n.title); return true })
    .sort((a, b) => (b.date ?? b.pub_date ?? '').localeCompare(a.date ?? a.pub_date ?? ''))

  loading.value = false
})

function openChangePortafolio() {
  selectedPortafolioUuid.value = portafolio.value?.uuid ?? null
  changeDialog.value = true
}

async function savePortafolioChange() {
  if (!selectedPortafolioUuid.value) return
  if (selectedPortafolioUuid.value === portafolio.value?.uuid) {
    changeDialog.value = false
    return
  }
  changeSaving.value = true
  try {
    // Quita del portafolio actual
    if (portafolio.value?.entry_uuid) {
      await DeletePortafolioEntryUseCase(portafolio.value.entry_uuid)
    }
    // Añade al nuevo
    await AddAccionToPortafolioUseCase(selectedPortafolioUuid.value, {
      accion_uuid: accion.value.uuid,
      status: 'watchlist',
    })
    const nuevo = allPortafolios.value.find(p => p.uuid === selectedPortafolioUuid.value)
    portafolio.value = { ...portafolio.value, uuid: nuevo.uuid, nombre: nuevo.nombre }
    changeDialog.value = false
    toast.add({ severity: 'success', summary: `Movida a "${nuevo.nombre}"`, life: 2000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.error ?? e.message, life: 4000 })
  } finally {
    changeSaving.value = false
  }
}

async function addToPortafolio() {
  try {
    const portafolios = await ListPortafolioUseCase()
    const def = portafolios.find(p => p.is_default) ?? portafolios[0]
    if (!def) { toast.add({ severity: 'warn', summary: 'Crea un portafolio primero', life: 3000 }); return }
    await AddAccionToPortafolioUseCase(def.uuid, { accion_uuid: accion.value.uuid, status: 'watchlist' })
    toast.add({ severity: 'success', summary: `${accion.value.symbol} añadida a "${def.nombre}"`, life: 2500 })
    router.push(`/portafolio/${def.uuid}`)
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.error ?? e.message, life: 4000 })
  }
}
</script>

<style scoped>
.header-left { display: flex; align-items: center; gap: 1.25rem; }
.back-btn {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: none; border: none; cursor: pointer;
  color: var(--tokyo-fg-dim); font-size: 0.85rem; padding: 4px 0;
}
.back-btn:hover { color: var(--tokyo-fg); }
.title-row { display: flex; align-items: center; gap: 0.6rem; }
.exchange-badge {
  font-size: 0.75rem; color: var(--tokyo-fg-dim); font-family: monospace;
  background: var(--tokyo-bg-tertiary); padding: 1px 6px; border-radius: 4px;
}
.header-right { display: flex; align-items: center; }

.portafolio-chip {
  display: inline-flex; align-items: center; gap: 0.5rem;
  background: var(--tokyo-bg-secondary);
  border: 1px solid var(--tokyo-bg-tertiary);
  border-radius: 8px; padding: 0.4rem 0.75rem;
  font-size: 0.85rem;
}
.portafolio-link { color: var(--tokyo-cyan); text-decoration: none; font-weight: 600; }
.portafolio-link:hover { text-decoration: underline; }
.change-btn {
  background: none; border: none; cursor: pointer;
  color: #94a3b8; padding: 2px 4px; border-radius: 4px;
  transition: color 0.15s;
}
.change-btn:hover { color: var(--tokyo-blue); }

.form-fields { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.field label { font-size: 0.82rem; color: var(--tokyo-fg-dim); }

.loading-msg { padding: 3rem; text-align: center; color: var(--tokyo-fg-dim); }
.detail-layout { display: flex; flex-direction: column; gap: 2rem; }
.detail-section { display: flex; flex-direction: column; gap: 1rem; }
.section-title {
  font-size: 0.85rem; font-weight: 600; color: var(--tokyo-fg-dim);
  text-transform: uppercase; letter-spacing: 0.05em;
  display: flex; align-items: center; gap: 0.5rem;
  border-bottom: 1px solid var(--tokyo-bg-tertiary); padding-bottom: 0.5rem;
}
.section-empty { color: var(--tokyo-fg-dim); font-size: 0.85rem; font-style: italic; }

/* Precios */
.history-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; }
@media (max-width: 800px) { .history-grid { grid-template-columns: repeat(2, 1fr); } }
.history-card {
  background: var(--tokyo-bg-secondary); border: 1px solid var(--tokyo-bg-tertiary);
  border-radius: 10px; padding: 1rem; display: flex; flex-direction: column; gap: 0.3rem;
}
.history-card.today { border-color: var(--tokyo-cyan); }
.history-card.up    { border-left: 3px solid #4ade80; }
.history-card.down  { border-left: 3px solid #f87171; }
.history-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tokyo-fg-dim); }
.history-price { font-size: 1.5rem; font-weight: 700; }
.history-date  { font-size: 0.72rem; color: var(--tokyo-fg-dim); }
.history-pct   { font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem; margin-top: 0.2rem; }
.history-card.up   .history-pct { color: #4ade80; }
.history-card.down .history-pct { color: #f87171; }
.today-label { color: var(--tokyo-cyan); font-size: 0.78rem; font-weight: 400; }

/* Descripción */
.desc-layout { display: flex; flex-direction: column; gap: 1rem; }
.desc-facts { display: flex; flex-wrap: wrap; gap: 0.75rem 2rem; background: var(--tokyo-bg-secondary); border-radius: 8px; padding: 1rem; }
.fact { display: flex; flex-direction: column; gap: 0.15rem; }
.fact-label { font-size: 0.7rem; text-transform: uppercase; color: var(--tokyo-fg-dim); letter-spacing: 0.04em; }
.fact-value { font-size: 0.9rem; font-weight: 500; }
.fact-link  { font-size: 0.85rem; color: var(--tokyo-blue); text-decoration: none; }
.fact-link:hover { text-decoration: underline; }
.cap-badge {
  font-size: 0.72rem; font-weight: 600;
  border: 1px solid; border-radius: 4px;
  padding: 1px 5px; margin-left: 0.4rem;
  vertical-align: middle;
}
.desc-summary { font-size: 0.88rem; line-height: 1.75; color: var(--tokyo-fg-dim); max-width: 900px; }

/* Noticias */
.news-list { display: flex; flex-direction: column; }
.news-item { padding: 0.85rem 0; border-bottom: 1px solid var(--tokyo-bg-tertiary); display: flex; flex-direction: column; gap: 0.3rem; }
.news-item:last-child { border-bottom: none; }
.news-meta { display: flex; align-items: center; gap: 0.75rem; }
.news-date  { font-size: 0.75rem; color: var(--tokyo-fg-dim); font-family: monospace; }
.news-movement { font-size: 0.75rem; font-weight: 700; }
.news-movement.up   { color: #4ade80; }
.news-movement.down { color: #f87171; }
.news-title { font-size: 0.9rem; font-weight: 500; color: var(--tokyo-blue); text-decoration: none; }
.news-title:hover { text-decoration: underline; }
.news-title.no-link { color: var(--tokyo-fg); }
.news-summary { font-size: 0.8rem; color: var(--tokyo-fg-dim); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.news-analysis { font-size: 0.78rem; color: var(--tokyo-fg-dim); background: var(--tokyo-bg-secondary); border-left: 2px solid var(--tokyo-purple, #bb9af7); padding: 0.3rem 0.6rem; border-radius: 0 4px 4px 0; display: flex; align-items: flex-start; gap: 0.4rem; }
</style>
