<template>
  <div class="page">
    <div class="page__header">
      <div>
        <h1 class="page__title">{{ ligaNombre }} — Equipos por goles</h1>
        <span class="page__subtitle" v-if="data.total">
          {{ data.total }} equipos · ordenados por goles totales por partido
        </span>
      </div>
      <div class="page__actions">
        <RouterLink to="/futbol/ligas/gpm">
          <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" size="small" />
        </RouterLink>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <i class="pi pi-spin pi-spinner" /> Cargando datos de ESPN...
    </div>

    <div v-else-if="!data.total" class="empty-state">
      Sin datos para esta liga.
    </div>

    <template v-else>

      <!-- Cards extremos -->
      <div class="extremos-grid">
        <div class="panel extremo-panel">
          <div class="extremo-header extremo-header--low">
            <span class="extremo-icon">🔒</span>
            <div>
              <h2 class="extremo-title">Más "under"</h2>
              <span class="extremo-sub">Menos goles totales por partido</span>
            </div>
          </div>
          <div class="extremo-list">
            <RouterLink
              v-for="(eq, i) in bottom5"
              :key="eq.espn_team_id || eq.equipo"
              :to="eq.espn_team_id ? `/futbol/ligas/${route.params.codigo}/equipos/${eq.espn_team_id}/analisis` : '#'"
              class="equipo-card equipo-card--low equipo-card--link"
            >
              <span class="card-rank">{{ i + 1 }}</span>
              <div class="card-info">
                <span class="card-nombre">{{ eq.equipo }}</span>
                <span class="card-sub">Pos {{ eq.pos }}° · {{ eq.pts }}pts</span>
              </div>
              <div class="card-stats">
                <div class="stat-pill stat-pill--atk">⚽ {{ eq.gf_pj }}</div>
                <div class="stat-pill stat-pill--def">🛡 {{ eq.gc_pj }}</div>
              </div>
              <span class="card-gpm card-gpm--low">{{ eq.total_gpm }}</span>
            </RouterLink>
          </div>
        </div>

        <div class="panel extremo-panel">
          <div class="extremo-header extremo-header--high">
            <span class="extremo-icon">⚽⚽</span>
            <div>
              <h2 class="extremo-title">Más goleadores</h2>
              <span class="extremo-sub">Más goles totales por partido</span>
            </div>
          </div>
          <div class="extremo-list">
            <RouterLink
              v-for="(eq, i) in top5"
              :key="eq.espn_team_id || eq.equipo"
              :to="eq.espn_team_id ? `/futbol/ligas/${route.params.codigo}/equipos/${eq.espn_team_id}/analisis` : '#'"
              class="equipo-card equipo-card--high equipo-card--link"
            >
              <span class="card-rank">{{ i + 1 }}</span>
              <div class="card-info">
                <span class="card-nombre">{{ eq.equipo }}</span>
                <span class="card-sub">Pos {{ eq.pos }}° · {{ eq.pts }}pts</span>
              </div>
              <div class="card-stats">
                <div class="stat-pill stat-pill--atk">⚽ {{ eq.gf_pj }}</div>
                <div class="stat-pill stat-pill--def">🛡 {{ eq.gc_pj }}</div>
              </div>
              <span class="card-gpm card-gpm--high">{{ eq.total_gpm }}</span>
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- Tabla completa -->
      <div class="panel tabla-panel">
        <h2 class="tabla-title">Ranking completo</h2>
        <div class="tabla-wrap">
          <table class="tabla">
            <thead>
              <tr>
                <th class="col-rank">Rank</th>
                <th class="col-pos">Pos</th>
                <th class="col-equipo">Equipo</th>
                <th class="col-pj">PJ</th>
                <th class="col-pts">Pts</th>
                <th class="col-gf">GF</th>
                <th class="col-gc">GC</th>
                <th class="col-gfpj">GF/pj</th>
                <th class="col-gcpj">GC/pj</th>
                <th class="col-total">Total/pj</th>
                <th class="col-analisis">Análisis</th>
                <th class="col-bar"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(eq, i) in data.equipos"
                :key="eq.espn_team_id || eq.equipo"
                :class="rowClass(i, data.equipos.length)"
              >
                <td class="col-rank">{{ i + 1 }}</td>
                <td class="col-pos">{{ eq.pos }}°</td>
                <td class="col-equipo">{{ eq.equipo }}</td>
                <td class="col-pj">{{ eq.pj }}</td>
                <td class="col-pts">{{ eq.pts }}</td>
                <td class="col-gf">{{ eq.gf }}</td>
                <td class="col-gc">{{ eq.gc }}</td>
                <td class="col-gfpj">
                  <span class="gfpj-val">{{ eq.gf_pj }}</span>
                </td>
                <td class="col-gcpj">{{ eq.gc_pj }}</td>
                <td class="col-total">
                  <span class="total-pill" :class="totalClass(i, data.equipos.length)">{{ eq.total_gpm }}</span>
                </td>
                <td class="col-analisis">
                  <RouterLink
                    v-if="eq.espn_team_id"
                    :to="`/futbol/ligas/${route.params.codigo}/equipos/${eq.espn_team_id}/analisis`"
                  >
                    <Button icon="pi pi-chart-bar" text size="small" />
                  </RouterLink>
                  <span v-else class="analisis-empty">—</span>
                </td>
                <td class="col-bar">
                  <div class="bar-wrap">
                    <div class="bar" :class="barClass(i, data.equipos.length)" :style="{ width: barPct(eq.total_gpm) + '%' }" />
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
import { useRoute, RouterLink } from 'vue-router'
import Button from 'primevue/button'
import { GetLigaEquiposUseCase } from '@/Futbol/Application/UseCase/GetLigaEquipos/GetLigaEquiposUseCase'

const route   = useRoute()
const loading = ref(true)
const data    = ref({ equipos: [], liga: '', total: 0 })

const ligaNombre = computed(() => {
  const codigo = route.params.codigo
  return codigo ? codigo.toUpperCase() : '—'
})

onMounted(async () => {
  data.value   = await GetLigaEquiposUseCase(route.params.codigo)
  loading.value = false
})

const bottom5 = computed(() => data.value.equipos.slice(0, 5))
const top5    = computed(() => [...data.value.equipos].reverse().slice(0, 5))

const gpmMin = computed(() => data.value.equipos[0]?.total_gpm ?? 0)
const gpmMax = computed(() => data.value.equipos[data.value.equipos.length - 1]?.total_gpm ?? 5)

function barPct(gpm) {
  const range = gpmMax.value - gpmMin.value || 1
  return Math.round(((gpm - gpmMin.value) / range) * 85 + 15)
}

function rowClass(i, total) {
  if (i < 3) return 'row--low'
  if (i >= total - 3) return 'row--high'
  return ''
}

function totalClass(i, total) {
  if (i < 3) return 'pill--low'
  if (i >= total - 3) return 'pill--high'
  return 'pill--mid'
}

function barClass(i, total) {
  if (i < 3) return 'bar--low'
  if (i >= total - 3) return 'bar--high'
  return 'bar--mid'
}
</script>

<style scoped>
.loading-state { color: var(--tokyo-fg-dim); padding: var(--space-8); text-align: center; }
.empty-state   { color: var(--tokyo-fg-dim); padding: var(--space-8); text-align: center; }

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
  padding-bottom: var(--space-3); border-bottom: 2px solid;
}
.extremo-header--high { border-color: #f7768e; }
.extremo-header--low  { border-color: #7aa2f7; }
.extremo-icon  { font-size: 1.4rem; }
.extremo-title { font-size: 1.05rem; font-weight: 700; margin: 0; }
.extremo-sub   { font-size: 0.75rem; color: var(--tokyo-fg-dim); }

.extremo-list { display: flex; flex-direction: column; gap: var(--space-2); }

.equipo-card {
  display: grid;
  grid-template-columns: 22px 1fr auto 52px;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-2) var(--space-3);
  border-radius: 6px;
  border-left: 3px solid transparent;
}
.equipo-card--link {
  text-decoration: none;
  color: inherit;
}
.equipo-card--link:hover {
  transform: translateY(-1px);
}
.equipo-card--low  { border-color: #7aa2f7; background: rgba(122,162,247,0.05); }
.equipo-card--high { border-color: #f7768e; background: rgba(247,118,142,0.05); }

.card-rank  { font-size: 0.72rem; color: var(--tokyo-fg-dim); font-weight: 700; text-align: center; }
.card-info  { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.card-nombre { font-size: 0.9rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.card-sub   { font-size: 0.7rem; color: var(--tokyo-fg-dim); }

.card-stats { display: flex; gap: var(--space-1); }
.stat-pill  { font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; font-variant-numeric: tabular-nums; }
.stat-pill--atk { background: rgba(158,206,106,0.12); color: #9ece6a; }
.stat-pill--def { background: rgba(122,162,247,0.12); color: #7aa2f7; }

.card-gpm { font-size: 1rem; font-weight: 800; text-align: right; font-variant-numeric: tabular-nums; }
.card-gpm--low  { color: #7aa2f7; }
.card-gpm--high { color: #f7768e; }

/* Tabla */
.tabla-panel { display: flex; flex-direction: column; gap: var(--space-4); }
.tabla-title { font-size: 1rem; font-weight: 700; margin: 0; }
.tabla-wrap  { overflow-x: auto; }

.tabla { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.tabla th {
  text-align: left; padding: var(--space-2) var(--space-3);
  font-size: 0.72rem; color: var(--tokyo-fg-dim);
  text-transform: uppercase; letter-spacing: 0.05em;
  border-bottom: 1px solid var(--tokyo-bg-tertiary);
  white-space: nowrap;
}
.tabla td {
  padding: var(--space-2) var(--space-3);
  border-bottom: 1px solid rgba(255,255,255,0.03);
  vertical-align: middle;
}
.tabla tbody tr:hover td { background: var(--tokyo-bg-tertiary); }

.row--low  { border-left: 3px solid rgba(122,162,247,0.4); }
.row--high { border-left: 3px solid rgba(247,118,142,0.4); }

.col-rank   { width: 36px; color: var(--tokyo-fg-dim); font-weight: 700; text-align: center; }
.col-pos    { width: 36px; color: var(--tokyo-fg-dim); text-align: center; }
.col-equipo { font-weight: 600; }
.col-analisis { width: 72px; text-align: center; }
.col-pj, .col-pts, .col-gf, .col-gc { text-align: center; color: var(--tokyo-fg-dim); }
.col-gfpj, .col-gcpj, .col-total { text-align: center; }
.col-bar    { width: 100px; }
.analisis-empty { color: var(--tokyo-fg-dim); }

.gfpj-val { font-variant-numeric: tabular-nums; color: #9ece6a; font-weight: 600; }

.total-pill {
  display: inline-block; padding: 2px 8px; border-radius: 12px;
  font-weight: 700; font-size: 0.85rem; font-variant-numeric: tabular-nums;
}
.pill--low  { background: rgba(122,162,247,0.12); color: #7aa2f7; }
.pill--mid  { background: rgba(224,175,104,0.10); color: #e0af68; }
.pill--high { background: rgba(247,118,142,0.12); color: #f7768e; }

.bar-wrap { height: 5px; background: var(--tokyo-bg-tertiary); border-radius: 3px; overflow: hidden; }
.bar      { height: 100%; border-radius: 3px; }
.bar--low  { background: #7aa2f7; opacity: 0.7; }
.bar--mid  { background: #e0af68; opacity: 0.7; }
.bar--high { background: #f7768e; opacity: 0.7; }
</style>
