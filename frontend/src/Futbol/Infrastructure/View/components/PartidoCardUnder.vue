<template>
  <div class="partido-card panel">

    <!-- Header: liga + O/U badge -->
    <div class="card-header">
      <span class="liga-badge">{{ p.liga.nombre }}</span>
      <div class="card-header-right">
        <span v-if="ou" class="ou-badge">O/U {{ ou }}</span>
        <RouterLink :to="`/futbol/partidos/${p.uuid}`" class="detalle-link">
          <i class="pi pi-arrow-right" />
        </RouterLink>
      </div>
    </div>

    <!-- Equipos y hora -->
    <div class="equipos">
      <div class="equipo">
        <span class="pos-badge">{{ p.tabla.pos_local }}°</span>
        <div class="equipo-info">
          <span class="equipo-nombre">{{ p.equipo_local }}</span>
          <span v-if="rankLocal" class="rank-badge" :class="rankClass(rankLocal)">
            🔒 #{{ rankLocal }} liga
          </span>
        </div>
      </div>
      <div class="marcador">
        <span class="hora">{{ horaLocal }}</span>
      </div>
      <div class="equipo equipo--right">
        <div class="equipo-info equipo-info--right">
          <span class="equipo-nombre">{{ p.equipo_visitante }}</span>
          <span v-if="rankVisitante" class="rank-badge" :class="rankClass(rankVisitante)">
            🔒 #{{ rankVisitante }} liga
          </span>
        </div>
        <span class="pos-badge">{{ p.tabla.pos_visitante }}°</span>
      </div>
    </div>

    <!-- Métricas under -->
    <div class="under-metrics">
      <div v-if="xg" class="metric">
        <span class="metric-label">xG estimado</span>
        <span class="metric-val" :class="xgClass">{{ xg }}</span>
        <span class="metric-sub">{{ xgLocal }} + {{ xgVisit }}</span>
      </div>
      <div v-if="probEmpate" class="metric">
        <span class="metric-label">Prob. empate</span>
        <span class="metric-val">{{ pctEmpate }}%</span>
      </div>
      <div v-if="h2hBajo" class="metric">
        <span class="metric-label">H2H ≤2 goles</span>
        <span class="metric-val">{{ h2hBajo }}%</span>
        <span class="metric-sub">de {{ h2hPartidos }} partidos</span>
      </div>
      <div v-if="p.forma.local" class="metric">
        <span class="metric-label">Forma L</span>
        <FormaChips :forma="p.forma.local" />
      </div>
      <div v-if="p.forma.visitante" class="metric">
        <span class="metric-label">Forma V</span>
        <FormaChips :forma="p.forma.visitante" />
      </div>
    </div>

    <!-- Breakdown del score under -->
    <div class="score-breakdown">
      <div v-for="(val, key) in razonesFiltradas" :key="key" class="breakdown-item" :class="(val.puntos ?? 0) < 0 ? 'neg' : 'pos'">
        <span class="bd-label">{{ scoreLabel(key) }}</span>
        <span class="bd-puntos">{{ (val.puntos ?? 0) > 0 ? '+' : '' }}{{ val.puntos ?? 0 }}</span>
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import FormaChips from './FormaChips.vue'

const props = defineProps({
  seleccion: { type: Object, required: true },
})

const p       = computed(() => props.seleccion.partido)
const razones = computed(() => props.seleccion.razones ?? {})

const ou          = computed(() => p.value.odds?.over_under ?? null)
const probEmpate  = computed(() => p.value.probabilidades?.empate ?? null)
const pctEmpate   = computed(() => probEmpate.value ? Math.round(probEmpate.value * 100) : 0)

const xgData   = computed(() => razones.value.xg_estimado ?? null)
const xg       = computed(() => xgData.value?.total_xg ?? null)
const xgLocal  = computed(() => xgData.value?.local_xg ?? null)
const xgVisit  = computed(() => xgData.value?.visit_xg ?? null)
const xgClass  = computed(() => {
  if (!xg.value) return ''
  if (xg.value < 1.5) return 'xg--low'
  if (xg.value < 2.5) return 'xg--mid'
  return 'xg--high'
})

const h2hBajo     = computed(() => razones.value.h2h_bajo_marcador?.ratio_pct ?? null)
const h2hPartidos = computed(() => razones.value.h2h_bajo_marcador?.partidos ?? null)

const rankEquipos   = computed(() => razones.value.rank_equipos ?? null)
const rankLocal     = computed(() => rankEquipos.value?.local ?? null)
const rankVisitante = computed(() => rankEquipos.value?.visitante ?? null)
const totalEquipos  = computed(() => rankEquipos.value?.total_equipos ?? 20)

function rankClass(rank) {
  const pct = rank / totalEquipos.value
  if (pct <= 0.20) return 'rank--strong'
  if (pct <= 0.40) return 'rank--mid'
  return 'rank--weak'
}

const horaLocal = computed(() => {
  if (!p.value.hora_utc) return '—'
  try {
    return new Date(p.value.hora_utc).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
  } catch { return '—' }
})

const SKIP = new Set(['xg_estimado', 'rank_equipos'])
const razonesFiltradas = computed(() =>
  Object.fromEntries(Object.entries(razones.value).filter(([k]) => !SKIP.has(k)))
)

const LABELS = {
  over_under:        'O/U bookmaker',
  empate_probable:   'Empate probable',
  h2h_bajo_marcador: 'H2H ≤2 goles',
}
function scoreLabel(key) { return LABELS[key] ?? key }
</script>

<style scoped>
.partido-card { display: flex; flex-direction: column; gap: var(--space-3); }

.card-header       { display: flex; justify-content: space-between; align-items: center; }
.card-header-right { display: flex; align-items: center; gap: var(--space-2); }
.liga-badge  { font-size: 0.75rem; color: var(--tokyo-fg-dim); text-transform: uppercase; letter-spacing: 0.05em; }
.detalle-link { color: var(--tokyo-fg-dim); font-size: 0.75rem; opacity: 0.6; transition: opacity 0.2s; }
.detalle-link:hover { opacity: 1; }
.ou-badge { font-size: 0.78rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; background: rgba(187,154,247,0.15); color: #bb9af7; }

.equipos { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: var(--space-3); padding: var(--space-2) 0; }
.equipo        { display: flex; align-items: center; gap: var(--space-2); }
.equipo--right { flex-direction: row-reverse; }
.equipo-info   { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.equipo-info--right { align-items: flex-end; }
.equipo-nombre { font-size: 0.95rem; font-weight: 600; }
.pos-badge     { font-size: 0.75rem; background: var(--tokyo-bg-tertiary); border-radius: 4px; padding: 1px 5px; color: var(--tokyo-fg-dim); min-width: 26px; text-align: center; flex-shrink: 0; }
.marcador      { text-align: center; min-width: 64px; }
.hora          { font-size: 0.85rem; color: var(--tokyo-fg-dim); font-variant-numeric: tabular-nums; }

.rank-badge  { font-size: 0.65rem; font-weight: 700; padding: 1px 6px; border-radius: 10px; letter-spacing: 0.02em; width: fit-content; }
.rank--strong { background: rgba(122,162,247,0.18); color: #7aa2f7; }
.rank--mid    { background: rgba(224,175,104,0.15); color: #e0af68; }
.rank--weak   { background: rgba(148,148,148,0.10); color: var(--tokyo-fg-dim); }

.under-metrics { display: flex; flex-wrap: wrap; gap: var(--space-4); padding: var(--space-2) 0; border-top: 1px solid var(--tokyo-bg-tertiary); }
.metric { display: flex; flex-direction: column; gap: 2px; }
.metric-label { font-size: 0.68rem; color: var(--tokyo-fg-dim); text-transform: uppercase; letter-spacing: 0.04em; }
.metric-val   { font-size: 1.05rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.metric-sub   { font-size: 0.68rem; color: var(--tokyo-fg-dim); }

.xg--low  { color: #9ece6a; }
.xg--mid  { color: #e0af68; }
.xg--high { color: #f7768e; }

.score-breakdown { display: flex; flex-wrap: wrap; gap: var(--space-2); padding-top: var(--space-1); }
.breakdown-item  { display: flex; align-items: center; gap: 4px; font-size: 0.72rem; background: var(--tokyo-bg-tertiary); border-radius: 4px; padding: 2px 6px; }
.breakdown-item.pos .bd-puntos { color: #9ece6a; }
.breakdown-item.neg .bd-puntos { color: #f7768e; }
.bd-label  { color: var(--tokyo-fg-dim); }
.bd-puntos { font-weight: 700; }
</style>
