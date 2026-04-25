<template>
  <div class="outbox-panel">
    <div class="outbox-panel__header">
      <h2 class="outbox-panel__title">📬 Identity Outbox</h2>
      <div class="outbox-panel__controls">
        <Tag :value="statusTag" :severity="statusSeverity" />
        <Button
          icon="pi pi-send"
          label="→ Rabbit + Panel"
          size="small"
          severity="info"
          :loading="publishing"
          @click="runPipeline"
          v-tooltip="'Publicar outbox → RabbitMQ → Panel'"
        />
        <Button
          icon="pi pi-refresh"
          size="small"
          severity="secondary"
          :loading="loading"
          @click="refresh"
          v-tooltip="'Recargar'"
        />
        <Button
          :icon="polling ? 'pi pi-pause' : 'pi pi-play'"
          size="small"
          :severity="polling ? 'warn' : 'success'"
          @click="togglePolling"
          v-tooltip="polling ? 'Pausar auto-refresh' : 'Iniciar auto-refresh (30s)'"
        />
      </div>
    </div>

    <div v-if="error" class="outbox-panel__error">{{ error }}</div>

    <div class="outbox-panel__table-wrap">
      <table class="outbox-table">
        <thead>
          <tr>
            <th>Created At</th>
            <th>Event Key</th>
            <th>Type</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="rows.length === 0">
            <td colspan="4" class="outbox-table__empty">Sin mensajes</td>
          </tr>
          <tr
            v-for="row in rows"
            :key="row.id"
            :class="rowClass(row.status)"
          >
            <td class="outbox-table__time">{{ formatDate(row.created_at) }}</td>
            <td class="outbox-table__key outbox-table__key--wide" :title="row.event_key">{{ shortKey(row.event_key) }}</td>
            <td>{{ row.aggregate_type ?? '—' }}</td>
            <td>
              <span class="outbox-table__status" :class="'outbox-table__status--' + row.status">
                {{ row.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="outbox-panel__footer">
      <span>{{ rows.length }} mensajes · última actualización: {{ lastRefresh }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { GetOutboxUseCase } from '@/Pruebas/Identity/Application/UseCase/Outbox/GetOutboxUseCase'
import { RunPipelineUseCase } from '@/Pruebas/Identity/Application/UseCase/Pipeline/RunPipelineUseCase'

const POLL_INTERVAL_MS = 30000

const rows = ref([])
const loading = ref(false)
const publishing = ref(false)
const error = ref(null)
const polling = ref(true)
const lastRefresh = ref('—')
let timer = null

const statusTag = computed(() => {
  if (loading.value) return '⏳ Cargando'
  if (error.value) return '❌ Error'
  return polling.value ? '🟢 Live (30s)' : '⏸ Pausado'
})

const statusSeverity = computed(() => {
  if (error.value) return 'danger'
  return polling.value ? 'success' : 'secondary'
})

function rowClass(status) {
  if (status === 'published') return 'outbox-table__row--published'
  if (status === 'failed') return 'outbox-table__row--failed'
  if (status === 'pending') return 'outbox-table__row--pending'
  return ''
}

function shortKey(key) {
  // identity.user.creation_completed.v1 → user.creation_completed
  return key.replace(/^identity\./, '').replace(/\.v\d+$/, '')
}

function formatDate(raw) {
  if (!raw) return '—'
  const d = new Date(raw.replace(' ', 'T') + 'Z')
  return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

async function refresh() {
  loading.value = true
  error.value = null
  const result = await GetOutboxUseCase(100)
  loading.value = false
  if (result.success) {
    rows.value = result.data
    const now = new Date()
    lastRefresh.value = now.toLocaleTimeString('es-ES')
  } else {
    error.value = result.error
  }
}

async function runPipeline() {
  publishing.value = true
  error.value = null
  const result = await RunPipelineUseCase()
  publishing.value = false
  if (!result.success) {
    error.value = result.error
  }
  await refresh()
}

function togglePolling() {
  polling.value = !polling.value
  if (polling.value) {
    startPolling()
  } else {
    stopPolling()
  }
}

function startPolling() {
  stopPolling()
  timer = setInterval(refresh, POLL_INTERVAL_MS)
}

function stopPolling() {
  if (timer) {
    clearInterval(timer)
    timer = null
  }
}

onMounted(() => {
  refresh()
  startPolling()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<style scoped>
.outbox-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.outbox-panel__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  flex-shrink: 0;
}

.outbox-panel__title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 600;
  color: #1e293b;
}

.outbox-panel__controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.outbox-panel__error {
  padding: 0.5rem 1rem;
  background: #fef2f2;
  border-bottom: 1px solid #fecaca;
  font-size: 0.8rem;
  color: #dc2626;
}

.outbox-panel__table-wrap {
  flex: 1;
  overflow-y: auto;
}

.outbox-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.78rem;
}

.outbox-table thead th {
  position: sticky;
  top: 0;
  background: #f1f5f9;
  padding: 0.5rem 0.6rem;
  text-align: left;
  font-weight: 600;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.outbox-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.1s;
}

.outbox-table tbody tr:hover { background: #f8fafc; }

.outbox-table tbody td {
  padding: 0.45rem 0.6rem;
  color: #334155;
  vertical-align: middle;
}

.outbox-table__empty {
  text-align: center;
  color: #94a3b8;
  padding: 2rem !important;
}

.outbox-table__key {
  font-family: monospace;
  color: #6366f1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.outbox-table__key--wide {
  max-width: 260px;
  width: 100%;
}

.outbox-table__time {
  white-space: nowrap;
  color: #64748b;
}

.outbox-table__attempts {
  text-align: center;
}

/* Row variants */
.outbox-table__row--published { background: #f0fdf4; }
.outbox-table__row--failed { background: #fef2f2; }
.outbox-table__row--pending { background: #fffbeb; }

/* Status badge */
.outbox-table__status {
  display: inline-block;
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
}
.outbox-table__status--pending { background: #fef9c3; color: #854d0e; }
.outbox-table__status--publishing { background: #dbeafe; color: #1d4ed8; }
.outbox-table__status--published { background: #dcfce7; color: #166534; }
.outbox-table__status--failed { background: #fee2e2; color: #991b1b; }

.outbox-panel__footer {
  padding: 0.4rem 0.75rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  font-size: 0.72rem;
  color: #94a3b8;
  flex-shrink: 0;
}
</style>
