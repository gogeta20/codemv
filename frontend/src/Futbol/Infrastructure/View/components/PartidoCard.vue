<template>
  <div class="partido-card panel">

    <!-- Header: liga + score -->
    <div class="card-header">
      <span class="liga-badge">{{ p.liga.nombre }}</span>
      <div class="card-header-right">
        <span class="score-badge" :class="scoreClass">{{ seleccion.partido.score_analisis }} pts</span>
        <RouterLink :to="`/futbol/partidos/${p.uuid}`" class="detalle-link">
          <i class="pi pi-arrow-right" />
        </RouterLink>
      </div>
    </div>

    <!-- Equipos y hora -->
    <div class="equipos">
      <div class="equipo" :class="{ 'fav': probLocal > probVisitante }">
        <span class="pos-badge">{{ p.tabla.pos_local }}°</span>
        <span v-if="zonaLocal" class="zona-badge" :class="`zona--${zonaLocal}`">{{ zonaLabel(zonaLocal) }}</span>
        <span class="equipo-nombre">{{ p.equipo_local }}</span>
        <span class="pts-badge">{{ p.tabla.pts_local }}pts</span>
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
        <span class="pts-badge">{{ p.tabla.pts_visitante }}pts</span>
        <span class="equipo-nombre">{{ p.equipo_visitante }}</span>
        <span v-if="zonaVisitante" class="zona-badge" :class="`zona--${zonaVisitante}`">{{ zonaLabel(zonaVisitante) }}</span>
        <span class="pos-badge">{{ p.tabla.pos_visitante }}°</span>
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
      <div class="stat" v-if="p.forma.local">
        <span class="stat-label">Forma L</span>
        <FormaChips :forma="p.forma.local" />
      </div>
      <div class="stat" v-if="p.forma.visitante">
        <span class="stat-label">Forma V</span>
        <FormaChips :forma="p.forma.visitante" />
      </div>
    </div>

    <!-- Alerta trampa de empate -->
    <div v-if="trampaEmpate" class="trampa-alert" :class="`trampa--${trampaEmpate.nivel}`">
      <span class="trampa-icon">⚠️</span>
      <div class="trampa-body">
        <span class="trampa-titulo">Trampa de empate [{{ trampaEmpate.nivel }}]</span>
        <span v-for="r in trampaEmpate.razones" :key="r" class="trampa-razon">{{ r }}</span>
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
import FormaChips from './FormaChips.vue'

const props = defineProps({
  seleccion: { type: Object, required: true },
})

const p = computed(() => props.seleccion.partido)

const probLocal    = computed(() => p.value.probabilidades?.local ?? 0)
const probEmpate   = computed(() => p.value.probabilidades?.empate ?? 0)
const probVisitante = computed(() => p.value.probabilidades?.visitante ?? 0)

const pctLocal  = computed(() => probLocal.value  ? Math.round(probLocal.value * 100)  : 0)
const pctEmpate = computed(() => probEmpate.value ? Math.round(probEmpate.value * 100) : 0)
const pctVisita = computed(() => probVisitante.value ? Math.round(probVisitante.value * 100) : 0)

const horaLocal = computed(() => {
  if (!p.value.hora_utc) return '—'
  try {
    return new Date(p.value.hora_utc).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
  } catch { return '—' }
})

const scoreClass = computed(() => {
  const s = props.seleccion.partido.score_analisis
  if (s >= 8) return 'score--high'
  if (s >= 5) return 'score--mid'
  return 'score--low'
})

const SCORE_LABELS = {
  gap_tabla:        'Brecha tabla',
  extremo_tabla:    'Top3 vs Bottom3',
  brecha_puntos:    'Diferencia pts',
  odds_claridad:    'Claridad odds',
  prob_pickcenter:  'Pickcenter',
  h2h_dominio:      'Dominio H2H',
  h2h_empates_alto: 'H2H empates',
  forma_gap:        'Diferencia forma',
  racha_perfecta:   'Racha perfecta',
  local_favorito:   'Local favorito',
  temporada_joven:  'Temporada joven',
  empate_probable:  'Empate probable',
  zonas:            'Motivación',
}

const SKIP_BREAKDOWN = new Set(['trampa_empate', 'zonas'])

const breakdownFiltrado = computed(() => {
  const razones = props.seleccion.razones
  if (!razones) return {}
  return Object.fromEntries(Object.entries(razones).filter(([k]) => !SKIP_BREAKDOWN.has(k)))
})

const trampaEmpate = computed(() => p.value.score_detalle?.trampa_empate ?? null)
const zonaLocal    = computed(() => p.value.score_detalle?.zonas?.local ?? null)
const zonaVisitante = computed(() => p.value.score_detalle?.zonas?.visitante ?? null)

const ZONA_LABELS = {
  champions:        'CL',
  europa:           'EL',
  burbuja_arriba:   '↑',
  media_tabla:      null,
  burbuja_descenso: '↓',
  descenso:         'DESC',
}

function zonaLabel(zona) { return ZONA_LABELS[zona] ?? zona }
function scoreLabel(key) { return SCORE_LABELS[key] ?? key }
</script>

<style scoped>
.partido-card { display: flex; flex-direction: column; gap: var(--space-3); }

.card-header       { display: flex; justify-content: space-between; align-items: center; }
.card-header-right { display: flex; align-items: center; gap: var(--space-2); }
.liga-badge  { font-size: 0.75rem; color: var(--tokyo-fg-dim); text-transform: uppercase; letter-spacing: 0.05em; }
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
.pos-badge     { font-size: 0.75rem; background: var(--tokyo-bg-tertiary); border-radius: 4px; padding: 1px 5px; color: var(--tokyo-fg-dim); min-width: 26px; text-align: center; }
.pts-badge     { font-size: 0.72rem; color: var(--tokyo-fg-dim); }

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

/* Zona badges */
.zona-badge { font-size: 0.65rem; font-weight: 700; padding: 1px 5px; border-radius: 3px; letter-spacing: 0.03em; }
.zona--champions        { background: rgba(122,162,247,0.15); color: #7aa2f7; }
.zona--europa           { background: rgba(158,206,106,0.15); color: #9ece6a; }
.zona--burbuja_arriba   { background: rgba(148,148,148,0.1);  color: var(--tokyo-fg-dim); }
.zona--burbuja_descenso { background: rgba(224,175,104,0.12); color: #e0af68; }
.zona--descenso         { background: rgba(247,118,142,0.15); color: #f7768e; }

/* Trampa de empate */
.trampa-alert {
  display: flex; align-items: flex-start; gap: var(--space-2);
  padding: var(--space-2) var(--space-3);
  border-radius: 6px; border-left: 3px solid;
}
.trampa--alto   { background: rgba(247,118,142,0.08); border-color: #f7768e; }
.trampa--medio  { background: rgba(224,175,104,0.08); border-color: #e0af68; }
.trampa--bajo   { background: rgba(148,148,148,0.06); border-color: var(--tokyo-fg-dim); }
.trampa-icon    { font-size: 0.9rem; margin-top: 1px; }
.trampa-body    { display: flex; flex-direction: column; gap: 2px; }
.trampa-titulo  { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
.trampa--alto  .trampa-titulo  { color: #f7768e; }
.trampa--medio .trampa-titulo  { color: #e0af68; }
.trampa--bajo  .trampa-titulo  { color: var(--tokyo-fg-dim); }
.trampa-razon   { font-size: 0.72rem; color: var(--tokyo-fg-dim); }
</style>
