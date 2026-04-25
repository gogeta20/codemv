<template>
  <div class="test-card">
    <div class="test-card__header">
      <h3>🏢 Crear Organización</h3>
      <Tag :value="statusLabel" :severity="statusSeverity" />
    </div>

    <div class="test-card__form">
      <div class="test-card__field">
        <label>Organization Code</label>
        <InputText v-model="form.organizationCode" placeholder="WABC123" :disabled="running" />
      </div>
      <div class="test-card__field">
        <label>Organization Name</label>
        <InputText v-model="form.organizationName" placeholder="Test Org" :disabled="running" />
      </div>
      <div class="test-card__field">
        <label>Email</label>
        <InputText v-model="form.email" placeholder="test@example.com" :disabled="running" />
      </div>
      <div class="test-card__field">
        <label>Provider</label>
        <InputText v-model="form.provider" placeholder="ldap" :disabled="running" />
      </div>
    </div>

    <div class="test-card__actions">
      <Button
        label="▶ Ejecutar"
        icon="pi pi-play"
        :loading="running"
        :disabled="!canRun"
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
      <span v-if="result" class="test-card__duration">{{ result.duration }}ms</span>
    </div>

    <div v-if="result" class="test-card__result" :class="result.success ? 'test-card__result--ok' : 'test-card__result--fail'">
      <pre v-if="result.success">{{ JSON.stringify(result.data, null, 2) }}</pre>
      <pre v-else>{{ result.error }}</pre>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { CreateOrganizationUseCase } from '@/Pruebas/Identity/Application/UseCase/Identity/CreateOrganization/CreateOrganizationUseCase'

const form = ref({
  uuid: '',
  organizationCode: '',
  organizationName: '',
  email: '',
  provider: 'ldap',
})

const running = ref(false)
const result = ref(null)

const canRun = computed(() =>
  form.value.organizationCode && form.value.organizationName && form.value.email && !running.value
)

const statusLabel = computed(() => {
  if (running.value) return '⏳ Running'
  if (!result.value) return '⬜ Pendiente'
  return result.value.success ? '✅ Pass' : '❌ Fail'
})

const statusSeverity = computed(() => {
  if (running.value) return 'warn'
  if (!result.value) return 'secondary'
  return result.value.success ? 'success' : 'danger'
})

function randomLetters(n) {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVXYZ'
  return Array.from({ length: n }, () => chars[Math.floor(Math.random() * chars.length)]).join('')
}

function randomDigits(n) {
  return Array.from({ length: n }, () => Math.floor(Math.random() * 10)).join('')
}

function generate() {
  const code = 'W' + randomLetters(3) + randomDigits(3)
  const nameSuffix = code.toLowerCase()
  form.value.uuid = crypto.randomUUID()
  form.value.organizationCode = code
  form.value.organizationName = `Test Org ${code}`
  form.value.email = `test-${nameSuffix}@codemv-pruebas.local`
  form.value.provider = 'ldap'
  result.value = null
}

onMounted(() => {
  generate()
})

async function run() {
  running.value = true
  result.value = null

  result.value = await CreateOrganizationUseCase({
    uuid: form.value.uuid,
    organizationCode: form.value.organizationCode,
    organizationName: form.value.organizationName,
    email: form.value.email,
    provider: form.value.provider,
  })

  running.value = false
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

.test-card__header h3 {
  margin: 0;
  font-size: 1rem;
}

.test-card__form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.test-card__field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.test-card__field label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
}

.test-card__actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.test-card__duration {
  font-size: 0.8rem;
  color: #94a3b8;
}

.test-card__result {
  border-radius: 6px;
  padding: 0.75rem;
  font-size: 0.8rem;
  overflow-x: auto;
}

.test-card__result pre {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
}

.test-card__result--ok {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
}

.test-card__result--fail {
  background: #fef2f2;
  border: 1px solid #fecaca;
}
</style>
