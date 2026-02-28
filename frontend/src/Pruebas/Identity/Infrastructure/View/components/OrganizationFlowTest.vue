<template>
  <div class="test-card">
    <div class="test-card__header">
      <h3>🏢 Crear 2 Organizaciones</h3>
      <Tag :value="globalStatus" :severity="globalSeverity" />
    </div>

    <div class="test-card__actions">
      <Button
        label="▶ Crear Org A + Org B"
        icon="pi pi-play"
        :loading="running"
        :disabled="running"
        @click="run"
        size="small"
      />
      <Button
        label="🎲 Regenerar"
        icon="pi pi-refresh"
        severity="secondary"
        size="small"
        :disabled="running"
        @click="generate"
      />
    </div>

    <!-- Org A -->
    <div class="step" :class="stepClass(orgA)">
      <div class="step__header">
        <span class="step__label">Org A</span>
        <span class="step__code">{{ orgAData.organizationCode }}</span>
        <Tag v-if="orgA" :value="orgA.success ? '✅' : '❌'" :severity="orgA.success ? 'success' : 'danger'" />
        <span v-if="orgA" class="step__duration">{{ orgA.duration }}ms</span>
      </div>
      <pre v-if="orgA" class="step__result">{{ orgA.success ? JSON.stringify(orgA.data, null, 2) : orgA.error }}</pre>
    </div>

    <!-- Org B -->
    <div class="step" :class="stepClass(orgB)">
      <div class="step__header">
        <span class="step__label">Org B</span>
        <span class="step__code">{{ orgBData.organizationCode }}</span>
        <Tag v-if="orgB" :value="orgB.success ? '✅' : '❌'" :severity="orgB.success ? 'success' : 'danger'" />
        <span v-if="orgB" class="step__duration">{{ orgB.duration }}ms</span>
      </div>
      <pre v-if="orgB" class="step__result">{{ orgB.success ? JSON.stringify(orgB.data, null, 2) : orgB.error }}</pre>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { CreateOrganizationUseCase } from '@/Pruebas/Identity/Application/UseCase/Identity/CreateOrganization/CreateOrganizationUseCase'

const emit = defineEmits(['completed'])

const running = ref(false)
const orgA = ref(null)
const orgB = ref(null)

const orgAData = ref({ organizationCode: '', organizationName: '', email: '' })
const orgBData = ref({ organizationCode: '', organizationName: '', email: '' })

const globalStatus = computed(() => {
  if (running.value) return '⏳ Running'
  if (!orgA.value && !orgB.value) return '⬜ Pendiente'
  if (orgA.value?.success && orgB.value?.success) return '✅ 2/2 Pass'
  return '❌ Fail'
})

const globalSeverity = computed(() => {
  if (running.value) return 'warn'
  if (!orgA.value && !orgB.value) return 'secondary'
  if (orgA.value?.success && orgB.value?.success) return 'success'
  return 'danger'
})

function stepClass(result) {
  if (!result) return ''
  return result.success ? 'step--ok' : 'step--fail'
}

function randomLetters(n) {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVXYZ'
  return Array.from({ length: n }, () => chars[Math.floor(Math.random() * chars.length)]).join('')
}

function randomDigits(n) {
  return Array.from({ length: n }, () => Math.floor(Math.random() * 10)).join('')
}

function generateOrgData() {
  const code = 'W' + randomLetters(3) + randomDigits(3)
  return {
    organizationCode: code,
    organizationName: `Test Org ${code}`,
    email: `test-${code.toLowerCase()}@codemv-pruebas.local`,
  }
}

function generate() {
  orgAData.value = generateOrgData()
  orgBData.value = generateOrgData()
  orgA.value = null
  orgB.value = null
}

onMounted(() => generate())

function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms))
}

async function run() {
  running.value = true
  orgA.value = null
  orgB.value = null

  // Step 1: Create Org A
  orgA.value = await CreateOrganizationUseCase({
    organizationCode: orgAData.value.organizationCode,
    organizationName: orgAData.value.organizationName,
    email: orgAData.value.email,
    provider: 'ldap',
  })

  if (!orgA.value.success) {
    running.value = false
    return
  }

  // Wait 2s for AD propagation
  await delay(2000)

  // Step 2: Create Org B
  orgB.value = await CreateOrganizationUseCase({
    organizationCode: orgBData.value.organizationCode,
    organizationName: orgBData.value.organizationName,
    email: orgBData.value.email,
    provider: 'ldap',
  })

  running.value = false

  // Emit data for downstream steps
  if (orgA.value.success && orgB.value.success) {
    emit('completed', {
      orgA: {
        uuid: orgA.value.data?.data?.remote?.uuid,
        code: orgAData.value.organizationCode,
      },
      orgB: {
        uuid: orgB.value.data?.data?.remote?.uuid,
        code: orgBData.value.organizationCode,
      },
    })
  }
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
  margin-bottom: 0.25rem;
}
.step__label { font-weight: 600; font-size: 0.85rem; }
.step__code { font-family: monospace; font-size: 0.82rem; color: #6366f1; }
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
