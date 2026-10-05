<template>
  <div class="page">
    <div class="page__header">
      <div>
        <h1 class="page__title">Copas europeas</h1>
        <span class="page__subtitle">{{ filtered.length }} partidos ordenados por claridad de lectura</span>
      </div>
      <div class="page__actions page__actions--stack">
        <input v-model="fecha" type="date" class="date-input" @change="reload" />
        <RouterLink to="/futbol/seleccion">
          <Button label="Volver al dashboard" icon="pi pi-arrow-left" severity="secondary" size="small" />
        </RouterLink>
      </div>
    </div>

    <section class="panel cups-hero">
      <div>
        <span class="dashboard-kicker">Lectura rápida</span>
        <h2 class="cups-hero__title">Primero quién parte por encima. Luego ya entras al detalle.</h2>
        <p class="cups-hero__text">
          La pantalla prioriza cruces con diferencia estructural clara entre ligas para encontrar partidos más previsibles.
        </p>
      </div>
      <div class="cups-hero__controls">
        <Select v-model="competitionFilter" :options="competitionOptions" optionLabel="label" optionValue="value" class="filter-select" />
        <Select v-model="confidenceFilter" :options="confidenceOptions" optionLabel="label" optionValue="value" class="filter-select" />
      </div>
    </section>

    <section v-if="!loading && filtered.length" class="panel cups-summary">
      <div class="summary-chip">
        <strong>{{ highConfidenceCount }}</strong>
        <span>claros</span>
      </div>
      <div class="summary-chip">
        <strong>{{ mediumConfidenceCount }}</strong>
        <span>intermedios</span>
      </div>
      <div class="summary-chip">
        <strong>{{ lowConfidenceCount }}</strong>
        <span>parejos</span>
      </div>
    </section>

    <div v-if="loading" class="loading-state">
      <i class="pi pi-spin pi-spinner" /> Cargando copas...
    </div>

    <div v-else-if="!filtered.length" class="empty-state panel">
      No hay partidos para la fecha seleccionada con los filtros actuales.
    </div>

    <section v-else class="cups-list">
      <article v-for="partido in filtered" :key="partido.event_id" class="panel match-card">
        <div class="match-card__top">
          <div class="match-meta">
            <span class="match-competition">{{ shortCompetition(partido.competition.label) }}</span>
            <span class="match-time">{{ formatHora(partido.date) }}</span>
          </div>
          <div class="match-badges">
            <span class="confidence-pill" :class="`confidence-pill--${confidenceClass(partido.strength.confidence)}`">
              {{ partido.strength.confidence }}
            </span>
            <RouterLink :to="`/futbol/copas/${partido.competition.code}/${partido.event_id}`">
              <Button icon="pi pi-eye" text size="small" />
            </RouterLink>
          </div>
        </div>

        <div class="match-card__main">
          <div class="team-side" :class="{ 'team-side--fav': partido.strength.favorite_side === 'local' }">
            <strong>{{ partido.local.name }}</strong>
            <span>{{ prettyDomestic(partido.local.domestic) }}</span>
            <small>#{{ partido.local.domestic.strength_rank }} · {{ partido.local.domestic.strength_score }}</small>
          </div>

          <div class="match-center">
            <span class="match-reading" :class="`match-reading--${favoriteClass(partido.strength.favorite_side)}`">
              {{ summaryLabel(partido) }}
            </span>
            <div class="match-score">
              <template v-if="partido.local.score !== null || partido.visitante.score !== null">
                {{ scoreVal(partido.local.score) }} - {{ scoreVal(partido.visitante.score) }}
              </template>
              <template v-else>
                vs
              </template>
            </div>
            <small>{{ signedDiff(partido.strength.diff) }} puntos de fuerza</small>
          </div>

          <div class="team-side team-side--right" :class="{ 'team-side--fav': partido.strength.favorite_side === 'visitante' }">
            <strong>{{ partido.visitante.name }}</strong>
            <span>{{ prettyDomestic(partido.visitante.domestic) }}</span>
            <small>#{{ partido.visitante.domestic.strength_rank }} · {{ partido.visitante.domestic.strength_score }}</small>
          </div>
        </div>

        <p class="match-summary">{{ partido.strength.summary }}</p>
      </article>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Button from 'primevue/button'
import Select from 'primevue/select'
import { GetCopasPartidosUseCase } from '@/Futbol/Application/UseCase/GetCopasPartidos/GetCopasPartidosUseCase'

const loading = ref(true)
const partidos = ref([])
const competitionFilter = ref('all')
const confidenceFilter = ref('all')
const fecha = ref(new Date().toISOString().slice(0, 10))

const competitionOptions = [
  { label: 'Todas las copas', value: 'all' },
  { label: 'Champions', value: 'uefa.champions' },
  { label: 'Europa League', value: 'uefa.europa' },
  { label: 'Conference', value: 'uefa.europa.conf' },
]

const confidenceOptions = [
  { label: 'Todas las lecturas', value: 'all' },
  { label: 'Muy alta / alta', value: 'strong' },
  { label: 'Media', value: 'medium' },
  { label: 'Baja / pareja', value: 'low' },
]

const filtered = computed(() => {
  return partidos.value
    .filter(item => {
      const byCompetition = competitionFilter.value === 'all' || item.competition.code === competitionFilter.value
      const byConfidence = filterConfidence(item.strength.confidence)
      return byCompetition && byConfidence
    })
    .slice()
    .sort((a, b) => comparePartidos(a, b))
})

const highConfidenceCount = computed(() => filtered.value.filter(item => ['muy alta', 'alta'].includes(item.strength.confidence)).length)
const mediumConfidenceCount = computed(() => filtered.value.filter(item => item.strength.confidence === 'media').length)
const lowConfidenceCount = computed(() => filtered.value.filter(item => ['baja', 'muy baja'].includes(item.strength.confidence)).length)

onMounted(reload)

async function reload() {
  loading.value = true
  try {
    partidos.value = await GetCopasPartidosUseCase(fecha.value)
  } finally {
    loading.value = false
  }
}

function filterConfidence(confidence) {
  if (confidenceFilter.value === 'all') return true
  if (confidenceFilter.value === 'strong') return ['muy alta', 'alta'].includes(confidence)
  if (confidenceFilter.value === 'medium') return confidence === 'media'
  return ['baja', 'muy baja'].includes(confidence)
}

function formatHora(value) {
  if (!value) return '—'
  return new Date(value).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
}

function shortCompetition(label) {
  return label.replace('UEFA ', '')
}

function scoreVal(value) {
  return value === null || value === undefined ? '—' : value
}

function summaryLabel(item) {
  if (item.strength.favorite_side === 'even') return 'Cruce parejo'
  return item.strength.favorite_side === 'local' ? 'Mejor liga local' : 'Mejor liga visitante'
}

function prettyDomestic(domestic) {
  const country = domestic?.country ?? 'País pendiente'
  const league = domestic?.league_label ?? 'Liga pendiente'
  return `${country} · ${league}`
}

function signedDiff(value) {
  if (value === 0) return '0'
  return value > 0 ? `+${value}` : `${value}`
}

function confidenceClass(confidence) {
  if (['muy alta', 'alta'].includes(confidence)) return 'strong'
  if (confidence === 'media') return 'medium'
  return 'low'
}

function favoriteClass(side) {
  if (side === 'local') return 'local'
  if (side === 'visitante') return 'visitante'
  return 'even'
}

function comparePartidos(a, b) {
  const confidenceGap = confidenceWeight(b.strength.confidence) - confidenceWeight(a.strength.confidence)
  if (confidenceGap !== 0) return confidenceGap

  const diffGap = Math.abs(b.strength.diff) - Math.abs(a.strength.diff)
  if (diffGap !== 0) return diffGap

  const timeGap = hasKnownKickoff(b) - hasKnownKickoff(a)
  if (timeGap !== 0) return timeGap

  return String(a.date).localeCompare(String(b.date))
}

function confidenceWeight(confidence) {
  if (confidence === 'muy alta') return 4
  if (confidence === 'alta') return 3
  if (confidence === 'media') return 2
  if (confidence === 'baja') return 1
  return 0
}

function hasKnownKickoff(item) {
  const detail = item?.status?.detail ?? ''
  return /\d{1,2}:\d{2}/.test(detail) ? 1 : 0
}
</script>

<style scoped>
.page__actions--stack { display: flex; align-items: center; gap: .75rem; }
.date-input, .filter-select { min-width: 180px; }
.cups-hero { display: flex; justify-content: space-between; gap: 1.5rem; align-items: end; margin-bottom: 1rem; }
.cups-hero__title { margin: .2rem 0 .4rem; font-size: 1.2rem; }
.cups-hero__text { margin: 0; color: var(--tokyo-fg-dim); max-width: 58ch; }
.cups-hero__controls { display: flex; gap: .75rem; flex-wrap: wrap; }
.cups-summary { display: flex; gap: .75rem; margin-bottom: 1rem; }
.summary-chip { min-width: 110px; border-radius: 16px; padding: .8rem 1rem; background: rgba(255,255,255,.03); }
.summary-chip strong { display: block; font-size: 1.25rem; }
.summary-chip span { color: var(--tokyo-fg-dim); font-size: .8rem; text-transform: uppercase; }
.cups-list { display: grid; gap: 1rem; }
.match-card { padding: 1rem 1.1rem; }
.match-card__top { display: flex; justify-content: space-between; gap: 1rem; align-items: center; margin-bottom: .85rem; }
.match-meta { display: flex; flex-direction: column; gap: .2rem; }
.match-competition { font-size: .8rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #7aa2f7; }
.match-time { color: var(--tokyo-fg-dim); font-size: .85rem; }
.match-badges { display: flex; align-items: center; gap: .5rem; }
.confidence-pill, .match-reading { display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; padding: .25rem .7rem; font-size: .76rem; font-weight: 800; }
.confidence-pill--strong { background: rgba(158,206,106,.16); color: #9ece6a; }
.confidence-pill--medium { background: rgba(224,175,104,.16); color: #e0af68; }
.confidence-pill--low { background: rgba(127,127,127,.16); color: var(--tokyo-fg-dim); }
.match-card__main { display: grid; grid-template-columns: 1fr 220px 1fr; gap: 1rem; align-items: center; }
.team-side { border: 1px solid rgba(255,255,255,.06); border-radius: 16px; padding: .9rem 1rem; background: rgba(255,255,255,.02); }
.team-side--right { text-align: right; }
.team-side--fav { border-color: rgba(122,162,247,.45); background: rgba(122,162,247,.09); }
.team-side strong { display: block; font-size: 1rem; }
.team-side span { display: block; margin-top: .2rem; color: var(--tokyo-fg-dim); font-size: .85rem; }
.team-side small { display: block; margin-top: .45rem; font-weight: 700; color: #c0caf5; }
.match-center { text-align: center; }
.match-reading--local { background: rgba(122,162,247,.16); color: #7aa2f7; }
.match-reading--visitante { background: rgba(247,118,142,.15); color: #f7768e; }
.match-reading--even { background: rgba(127,127,127,.16); color: var(--tokyo-fg-dim); }
.match-score { margin: .5rem 0 .25rem; font-size: 1.45rem; font-weight: 800; }
.match-center small { color: var(--tokyo-fg-dim); }
.match-summary { margin: .95rem 0 0; color: var(--tokyo-fg-dim); }
.loading-state, .empty-state { padding: var(--space-6); text-align: center; color: var(--tokyo-fg-dim); }
@media (max-width: 1000px) {
  .cups-hero { flex-direction: column; align-items: stretch; }
  .cups-summary { flex-wrap: wrap; }
  .match-card__main { grid-template-columns: 1fr; }
  .team-side--right, .match-center { text-align: left; }
}
@media (max-width: 900px) {
  .page__actions--stack { flex-direction: column; align-items: stretch; }
}
</style>
