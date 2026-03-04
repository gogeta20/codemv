<template>
  <div>
    <nav class="ad-subnav mb-6">
      <RouterLink to="/ad/organizations" class="ad-pill">
        <i class="pi pi-sitemap" />
        Organizaciones
      </RouterLink>
      <RouterLink to="/ad/users" class="ad-pill">
        <i class="pi pi-users" />
        Usuarios
      </RouterLink>
    </nav>

    <RouterView />
  </div>
</template>

<script setup>
import { RouterLink, RouterView } from 'vue-router'
</script>

<style scoped>
.ad-subnav {
  display: flex;
  gap: 0.5rem;
}

.ad-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem 1rem;
  border-radius: 999px;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--tokyo-fg-dim);
  background: var(--tokyo-bg-secondary);
  border: 1px solid var(--tokyo-bg-tertiary);
  text-decoration: none;
  transition: color 0.15s, background 0.15s, border-color 0.15s;
}

.ad-pill:hover {
  color: var(--p-primary-500, #6366f1);
  border-color: var(--p-primary-300, #a5b4fc);
}

.ad-pill.router-link-active {
  color: var(--p-primary-500, #6366f1);
  background: var(--p-primary-50, #eef2ff);
  border-color: var(--p-primary-300, #a5b4fc);
  font-weight: 600;
}
</style>
