<template>
  <div class="app">
    <header class="app-header">
      <div class="app-header__inner">
        <RouterLink to="/ad/organizations" class="app-header__logo">
          <i class="pi pi-book" style="font-size: 1.2rem" />
          CodeMV
        </RouterLink>
        <nav class="app-header__nav">
          <RouterLink to="/studies" v-tooltip.bottom="'Studies'">
            <i class="pi pi-book" />
          </RouterLink>
          <RouterLink to="/ad/organizations" v-tooltip.bottom="'Active Directory'">
            <i class="pi pi-sitemap" />
          </RouterLink>
          <RouterLink to="/acciones" v-tooltip.bottom="'Acciones'">
            <i class="pi pi-chart-line" />
          </RouterLink>
          <RouterLink to="/portafolio" v-tooltip.bottom="'Portafolio'">
            <i class="pi pi-briefcase" />
          </RouterLink>
          <RouterLink to="/futbol/seleccion" v-tooltip.bottom="'Fútbol'">
            <span class="app-header__sport-ball" aria-hidden="true">⚽</span>
          </RouterLink>
          <RouterLink to="/pruebas" v-tooltip.bottom="'Pruebas'">
            <i class="pi pi-check-square" />
          </RouterLink>
          <Button
            :icon="uuidCopied ? 'pi pi-check' : 'pi pi-hashtag'"
            size="small"
            text
            v-tooltip.bottom="uuidCopied ? '¡Copiado!' : 'Generar UUID'"
            :style="{ color: uuidCopied ? '#22c55e' : undefined }"
            @click="copyUuid"
          />
        </nav>
      </div>
    </header>

    <main class="app-main">
      <RouterView />
    </main>

    <DevFooter />
    <Toast />
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterView, RouterLink } from 'vue-router'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import DevFooter from '@/core/components/DevFooter.vue'

const uuidCopied = ref(false)

async function copyUuid() {
  await navigator.clipboard.writeText(crypto.randomUUID())
  uuidCopied.value = true
  setTimeout(() => { uuidCopied.value = false }, 1500)
}
</script>

<style>
/* Reset and body are in core/styles/base.css */

.app-header {
  background: var(--tokyo-bg-secondary);
  border-bottom: 1px solid var(--tokyo-bg-tertiary);
  position: sticky;
  top: 0;
  z-index: 100;
}

.app-header__inner {
  padding: 0 1.5rem;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.app-header__logo {
  font-weight: 700;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--p-primary-500, #6366f1);
  text-decoration: none;
}

.app-header__nav {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.app-header__nav a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 6px;
  color: var(--tokyo-fg-dim);
  text-decoration: none;
  font-size: 1rem;
  transition: color 0.15s, background 0.15s;
}

.app-header__sport-ball {
  font-size: 1rem;
  line-height: 1;
}

.app-header__nav a:hover,
.app-header__nav a.router-link-active {
  color: var(--tokyo-blue);
  background: var(--tokyo-bg-tertiary);
}

.app-main {
  padding: 2rem 1.5rem 3.5rem;
}
</style>
