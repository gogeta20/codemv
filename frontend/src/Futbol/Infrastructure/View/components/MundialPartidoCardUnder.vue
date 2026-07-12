<template>
  <div class="partido-card panel">

    <!-- Header: fase/grupo + O/U badge -->
    <div class="card-header">
      <div class="header-left">
        <span v-if="p.grupo" class="grupo-badge">Grupo {{ p.grupo }}</span>
        <span class="fase-label">{{ faseLabel }}</span>
      </div>
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
        <span class="equipo-nombre">{{ p.equipo_local }}</span>
      </div>
      <div class="marcador">
        <span class="hora">{{ horaLocal }}</span>
      </div>
      <div class="equipo equipo--right">
        <span class="equipo-nombre">{{ p.equipo_visitante }}</span>
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
      <div class="metric">
        <span class="metric-label">H2H</span>
        <span class="metric-val">{{ p.h2h.ganados_local }}-{{ p.h2h.empates }}-{{ p.h2h.ganados_visitante }}</span>
      </div>
    </div>

    <!-- Breakdown -->
    <div class="score-breakdown">
      <div v-for="(val, key) in breakdownFiltrado" :key="key" class="breakdown-item" :class="(val.puntos ?? 0) < 0 ? 'neg' : 'pos'">
        <span class="bd-label">{{ scoreLabel(key) }}</span>
        <span class="bd-puntos">{{ (val.puntos ?? 0) > 0 ? '+' : '' }}{{ val.puntos ?? 0 }}</span>
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  seleccion: { type: Object, required: true },
})

const p = computed(() => props.seleccion.partido)

const FASE_LABELS = {
  group_stage:   'Fase de Grupos',
  round_of_16:   'Octavos de Final',
  quarter_final: 'Cuartos de Final',
  semi_final:    'Semifinales',
  final:         'Gran Final',
}

const faseLabel  = computed(() => FASE_LABELS[p.value.fase] ?? p.value.fase ?? '')
const ou         = computed(() => p.value.odds?.over_under ?? null)
const probEmpate = computed(() => p.value.probabilidades?.empate ?? 0)
const pctEmpate  = computed(() => probEmpate.value ? Math.round(probEmpate.value * 100) : 0)

const xgData  = computed(() => p.value.score_under_detalle?.xg_estimado ?? null)
const xg      = computed(() => xgData.value?.total_xg ?? null)
const xgLocal = computed(() => xgData.value?.local ?? null)
const xgVisit = computed(() => xgData.value?.visitante ?? null)
const xgClass = computed(() => {
  const v = xg.value
  if (!v) return ''
  if (v < 1.5) return 'xg--bajo'
  if (v < 2.2) return 'xg--medio'
  return 'xg--alto'
})

const horaLocal = computed(() => {
  if (!p.value.hora_utc) return '—'
  try {
    return new Date(p.value.hora_utc).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
  } catch { return '—' }
})

const UNDER_LABELS = {
  ou_bajo:          'O/U bajo',
  prob_empate:      'Empate probable',
  h2h_pocos_goles:  'H2H pocos goles',
  forma_defensiva:  'Forma defensiva',
  xg_bajo:          'xG bajo',
}

const breakdownFiltrado = computed(() => {
  const razones = props.seleccion.razones
  if (!razones) return {}
  return Object.fromEntries(Object.entries(razones).filter(([, v]) => v?.puntos !== undefined))
})

function scoreLabel(key) { return UNDER_LABELS[key] ?? key }
</script>

<style scoped>
.partido-card { display: flex; flex-direction: column; gap: var(--space-3); }

.card-header       { display: flex; justify-content: space-between; align-items: center; }
.card-header-right { display: flex; align-items: center; gap: var(--space-2); }
.header-left       { display: flex; align-items: center; gap: var(--space-2); }

.grupo-badge {
  font-size: 0.72rem; font-weight: 800; padding: 2px 8px;
  background: rgba(187,154,247,0.15); color: #bb9af7;
  border-radius: 4px; letter-spacing: 0.05em;
}
.fase-label { font-size: 0.75rem; color: var(--tokyo-fg-dim); text-transform: uppercase; letter-spacing: 0.04em; }

.ou-badge {
  font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 20px;
  background: rgba(125,207,255,0.15); color: #7dcfff;
}
.detalle-link { color: var(--tokyo-fg-dim); font-size: 0.75rem; opacity: 0.6; transition: opacity 0.2s; }
.detalle-link:hover { opacity: 1; }

.equipos { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: var(--space-3); padding: var(--space-2) 0; }
.equipo  { display: flex; align-items: center; gap: var(--space-2); }
.equipo--right { flex-direction: row-reverse; }
.equipo-nombre { font-size: 0.95rem; font-weight: 600; }

.marcador { text-align: center; min-width: 64px; }
.hora     { font-size: 0.85rem; color: var(--tokyo-fg-dim); font-variant-numeric: tabular-nums; }

.under-metrics { display: flex; flex-wrap: wrap; gap: var(--space-4); padding: var(--space-2) 0; border-top: 1px solid var(--tokyo-bg-tertiary); }
.metric        { display: flex; flex-direction: column; gap: 2px; min-width: 70px; }
.metric-label  { font-size: 0.68rem; color: var(--tokyo-fg-dim); text-transform: uppercase; letter-spacing: 0.04em; }
.metric-val    { font-size: 0.9rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.metric-sub    { font-size: 0.68rem; color: var(--tokyo-fg-dim); }
.xg--bajo  { color: #9ece6a; }
.xg--medio { color: #e0af68; }
.xg--alto  { color: #f7768e; }

.score-breakdown { display: flex; flex-wrap: wrap; gap: var(--space-2); }
.breakdown-item  { display: flex; align-items: center; gap: 4px; font-size: 0.72rem; background: var(--tokyo-bg-tertiary); border-radius: 4px; padding: 2px 6px; }
.breakdown-item.pos .bd-puntos { color: #9ece6a; }
.breakdown-item.neg .bd-puntos { color: #f7768e; }
.bd-label  { color: var(--tokyo-fg-dim); }
.bd-puntos { font-weight: 700; }
</style>
