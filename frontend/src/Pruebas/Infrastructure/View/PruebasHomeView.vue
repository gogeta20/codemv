<template>
  <div>
    <div class="page__header">
      <h1 class="page__title">🧪 Pruebas</h1>
      <span class="page__subtitle">Selecciona un contexto de pruebas</span>
    </div>

    <div class="context-grid">
      <RouterLink
        v-for="ctx in contexts"
        :key="ctx.path"
        :to="ctx.path"
        class="context-card"
      >
        <div class="context-card__icon">{{ ctx.icon }}</div>
        <div class="context-card__body">
          <h3>{{ ctx.name }}</h3>
          <p>{{ ctx.description }}</p>
        </div>
        <div class="context-card__arrow">
          <i class="pi pi-chevron-right" />
        </div>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'

const contexts = [
  {
    name: 'Identity',
    icon: '🔐',
    description: 'Crear/eliminar organizaciones y usuarios contra Identity API + verificar en AD',
    path: '/pruebas/identity',
  },
  // Futuro:
  // { name: 'DNS', icon: '🌐', description: 'Tests de resolución DNS', path: '/pruebas/dns' },
  // { name: 'Panel', icon: '📊', description: 'Tests del Panel', path: '/pruebas/panel' },
]
</script>

<style scoped>
.context-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.context-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  text-decoration: none;
  color: inherit;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.context-card:hover {
  border-color: #6366f1;
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.12);
}

.context-card__icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.context-card__body {
  flex: 1;
}

.context-card__body h3 {
  margin: 0 0 0.25rem;
  font-size: 1.05rem;
  font-weight: 600;
  color: #1e293b;
}

.context-card__body p {
  margin: 0;
  font-size: 0.82rem;
  color: #64748b;
  line-height: 1.4;
}

.context-card__arrow {
  color: #cbd5e1;
  font-size: 1rem;
}

.context-card:hover .context-card__arrow {
  color: #6366f1;
}
</style>
