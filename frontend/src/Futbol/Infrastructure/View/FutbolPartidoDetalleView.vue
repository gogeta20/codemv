<template>
  <div class="page">
    <div v-if="loading" class="loading-state">
      <i class="pi pi-spin pi-spinner" /> Cargando partido...
    </div>

    <template v-else-if="partido">
      <!-- Header -->
      <div class="page__header">
        <div>
          <span class="liga-label">{{ ligaLabel }}</span>
          <h1 class="page__title match-title">
            {{ partido.equipo_local }}
            <span class="vs-sep">vs</span>
            {{ partido.equipo_visitante }}
          </h1>
          <span class="page__subtitle">
            {{ estadio }} · {{ horaLocal }}
            <Tag :value="estadoLabel" :severity="estadoSeverity" class="ml-2" />
          </span>
        </div>
        <div class="page__actions">
          <span class="score-chip" :class="scoreClass" title="Puntuación de analizabilidad del algoritmo, no es el resultado del partido">
            Score IA {{ partido.score_analisis }}
          </span>
          <RouterLink to="/futbol/partidos">
            <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" size="small" />
          </RouterLink>
        </div>
      </div>

      <!-- Resultado si ya empezó/terminó -->
      <div v-if="partido.resultado.goles_local !== null" class="match-meta">
        <div class="resultado-final">
          <span class="gol-num">{{ partido.resultado.goles_local }}</span>
          <span class="gol-sep">–</span>
          <span class="gol-num">{{ partido.resultado.goles_visitante }}</span>
        </div>
      </div>

      <!-- Equipos + tabla -->
      <div class="equipos-panel panel">
        <div class="equipo-col" :class="{ fav: esFavLocal }">
          <span class="equipo-nombre">{{ partido.equipo_local }}</span>
          <div class="equipo-stats">
            <span class="stat-pill">{{ partido.tabla.pos_local }}° lugar</span>
            <span class="stat-pill">{{ partido.tabla.pts_local }} pts</span>
            <span class="stat-pill">{{ partido.tabla.pj_local }} PJ</span>
          </div>
          <div v-if="partido.forma.local" class="forma-row">
            <span class="forma-label">Forma</span>
            <FormaChips :forma="partido.forma.local" />
          </div>
        </div>

        <!-- Probabilidades verticales -->
        <div class="prob-center">
          <div v-if="hasProbabilidades" class="prob-bar-v">
            <div class="prob-seg-v prob-loc" :style="{ height: pctLocal + '%' }">
              <span v-if="pctLocal > 12">{{ pctLocal }}%</span>
            </div>
            <div class="prob-seg-v prob-emp" :style="{ height: pctEmpate + '%' }">
              <span v-if="pctEmpate > 12">{{ pctEmpate }}%</span>
            </div>
            <div class="prob-seg-v prob-vis" :style="{ height: pctVisitante + '%' }">
              <span v-if="pctVisitante > 12">{{ pctVisitante }}%</span>
            </div>
          </div>
          <div v-else class="hora-central">{{ horaLocal }}</div>
          <div v-if="hasProbabilidades" class="prob-bar-labels">
            <span>L</span><span>D</span><span>V</span>
          </div>
        </div>

        <div class="equipo-col equipo-col--right" :class="{ fav: esFavVisitante }">
          <span class="equipo-nombre">{{ partido.equipo_visitante }}</span>
          <div class="equipo-stats">
            <span class="stat-pill">{{ partido.tabla.pos_visitante }}° lugar</span>
            <span class="stat-pill">{{ partido.tabla.pts_visitante }} pts</span>
            <span class="stat-pill">{{ partido.tabla.pj_visitante }} PJ</span>
          </div>
          <div v-if="partido.forma.visitante" class="forma-row forma-row--right">
            <FormaChips :forma="partido.forma.visitante" />
            <span class="forma-label">Forma</span>
          </div>
        </div>
      </div>

      <!-- Odds + O/U -->
      <div class="panel odds-panel">
        <div class="section-title">Probabilidades de victoria</div>
        <div class="odds-grid">
          <div class="odd-item" v-if="partido.odds.local">
            <span class="odd-label">{{ partido.equipo_local }}</span>
            <span class="odd-pct" :class="oddsColor(partido.odds.local)">{{ impliedPct(partido.odds.local) }}%</span>
            <span class="odd-raw">{{ fmtOdds(partido.odds.local) }}</span>
          </div>
          <div class="odd-item" v-if="partido.odds.empate">
            <span class="odd-label">Empate</span>
            <span class="odd-pct">{{ impliedPct(partido.odds.empate) }}%</span>
            <span class="odd-raw">{{ fmtOdds(partido.odds.empate) }}</span>
          </div>
          <div class="odd-item" v-if="partido.odds.visitante">
            <span class="odd-label">{{ partido.equipo_visitante }}</span>
            <span class="odd-pct" :class="oddsColor(partido.odds.visitante)">{{ impliedPct(partido.odds.visitante) }}%</span>
            <span class="odd-raw">{{ fmtOdds(partido.odds.visitante) }}</span>
          </div>
          <div class="odd-item odd-item--sep" v-if="partido.odds.over_under">
            <span class="odd-label">Más/menos goles</span>
            <span class="odd-pct">{{ partido.odds.over_under }} goles</span>
            <span class="odd-raw">O/U</span>
          </div>
          <div class="odd-item" v-if="partido.odds.spread">
            <span class="odd-label">Hándicap favorito</span>
            <span class="odd-pct">{{ partido.odds.spread > 0 ? '+' : '' }}{{ partido.odds.spread }}</span>
            <span class="odd-raw">Spread</span>
          </div>
        </div>
      </div>

      <!-- Corners promedio -->
      <div v-if="partido.corners?.local || partido.corners?.visitante" class="panel corners-panel">
        <div class="section-title">Corners — promedio temporada (últimos 10 partidos)</div>
        <div class="corners-grid">
          <div class="corners-team" v-if="partido.corners.local">
            <div class="corners-nombre">{{ partido.equipo_local }}</div>
            <div class="corners-avg">{{ partido.corners.local.promedio }}</div>
            <div class="corners-sub">por partido</div>
            <div class="corners-detalle">
              <span
                v-for="(c, i) in partido.corners.local.detalle"
                :key="i"
                class="corner-pip"
                :class="pipClass(c)"
              >{{ c }}</span>
            </div>
          </div>

          <div class="corners-vs">
            <span>vs</span>
            <div class="corners-total" v-if="partido.corners.local && partido.corners.visitante">
              <span class="corners-total-num">{{ (partido.corners.local.promedio + partido.corners.visitante.promedio).toFixed(1) }}</span>
              <span class="corners-total-label">total estimado</span>
            </div>
          </div>

          <div class="corners-team corners-team--right" v-if="partido.corners.visitante">
            <div class="corners-nombre">{{ partido.equipo_visitante }}</div>
            <div class="corners-avg">{{ partido.corners.visitante.promedio }}</div>
            <div class="corners-sub">por partido</div>
            <div class="corners-detalle">
              <span
                v-for="(c, i) in partido.corners.visitante.detalle"
                :key="i"
                class="corner-pip"
                :class="pipClass(c)"
              >{{ c }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- H2H -->
      <div class="panel h2h-panel">
        <div class="section-title">Head to Head</div>
        <div class="h2h-summary">
          <div class="h2h-num local">{{ partido.h2h.ganados_local }} <span>Local</span></div>
          <div class="h2h-num empate">{{ partido.h2h.empates }} <span>Empate</span></div>
          <div class="h2h-num visit">{{ partido.h2h.ganados_visitante }} <span>Visitante</span></div>
        </div>
        <div v-if="partido.h2h.detalle?.length" class="h2h-detalle">
          <div v-for="g in partido.h2h.detalle" :key="g.fecha + g.resultado" class="h2h-row" :class="g.ganador">
            <span class="h2h-fecha">{{ g.fecha }}</span>
            <span class="h2h-match">
              <span :class="h2hTeamClass(g, 'local')">{{ h2hTeamName(g, 'local') }}</span>
              <strong class="h2h-score">{{ g.resultado }}</strong>
              <span :class="h2hTeamClass(g, 'visitante')">{{ h2hTeamName(g, 'visitante') }}</span>
            </span>
          </div>
        </div>
      </div>

      <!-- Líderes por equipo -->
      <div v-if="partido.lideres" class="panel lideres-panel">
        <div class="section-title">Líderes de temporada</div>
        <div class="lideres-grid">
          <div v-for="side in ['local', 'visitante']" :key="side" class="lideres-team">
            <div class="lideres-team-header">
              <img v-if="partido.lideres[side]?.logo" :src="partido.lideres[side].logo" class="team-logo" alt="" />
              <span class="lideres-team-nombre">{{ partido.lideres[side]?.equipo }}</span>
            </div>
            <div class="lideres-cats">
              <div v-for="(jugadores, cat) in partido.lideres[side]?.stats" :key="cat" class="lider-cat">
                <div class="lider-cat-title">{{ catLabel(cat) }}</div>
                <div v-for="j in jugadores" :key="j.jugador" class="lider-row">
                  <span class="lider-nombre">{{ j.jugador }}</span>
                  <span class="lider-valor">{{ j.valor }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Score breakdown -->
      <div class="panel score-panel">
        <div class="section-title">Score de analizabilidad</div>
        <div class="breakdown-wrap">
          <div
            v-for="(val, key) in partido.score_detalle"
            :key="key"
            class="breakdown-chip"
            :class="val > 0 ? 'pos' : val < 0 ? 'neg' : 'zero'"
          >
            <span class="bd-label">{{ scoreLabel(key) }}</span>
            <span class="bd-val">{{ val > 0 ? '+' : '' }}{{ val }}</span>
          </div>
        </div>
      </div>

      <FutbolLeyenda />
    </template>

    <div v-else class="loading-state">Partido no encontrado.</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import FormaChips from './components/FormaChips.vue'
import FutbolLeyenda from './components/FutbolLeyenda.vue'
import { GetPartidoDetalleUseCase } from '@/Futbol/Application/UseCase/GetPartidoDetalle/GetPartidoDetalleUseCase'

const route   = useRoute()
const loading = ref(true)
const partido = ref(null)

onMounted(async () => {
  partido.value = await GetPartidoDetalleUseCase(route.params.uuid)
  loading.value = false
})

const probLocal     = computed(() => partido.value?.probabilidades?.local     ?? 0)
const probEmpate    = computed(() => partido.value?.probabilidades?.empate    ?? 0)
const probVisitante = computed(() => partido.value?.probabilidades?.visitante ?? 0)
const hasProbabilidades = computed(() => probLocal.value || probEmpate.value || probVisitante.value)

const pctLocal     = computed(() => Math.round((probLocal.value     ?? 0) * 100))
const pctEmpate    = computed(() => Math.round((probEmpate.value    ?? 0) * 100))
const pctVisitante = computed(() => Math.round((probVisitante.value ?? 0) * 100))

const esFavLocal     = computed(() => probLocal.value > probVisitante.value && probLocal.value > 0)
const esFavVisitante = computed(() => probVisitante.value > probLocal.value && probVisitante.value > 0)

const horaLocal = computed(() => {
  const h = partido.value?.hora_utc
  if (!h) return '—'
  try { return new Date(h).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) } catch { return '—' }
})

const estadio = computed(() => partido.value?.estadio || '—')

// El nombre de la liga ya incluye el país ("País — Liga"); solo lo agregamos aparte si no está repetido (ej. Mundial: "FIFA World Cup 2026" · "International").
const ligaLabel = computed(() => {
  const liga = partido.value?.liga
  if (!liga) return ''
  if (!liga.pais || liga.nombre.includes(liga.pais)) return liga.nombre
  return `${liga.nombre} · ${liga.pais}`
})

const estadoLabel = computed(() => ({
  programado: 'Programado', en_juego: 'En juego', finalizado: 'Finalizado',
}[partido.value?.estado] ?? partido.value?.estado))

const estadoSeverity = computed(() => ({
  programado: 'secondary', en_juego: 'warn', finalizado: 'success',
}[partido.value?.estado] ?? 'secondary'))

const scoreClass = computed(() => {
  const s = partido.value?.score_analisis ?? 0
  if (s >= 8) return 'high'
  if (s >= 5) return 'mid'
  return 'low'
})

function fmtOdds(v) {
  if (v === null || v === undefined) return '—'
  return Number(v).toFixed(2)
}

// odds_local/empate/visitante son cuotas decimales (ej: 1.32 = pagan 1.32 por cada 1 apostado).
// Probabilidad implícita = 1 / cuota. La suma de las 3 supera el 100% por el margen de la casa (vig), es normal.
function impliedPct(cuotaDecimal) {
  if (cuotaDecimal === null || cuotaDecimal === undefined || cuotaDecimal <= 0) return '—'
  return Math.round((1 / cuotaDecimal) * 100)
}

function oddsColor(v) {
  if (v === null || v === undefined) return ''
  return v < 2 ? 'fav-odds' : 'dog-odds'
}

const CAT_LABELS = {
  goalsLeaders:   'Goles',
  assistsLeaders: 'Asistencias',
  totalShots:     'Tiros totales',
  accuratePasses: 'Pases precisos',
  saves:          'Atajadas',
}

function catLabel(key) { return CAT_LABELS[key] ?? key }

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
  close_match:      'Partido cerrado',
}

function scoreLabel(key) { return SCORE_LABELS[key] ?? key }

function pipClass(c) {
  if (c >= 8) return 'pip--high'
  if (c >= 5) return 'pip--mid'
  return 'pip--low'
}

// ganador W = today's home team (equipo_local) won, L = today's home team lost
// Color: equipo_local = blue, equipo_visitante = red — regardless of historical home/away
// UPPERCASE = won that match, lowercase = lost
function h2hTeamClass(g, side) {
  const name = side === 'local' ? g.local : g.visitante
  const isTodayHome = name === partido.value?.equipo_local
  const base = isTodayHome ? 'h2h-team--home' : 'h2h-team--away'
  if (g.ganador === 'D') return `${base} h2h-team--draw`
  const won = isTodayHome ? g.ganador === 'W' : g.ganador === 'L'
  return `${base} ${won ? 'h2h-team--winner' : 'h2h-team--loser'}`
}

function h2hTeamName(g, side) {
  const name = side === 'local' ? g.local : g.visitante
  if (g.ganador === 'D') return name.toLowerCase()
  const isTodayHome = name === partido.value?.equipo_local
  const won = isTodayHome ? g.ganador === 'W' : g.ganador === 'L'
  return won ? name.toUpperCase() : name.toLowerCase()
}
</script>

<style scoped>
.loading-state { color: var(--tokyo-fg-dim); padding: var(--space-8); text-align: center; }

.liga-label  { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--tokyo-fg-dim); }
.match-title { font-size: 1.5rem; margin: var(--space-1) 0; }
.vs-sep      { color: var(--tokyo-fg-dim); font-weight: 400; margin: 0 var(--space-2); }

.match-meta { display: flex; align-items: center; margin-bottom: var(--space-4); }
.score-chip { font-size: 0.82rem; font-weight: 800; padding: 3px 10px; border-radius: 12px; cursor: help; }
.score-chip.high { background: rgba(158,206,106,0.15); color: #9ece6a; }
.score-chip.mid  { background: rgba(224,175,104,0.15); color: #e0af68; }
.score-chip.low  { background: rgba(148,148,148,0.1);  color: var(--tokyo-fg-dim); }

.resultado-final { display: flex; align-items: center; gap: var(--space-2); }
.gol-num  { font-size: 1.8rem; font-weight: 900; }
.gol-sep  { font-size: 1.4rem; color: var(--tokyo-fg-dim); }

/* Equipos panel */
.equipos-panel { display: grid; grid-template-columns: 1fr 90px 1fr; align-items: center; gap: var(--space-4); }

.equipo-col { display: flex; flex-direction: column; gap: var(--space-2); }
.equipo-col--right { align-items: flex-end; text-align: right; }
.equipo-col.fav .equipo-nombre { color: var(--tokyo-cyan); }
.equipo-nombre { font-size: 1.1rem; font-weight: 700; }

.equipo-stats { display: flex; flex-wrap: wrap; gap: var(--space-1); }
.equipo-col--right .equipo-stats { justify-content: flex-end; }
.stat-pill { font-size: 0.72rem; background: var(--tokyo-bg-tertiary); border-radius: 4px; padding: 2px 7px; color: var(--tokyo-fg-dim); }

.forma-row { display: flex; align-items: center; gap: var(--space-2); }
.forma-row--right { flex-direction: row-reverse; }
.forma-label { font-size: 0.7rem; color: var(--tokyo-fg-dim); }

/* Prob vertical bar */
.prob-center { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.prob-bar-v  { display: flex; height: 80px; width: 54px; border-radius: 6px; overflow: hidden; background: var(--tokyo-bg-tertiary); }
.prob-seg-v  { display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 700; transition: height 0.3s; min-height: 0; overflow: hidden; flex-direction: column; }
.prob-loc  { background: rgba(122,162,247,0.35); color: #7aa2f7; width: 100%; }
.prob-emp  { background: rgba(148,148,148,0.2);  color: var(--tokyo-fg-dim); width: 100%; }
.prob-vis  { background: rgba(247,118,142,0.3);  color: #f7768e; width: 100%; }
.prob-bar-labels { display: flex; gap: 8px; font-size: 0.68rem; color: var(--tokyo-fg-dim); }
.hora-central { font-size: 1.1rem; font-weight: 700; color: var(--tokyo-fg-dim); }

/* Odds */
.odds-panel .odds-grid { display: flex; flex-wrap: wrap; gap: var(--space-5); margin-top: var(--space-3); }
.odd-item         { display: flex; flex-direction: column; align-items: center; gap: 3px; min-width: 80px; }
.odd-item--sep    { border-left: 1px solid var(--tokyo-bg-tertiary); padding-left: var(--space-5); }
.odd-label        { font-size: 0.72rem; color: var(--tokyo-fg-dim); text-align: center; max-width: 100px; }
.odd-pct          { font-size: 1.25rem; font-weight: 800; font-variant-numeric: tabular-nums; }
.odd-raw          { font-size: 0.72rem; color: var(--tokyo-fg-dim); font-variant-numeric: tabular-nums; }
.fav-odds         { color: var(--tokyo-cyan); }
.dog-odds         { color: #e0af68; }

/* H2H */
.h2h-summary { display: flex; gap: var(--space-6); margin: var(--space-3) 0; }
.h2h-num { display: flex; flex-direction: column; align-items: center; gap: 2px; font-size: 1.4rem; font-weight: 800; }
.h2h-num span { font-size: 0.68rem; font-weight: 400; color: var(--tokyo-fg-dim); }
.h2h-num.local { color: #7aa2f7; }
.h2h-num.empate { color: var(--tokyo-fg-dim); }
.h2h-num.visit  { color: #f7768e; }

.h2h-detalle { display: flex; flex-direction: column; margin-top: var(--space-2); }
.h2h-row { display: flex; align-items: center; gap: var(--space-3); font-size: 0.82rem; padding: var(--space-2) var(--space-2); border-bottom: 1px solid var(--tokyo-bg-tertiary); }
.h2h-row:last-child { border-bottom: none; }
.h2h-row.W { background: rgba(158,206,106,0.04); }
.h2h-row.L { background: rgba(247,118,142,0.04); }
.h2h-fecha { color: var(--tokyo-fg-dim); min-width: 88px; font-size: 0.75rem; }
.h2h-match { flex: 1; display: flex; align-items: center; gap: var(--space-2); }
.h2h-score { font-variant-numeric: tabular-nums; color: var(--tokyo-fg); padding: 0 var(--space-1); }

/* Team name coloring in H2H */
.h2h-team--home   { color: #7aa2f7; font-weight: 600; }
.h2h-team--away   { color: #f7768e; font-weight: 600; }
.h2h-team--winner { font-weight: 800; }
.h2h-team--loser  { opacity: 0.5; font-weight: 400; }
.h2h-team--draw   { opacity: 0.65; font-weight: 400; }

/* Líderes */
.lideres-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-top: var(--space-3); }
.lideres-team-header { display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-4); padding-bottom: var(--space-2); border-bottom: 2px solid var(--tokyo-bg-tertiary); }
.team-logo { width: 32px; height: 32px; object-fit: contain; }
.lideres-team-nombre { font-size: 1rem; font-weight: 700; }

.lideres-cats { display: flex; flex-direction: column; gap: var(--space-5); }
.lider-cat-title { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--tokyo-fg-dim); margin-bottom: var(--space-2); opacity: 0.7; }
.lider-row  {
  display: flex; justify-content: space-between; align-items: center;
  gap: var(--space-3); font-size: 0.85rem;
  padding: var(--space-2) var(--space-2);
  border-radius: 5px;
}
.lider-row:nth-child(odd)  { background: var(--tokyo-bg-tertiary); }
.lider-nombre { color: var(--tokyo-fg); font-weight: 500; }
.lider-valor  { color: var(--tokyo-cyan); font-size: 0.8rem; font-weight: 600; text-align: right; white-space: nowrap; }

/* Score breakdown */
.breakdown-wrap { display: flex; flex-wrap: wrap; gap: var(--space-2); margin-top: var(--space-3); }
.breakdown-chip { display: flex; align-items: center; gap: 5px; font-size: 0.75rem; padding: 3px 8px; border-radius: 6px; background: var(--tokyo-bg-tertiary); }
.breakdown-chip.pos .bd-val { color: #9ece6a; font-weight: 700; }
.breakdown-chip.neg .bd-val { color: #f7768e; font-weight: 700; }
.breakdown-chip.zero .bd-val { color: var(--tokyo-fg-dim); }
.bd-label { color: var(--tokyo-fg-dim); }

/* Corners */
.corners-panel .corners-grid { display: grid; grid-template-columns: 1fr 80px 1fr; align-items: center; gap: var(--space-4); margin-top: var(--space-3); }
.corners-team { display: flex; flex-direction: column; gap: var(--space-1); }
.corners-team--right { align-items: flex-end; }
.corners-nombre { font-size: 0.78rem; color: var(--tokyo-fg-dim); }
.corners-avg { font-size: 2rem; font-weight: 900; color: var(--tokyo-cyan); font-variant-numeric: tabular-nums; line-height: 1; }
.corners-sub { font-size: 0.68rem; color: var(--tokyo-fg-dim); opacity: 0.6; }
.corners-detalle { display: flex; flex-wrap: wrap; gap: 4px; margin-top: var(--space-2); }
.corners-team--right .corners-detalle { justify-content: flex-end; }
.corner-pip { font-size: 0.72rem; font-weight: 700; padding: 2px 5px; border-radius: 4px; min-width: 22px; text-align: center; font-variant-numeric: tabular-nums; }
.pip--high { background: rgba(158,206,106,0.2); color: #9ece6a; }
.pip--mid  { background: rgba(224,175,104,0.15); color: #e0af68; }
.pip--low  { background: rgba(148,148,148,0.1); color: var(--tokyo-fg-dim); }
.corners-vs { display: flex; flex-direction: column; align-items: center; gap: var(--space-2); color: var(--tokyo-fg-dim); font-size: 0.8rem; }
.corners-total { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.corners-total-num { font-size: 1.1rem; font-weight: 800; color: var(--tokyo-fg); }
.corners-total-label { font-size: 0.65rem; color: var(--tokyo-fg-dim); opacity: 0.7; text-align: center; }

.section-title { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--tokyo-fg-dim); opacity: 0.7; margin-bottom: var(--space-2); }

@media (max-width: 640px) {
  .equipos-panel  { grid-template-columns: 1fr; }
  .prob-center    { display: none; }
  .lideres-grid   { grid-template-columns: 1fr; }
  .equipo-col--right { align-items: flex-start; text-align: left; }
}
</style>
