<template>
  <div class="test-card">
    <div class="test-card__header">
      <h3>🗑️ Eliminar Organización</h3>
      <Tag :value="statusLabel" :severity="statusSeverity" />
    </div>

    <div class="test-card__form">
      <div class="test-card__field test-card__field--wide">
        <label>Organization UUID</label>
        <InputText v-model="uuid" placeholder="4d27277e-ea1b-11f0-a4a9-66e51db3f3c6" :disabled="running" />
      </div>
    </div>

    <div class="test-card__actions">
      <Button
        label="▶ Ejecutar"
        icon="pi pi-play"
        severity="danger"
        :loading="running"
        :disabled="!uuid || running"
        @click="run"
        size="small"
      />
      <span v-if="result" class="test-card__duration">{{ result.duration }}ms</span>
    </div>

    <div v-if="result" class="test-card__result" :class="result.success ? 'test-card__result--ok' : 'test-card__result--fail'">
      <pre>{{ result.success ? JSON.stringify(result.data, null, 2) : result.error }}</pre>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { DeleteOrganizationUseCase } from '@/Pruebas/Identity/Application/UseCase/Identity/DeleteOrganization/DeleteOrganizationUseCase'

const uuid = ref('')
const running = ref(false)
const result = ref(null)

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

async function run() {
  running.value = true
  result.value = null
  result.value = await DeleteOrganizationUseCase(uuid.value)
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
.test-card__header h3 { margin: 0; font-size: 1rem; }
.test-card__form {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}
.test-card__field { display: flex; flex-direction: column; gap: 0.25rem; }
.test-card__field--wide { flex: 1; }
.test-card__field label { font-size: 0.75rem; font-weight: 500; color: #64748b; }
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
