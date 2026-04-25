<template>
  <div>
    <div class="page__header">
      <h1 class="page__title">🤖 Informe para Claude</h1>
      <div class="page__actions">
        <Button
          label="Volver"
          icon="pi pi-arrow-left"
          severity="secondary"
          size="small"
          @click="$router.back()"
        />
        <Button
          label="Copiar todo"
          icon="pi pi-copy"
          size="small"
          :disabled="!content"
          @click="copy"
        />
        <Transition name="fade">
          <Tag v-if="copied" value="✅ Copiado" severity="success" />
        </Transition>
      </div>
    </div>

    <div v-if="!content" class="report-empty">
      No hay informe generado. Ejecuta un wizard primero y pulsa <strong>Ver informe</strong>.
    </div>

    <template v-else>
      <div class="report-meta">
        Generado: {{ timestamp }}
      </div>
      <div class="report-content">
        <pre class="report-pre">{{ content }}</pre>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const STORAGE_KEY = 'pruebas_test_report'

const raw = ref(null)
const copied = ref(false)

const content = computed(() => raw.value?.markdown ?? null)
const timestamp = computed(() => {
  if (!raw.value?.timestamp) return ''
  return new Date(raw.value.timestamp).toLocaleString('es-ES')
})

onMounted(() => {
  const stored = localStorage.getItem(STORAGE_KEY)
  if (stored) raw.value = JSON.parse(stored)
})

async function copy() {
  if (!content.value) return
  await navigator.clipboard.writeText(content.value)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2500)
}
</script>

<style scoped>
.report-empty {
  padding: 2.5rem;
  text-align: center;
  color: #94a3b8;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
  font-size: 0.9rem;
}

.report-meta {
  font-size: 0.8rem;
  color: #94a3b8;
  margin-bottom: 0.75rem;
}

.report-content {
  background: #0f172a;
  border-radius: 8px;
  padding: 1.5rem;
  overflow-x: auto;
}

.report-pre {
  margin: 0;
  font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
  font-size: 0.82rem;
  color: #e2e8f0;
  white-space: pre-wrap;
  word-break: break-word;
  line-height: 1.6;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
