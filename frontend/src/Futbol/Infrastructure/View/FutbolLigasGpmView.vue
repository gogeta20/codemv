<template>
  <div class="page">
    <div class="page__header">
      <div>
        <h1 class="page__title">Ligas del mundo — Goles por partido</h1>
        <span class="page__subtitle" v-if="data.total">
          {{ data.total }} ligas · actualizado {{ data.actualizado }}
        </span>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <i class="pi pi-spin pi-spinner" /> Cargando datos...
    </div>

    <template v-else-if="data.total === 0">
      <div class="empty-state">Sin datos. Ejecuta <code>python3 steps/ligas_gpm.py</code> para poblar.</div>
    </template>

    <template v-else>

      <!-- Top / Bottom cards -->
      <div class="extremos-grid">
        <div class="panel extremo-panel">
          <div class="extremo-header extremo-header--high">
            <span class="extremo-icon">⚽⚽</span>
            <div>
              <h2 class="extremo-title">Más goles</h2>
              <span class="extremo-sub">Ligas más goleadoras</span>
            </div>
          </div>
          <div class="extremo-list">
            <RouterLink v-for="(liga, i) in data.mas_goles" :key="liga.codigo" :to="`/futbol/ligas/${liga.codigo}/equipos`" class="extremo-row extremo-row--link">
              <span class="extremo-pos">{{ i + 1 }}</span>
              <div class="extremo-info">
                <span class="extremo-nombre">{{ liga.liga }}</span>
                <span class="extremo-pais">{{ liga.pais }}<span v-if="liga.tier > 1" class="tier-badge">Div {{ liga.tier }}</span></span>
              </div>
              <div class="bar-wrap">
                <div class="bar bar--high" :style="{ width: barPct(liga.gpm, 'high') + '%' }" />
              </div>
              <span class="gpm gpm--high">{{ liga.gpm }}</span>
            </RouterLink>
          </div>
        </div>

        <div class="panel extremo-panel">
          <div class="extremo-header extremo-header--low">
            <span class="extremo-icon">🔒</span>
            <div>
              <h2 class="extremo-title">Menos goles</h2>
              <span class="extremo-sub">Ligas más defensivas</span>
            </div>
          </div>
          <div class="extremo-list">
            <RouterLink v-for="(liga, i) in data.menos_goles" :key="liga.codigo" :to="`/futbol/ligas/${liga.codigo}/equipos`" class="extremo-row extremo-row--link">
              <span class="extremo-pos">{{ i + 1 }}</span>
              <div class="extremo-info">
                <span class="extremo-nombre">{{ liga.liga }}</span>
                <span class="extremo-pais">{{ liga.pais }}<span v-if="liga.tier > 1" class="tier-badge">Div {{ liga.tier }}</span></span>
              </div>
              <div class="bar-wrap">
                <div class="bar bar--low" :style="{ width: barPct(liga.gpm, 'low') + '%' }" />
              </div>
              <span class="gpm gpm--low">{{ liga.gpm }}</span>
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- Tabla completa -->
      <div class="panel tabla-panel">
        <div class="tabla-header">
          <h2 class="tabla-title">Ranking completo</h2>
          <input
            v-model="filtro"
            class="tabla-search"
            placeholder="Buscar liga o país..."
          />
        </div>

        <div class="tabla-wrap">
          <table class="tabla">
            <thead>
              <tr>
                <th class="col-pos">#</th>
                <th class="col-liga">Liga</th>
                <th class="col-pais">País</th>
                <th class="col-tier">Div</th>
                <th class="col-partidos">Partidos</th>
                <th class="col-gpm">Goles/partido</th>
                <th class="col-bar"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(liga, i) in ligasFiltradas" :key="liga.codigo" :class="rowClass(liga.gpm)" class="tabla-row--link" @click="$router.push(`/futbol/ligas/${liga.codigo}/equipos`)">
                <td class="col-pos">{{ posicionReal(liga.codigo) }}</td>
                <td class="col-liga">{{ liga.liga }}</td>
                <td class="col-pais">{{ liga.pais }}</td>
                <td class="col-tier">{{ liga.tier }}</td>
                <td class="col-partidos">{{ liga.partidos }}</td>
                <td class="col-gpm">
                  <span class="gpm-pill" :class="gpmClass(liga.gpm)">{{ liga.gpm }}</span>
                </td>
                <td class="col-bar">
                  <div class="inline-bar-wrap">
                    <div class="inline-bar" :class="gpmBarClass(liga.gpm)" :style="{ width: barPctTabla(liga.gpm) + '%' }" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { GetLigasGpmUseCase } from '@/Futbol/Application/UseCase/GetLigasGpm/GetLigasGpmUseCase'

const $router = useRouter()

const loading = ref(true)
const filtro  = ref('')
const data    = ref({ mas_goles: [], menos_goles: [], todas: [], total: 0, actualizado: '' })

onMounted(async () => {
  data.value   = await GetLigasGpmUseCase()
  loading.value = false
})

const ligasFiltradas = computed(() => {
  if (!filtro.value) return data.value.todas
  const q = filtro.value.toLowerCase()
  return data.value.todas.filter(l =>
    l.liga.toLowerCase().includes(q) || l.pais.toLowerCase().includes(q)
  )
})

const gpmMin = computed(() => Math.min(...(data.value.todas.map(l => l.gpm))) || 1.5)
const gpmMax = computed(() => Math.max(...(data.value.todas.map(l => l.gpm))) || 4.0)

function barPct(gpm, side) {
  const range = gpmMax.value - gpmMin.value || 1
  if (side === 'high') return Math.round(((gpm - gpmMin.value) / range) * 80 + 20)
  else return Math.round(((gpmMax.value - gpm) / range) * 80 + 20)
}

function barPctTabla(gpm) {
  const range = gpmMax.value - gpmMin.value || 1
  return Math.round(((gpm - gpmMin.value) / range) * 90 + 10)
}

function posicionReal(codigo) {
  return data.value.todas.findIndex(l => l.codigo === codigo) + 1
}

function gpmClass(gpm) {
  if (gpm >= 3.0) return 'gpm--high'
  if (gpm <= 2.3) return 'gpm--low'
  return 'gpm--mid'
}

function gpmBarClass(gpm) {
  if (gpm >= 3.0) return 'inline-bar--high'
  if (gpm <= 2.3) return 'inline-bar--low'
  return 'inline-bar--mid'
}

function rowClass(gpm) {
  if (gpm >= 3.0) return 'row--high'
  if (gpm <= 2.3) return 'row--low'
  return ''
}
</script>

<style scoped>
.loading-state { color: var(--tokyo-fg-dim); padding: var(--space-8); text-align: center; }
.empty-state   { color: var(--tokyo-fg-dim); padding: var(--space-8); text-align: center; }
.empty-state code { background: var(--tokyo-bg-tertiary); padding: 2px 6px; border-radius: 4px; }

/* Extremos */
.extremos-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-5);
  margin-bottom: var(--space-5);
}
@media (max-width: 860px) { .extremos-grid { grid-template-columns: 1fr; } }

.extremo-panel { display: flex; flex-direction: column; gap: var(--space-4); }

.extremo-header {
  display: flex; align-items: center; gap: var(--space-3);
  padding-bottom: var(--space-3);
  border-bottom: 2px solid;
}
.extremo-header--high { border-color: #f7768e; }
.extremo-header--low  { border-color: #7aa2f7; }
.extremo-icon  { font-size: 1.4rem; }
.extremo-title { font-size: 1.05rem; font-weight: 700; margin: 0; }
.extremo-sub   { font-size: 0.75rem; color: var(--tokyo-fg-dim); }

.extremo-list { display: flex; flex-direction: column; gap: var(--space-3); }
.extremo-row  {
  display: grid;
  grid-template-columns: 22px 1fr 90px 52px;
  align-items: center;
  gap: var(--space-2);
}

.extremo-pos { font-size: 0.72rem; color: var(--tokyo-fg-dim); font-weight: 700; text-align: center; }
.extremo-info { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.extremo-nombre { font-size: 0.88rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.extremo-pais   { font-size: 0.7rem; color: var(--tokyo-fg-dim); display: flex; align-items: center; gap: var(--space-1); }

.tier-badge { font-size: 0.62rem; background: var(--tokyo-bg-tertiary); padding: 1px 4px; border-radius: 3px; }

.extremo-row--link { display: grid; text-decoration: none; color: inherit; cursor: pointer; border-radius: 6px; transition: background 0.15s; }
.extremo-row--link:hover { background: var(--tokyo-bg-tertiary); }

.bar-wrap { height: 6px; background: var(--tokyo-bg-tertiary); border-radius: 3px; overflow: hidden; }
.bar      { height: 100%; border-radius: 3px; transition: width 0.6s ease; }
.bar--high { background: #f7768e; }
.bar--low  { background: #7aa2f7; }

.gpm { font-size: 1rem; font-weight: 800; font-variant-numeric: tabular-nums; text-align: right; }
.gpm--high { color: #f7768e; }
.gpm--low  { color: #7aa2f7; }

/* Tabla completa */
.tabla-panel { display: flex; flex-direction: column; gap: var(--space-4); }
.tabla-header {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: var(--space-3);
}
.tabla-title { font-size: 1rem; font-weight: 700; margin: 0; }
.tabla-search {
  background: var(--tokyo-bg-tertiary);
  border: 1px solid var(--tokyo-border);
  border-radius: 6px;
  padding: 6px 12px;
  color: var(--tokyo-fg);
  font-size: 0.85rem;
  width: 220px;
  outline: none;
}
.tabla-search:focus { border-color: var(--tokyo-cyan); }

.tabla-wrap { overflow-x: auto; }

.tabla {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}
.tabla th {
  text-align: left;
  padding: var(--space-2) var(--space-3);
  font-size: 0.72rem;
  color: var(--tokyo-fg-dim);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid var(--tokyo-bg-tertiary);
  white-space: nowrap;
}
.tabla td {
  padding: var(--space-2) var(--space-3);
  border-bottom: 1px solid rgba(255,255,255,0.03);
  vertical-align: middle;
}
.tabla-row--link { cursor: pointer; }
.tabla tbody tr:hover td { background: var(--tokyo-bg-tertiary); }

.row--high td { border-left: 2px solid rgba(247,118,142,0.3); }
.row--low  td { border-left: 2px solid rgba(122,162,247,0.3); }

.col-pos     { width: 36px; color: var(--tokyo-fg-dim); font-weight: 700; text-align: center; }
.col-liga    { font-weight: 600; }
.col-pais    { color: var(--tokyo-fg-dim); }
.col-tier    { color: var(--tokyo-fg-dim); text-align: center; }
.col-partidos { color: var(--tokyo-fg-dim); text-align: right; }
.col-gpm     { text-align: right; width: 110px; }
.col-bar     { width: 120px; }

.gpm-pill {
  display: inline-block;
  font-weight: 700;
  font-size: 0.85rem;
  font-variant-numeric: tabular-nums;
  padding: 2px 8px;
  border-radius: 12px;
}
.gpm--high { background: rgba(247,118,142,0.12); color: #f7768e; }
.gpm--mid  { background: rgba(224,175,104,0.10); color: #e0af68; }
.gpm--low  { background: rgba(122,162,247,0.12); color: #7aa2f7; }

.inline-bar-wrap { height: 5px; background: var(--tokyo-bg-tertiary); border-radius: 3px; overflow: hidden; }
.inline-bar      { height: 100%; border-radius: 3px; }
.inline-bar--high { background: #f7768e; opacity: 0.7; }
.inline-bar--mid  { background: #e0af68; opacity: 0.7; }
.inline-bar--low  { background: #7aa2f7; opacity: 0.7; }
</style>
