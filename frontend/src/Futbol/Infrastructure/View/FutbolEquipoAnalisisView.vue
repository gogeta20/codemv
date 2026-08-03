<template>
  <div class="page">
    <div class="page__header">
      <div>
        <h1 class="page__title">Analizador de equipo</h1>
        <span class="page__subtitle">
          {{ data.team?.name || route.params.teamId }} · {{ data.league?.name || route.params.codigo?.toUpperCase() }}
        </span>
      </div>
      <div class="page__actions">
        <RouterLink :to="`/futbol/ligas/${route.params.codigo}/equipos`">
          <Button label="Volver a equipos" icon="pi pi-arrow-left" severity="secondary" size="small" />
        </RouterLink>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <i class="pi pi-spin pi-spinner" /> Cargando análisis del equipo...
    </div>

    <div v-else-if="error" class="empty-state">
      {{ error }}
    </div>

    <template v-else>
      <section class="panel">
        <div class="section-head">
          <h2 class="section-title">Resumen bruto</h2>
        </div>
        <div class="meta-grid">
          <div class="meta-card">
            <span class="meta-card__label">Equipo</span>
            <strong class="meta-card__value">{{ data.team?.name || '—' }}</strong>
            <span class="meta-card__sub">ID {{ data.team?.id || route.params.teamId }}</span>
          </div>
          <div class="meta-card">
            <span class="meta-card__label">Liga</span>
            <strong class="meta-card__value">{{ data.league?.name || '—' }}</strong>
            <span class="meta-card__sub">{{ data.league?.code || route.params.codigo }}</span>
          </div>
          <div class="meta-card">
            <span class="meta-card__label">Temporada usada</span>
            <strong class="meta-card__value">{{ data.season?.label || '—' }}</strong>
            <span class="meta-card__sub">
              {{ data.season?.fallback_applied ? 'fallback aplicado' : 'sin fallback' }}
            </span>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="section-head">
          <h2 class="section-title">Leaders</h2>
        </div>
        <div class="table-wrap">
          <table class="raw-table">
            <thead>
              <tr>
                <th>Métrica</th>
                <th>Jugador</th>
                <th>Dato principal</th>
                <th>PJ</th>
                <th>Extra</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in leaderRows" :key="row.key">
                <td>{{ row.label }}</td>
                <td>{{ row.player }}</td>
                <td>{{ row.main }}</td>
                <td>{{ row.appearances }}</td>
                <td>{{ row.extra }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="tables-grid">
        <div class="panel">
          <div class="section-head">
            <h2 class="section-title">Top goleadores</h2>
          </div>
          <div class="table-wrap">
            <table class="raw-table">
              <thead>
                <tr>
                  <th>Rank</th>
                  <th>Jugador</th>
                  <th>PJ</th>
                  <th>Goles</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in data.tables?.top_scorers || []" :key="`${row.athlete?.uid}-${row.rank}`">
                  <td>{{ row.rank }}</td>
                  <td>{{ row.athlete?.name }}</td>
                  <td>{{ row.appearances }}</td>
                  <td>{{ row.totalGoals }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="panel">
          <div class="section-head">
            <h2 class="section-title">Top asistencias</h2>
          </div>
          <div class="table-wrap">
            <table class="raw-table">
              <thead>
                <tr>
                  <th>Rank</th>
                  <th>Jugador</th>
                  <th>PJ</th>
                  <th>Asistencias</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in data.tables?.top_assists || []" :key="`${row.athlete?.uid}-${row.rank}`">
                  <td>{{ row.rank }}</td>
                  <td>{{ row.athlete?.name }}</td>
                  <td>{{ row.appearances }}</td>
                  <td>{{ row.goalAssists }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section class="tables-grid">
        <div class="panel">
          <div class="section-head">
            <h2 class="section-title">Disciplina</h2>
          </div>
          <div class="table-wrap">
            <table class="raw-table">
              <thead>
                <tr>
                  <th>Rank</th>
                  <th>Jugador</th>
                  <th>PJ</th>
                  <th>YC</th>
                  <th>RC</th>
                  <th>Pts</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in data.tables?.discipline || []" :key="`${row.athlete?.uid}-${row.rank}`">
                  <td>{{ row.rank }}</td>
                  <td>{{ row.athlete?.name }}</td>
                  <td>{{ row.appearances }}</td>
                  <td>{{ row.yellowCards }}</td>
                  <td>{{ row.redCards }}</td>
                  <td>{{ row.points }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="panel">
          <div class="section-head">
            <h2 class="section-title">Cobertura no disponible</h2>
          </div>
          <div class="table-wrap">
            <table class="raw-table">
              <thead>
                <tr>
                  <th>Key</th>
                  <th>Motivo</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in data.coverage?.unavailable_metrics || []" :key="item.key">
                  <td>{{ item.key }}</td>
                  <td>{{ item.reason }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="section-head">
          <h2 class="section-title">Performance</h2>
        </div>
        <div class="performance-stack">
          <div v-for="table in performanceTables" :key="table.key" class="performance-block">
            <h3 class="performance-block__title">{{ table.label }}</h3>
            <div class="table-wrap">
              <table class="raw-table">
                <thead>
                  <tr>
                    <th v-for="column in table.columns" :key="column">{{ column }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, rowIndex) in table.rows" :key="`${table.key}-${rowIndex}`">
                    <td v-for="column in table.columns" :key="`${table.key}-${rowIndex}-${column}`">
                      {{ formatCell(row[column]) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="section-head">
          <h2 class="section-title">Payload rápido</h2>
        </div>
        <pre class="raw-pre">{{ debugPayload }}</pre>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import Button from 'primevue/button'
import { GetEquipoAnalisisUseCase } from '@/Futbol/Application/UseCase/GetEquipoAnalisis/GetEquipoAnalisisUseCase'

const route = useRoute()
const loading = ref(true)
const error = ref('')
const data = ref({ team: null, league: null, season: null, coverage: null, leaders: {}, tables: {} })

const leaderRows = computed(() => [
  {
    key: 'top_scorer',
    label: 'top_scorer',
    player: data.value.leaders?.top_scorer?.player || '—',
    main: data.value.leaders?.top_scorer?.goals ?? '—',
    appearances: data.value.leaders?.top_scorer?.appearances ?? '—',
    extra: data.value.leaders?.top_scorer?.per_match ?? '—',
  },
  {
    key: 'top_assister',
    label: 'top_assister',
    player: data.value.leaders?.top_assister?.player || '—',
    main: data.value.leaders?.top_assister?.assists ?? '—',
    appearances: data.value.leaders?.top_assister?.appearances ?? '—',
    extra: data.value.leaders?.top_assister?.per_match ?? '—',
  },
  {
    key: 'best_goals_per_match',
    label: 'best_goals_per_match',
    player: data.value.leaders?.best_goals_per_match?.player || '—',
    main: data.value.leaders?.best_goals_per_match?.goals ?? '—',
    appearances: data.value.leaders?.best_goals_per_match?.appearances ?? '—',
    extra: data.value.leaders?.best_goals_per_match?.goals_per_match ?? '—',
  },
  {
    key: 'most_yellow_cards',
    label: 'most_yellow_cards',
    player: data.value.leaders?.most_yellow_cards?.player || '—',
    main: data.value.leaders?.most_yellow_cards?.yellow_cards ?? '—',
    appearances: data.value.leaders?.most_yellow_cards?.appearances ?? '—',
    extra: 'YC',
  },
  {
    key: 'most_red_cards',
    label: 'most_red_cards',
    player: data.value.leaders?.most_red_cards?.player || '—',
    main: data.value.leaders?.most_red_cards?.red_cards ?? '—',
    appearances: data.value.leaders?.most_red_cards?.appearances ?? '—',
    extra: 'RC',
  },
  {
    key: 'discipline_points',
    label: 'discipline_points',
    player: data.value.leaders?.discipline_points?.player || '—',
    main: data.value.leaders?.discipline_points?.points ?? '—',
    appearances: data.value.leaders?.discipline_points?.appearances ?? '—',
    extra: 'Pts',
  },
])

const performanceTables = computed(() => {
  const performance = data.value.tables?.performance || {}

  return Object.entries(performance).map(([key, rows]) => {
    const firstRow = Array.isArray(rows) && rows.length ? rows[0] : {}
    return {
      key,
      label: key,
      columns: Object.keys(firstRow),
      rows: Array.isArray(rows) ? rows : [],
    }
  })
})

const debugPayload = computed(() => JSON.stringify({
  team: data.value.team,
  league: data.value.league,
  season: data.value.season,
  available_views: data.value.available_views,
}, null, 2))

onMounted(async () => {
  try {
    data.value = await GetEquipoAnalisisUseCase({
      liga: route.params.codigo,
      teamId: route.params.teamId,
      season: route.query.season || null,
    })
  } catch (e) {
    error.value = e.response?.data?.error || 'No se pudo cargar el análisis del equipo.'
  } finally {
    loading.value = false
  }
})

function formatCell(value) {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  if (typeof value === 'object') {
    return JSON.stringify(value)
  }

  return value
}
</script>

<style scoped>
.loading-state, .empty-state { color: var(--tokyo-fg-dim); padding: var(--space-8); text-align: center; }
.section-head { margin-bottom: var(--space-3); }
.section-title { margin: 0; font-size: 1rem; }
.meta-grid, .tables-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: var(--space-4); margin-bottom: var(--space-5); }
.tables-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.meta-card { display: flex; flex-direction: column; gap: 6px; padding: var(--space-3); border-radius: 12px; background: rgba(41, 46, 66, 0.82); border: 1px solid rgba(125, 207, 255, 0.12); }
.meta-card__label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--tokyo-fg-dim); }
.meta-card__value { font-size: 1rem; }
.meta-card__sub { color: var(--tokyo-fg-dim); }
.table-wrap { overflow-x: auto; }
.raw-table { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
.raw-table th, .raw-table td { padding: var(--space-2) var(--space-3); border-bottom: 1px solid rgba(255,255,255,0.05); text-align: left; vertical-align: top; }
.raw-table th { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--tokyo-fg-dim); white-space: nowrap; }
.performance-stack { display: flex; flex-direction: column; gap: var(--space-4); }
.performance-block__title { margin: 0 0 var(--space-2); font-size: 0.92rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tokyo-cyan); }
.raw-pre { margin: 0; padding: var(--space-3); border-radius: 10px; background: rgba(41, 46, 66, 0.82); color: var(--tokyo-fg-dim); overflow-x: auto; font-size: 0.82rem; }
@media (max-width: 960px) { .meta-grid, .tables-grid { grid-template-columns: 1fr; } }
</style>
