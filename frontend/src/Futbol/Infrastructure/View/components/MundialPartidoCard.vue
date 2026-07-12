<template>
  <div class="partido-card panel">

    <!-- Header: fase/grupo + score -->
    <div class="card-header">
      <div class="header-left">
        <span v-if="p.grupo" class="grupo-badge">Grupo {{ p.grupo }}</span>
        <span class="fase-label">{{ faseLabel }}</span>
      </div>
      <div class="card-header-right">
        <span class="score-badge" :class="scoreClass">{{ p.score_analisis }} pts</span>
        <RouterLink :to="`/futbol/partidos/${p.uuid}`" class="detalle-link">
          <i class="pi pi-arrow-right" />
        </RouterLink>
      </div>
    </div>

    <!-- Equipos y hora -->
    <div class="equipos">
      <div class="equipo" :class="{ 'fav': probLocal > probVisitante }">
        <span v-if="rankLocal" class="rank-badge" :class="rankClass(rankLocal)">FIFA #{{ rankLocal }}</span>
        <span class="equipo-nombre">{{ p.equipo_local }}</span>
        <span v-if="ptsLocal !== null" class="pts-badge">{{ ptsLocal }}pts</span>
      </div>
      <div class="marcador">
        <template v-if="p.resultado.goles_local !== null">
          <span class="gol">{{ p.resultado.goles_local }}</span>
          <span class="sep">-</span>
          <span class="gol">{{ p.resultado.goles_visitante }}</span>
        </template>
        <template v-else>
          <span class="hora">{{ horaLocal }}</span>
        </template>
      </div>
      <div class="equipo equipo--right" :class="{ 'fav': probVisitante > probLocal }">
        <span v-if="ptsVisitante !== null" class="pts-badge">{{ ptsVisitante }}pts</span>
        <span class="equipo-nombre">{{ p.equipo_visitante }}</span>
        <span v-if="rankVisitante" class="rank-badge" :class="rankClass(rankVisitante)">FIFA #{{ rankVisitante }}</span>
      </div>
    </div>

    <!-- Probabilidades -->
    <div v-if="probLocal || probEmpate || probVisitante" class="prob-bar-wrap">
      <div class="prob-bar">
        <div class="prob-seg prob-local"  :style="{ width: pctLocal + '%' }">{{ pctLocal }}%</div>
        <div class="prob-seg prob-empate" :style="{ width: pctEmpate + '%' }">{{ pctEmpate }}%</div>
        <div class="prob-seg prob-visit"  :style="{ width: pctVisita + '%' }">{{ pctVisita }}%</div>
      </div>
      <div class="prob-labels">
        <span>Local</span><span>Empate</span><span>Visitante</span>
      </div>
    </div>

    <!-- Stats row -->
    <div class="stats-row">
      <div class="stat" v-if="p.odds.over_under">
        <span class="stat-label">O/U</span>
        <span class="stat-val">{{ p.odds.over_under }}</span>
      </div>
      <div class="stat" v-if="p.odds.spread">
        <span class="stat-label">Spread</span>
        <span class="stat-val">{{ p.odds.spread > 0 ? '+' : '' }}{{ p.odds.spread }}</span>
      </div>
      <div class="stat">
        <span class="stat-label">H2H</span>
        <span class="stat-val">{{ p.h2h.ganados_local }}-{{ p.h2h.empates }}-{{ p.h2h.ganados_visitante }}</span>
      </div>
    </div>

    <!-- Breakdown del score -->
    <div class="score-breakdown">
      <div v-for="(val, key) in breakdownFiltrado" :key="key" class="breakdown-item" :class="(val.puntos ?? 0) < 0 ? 'neg' : 'pos'">
        <span class="bd-label">{{ scoreLabel(key) }}</span>
        <span class="bd-puntos">{{ (val.puntos ?? 0) > 0 ? '+' : '' }}{{ val.puntos ?? 0 }}</span>
      </div>
    </div>

    <!-- Goleadores si el partido terminó -->
    <div v-if="p.resultado.goleadores?.length" class="goleadores">
      <span v-for="g in p.resultado.goleadores" :key="g.jugador + g.minuto" class="gol-item">
        ⚽ {{ g.jugador }} {{ g.minuto }}'
      </span>
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

const faseLabel = computed(() => FASE_LABELS[p.value.fase] ?? p.value.fase ?? '')

const probLocal     = computed(() => p.value.probabilidades?.local ?? 0)
const probEmpate    = computed(() => p.value.probabilidades?.empate ?? 0)
const probVisitante = computed(() => p.value.probabilidades?.visitante ?? 0)

const pctLocal  = computed(() => probLocal.value     ? Math.round(probLocal.value * 100)     : 0)
const pctEmpate = computed(() => probEmpate.value    ? Math.round(probEmpate.value * 100)    : 0)
const pctVisita = computed(() => probVisitante.value ? Math.round(probVisitante.value * 100) : 0)

const ptsLocal     = computed(() => p.value.tabla?.pts_local ?? null)
const ptsVisitante = computed(() => p.value.tabla?.pts_visitante ?? null)

const rankLocal     = computed(() => props.seleccion.razones?.ranking_gap?.rank_local ?? null)
const rankVisitante = computed(() => props.seleccion.razones?.ranking_gap?.rank_visitante ?? null)

const horaLocal = computed(() => {
  if (!p.value.hora_utc) return '—'
  try {
    return new Date(p.value.hora_utc).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
  } catch { return '—' }
})

const scoreClass = computed(() => {
  const s = p.value.score_analisis
  if (s >= 8) return 'score--high'
  if (s >= 5) return 'score--mid'
  return 'score--low'
})

function rankClass(rank) {
  if (rank <= 10) return 'rank--elite'
  if (rank <= 30) return 'rank--top'
  return 'rank--mid'
}

const SCORE_LABELS = {
  ranking_gap:     'Brecha FIFA',
  elite_vs_debil:  'Elite vs débil',
  grupo_pos_gap:   'Posición grupo',
  eliminacion:     'Fase eliminatoria',
  prob_pickcenter: 'Pickcenter',
  empate_probable: 'Empate probable',
  odds_fav:        'Claridad odds',
  h2h_dominio:     'Dominio H2H',
  ultimo_partido:  'Último partido',
}

const breakdownFiltrado = computed(() => {
  const razones = props.seleccion.razones
  if (!razones) return {}
  return Object.fromEntries(Object.entries(razones).filter(([, v]) => v?.puntos !== undefined))
})

function scoreLabel(key) { return SCORE_LABELS[key] ?? key }
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

.detalle-link { color: var(--tokyo-fg-dim); font-size: 0.75rem; opacity: 0.6; transition: opacity 0.2s; }
.detalle-link:hover { opacity: 1; }
.score-badge { font-size: 0.78rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
.score--high { background: rgba(158,206,106,0.15); color: #9ece6a; }
.score--mid  { background: rgba(224,175,104,0.15); color: #e0af68; }
.score--low  { background: rgba(148,148,148,0.1);  color: var(--tokyo-fg-dim); }

.equipos { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: var(--space-3); padding: var(--space-2) 0; }
.equipo  { display: flex; align-items: center; gap: var(--space-2); }
.equipo--right { flex-direction: row-reverse; }
.equipo.fav .equipo-nombre { color: var(--tokyo-cyan); }
.equipo-nombre { font-size: 0.95rem; font-weight: 600; }
.pts-badge     { font-size: 0.72rem; color: var(--tokyo-fg-dim); }

.rank-badge { font-size: 0.68rem; font-weight: 700; padding: 1px 5px; border-radius: 3px; white-space: nowrap; }
.rank--elite { background: rgba(224,175,104,0.2); color: #e0af68; }
.rank--top   { background: rgba(122,162,247,0.15); color: #7aa2f7; }
.rank--mid   { background: rgba(148,148,148,0.1);  color: var(--tokyo-fg-dim); }

.marcador { text-align: center; min-width: 64px; }
.hora     { font-size: 0.85rem; color: var(--tokyo-fg-dim); font-variant-numeric: tabular-nums; }
.gol      { font-size: 1.3rem; font-weight: 800; }
.sep      { font-size: 1.1rem; color: var(--tokyo-fg-dim); padding: 0 4px; }

.prob-bar-wrap { display: flex; flex-direction: column; gap: 4px; }
.prob-bar {
  display: flex; height: 20px; border-radius: 4px; overflow: hidden;
  background: var(--tokyo-bg-tertiary);
}
.prob-seg {
  display: flex; align-items: center; justify-content: center;
  font-size: 0.7rem; font-weight: 700; transition: width 0.3s;
  min-width: 0; overflow: hidden;
}
.prob-local  { background: rgba(122,162,247,0.35); color: #7aa2f7; }
.prob-empate { background: rgba(148,148,148,0.2);  color: var(--tokyo-fg-dim); }
.prob-visit  { background: rgba(247,118,142,0.3);  color: #f7768e; }
.prob-labels {
  display: flex; justify-content: space-between;
  font-size: 0.68rem; color: var(--tokyo-fg-dim); padding: 0 2px;
}

.stats-row { display: flex; flex-wrap: wrap; gap: var(--space-3); padding: var(--space-1) 0; border-top: 1px solid var(--tokyo-bg-tertiary); }
.stat      { display: flex; align-items: center; gap: var(--space-1); }
.stat-label { font-size: 0.72rem; color: var(--tokyo-fg-dim); }
.stat-val   { font-size: 0.8rem; font-weight: 600; font-variant-numeric: tabular-nums; }

.score-breakdown { display: flex; flex-wrap: wrap; gap: var(--space-2); padding-top: var(--space-1); }
.breakdown-item  { display: flex; align-items: center; gap: 4px; font-size: 0.72rem; background: var(--tokyo-bg-tertiary); border-radius: 4px; padding: 2px 6px; }
.breakdown-item.pos .bd-puntos { color: #9ece6a; }
.breakdown-item.neg .bd-puntos { color: #f7768e; }
.bd-label  { color: var(--tokyo-fg-dim); }
.bd-puntos { font-weight: 700; }

.goleadores { display: flex; flex-wrap: wrap; gap: var(--space-2); padding-top: var(--space-1); border-top: 1px solid var(--tokyo-bg-tertiary); }
.gol-item   { font-size: 0.8rem; color: var(--tokyo-fg-dim); }
</style>
