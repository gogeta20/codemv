<template>
  <div class="page">
    <div class="page__header">
      <div>
        <h1 class="page__title">{{ partido?.competition?.label ?? 'Detalle de copa' }}</h1>
        <span class="page__subtitle">{{ partido?.name ?? 'Cargando...' }}</span>
      </div>
      <div class="page__actions">
        <RouterLink to="/futbol/copas">
          <Button label="Volver a copas" icon="pi pi-arrow-left" severity="secondary" size="small" />
        </RouterLink>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <i class="pi pi-spin pi-spinner" /> Cargando detalle...
    </div>

    <template v-else-if="partido">
      <section class="panel detail-hero">
        <div class="team-block">
          <img v-if="partido.local.logo" :src="partido.local.logo" :alt="partido.local.name" class="team-logo" />
          <strong>{{ partido.local.name }}</strong>
          <span>{{ partido.local.domestic.country }} · {{ partido.local.domestic.league_label }}</span>
          <span class="strength-pill">#{{ partido.local.domestic.strength_rank }} · {{ partido.local.domestic.strength_score }}</span>
        </div>

        <div class="hero-center">
          <span class="hero-status">{{ partido.status.detail ?? partido.status.description }}</span>
          <div class="hero-score">
            {{ scoreVal(partido.local.score) }} - {{ scoreVal(partido.visitante.score) }}
          </div>
          <span class="hero-date">{{ formatDate(partido.date) }}</span>
        </div>

        <div class="team-block team-block--right">
          <img v-if="partido.visitante.logo" :src="partido.visitante.logo" :alt="partido.visitante.name" class="team-logo" />
          <strong>{{ partido.visitante.name }}</strong>
          <span>{{ partido.visitante.domestic.country }} · {{ partido.visitante.domestic.league_label }}</span>
          <span class="strength-pill">#{{ partido.visitante.domestic.strength_rank }} · {{ partido.visitante.domestic.strength_score }}</span>
        </div>
      </section>

      <section class="detail-grid">
        <article class="panel detail-card">
          <span class="dashboard-kicker">Lectura rápida</span>
          <h2>{{ partido.strength.summary }}</h2>
          <p>Diferencia de fuerza: <strong>{{ signed(partido.strength.diff) }}</strong></p>
          <p>Confianza: <strong>{{ partido.strength.confidence }}</strong></p>
        </article>

        <article class="panel detail-card">
          <span class="dashboard-kicker">Contexto</span>
          <p><strong>Sede:</strong> {{ partido.details?.venue?.name ?? partido.venue.name ?? '—' }}</p>
          <p><strong>Ciudad:</strong> {{ partido.details?.venue?.city ?? partido.venue.city ?? '—' }}</p>
          <p><strong>País:</strong> {{ partido.details?.venue?.country ?? partido.venue.country ?? '—' }}</p>
          <p><strong>Temporada:</strong> {{ partido.details?.season ?? '—' }}</p>
        </article>

        <article class="panel detail-card">
          <span class="dashboard-kicker">Forma</span>
          <p><strong>{{ partido.local.short_name }}:</strong> {{ partido.local.form ?? '—' }}</p>
          <p><strong>{{ partido.visitante.short_name }}:</strong> {{ partido.visitante.form ?? '—' }}</p>
        </article>
      </section>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import Button from 'primevue/button'
import { GetCopaPartidoDetalleUseCase } from '@/Futbol/Application/UseCase/GetCopaPartidoDetalle/GetCopaPartidoDetalleUseCase'

const route = useRoute()
const loading = ref(true)
const partido = ref(null)

onMounted(async () => {
  try {
    partido.value = await GetCopaPartidoDetalleUseCase(route.params.competition, route.params.eventId)
  } finally {
    loading.value = false
  }
})

function scoreVal(value) {
  return value === null || value === undefined ? '—' : value
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function signed(value) {
  return value > 0 ? `+${value}` : String(value)
}
</script>

<style scoped>
.loading-state { padding: var(--space-6); text-align: center; color: var(--tokyo-fg-dim); }
.detail-hero { display: grid; grid-template-columns: 1fr auto 1fr; gap: 1rem; align-items: center; margin-bottom: 1rem; }
.team-block { display: flex; flex-direction: column; gap: .25rem; }
.team-block--right { text-align: right; align-items: end; }
.team-logo { width: 48px; height: 48px; object-fit: contain; }
.hero-center { text-align: center; }
.hero-status, .hero-date { color: var(--tokyo-fg-dim); font-size: .82rem; }
.hero-score { font-size: 2rem; font-weight: 800; margin: .35rem 0; }
.strength-pill { display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; padding: .2rem .55rem; background: rgba(122,162,247,.16); color: #7aa2f7; font-size: .74rem; font-weight: 700; }
.detail-grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 1rem; }
.detail-card h2 { margin: .25rem 0 .6rem; font-size: 1rem; }
.detail-card p { margin: .35rem 0; color: var(--tokyo-fg-dim); }
@media (max-width: 900px) {
  .detail-hero { grid-template-columns: 1fr; text-align: center; }
  .team-block, .team-block--right { align-items: center; text-align: center; }
  .detail-grid { grid-template-columns: 1fr; }
}
</style>
