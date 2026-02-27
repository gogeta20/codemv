<template>
  <div style="padding: 2rem; max-width: 900px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
      <Button icon="pi pi-arrow-left" label="Volver" text size="small" @click="$router.back()" />
      <div style="display: flex; gap: 0.5rem; align-items: center;">
        <Button
          v-if="study"
          :icon="copied ? 'pi pi-check' : 'pi pi-copy'"
          size="small"
          text
          :style="{ color: copied ? '#22c55e' : undefined }"
          v-tooltip="'Copiar endpoint /ctx'"
          @click="copyCtx"
        />
        <Button
          v-if="study"
          label="Editar"
          icon="pi pi-pencil"
          size="small"
          @click="router.push(`/studies/${route.params.uuid}/edit`)"
        />
      </div>
    </div>
    <div v-if="loading">Cargando...</div>
    <div v-else-if="study">
      <h1>{{ study.title }}</h1>
      <div style="color: #888; font-size: 0.9rem; margin-bottom: 1rem;">
        {{ study.category?.name }} · {{ study.created_at }}
        <span v-if="study.is_favorite"> ⭐</span>
      </div>
      <div v-if="study.summary" class="study-summary">
        {{ study.summary }}
      </div>
      <div class="study-content md-body" v-html="renderedContent" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import api from '@/core/http/api'
import { useMarkdown } from '@/core/composables/useMarkdown'

const route = useRoute()
const router = useRouter()
const { render } = useMarkdown()
const study = ref(null)
const loading = ref(true)
const copied = ref(false)

const renderedContent = computed(() => render(study.value?.content ?? ''))

onMounted(async () => {
  const { data } = await api.get(`/api/studies/${route.params.uuid}`)
  study.value = data.data
  loading.value = false
})

async function copyCtx() {
  await navigator.clipboard.writeText(`http://localhost:8280/api/studies/${route.params.uuid}/ctx`)
  copied.value = true
  setTimeout(() => { copied.value = false }, 1500)
}
</script>

<style scoped>
.study-summary {
  background: #f8f8f8;
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1.5rem;
  color: #555;
  font-size: 0.95rem;
  line-height: 1.5;
}
</style>

<style>
/* Non-scoped: targets markdown-it generated HTML inside .md-body */
.md-body {
  line-height: 1.75;
  font-size: 0.97rem;
  color: #1e293b;
}
.md-body h1, .md-body h2, .md-body h3, .md-body h4 {
  font-weight: 700;
  margin: 1.5rem 0 0.5rem;
  color: #0f172a;
}
.md-body h1 { font-size: 1.6rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.3rem; }
.md-body h2 { font-size: 1.3rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.2rem; }
.md-body h3 { font-size: 1.1rem; }
.md-body p  { margin: 0.75rem 0; }
.md-body ul, .md-body ol { padding-left: 1.5rem; margin: 0.75rem 0; }
.md-body li { margin: 0.25rem 0; }
.md-body blockquote {
  border-left: 4px solid #6366f1;
  margin: 1rem 0;
  padding: 0.5rem 1rem;
  background: #f1f5ff;
  color: #475569;
  border-radius: 0 6px 6px 0;
}
.md-body code:not(.hljs) {
  background: #f1f5f9;
  color: #6366f1;
  padding: 0.15em 0.4em;
  border-radius: 4px;
  font-size: 0.875em;
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
}
.md-body .hljs-block {
  margin: 1rem 0;
  border-radius: 8px;
  overflow: auto;
  background: #1e1e2e;
}
.md-body .hljs-block code {
  display: block;
  padding: 1rem 1.25rem;
  font-size: 0.85rem;
  font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
  line-height: 1.6;
}
.md-body a { color: #6366f1; text-decoration: underline; }
.md-body table {
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
  font-size: 0.9rem;
}
.md-body th, .md-body td {
  border: 1px solid #e2e8f0;
  padding: 0.5rem 0.75rem;
  text-align: left;
}
.md-body th { background: #f8fafc; font-weight: 600; }
.md-body hr { border: none; border-top: 1px solid #e2e8f0; margin: 1.5rem 0; }
</style>
