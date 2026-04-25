<template>
  <div class="test-card">
    <div class="test-card__header">
      <h3>🔘 Deshabilitar / Rehabilitar User 2</h3>
      <Tag :value="globalStatus" :severity="globalSeverity" />
    </div>

    <div v-if="!userUuid" class="test-card__disabled">
      ⚠️ Completa primero el paso de Crear Usuarios
    </div>

    <template v-else>
      <div class="test-card__info">
        User 2: <code>{{ userUuid }}</code>
      </div>

      <!-- Disable -->
      <div class="step" :class="stepClass(disableResult)">
        <div class="step__header">
          <span class="step__label">Deshabilitar</span>
          <span class="step__badge">user.status.disabled</span>
          <Tag v-if="disableResult" :value="disableResult.success ? '✅' : '❌'" :severity="disableResult.success ? 'success' : 'danger'" />
          <span v-if="disableResult" class="step__duration">{{ disableResult.duration }}ms</span>
        </div>
        <pre v-if="disableResult && !disableResult.success" class="step__result">{{ disableResult.error }}</pre>
      </div>

      <!-- Enable -->
      <div class="step" :class="stepClass(enableResult)">
        <div class="step__header">
          <span class="step__label">Rehabilitar</span>
          <span class="step__badge">user.status.enabled</span>
          <Tag v-if="enableResult" :value="enableResult.success ? '✅' : '❌'" :severity="enableResult.success ? 'success' : 'danger'" />
          <span v-if="enableResult" class="step__duration">{{ enableResult.duration }}ms</span>
        </div>
        <pre v-if="enableResult && !enableResult.success" class="step__result">{{ enableResult.error }}</pre>
      </div>

      <div class="test-card__actions">
        <Button
          label="▶ Ejecutar"
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
import { ChangeUserStatusUseCase } from '@/Pruebas/Identity/Application/UseCase/Identity/ChangeUserStatus/ChangeUserStatusUseCase'

const STATUS_DISABLED = 'user.status.disabled'
const STATUS_ENABLED = 'user.status.enabled'

const props = defineProps({
  userUuid: { type: String, default: null },
  autoRun: { type: Boolean, default: false },
})

const emit = defineEmits(['completed'])

const running = ref(false)
const hasRun = ref(false)
const disableResult = ref(null)
const enableResult = ref(null)

const globalStatus = computed(() => {
  if (!props.userUuid) return '⬜ Esperando usuarios'
  if (running.value) return '⏳ Running'
  if (!disableResult.value && !enableResult.value) return '⬜ Pendiente'
  if (disableResult.value?.success && enableResult.value?.success) return '✅ 2/2 Pass'
  return '❌ Fail'
})

const globalSeverity = computed(() => {
  if (!props.userUuid || (!disableResult.value && !enableResult.value)) return 'secondary'
  if (running.value) return 'warn'
  if (disableResult.value?.success && enableResult.value?.success) return 'success'
  return 'danger'
})

function stepClass(result) {
  if (!result) return ''
  return result.success ? 'step--ok' : 'step--fail'
}

watch(() => [props.userUuid, props.autoRun], () => {
  if (props.autoRun && props.userUuid && !running.value && !hasRun.value) {
    hasRun.value = true
    run()
  }
}, { immediate: true })

async function run() {
  running.value = true
  disableResult.value = null
  enableResult.value = null

  disableResult.value = await ChangeUserStatusUseCase(props.userUuid, STATUS_DISABLED)

  if (!disableResult.value.success) {
    running.value = false
    emit('completed', { results: { disable: disableResult.value, enable: null } })
    return
  }

  enableResult.value = await ChangeUserStatusUseCase(props.userUuid, STATUS_ENABLED)

  running.value = false

  emit('completed', {
    results: {
      disable: disableResult.value,
      enable: enableResult.value,
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
.test-card__info {
  font-size: 0.82rem;
  margin-bottom: 0.75rem;
  color: #475569;
}
.test-card__info code { font-weight: 600; color: #6366f1; }
.test-card__actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 0.75rem;
}
.step {
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 0.6rem 0.75rem;
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
.step__badge {
  font-family: monospace;
  font-size: 0.75rem;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  padding: 0.1rem 0.4rem;
  color: #475569;
}
.step__duration { font-size: 0.75rem; color: #94a3b8; margin-left: auto; }
.step__result {
  margin: 0.4rem 0 0;
  font-size: 0.75rem;
  white-space: pre-wrap;
  word-break: break-word;
  color: #dc2626;
}
</style>
