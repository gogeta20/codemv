<template>
  <div v-if="hasData" class="report-bar">
    <div class="report-bar__left">
      <span class="report-bar__robot">🤖</span>
      <span class="report-bar__title">{{ report.title || 'Informe de pruebas' }}</span>
      <Tag
        :value="`${passCount}/${totalCount} OK`"
        :severity="allPassed ? 'success' : hasFails ? 'danger' : 'secondary'"
      />
    </div>
    <div class="report-bar__actions">
      <Button
        label="Ver informe"
        icon="pi pi-file"
        severity="secondary"
        size="small"
        outlined
        @click="openReport"
      />
      <Button
        label="Copiar para Claude"
        icon="pi pi-copy"
        size="small"
        @click="copy"
      />
      <Transition name="fade">
        <Tag v-if="copied" value="✅ Copiado" severity="success" />
      </Transition>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const STORAGE_KEY = 'pruebas_test_report'

const props = defineProps({
  /**
   * Report data structure:
   * {
   *   title: string,
   *   steps: Array<{
   *     name: string,
   *     status: 'ok' | 'fail' | 'pending',
   *     duration?: number,
   *     data?: object | null,
   *     error?: string | null,
   *     meta?: Record<string, string>
   *   }>
   * }
   */
  report: {
    type: Object,
    default: null,
  },
})

const router = useRouter()
const copied = ref(false)

const hasData = computed(() => (props.report?.steps?.length ?? 0) > 0)
const totalCount = computed(() => props.report?.steps?.length ?? 0)
const passCount = computed(() => props.report?.steps?.filter(s => s.status === 'ok').length ?? 0)
const failCount = computed(() => props.report?.steps?.filter(s => s.status === 'fail').length ?? 0)
const allPassed = computed(() => passCount.value === totalCount.value && totalCount.value > 0)
const hasFails = computed(() => failCount.value > 0)

function generateMarkdown() {
  const date = new Date().toLocaleString('es-ES')
  const title = props.report.title || 'Test'
  const lines = []

  lines.push(`# Informe de pruebas — ${title}`)
  lines.push(`Fecha: ${date}`)
  lines.push('')

  // Summary table
  lines.push('## Resumen')
  for (const step of props.report.steps) {
    const icon = step.status === 'ok' ? '✅' : step.status === 'fail' ? '❌' : '⬜'
    const dur = step.duration != null ? ` (${step.duration}ms)` : ''
    lines.push(`${icon} ${step.name}${dur}`)
  }
  lines.push('')

  // Detail per step
  lines.push('## Detalle de pasos')
  for (const step of props.report.steps) {
    const icon = step.status === 'ok' ? '✅' : step.status === 'fail' ? '❌' : '⬜'
    lines.push(`### ${icon} ${step.name}`)

    if (step.meta && Object.keys(step.meta).length > 0) {
      for (const [k, v] of Object.entries(step.meta)) {
        if (v != null) lines.push(`- **${k}**: \`${v}\``)
      }
    }

    if (step.error) {
      lines.push(`- **Error**: ${step.error}`)
    }

    if (step.data) {
      lines.push('```json')
      lines.push(JSON.stringify(step.data, null, 2))
      lines.push('```')
    }

    lines.push('')
  }

  return lines.join('\n')
}

function openReport() {
  const markdown = generateMarkdown()
  localStorage.setItem(STORAGE_KEY, JSON.stringify({
    markdown,
    timestamp: new Date().toISOString(),
  }))
  router.push('/pruebas/report')
}

async function copy() {
  const markdown = generateMarkdown()
  await navigator.clipboard.writeText(markdown)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2500)
}
</script>

<style scoped>
.report-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 1rem;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  margin-top: 2rem;
}

.report-bar__left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.report-bar__robot {
  font-size: 1.25rem;
  line-height: 1;
}

.report-bar__title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #334155;
}

.report-bar__actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
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
