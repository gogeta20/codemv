<template>
  <div class="page">
    <div class="page__header">
      <div>
        <h1 class="page__title">Partidos del día</h1>
        <span class="page__subtitle">{{ totalPartidos }} partidos en {{ ligas.length }} ligas — {{ fechaDisplay }}</span>
      </div>
      <div class="page__actions">
        <RouterLink to="/futbol/seleccion">
          <Button label="Ver selección" icon="pi pi-star" size="small" />
        </RouterLink>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <i class="pi pi-spin pi-spinner" /> Cargando partidos...
    </div>

    <template v-else>
      <!-- Tabs por liga -->
      <TabView v-model:activeIndex="tabActivo">
        <TabPanel v-for="liga in ligas" :key="liga" :header="liga">
          <DataTable
            :value="partidosPorLiga[liga]"
            stripedRows
            dataKey="uuid"
            class="partidos-table"
            tableStyle="table-layout: fixed; width: 100%"
          >
            <template #empty>Sin partidos hoy en esta liga.</template>

            <Column header="Hora" style="width: 70px">
              <template #body="{ data }">
                <span class="hora-val">{{ formatHora(data.hora_utc) }}</span>
              </template>
            </Column>

            <Column header="Local" style="width: 180px">
              <template #body="{ data }">
                <div class="equipo-cell">
                  <span class="pos">{{ data.tabla.pos_local }}°</span>
                  <span class="nombre" :class="{ 'fav': esFavLocal(data) }">{{ data.equipo_local }}</span>
                </div>
              </template>
            </Column>

            <Column header="Score" style="width: 80px; text-align: center">
              <template #body="{ data }">
                <div v-if="data.resultado.goles_local !== null" class="marcador-final">
                  {{ data.resultado.goles_local }} - {{ data.resultado.goles_visitante }}
                </div>
                <span v-else class="text-muted">vs</span>
              </template>
            </Column>

            <Column header="Visitante" style="width: 180px">
              <template #body="{ data }">
                <div class="equipo-cell equipo-cell--right">
                  <span class="nombre" :class="{ 'fav': esFavVisitante(data) }">{{ data.equipo_visitante }}</span>
                  <span class="pos">{{ data.tabla.pos_visitante }}°</span>
                </div>
              </template>
            </Column>

            <Column header="Prob" style="width: 110px">
              <template #body="{ data }">
                <div v-if="data.probabilidades.local" class="mini-bar">
                  <div class="mini-seg loc" :style="{ width: pct(data.probabilidades.local) }"></div>
                  <div class="mini-seg emp" :style="{ width: pct(data.probabilidades.empate) }"></div>
                  <div class="mini-seg vis" :style="{ width: pct(data.probabilidades.visitante) }"></div>
                </div>
                <span v-else class="text-muted">—</span>
              </template>
            </Column>

            <Column header="O/U" style="width: 60px; text-align: center">
              <template #body="{ data }">
                <span class="stat-val">{{ data.odds.over_under ?? '—' }}</span>
              </template>
            </Column>

            <Column header="Forma" style="width: 130px">
              <template #body="{ data }">
                <div class="formas">
                  <FormaChips v-if="data.forma.local" :forma="data.forma.local" />
                  <span v-else class="text-muted">—</span>
                  <span class="forma-sep">/</span>
                  <FormaChips v-if="data.forma.visitante" :forma="data.forma.visitante" />
                  <span v-else class="text-muted">—</span>
                </div>
              </template>
            </Column>

            <Column header="Score" style="width: 80px; text-align: center">
              <template #body="{ data }">
                <div class="score-cell">
                  <span class="score-chip" :class="scoreClass(data.score_analisis)">{{ data.score_analisis }}</span>
                  <span
                    v-if="data.score_detalle?.trampa_empate"
                    class="trampa-dot"
                    :class="`trampa-dot--${data.score_detalle.trampa_empate.nivel}`"
                    :title="`Trampa empate [${data.score_detalle.trampa_empate.nivel}]: ${data.score_detalle.trampa_empate.razones?.join(' · ')}`"
                  >⚠️</span>
                </div>
              </template>
            </Column>

            <Column header="Estado" style="width: 90px">
              <template #body="{ data }">
                <Tag :value="estadoLabel(data.estado)" :severity="estadoSeverity(data.estado)" />
              </template>
            </Column>

            <Column header="" style="width: 44px; text-align: center">
              <template #body="{ data }">
                <RouterLink :to="`/futbol/partidos/${data.uuid}`">
                  <Button icon="pi pi-eye" text size="small" />
                </RouterLink>
              </template>
            </Column>
          </DataTable>
        </TabPanel>
      </TabView>
    </template>

    <FutbolLeyenda />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import FormaChips from './components/FormaChips.vue'
import FutbolLeyenda from './components/FutbolLeyenda.vue'
import { GetPartidosDelDiaUseCase } from '@/Futbol/Application/UseCase/GetPartidosDelDia/GetPartidosDelDiaUseCase'

const loading   = ref(true)
const tabActivo = ref(0)
const partidos  = ref([])

const fechaDisplay = computed(() =>
  new Date().toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
)

const ligas = computed(() => {
  const set = new Set(partidos.value.map(p => p.liga.nombre))
  return [...set]
})

const partidosPorLiga = computed(() => {
  const map = {}
  for (const liga of ligas.value) {
    map[liga] = partidos.value
      .filter(p => p.liga.nombre === liga)
      .sort((a, b) => (a.hora_utc ?? '').localeCompare(b.hora_utc ?? ''))
  }
  return map
})

const totalPartidos = computed(() => partidos.value.length)

onMounted(async () => {
  partidos.value = await GetPartidosDelDiaUseCase()
  loading.value = false
})

function formatHora(horaUtc) {
  if (!horaUtc) return '—'
  try {
    return new Date(horaUtc).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
  } catch { return '—' }
}

function pct(v) { return v ? Math.round(v * 100) + '%' : '0%' }

function esFavLocal(data) {
  const pl = data.probabilidades?.local ?? 0
  const pv = data.probabilidades?.visitante ?? 0
  return pl > pv && pl > 0
}

function esFavVisitante(data) {
  const pl = data.probabilidades?.local ?? 0
  const pv = data.probabilidades?.visitante ?? 0
  return pv > pl && pv > 0
}

function scoreClass(s) {
  if (s >= 8) return 'high'
  if (s >= 5) return 'mid'
  return 'low'
}

function estadoLabel(e) {
  return { programado: 'Programado', en_juego: 'En juego', finalizado: 'Finalizado' }[e] ?? e
}

function estadoSeverity(e) {
  return { programado: 'secondary', en_juego: 'warn', finalizado: 'success' }[e] ?? 'secondary'
}
</script>

<style scoped>
.loading-state { color: var(--tokyo-fg-dim); padding: var(--space-8); text-align: center; }

.equipo-cell       { display: flex; align-items: center; gap: var(--space-2); }
.equipo-cell--right { flex-direction: row-reverse; }
.equipo-cell .pos  { font-size: 0.72rem; color: var(--tokyo-fg-dim); background: var(--tokyo-bg-tertiary); border-radius: 3px; padding: 1px 4px; min-width: 22px; text-align: center; }
.equipo-cell .nombre { font-size: 0.88rem; font-weight: 500; }
.equipo-cell .nombre.fav { color: var(--tokyo-cyan); font-weight: 700; }

.marcador-final { font-size: 0.9rem; font-weight: 700; text-align: center; letter-spacing: 0.05em; }

.mini-bar { display: flex; height: 8px; border-radius: 3px; overflow: hidden; width: 100%; background: var(--tokyo-bg-tertiary); }
.mini-seg { height: 100%; transition: width 0.3s; }
.mini-seg.loc { background: rgba(122,162,247,0.5); }
.mini-seg.emp { background: rgba(148,148,148,0.3); }
.mini-seg.vis { background: rgba(247,118,142,0.5); }

.stat-val { font-size: 0.82rem; font-weight: 600; font-variant-numeric: tabular-nums; }

.formas { display: flex; align-items: center; gap: 4px; }
.forma-sep { font-size: 0.7rem; color: var(--tokyo-bg-tertiary); }

.score-chip { font-size: 0.75rem; font-weight: 800; padding: 2px 7px; border-radius: 10px; }
.score-chip.high { background: rgba(158,206,106,0.15); color: #9ece6a; }
.score-chip.mid  { background: rgba(224,175,104,0.15); color: #e0af68; }
.score-chip.low  { background: rgba(148,148,148,0.1);  color: var(--tokyo-fg-dim); }

.hora-val { font-size: 0.82rem; font-variant-numeric: tabular-nums; color: var(--tokyo-fg-dim); }

.score-cell { display: flex; align-items: center; justify-content: center; gap: 4px; }
.trampa-dot { font-size: 0.75rem; cursor: default; }
</style>
