<template>
  <div class="test-card">
    <div class="test-card__header">
      <h3>🗑️ Eliminar Org A</h3>
      <Tag :value="statusLabel" :severity="statusSeverity" />
    </div>

    <div v-if="!orgUuid" class="test-card__disabled">
      ⚠️ Completa primero los pasos anteriores
    </div>

    <template v-else>
      <div class="test-card__info">
        Org A UUID: <code>{{ orgUuid }}</code>
      </div>

      <div class="test-card__actions">
        <Button
          label="▶ Eliminar Org A"
          icon="pi pi-play"
          severity="danger"
          :loading="running"
          :disabled="running"
          @click="run"
          size="small"
        />
        <span v-if="result" class="test-card__duration">{{ result.duration }}ms</span>
      </div>

      <div v-if="result" class="test-card__result" :class="result.success ? 'test-card__result--ok' : 'test-card__result--fail'">
        <pre>{{ result.success ? JSON.stringify(result.data, null, 2) : result.error }}</pre>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { DeleteOrganizationUseCase } from '@/Pruebas/Identity/Application/UseCase/Identity/DeleteOrganization/DeleteOrganizationUseCase'

const props = defineProps({
  orgUuid: { type: String, default: null },
  autoRun: { type: Boolean, default: false },
})

const emit = defineEmits(['completed'])

const running = ref(false)
const result = ref(null)

const statusLabel = computed(() => {
  if (!props.orgUuid) return '⬜ Esperando'
  if (running.value) return '⏳ Running'
  if (!result.value) return '⬜ Pendiente'
  return result.value.success ? '✅ Pass' : '❌ Fail'
})

const statusSeverity = computed(() => {
  if (!props.orgUuid || !result.value) return 'secondary'
  if (running.value) return 'warn'
  return result.value.success ? 'success' : 'danger'
})

watch(() => [props.orgUuid, props.autoRun], () => {
  if (props.autoRun && props.orgUuid && !running.value && !result.value) {
    run()
  }
})

async function run() {
  running.value = true
  result.value = null
  result.value = await DeleteOrganizationUseCase(props.orgUuid)
  running.value = false

  if (result.value.success) {
    emit('completed')
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
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}
.test-card__duration { font-size: 0.8rem; color: #94a3b8; }
.test-card__result {
  border-radius: 6px;
  padding: 0.75rem;
  font-size: 0.8rem;
  overflow-x: auto;
}
.test-card__result pre { margin: 0; white-space: pre-wrap; word-break: break-word; }
.test-card__result--ok { background: #f0fdf4; border: 1px solid #bbf7d0; }
.test-card__result--fail { background: #fef2f2; border: 1px solid #fecaca; }
</style>
