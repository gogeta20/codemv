<template>
  <div class="page">
    <div class="page__header">
      <div>
        <h1 class="page__title">Mundial 2026</h1>
        <span class="page__subtitle">{{ fechaDisplay }} — selección diaria de partidos</span>
      </div>
      <div class="page__actions">
        <RouterLink to="/futbol/seleccion">
          <Button label="Ligas de clubes" icon="pi pi-globe" severity="secondary" size="small" />
        </RouterLink>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <i class="pi pi-spin pi-spinner" /> Cargando selección del Mundial...
    </div>

    <template v-else>
      <TabView v-model:activeIndex="tabActivo">
        <TabPanel header="🌍 Top partidos">
          <div class="partidos-grid">
            <MundialPartidoCard v-for="s in seleccion.top" :key="s.uuid" :seleccion="s" />
            <div v-if="!seleccion.top?.length" class="empty-state">Sin partidos seleccionados para hoy.</div>
          </div>
        </TabPanel>
        <TabPanel header="🔒 Under — pocos goles">
          <div class="partidos-grid">
            <MundialPartidoCardUnder v-for="s in seleccion.under" :key="s.uuid" :seleccion="s" />
            <div v-if="!seleccion.under?.length" class="empty-state">Sin selección under para hoy.</div>
          </div>
        </TabPanel>
      </TabView>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Button from 'primevue/button'
import MundialPartidoCard from './components/MundialPartidoCard.vue'
import MundialPartidoCardUnder from './components/MundialPartidoCardUnder.vue'
import { GetMundialSeleccionUseCase } from '@/Futbol/Application/UseCase/GetMundialSeleccion/GetMundialSeleccionUseCase'

const loading   = ref(true)
const tabActivo = ref(0)
const seleccion = ref({ top: [], under: [] })

const fechaDisplay = computed(() => {
  return new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
})

onMounted(async () => {
  try {
    seleccion.value = await GetMundialSeleccionUseCase()
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.loading-state {
  display: flex; align-items: center; gap: var(--space-2);
  color: var(--tokyo-fg-dim); padding: var(--space-6) 0;
}
.partidos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: var(--space-4);
  padding-top: var(--space-4);
}
.empty-state {
  color: var(--tokyo-fg-dim); font-size: 0.9rem;
  padding: var(--space-6) 0; text-align: center; grid-column: 1 / -1;
}
</style>
