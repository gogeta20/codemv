<template>
  <div class="page">
    <div class="page__header">
      <div>
        <h1 class="page__title">Selección del día</h1>
        <span class="page__subtitle">{{ fechaDisplay }} — partidos elegidos para análisis</span>
      </div>
      <div class="page__actions">
        <RouterLink to="/futbol/mundial">
          <Button label="Mundial 2026" icon="pi pi-flag" severity="primary" size="small" />
        </RouterLink>
        <RouterLink to="/futbol/ligas/gpm">
          <Button label="Ligas — goles/partido" icon="pi pi-globe" severity="secondary" size="small" />
        </RouterLink>
        <RouterLink to="/futbol/partidos">
          <Button label="Ver todos los partidos" icon="pi pi-list" severity="secondary" size="small" />
        </RouterLink>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <i class="pi pi-spin pi-spinner" /> Cargando selección...
    </div>

    <template v-else>
      <TabView v-model:activeIndex="tabActivo">
        <TabPanel header="Tabla A — con contexto temporada">
          <div class="partidos-grid">
            <PartidoCard v-for="s in seleccion.con_temporada" :key="s.uuid" :seleccion="s" />
            <div v-if="!seleccion.con_temporada?.length" class="empty-state">Sin selección para hoy.</div>
          </div>
        </TabPanel>
        <TabPanel header="Tabla B — sin penalización temporada">
          <div class="partidos-grid">
            <PartidoCard v-for="s in seleccion.sin_temporada" :key="s.uuid" :seleccion="s" />
            <div v-if="!seleccion.sin_temporada?.length" class="empty-state">Sin selección para hoy.</div>
          </div>
        </TabPanel>
        <TabPanel header="🔒 Under — pocos goles esperados">
          <div class="partidos-grid">
            <PartidoCardUnder v-for="s in seleccion.under" :key="s.uuid" :seleccion="s" />
            <div v-if="!seleccion.under?.length" class="empty-state">Sin selección under para hoy.</div>
          </div>
        </TabPanel>

        <!-- TAB FAVORITOS -->
        <TabPanel>
          <template #header>
            <span>⭐ Favoritos</span>
            <span v-if="favoritos.length" class="fav-count-badge">{{ favoritos.length }}</span>
          </template>

          <!-- Panel de gestión -->
          <div class="fav-manager panel">
            <div class="fav-manager__header" @click="gestionVisible = !gestionVisible">
              <span class="fav-manager__title">
                <i class="pi pi-cog" /> Gestionar equipos favoritos
              </span>
              <i :class="gestionVisible ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" style="font-size:0.8rem; color:var(--tokyo-fg-dim)" />
            </div>

            <div v-if="gestionVisible" class="fav-manager__body">
              <div class="fav-search-row">
                <Select
                  v-model="ligaSeleccionada"
                  :options="LIGAS"
                  optionLabel="label"
                  optionValue="code"
                  placeholder="Selecciona una liga..."
                  class="fav-liga-select"
                  @change="onLigaChange"
                />
                <Select
                  v-model="equipoSeleccionado"
                  :options="equiposLiga"
                  optionLabel="team_name"
                  :placeholder="loadingEquipos ? 'Cargando...' : 'Elige equipo...'"
                  :disabled="!ligaSeleccionada || loadingEquipos"
                  class="fav-equipo-select"
                />
                <Button
                  icon="pi pi-plus"
                  label="Añadir"
                  size="small"
                  :disabled="!equipoSeleccionado"
                  :loading="saving"
                  @click="addFavorito"
                />
              </div>

              <div v-if="favoritos.length" class="fav-list">
                <div v-for="fav in favoritos" :key="fav.uuid" class="fav-item">
                  <span class="fav-item__star">⭐</span>
                  <span class="fav-item__name">{{ fav.team_name }}</span>
                  <span class="fav-item__liga">{{ fav.liga_nombre }} · {{ fav.pais }}</span>
                  <Button icon="pi pi-times" text severity="danger" size="small" @click="removeFavorito(fav)" />
                </div>
              </div>
              <div v-else class="fav-empty-hint">Aún no hay favoritos. Selecciona una liga y añade un equipo.</div>
            </div>
          </div>

          <!-- Partidos analizados hoy (PartidoCard completo) -->
          <template v-if="seleccion.favorito?.length">
            <p class="fav-section-label">Análisis de hoy</p>
            <div class="partidos-grid">
              <PartidoCard v-for="s in seleccion.favorito" :key="s.uuid" :seleccion="s" />
            </div>
          </template>

          <!-- Próximos partidos (real-time ESPN, equipos que no juegan hoy) -->
          <div v-if="!favoritos.length" class="empty-state" style="margin-top:var(--space-5)">
            Añade equipos favoritos para ver sus próximos partidos.
          </div>
          <template v-else>
            <p class="fav-section-label" style="margin-top:var(--space-5)">Próximos partidos</p>
            <div v-if="loadingPartidos" class="loading-state" style="padding:var(--space-4)">
              <i class="pi pi-spin pi-spinner" /> Buscando próximos partidos...
            </div>
            <div v-else class="fav-partidos-grid">
              <div v-for="entry in favoritosPartidos" :key="entry.favorito.uuid" class="fav-partido-card panel">
                <div class="fav-partido-card__header">
                  <span class="fav-partido-card__team">⭐ {{ entry.favorito.team_name }}</span>
                  <span class="fav-partido-card__liga">{{ entry.favorito.liga_nombre }}</span>
                </div>
                <template v-if="entry.proximo_partido">
                  <div class="fav-partido-card__match">
                    <span :class="{ 'fav-match--fav': entry.proximo_partido.es_local }">{{ entry.proximo_partido.equipo_local }}</span>
                    <span class="fav-match-sep">vs</span>
                    <span :class="{ 'fav-match--fav': !entry.proximo_partido.es_local }">{{ entry.proximo_partido.equipo_visit }}</span>
                  </div>
                  <div class="fav-partido-card__meta">
                    <span>📅 {{ formatFecha(entry.proximo_partido.fecha) }}</span>
                    <span>🕐 {{ formatHora(entry.proximo_partido.hora_utc) }}</span>
                    <span v-if="entry.proximo_partido.estadio">🏟 {{ entry.proximo_partido.estadio }}</span>
                  </div>
                </template>
                <div v-else class="fav-partido-card__empty">Sin partido próximo en el calendario ESPN.</div>
              </div>
            </div>
          </template>
        </TabPanel>
      </TabView>
    </template>

    <FutbolLeyenda />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Button from 'primevue/button'
import Select from 'primevue/select'
import { useToast } from 'primevue/usetoast'
import PartidoCard from './components/PartidoCard.vue'
import PartidoCardUnder from './components/PartidoCardUnder.vue'
import FutbolLeyenda from './components/FutbolLeyenda.vue'
import { GetSeleccionDiariaUseCase } from '@/Futbol/Application/UseCase/GetSeleccionDiaria/GetSeleccionDiariaUseCase'
import { ListFavoritosUseCase } from '@/Futbol/Application/UseCase/ListFavoritos/ListFavoritosUseCase'
import { AddFavoritoUseCase } from '@/Futbol/Application/UseCase/AddFavorito/AddFavoritoUseCase'
import { RemoveFavoritoUseCase } from '@/Futbol/Application/UseCase/RemoveFavorito/RemoveFavoritoUseCase'
import { GetFavoritosPartidosUseCase } from '@/Futbol/Application/UseCase/GetFavoritosPartidos/GetFavoritosPartidosUseCase'
import { GetTeamsByLigaUseCase } from '@/Futbol/Application/UseCase/GetTeamsByLiga/GetTeamsByLigaUseCase'

const toast = useToast()

const LIGAS = [
  { label: 'Alemania — Bundesliga',       code: 'ger.1' },
  { label: 'Alemania — 2. Bundesliga',    code: 'ger.2' },
  { label: 'Argentina — Liga Profesional',code: 'arg.1' },
  { label: 'Australia — A-League',        code: 'aus.1' },
  { label: 'Bélgica — First Division A',  code: 'bel.1' },
  { label: 'Bolivia — Liga Profesional',  code: 'bol.1' },
  { label: 'Brasil — Série A',            code: 'bra.1' },
  { label: 'Dinamarca — Superliga',       code: 'den.1' },
  { label: 'Escocia — Premiership',       code: 'sco.1' },
  { label: 'España — La Liga',            code: 'esp.1' },
  { label: 'España — Segunda División',   code: 'esp.2' },
  { label: 'Francia — Ligue 1',           code: 'fra.1' },
  { label: 'Inglaterra — Premier League', code: 'eng.1' },
  { label: 'Inglaterra — Championship',   code: 'eng.2' },
  { label: 'Italia — Serie A',            code: 'ita.1' },
  { label: 'Italia — Serie B',            code: 'ita.2' },
  { label: 'México — Liga MX',            code: 'mex.1' },
  { label: 'MLS',                         code: 'usa.1' },
  { label: 'Noruega — Eliteserien',       code: 'nor.1' },
  { label: 'Países Bajos — Eredivisie',   code: 'ned.1' },
  { label: 'Portugal — Primeira Liga',    code: 'por.1' },
  { label: 'Suecia — Allsvenskan',        code: 'swe.1' },
  { label: 'Turquía — Süper Lig',         code: 'tur.1' },
]

const loading         = ref(true)
const tabActivo       = ref(0)
const seleccion       = ref({ con_temporada: [], sin_temporada: [], under: [], favorito: [] })
const favoritos       = ref([])
const favoritosPartidos = ref([])
const loadingPartidos = ref(false)
const gestionVisible  = ref(false)
const ligaSeleccionada  = ref(null)
const equipoSeleccionado = ref(null)
const equiposLiga     = ref([])
const loadingEquipos  = ref(false)
const saving          = ref(false)

const fechaDisplay = computed(() =>
  new Date().toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
)

onMounted(async () => {
  const [sel, favs] = await Promise.all([
    GetSeleccionDiariaUseCase(),
    ListFavoritosUseCase(),
  ])
  seleccion.value = sel
  favoritos.value = favs
  loading.value = false

  if (favs.length) {
    loadPartidos()
  }
})

watch(tabActivo, (idx) => {
  // Tab favoritos es el índice 3
  if (idx === 3 && favoritos.value.length && !favoritosPartidos.value.length) {
    loadPartidos()
  }
})

async function loadPartidos() {
  loadingPartidos.value = true
  try {
    favoritosPartidos.value = await GetFavoritosPartidosUseCase()
  } finally {
    loadingPartidos.value = false
  }
}

async function onLigaChange() {
  equipoSeleccionado.value = null
  equiposLiga.value = []
  if (!ligaSeleccionada.value) return
  loadingEquipos.value = true
  try {
    equiposLiga.value = await GetTeamsByLigaUseCase(ligaSeleccionada.value)
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar los equipos', life: 3000 })
  } finally {
    loadingEquipos.value = false
  }
}

async function addFavorito() {
  if (!equipoSeleccionado.value || !ligaSeleccionada.value) return
  saving.value = true
  const ligaInfo = LIGAS.find(l => l.code === ligaSeleccionada.value)
  try {
    const nuevo = await AddFavoritoUseCase({
      espn_team_id:   equipoSeleccionado.value.espn_team_id,
      espn_liga_code: ligaSeleccionada.value,
      team_name:      equipoSeleccionado.value.team_name,
      liga_nombre:    ligaInfo?.label ?? ligaSeleccionada.value,
      pais:           ligaInfo?.label.split(' — ')[0] ?? '',
    })
    favoritos.value.push(nuevo)
    equipoSeleccionado.value = null
    toast.add({ severity: 'success', summary: `${nuevo.team_name} añadido`, life: 2000 })
    loadPartidos()
  } catch (e) {
    const msg = e.response?.status === 409 ? 'Ya está en favoritos' : 'Error al añadir'
    toast.add({ severity: 'error', summary: msg, life: 3000 })
  } finally {
    saving.value = false
  }
}

async function removeFavorito(fav) {
  try {
    await RemoveFavoritoUseCase(fav.uuid)
    favoritos.value = favoritos.value.filter(f => f.uuid !== fav.uuid)
    favoritosPartidos.value = favoritosPartidos.value.filter(e => e.favorito.uuid !== fav.uuid)
    toast.add({ severity: 'success', summary: `${fav.team_name} eliminado`, life: 2000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Error al eliminar', life: 3000 })
  }
}

function formatFecha(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr + 'T12:00:00').toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' })
}

function formatHora(isoStr) {
  if (!isoStr) return '—'
  return new Date(isoStr).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) + ' local'
}
</script>

<style scoped>
.loading-state { color: var(--tokyo-fg-dim); padding: var(--space-8); text-align: center; }
.partidos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(480px, 1fr)); gap: var(--space-5); padding-top: var(--space-4); }
.empty-state   { color: var(--tokyo-fg-dim); padding: var(--space-8); text-align: center; font-size: 0.9rem; }

/* Badge en la pestaña */
.fav-count-badge {
  display: inline-flex; align-items: center; justify-content: center;
  background: var(--tokyo-cyan); color: var(--tokyo-bg);
  border-radius: 10px; font-size: 0.65rem; font-weight: 700;
  min-width: 18px; height: 18px; padding: 0 5px; margin-left: 6px;
}

/* Panel gestión */
.fav-manager { margin-bottom: var(--space-4); }
.fav-manager__header {
  display: flex; justify-content: space-between; align-items: center;
  cursor: pointer; padding: var(--space-2) 0;
  user-select: none;
}
.fav-manager__title { font-size: 0.85rem; color: var(--tokyo-fg-dim); display: flex; align-items: center; gap: var(--space-2); }
.fav-manager__body  { padding-top: var(--space-3); display: flex; flex-direction: column; gap: var(--space-3); }

.fav-search-row { display: flex; gap: var(--space-2); align-items: center; flex-wrap: wrap; }
.fav-liga-select  { flex: 1; min-width: 200px; }
.fav-equipo-select { flex: 1; min-width: 180px; }

.fav-list { display: flex; flex-direction: column; gap: var(--space-2); }
.fav-item {
  display: flex; align-items: center; gap: var(--space-2);
  padding: var(--space-1) var(--space-2);
  background: var(--tokyo-bg-tertiary); border-radius: 6px;
}
.fav-item__star  { font-size: 0.85rem; }
.fav-item__name  { font-weight: 600; font-size: 0.9rem; flex: 1; }
.fav-item__liga  { font-size: 0.75rem; color: var(--tokyo-fg-dim); }
.fav-empty-hint  { font-size: 0.82rem; color: var(--tokyo-fg-dim); padding: var(--space-2) 0; }

/* Grid de partidos favoritos */
.fav-partidos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--space-4);
  padding-top: var(--space-4);
}

.fav-partido-card { display: flex; flex-direction: column; gap: var(--space-2); }
.fav-partido-card__header {
  display: flex; justify-content: space-between; align-items: center;
}
.fav-partido-card__team { font-weight: 700; font-size: 0.95rem; }
.fav-partido-card__liga { font-size: 0.72rem; color: var(--tokyo-fg-dim); text-transform: uppercase; letter-spacing: 0.04em; }

.fav-partido-card__match {
  display: flex; align-items: center; gap: var(--space-2);
  font-size: 0.9rem; font-weight: 600; flex-wrap: wrap;
}
.fav-match-sep   { color: var(--tokyo-fg-dim); font-weight: 400; }
.fav-match--fav  { color: var(--tokyo-cyan); }

.fav-partido-card__meta {
  display: flex; gap: var(--space-3); flex-wrap: wrap;
  font-size: 0.78rem; color: var(--tokyo-fg-dim);
}
.fav-partido-card__empty { font-size: 0.8rem; color: var(--tokyo-fg-dim); font-style: italic; }

.fav-section-label {
  font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.06em; color: var(--tokyo-fg-dim);
  margin: var(--space-4) 0 var(--space-2);
}
</style>
