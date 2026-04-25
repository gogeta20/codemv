<template>
  <div class="test-card">
    <div class="test-card__header">
      <h3>🔎 Verificar Organizaciones en Identity</h3>
      <Tag :value="globalStatus" :severity="globalSeverity" />
    </div>

    <div v-if="!ready" class="test-card__disabled">
      ⚠️ Completa primero el Paso 1 (Crear Organizaciones)
    </div>

    <template v-else>
      <!-- Org A -->
      <div class="step" :class="stepClass(orgAResult)">
        <div class="step__header">
          <span class="step__label">Org A</span>
          <span class="step__code">{{ orgAUuid }}</span>
          <Tag v-if="orgAResult" :value="orgAResult.success ? '✅' : '❌'" :severity="orgAResult.success ? 'success' : 'danger'" />
          <span v-if="orgAResult" class="step__duration">{{ orgAResult.duration }}ms</span>
        </div>
        <pre v-if="orgAResult" class="step__result">{{ orgAResult.success ? JSON.stringify(orgAResult.data, null, 2) : orgAResult.error }}</pre>
      </div>

      <!-- Org B -->
      <div class="step" :class="stepClass(orgBResult)">
        <div class="step__header">
          <span class="step__label">Org B</span>
          <span class="step__code">{{ orgBUuid }}</span>
          <Tag v-if="orgBResult" :value="orgBResult.success ? '✅' : '❌'" :severity="orgBResult.success ? 'success' : 'danger'" />
          <span v-if="orgBResult" class="step__duration">{{ orgBResult.duration }}ms</span>
        </div>
        <pre v-if="orgBResult" class="step__result">{{ orgBResult.success ? JSON.stringify(orgBResult.data, null, 2) : orgBResult.error }}</pre>
      </div>

      <div class="test-card__actions">
        <Button
          label="▶ Verificar"
          icon="pi pi-play"
          :loading="running"
          :disabled="running"
          @click="run"
          size="small"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { GetOrganizationUseCase } from '@/Pruebas/Identity/Application/UseCase/Identity/GetOrganization/GetOrganizationUseCase'

const props = defineProps({
  orgAUuid: { type: String, default: null },
  orgBUuid: { type: String, default: null },
  autoRun: { type: Boolean, default: false },
})

const emit = defineEmits(['completed'])

const running = ref(false)
const hasRun = ref(false)
const orgAResult = ref(null)
const orgBResult = ref(null)

const ready = computed(() => props.orgAUuid && props.orgBUuid)

const globalStatus = computed(() => {
  if (!ready.value) return '⬜ Esperando Paso 1'
  if (running.value) return '⏳ Running'
  if (!orgAResult.value && !orgBResult.value) return '⬜ Pendiente'
  if (orgAResult.value?.success && orgBResult.value?.success) return '✅ 2/2 Encontradas'
  return '❌ Fail'
})

const globalSeverity = computed(() => {
  if (!ready.value || (!orgAResult.value && !orgBResult.value)) return 'secondary'
  if (running.value) return 'warn'
  if (orgAResult.value?.success && orgBResult.value?.success) return 'success'
  return 'danger'
})

function stepClass(result) {
  if (!result) return ''
  return result.success ? 'step--ok' : 'step--fail'
}

watch(() => [props.orgAUuid, props.orgBUuid, props.autoRun], () => {
  if (props.autoRun && ready.value && !running.value && !hasRun.value) {
    hasRun.value = true
    run()
  }
}, { immediate: true })

async function run() {
  running.value = true
  orgAResult.value = null
  orgBResult.value = null

  orgAResult.value = await GetOrganizationUseCase(props.orgAUuid)
  orgBResult.value = await GetOrganizationUseCase(props.orgBUuid)

  running.value = false

  emit('completed', {
    results: {
      orgA: orgAResult.value,
      orgB: orgBResult.value,
    },
  })
}
</script>

<style scoped>
.test-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}
.test-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}
.test-card__header h3 { margin: 0; font-size: 1rem; }
.test-card__disabled {
  padding: 0.75rem;
  background: #fefce8;
  border: 1px solid #fde68a;
  border-radius: 6px;
  font-size: 0.85rem;
  color: #92400e;
}
.test-card__actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 0.75rem;
}
.step {
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 0.75rem;
  margin-bottom: 0.5rem;
  background: #f8fafc;
}
.step--ok { background: #f0fdf4; border-color: #bbf7d0; }
.step--fail { background: #fef2f2; border-color: #fecaca; }
.step__header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}
.step__label { font-weight: 600; font-size: 0.85rem; }
.step__code { font-family: monospace; font-size: 0.78rem; color: #6366f1; }
.step__duration { font-size: 0.75rem; color: #94a3b8; }
.step__result {
  margin: 0.25rem 0 0;
  font-size: 0.75rem;
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 200px;
  overflow-y: auto;
}
</style>
