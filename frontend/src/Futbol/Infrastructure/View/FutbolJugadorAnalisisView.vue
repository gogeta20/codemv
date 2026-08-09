<template>
  <div class="page">
    <div class="page__header">
      <div>
        <h1 class="page__title">{{ data.player?.nombre || 'Jugador' }}</h1>
        <span class="page__subtitle">{{ data.player?.posicion || '—' }} · {{ data.player?.equipo?.nombre || '—' }}</span>
      </div>
      <div class="page__actions">
        <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" size="small" @click="$router.back()" />
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <i class="pi pi-spin pi-spinner" /> Cargando análisis del jugador...
    </div>

    <div v-else-if="error" class="empty-state">
      {{ error }}
    </div>

    <template v-else>
      <section class="panel">
        <div class="section-head">
          <h2 class="section-title">Ficha</h2>
        </div>
        <div class="meta-grid">
          <div class="meta-card">
            <span class="meta-card__label">Posición</span>
            <strong class="meta-card__value">{{ data.player?.posicion || '—' }}</strong>
            <span class="meta-card__sub">Dorsal {{ data.player?.dorsal || '—' }}</span>
          </div>
          <div class="meta-card">
            <span class="meta-card__label">Equipo</span>
            <strong class="meta-card__value">{{ data.player?.equipo?.nombre || '—' }}</strong>
            <span class="meta-card__sub">{{ data.player?.estado || '—' }}</span>
          </div>
          <div class="meta-card">
            <span class="meta-card__label">Nacionalidad</span>
            <strong class="meta-card__value">{{ data.player?.nacionalidad || '—' }}</strong>
            <span class="meta-card__sub">Nace {{ data.player?.fecha_nacimiento || '—' }}</span>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="section-head">
          <h2 class="section-title">Por temporada</h2>
        </div>
        <div class="table-wrap">
          <table class="raw-table">
            <thead>
              <tr>
                <th>Temporada</th>
                <th>Equipo</th>
                <th>Titular</th>
                <th>Goles</th>
                <th>Asist.</th>
                <th>Tiros</th>
                <th>Tiros a puerta</th>
                <th>Faltas cometidas</th>
                <th>Faltas recibidas</th>
                <th>Fuera de juego</th>
                <th>TA</th>
                <th>TR</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in data.temporada || []" :key="row.temporada">
                <td>{{ row.temporada }}</td>
                <td>{{ row.equipo }}</td>
                <td>{{ row.strt }}</td>
                <td>{{ row.g }}</td>
                <td>{{ row.a }}</td>
                <td>{{ row.shot }}</td>
                <td>{{ row.sog }}</td>
                <td>{{ row.fc }}</td>
                <td>{{ row.fa }}</td>
                <td>{{ row.of }}</td>
                <td>{{ row.yc }}</td>
                <td>{{ row.rc }}</td>
              </tr>
              <tr v-if="!data.temporada?.length">
                <td colspan="12" class="empty-state">Sin datos de temporada para este jugador.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="panel">
        <div class="section-head">
          <h2 class="section-title">Últimos partidos</h2>
        </div>
        <div class="table-wrap">
          <table class="raw-table">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Competición</th>
                <th>Rival</th>
                <th>Resultado</th>
                <th>Titular</th>
                <th>Goles</th>
                <th>Asist.</th>
                <th>Tiros</th>
                <th>Tiros a puerta</th>
                <th>Faltas cometidas</th>
                <th>Faltas recibidas</th>
                <th>TA</th>
                <th>TR</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(partido, index) in data.ultimos_partidos || []" :key="index">
                <td>{{ formatFecha(partido.fecha) }}</td>
                <td>{{ partido.competicion }}</td>
                <td>{{ partido.rival }}</td>
                <td>{{ partido.resultado }} {{ partido.marcador }}</td>
                <td>{{ partido.stats?.app }}</td>
                <td>{{ partido.stats?.g }}</td>
                <td>{{ partido.stats?.a }}</td>
                <td>{{ partido.stats?.shot }}</td>
                <td>{{ partido.stats?.sog }}</td>
                <td>{{ partido.stats?.fc }}</td>
                <td>{{ partido.stats?.fa }}</td>
                <td>{{ partido.stats?.yc }}</td>
                <td>{{ partido.stats?.rc }}</td>
              </tr>
              <tr v-if="!data.ultimos_partidos?.length">
                <td colspan="13" class="empty-state">Sin partidos recientes para este jugador.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Button from 'primevue/button'
import { GetJugadorAnalisisUseCase } from '@/Futbol/Application/UseCase/GetJugadorAnalisis/GetJugadorAnalisisUseCase'

const route = useRoute()
const loading = ref(true)
const error = ref('')
const data = ref({ player: null, temporada: [], ultimos_partidos: [] })

onMounted(async () => {
  try {
    data.value = await GetJugadorAnalisisUseCase(route.params.playerId)
  } catch (e) {
    error.value = e?.response?.status === 404 ? 'Jugador no encontrado en ESPN.' : 'No se pudo cargar el análisis del jugador.'
  } finally {
    loading.value = false
  }
})

function formatFecha(fecha) {
  if (!fecha) return '—'
  return new Date(fecha).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<style scoped>
.loading-state, .empty-state { color: var(--tokyo-fg-dim); padding: var(--space-8); text-align: center; }
.section-head { margin-bottom: var(--space-3); }
.section-title { margin: 0; font-size: 1rem; }
.meta-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: var(--space-4); margin-bottom: var(--space-5); }
.meta-card { display: flex; flex-direction: column; gap: 6px; padding: var(--space-3); border-radius: 12px; background: rgba(41, 46, 66, 0.82); border: 1px solid rgba(125, 207, 255, 0.12); }
.meta-card__label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--tokyo-fg-dim); }
.meta-card__value { font-size: 1rem; }
.meta-card__sub { color: var(--tokyo-fg-dim); }
.table-wrap { overflow-x: auto; }
.raw-table { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
.raw-table th, .raw-table td { padding: var(--space-2) var(--space-3); border-bottom: 1px solid rgba(255,255,255,0.05); text-align: left; vertical-align: top; white-space: nowrap; }
.raw-table th { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--tokyo-fg-dim); }
@media (max-width: 960px) { .meta-grid { grid-template-columns: 1fr; } }
</style>
