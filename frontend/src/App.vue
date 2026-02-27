<template>
  <div class="app">
    <header class="app-header">
      <div class="app-header__inner">
        <span class="app-header__logo">
          <i class="pi pi-book" style="font-size: 1.2rem" />
          CodeMV
        </span>
        <nav class="app-header__nav">
          <RouterLink to="/studies">Studies</RouterLink>
          <RouterLink to="/ad/organizations">Active Directory</RouterLink>
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
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterView, RouterLink } from 'vue-router'
import Button from 'primevue/button'

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
  background: var(--p-surface-0, #fff);
  border-bottom: 1px solid var(--p-surface-200, #e2e8f0);
  position: sticky;
  top: 0;
  z-index: 100;
}

.app-header__inner {
  max-width: 1200px;
  margin: 0 auto;
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
}

.app-header__nav {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.app-header__nav a {
  color: var(--p-surface-600, #475569);
  text-decoration: none;
  font-size: 0.9rem;
}

.app-header__nav a:hover {
  color: var(--p-primary-500, #6366f1);
}

.app-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
}
</style>
