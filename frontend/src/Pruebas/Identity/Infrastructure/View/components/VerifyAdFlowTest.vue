<template>
  <div class="test-card">
    <div class="test-card__header">
      <h3>🔍 Verificar Estado Final en AD</h3>
      <Tag :value="globalStatus" :severity="globalSeverity" />
    </div>

    <div v-if="!ready" class="test-card__disabled">
      ⚠️ Completa primero todos los pasos anteriores
    </div>

    <template v-else>
      <div class="test-card__actions">
        <Button
          label="▶ Verificar en AD"
          icon="pi pi-play"
          :loading="running"
          :disabled="running"
          @click="run"
          size="small"
        />
      </div>

      <div v-for="check in checks" :key="check.label" class="step" :class="stepClass(check.result)">
        <div class="step__header">
          <span class="step__label">{{ check.label }}</span>
          <span class="step__expect">{{ check.shouldExist ? '(debe existir)' : '(NO debe existir)' }}</span>
          <Tag v-if="check.result" :value="check.result.success ? '✅' : '❌'" :severity="check.result.success ? 'success' : 'danger'" />
          <span v-if="check.result" class="step__duration">{{ check.result.duration }}ms</span>
        </div>
        <div v-if="check.result" class="step__detail">
          {{ check.result.exists ? 'Encontrado en AD' : 'No encontrado en AD' }}
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { VerifyInAdUseCase } from '@/Pruebas/Identity/Application/UseCase/Identity/VerifyInAd/VerifyInAdUseCase'

const props = defineProps({
  orgACode: { type: String, default: null },
  orgBCode: { type: String, default: null },
  autoRun: { type: Boolean, default: false },
})

const emit = defineEmits(['completed'])

const running = ref(false)
const hasRun = ref(false)
const ready = computed(() => props.orgACode && props.orgBCode)

const checks = reactive([
  { label: 'Org A', path: '', shouldExist: false, result: null },
  { label: 'Org B', path: '', shouldExist: true, result: null },
  { label: 'Users Org B', path: '', shouldExist: true, result: null },
])

const globalStatus = computed(() => {
  if (!ready.value) return '⬜ Esperando'
  if (running.value) return '⏳ Running'
  if (checks.every(c => c.result === null)) return '⬜ Pendiente'
  if (checks.every(c => c.result?.success)) return '✅ Todo correcto'
  return '❌ Hay fallos'
})

const globalSeverity = computed(() => {
  if (!ready.value || checks.every(c => c.result === null)) return 'secondary'
  if (running.value) return 'warn'
  if (checks.every(c => c.result?.success)) return 'success'
  return 'danger'
})

function stepClass(result) {
  if (!result) return ''
  return result.success ? 'step--ok' : 'step--fail'
}

watch(() => [props.orgACode, props.orgBCode, props.autoRun], () => {
  if (props.autoRun && ready.value && !running.value && !hasRun.value) {
    hasRun.value = true
    run()
  }
})

function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms))
}

async function run() {
  running.value = true

  // Update paths
  checks[0].path = `/api/ad/organizations/${props.orgACode}`
  checks[1].path = `/api/ad/organizations/${props.orgBCode}`
  checks[2].path = `/api/ad/organizations/${props.orgBCode}/users`

  // Reset
  checks.forEach(c => { c.result = null })

  // Wait 8s for AD propagation after delete (AD is slow for deletions)
  await delay(8000)

  // Check 1: Org A should NOT exist (was deleted)
  checks[0].result = await VerifyInAdUseCase(checks[0].path, false)
  await delay(1000)

  // Check 2: Org B should exist
  checks[1].result = await VerifyInAdUseCase(checks[1].path, true)
  await delay(1000)

  // Check 3: Org B should have users (the moved user)
  checks[2].result = await VerifyInAdUseCase(checks[2].path, true)

  running.value = false

  emit('completed', {
    orgA: checks[0].result,
    orgB: checks[1].result,
    usersOrgB: checks[2].result,
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
  margin-bottom: 0.75rem;
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
}
.step__label { font-weight: 600; font-size: 0.85rem; }
.step__expect { font-size: 0.75rem; color: #94a3b8; font-style: italic; }
.step__duration { font-size: 0.75rem; color: #94a3b8; }
.step__detail { font-size: 0.78rem; color: #475569; margin-top: 0.25rem; }
</style>
