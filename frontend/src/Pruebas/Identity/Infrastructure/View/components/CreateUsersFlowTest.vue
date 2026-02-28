<template>
  <div class="test-card">
    <div class="test-card__header">
      <h3>👤 Crear 2 Usuarios en Org A</h3>
      <Tag :value="globalStatus" :severity="globalSeverity" />
    </div>

    <div v-if="!orgCode" class="test-card__disabled">
      ⚠️ Completa primero el Paso 1 (Crear Organizaciones)
    </div>

    <template v-else>
      <div class="test-card__info">
        Organización: <code>{{ orgCode }}</code>
      </div>

      <div class="test-card__actions">
        <Button
          label="▶ Crear User 1 + User 2"
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

      <!-- User 1 -->
      <div class="step" :class="stepClass(user1Result)">
        <div class="step__header">
          <span class="step__label">User 1</span>
          <span class="step__code">{{ user1Data.email }}</span>
          <Tag v-if="user1Result" :value="user1Result.success ? '✅' : '❌'" :severity="user1Result.success ? 'success' : 'danger'" />
          <span v-if="user1Result" class="step__duration">{{ user1Result.duration }}ms</span>
        </div>
        <pre v-if="user1Result" class="step__result">{{ user1Result.success ? JSON.stringify(user1Result.data, null, 2) : user1Result.error }}</pre>
      </div>

      <!-- User 2 -->
      <div class="step" :class="stepClass(user2Result)">
        <div class="step__header">
          <span class="step__label">User 2</span>
          <span class="step__code">{{ user2Data.email }}</span>
          <Tag v-if="user2Result" :value="user2Result.success ? '✅' : '❌'" :severity="user2Result.success ? 'success' : 'danger'" />
          <span v-if="user2Result" class="step__duration">{{ user2Result.duration }}ms</span>
        </div>
        <pre v-if="user2Result" class="step__result">{{ user2Result.success ? JSON.stringify(user2Result.data, null, 2) : user2Result.error }}</pre>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { CreateUserUseCase } from '@/Pruebas/Identity/Application/UseCase/Identity/CreateUser/CreateUserUseCase'

const props = defineProps({
  orgCode: { type: String, default: null },
  autoRun: { type: Boolean, default: false },
})

const emit = defineEmits(['completed'])

const running = ref(false)
const user1Result = ref(null)
const user2Result = ref(null)
const user1Data = ref({})
const user2Data = ref({})

const globalStatus = computed(() => {
  if (!props.orgCode) return '⬜ Esperando Paso 1'
  if (running.value) return '⏳ Running'
  if (!user1Result.value && !user2Result.value) return '⬜ Pendiente'
  if (user1Result.value?.success && user2Result.value?.success) return '✅ 2/2 Pass'
  return '❌ Fail'
})

const globalSeverity = computed(() => {
  if (!props.orgCode || (!user1Result.value && !user2Result.value)) return 'secondary'
  if (running.value) return 'warn'
  if (user1Result.value?.success && user2Result.value?.success) return 'success'
  return 'danger'
})

function stepClass(result) {
  if (!result) return ''
  return result.success ? 'step--ok' : 'step--fail'
}

function randomStr(n) {
  const chars = 'abcdefghijklmnopqrstuvwxyz'
  return Array.from({ length: n }, () => chars[Math.floor(Math.random() * chars.length)]).join('')
}

function generateUserData(index) {
  const name = `Test${randomStr(4)}`
  const surname = `User${index}`
  const uuid = crypto.randomUUID()
  return {
    uuid,
    name,
    firstSurname: surname,
    lastName: 'Prueba',
    initials: `${name[0]}${surname[0]}`.toUpperCase(),
    email: `${name.toLowerCase()}.${surname.toLowerCase()}@codemv-pruebas.local`,
    password: 'T3st!Pr00f#2026',
  }
}

function generate() {
  user1Data.value = generateUserData(1)
  user2Data.value = generateUserData(2)
  user1Result.value = null
  user2Result.value = null
}

watch(() => props.orgCode, (val) => {
  if (val) generate()
}, { immediate: true })

watch(() => [props.orgCode, props.autoRun], () => {
  if (props.autoRun && props.orgCode && !running.value && !user1Result.value) {
    run()
  }
})

function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms))
}

async function run() {
  running.value = true
  user1Result.value = null
  user2Result.value = null

  // Create User 1
  user1Result.value = await CreateUserUseCase({
    ...user1Data.value,
    organizationCode: props.orgCode,
  })

  if (!user1Result.value.success) {
    running.value = false
    return
  }

  await delay(2000)

  // Create User 2
  user2Result.value = await CreateUserUseCase({
    ...user2Data.value,
    organizationCode: props.orgCode,
  })

  running.value = false

  if (user1Result.value.success && user2Result.value.success) {
    emit('completed', {
      user1: { uuid: user1Data.value.uuid, email: user1Data.value.email },
      user2: { uuid: user2Data.value.uuid, email: user2Data.value.email },
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
.test-card__disabled {
  padding: 0.75rem;
  background: #fefce8;
  border: 1px solid #fde68a;
  border-radius: 6px;
  font-size: 0.85rem;
  color: #92400e;
}
.test-card__info {
  font-size: 0.82rem;
  margin-bottom: 0.5rem;
  color: #475569;
}
.test-card__info code { font-weight: 600; color: #6366f1; }
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
