<template>
  <div>
    <div class="page__header">
      <div class="header-left">
        <button class="back-btn" @click="$router.push('/portafolio')"><i class="pi pi-arrow-left" /> Portafolios</button>
        <div v-if="portafolio.nombre">
          <div style="display:flex; align-items:center; gap:0.6rem">
            <h1 class="page__title" style="margin:0">{{ portafolio.nombre }}</h1>
            <i
              v-if="portafolio.is_default"
              class="pi pi-box"
              style="color: var(--tokyo-cyan); font-size:1.1rem"
              v-tooltip.top="'Portafolio por defecto'"
            />
            <button v-else class="set-default-btn" @click="setDefault" v-tooltip.top="'Marcar como default'">
              <i class="pi pi-box" /> default
            </button>
          </div>
          <span class="page__subtitle">{{ portafolio.descripcion ?? '' }}</span>
        </div>
      </div>
      <Button label="Añadir acción" icon="pi pi-plus" size="small" @click="openAdd" />
    </div>

    <div v-if="loading" class="loading-msg"><i class="pi pi-spin pi-spinner" /> Cargando...</div>

    <div v-else>
      <div class="status-filters">
        <button
          v-for="s in statuses" :key="s.value"
          class="status-chip"
          :class="{ active: activeStatus === s.value }"
          @click="activeStatus = activeStatus === s.value ? null : s.value"
        >
          {{ s.label }} <span class="count">{{ countByStatus(s.value) }}</span>
        </button>
      </div>

      <DataTable
        :value="filtered"
        stripedRows
        dataKey="uuid"
        paginator
        :rows="100"
        :rowsPerPageOptions="[50, 100, 250]"
        tableStyle="table-layout: fixed; width: 100%"
      >
        <template #empty>Sin acciones en este portafolio. Pulsa "Añadir acción".</template>

        <Column field="accion.symbol" header="Símbolo" sortable style="width: 90px">
          <template #body="{ data }">
            <span class="symbol-badge">{{ data.accion.symbol }}</span>
          </template>
        </Column>

        <Column field="accion.name" header="Nombre" sortable style="width: 160px">
          <template #body="{ data }">
            <RouterLink :to="`/acciones/${data.accion.uuid}`" class="name-link">{{ data.accion.name }}</RouterLink>
          </template>
        </Column>

        <Column field="accion.sector" header="Sector" sortable>
          <template #body="{ data }">
            <span class="sector-text">{{ data.accion.sector ?? '—' }}</span>
          </template>
        </Column>

        <Column field="accion.exchange" header="Mercado" style="width: 95px">
          <template #body="{ data }">
            <span class="exchange-text">{{ data.accion.exchange ?? '—' }}</span>
          </template>
        </Column>

        <Column field="accion.type" header="Tipo" style="width: 80px">
          <template #body="{ data }">
            <Tag :value="data.accion.type" severity="secondary" />
          </template>
        </Column>

        <Column field="accion.precio" header="Precio" sortable style="width: 95px; text-align: right">
          <template #body="{ data }">
            <span class="price-val">{{ data.accion.precio != null ? '$' + data.accion.precio.toFixed(2) : '—' }}</span>
          </template>
        </Column>

        <Column field="accion.change_pct" header="%" sortable style="width: 95px; text-align: right">
          <template #body="{ data }">
            <span v-if="data.accion.change_pct != null" class="change-badge" :class="data.accion.change_pct >= 0 ? 'up' : 'down'">
              <i :class="data.accion.change_pct >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" />
              {{ Math.abs(data.accion.change_pct).toFixed(2) }}%
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column field="accion.change_amount" header="Dif." sortable style="width: 80px; text-align: right">
          <template #body="{ data }">
            <span v-if="data.accion.change_amount != null" :class="data.accion.change_amount >= 0 ? 'amount-up' : 'amount-down'">
              {{ data.accion.change_amount >= 0 ? '+' : '' }}{{ data.accion.change_amount?.toFixed(2) }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column field="status" header="Estado" sortable style="width: 110px">
          <template #body="{ data }">
            <Tag :value="data.status" :severity="statusSeverity(data.status)" />
          </template>
        </Column>

        <Column field="notas" header="Notas" style="width: 160px">
          <template #body="{ data }">
            <span class="notes-text" v-tooltip.top="data.notas">{{ data.notas ? data.notas.slice(0, 30) + (data.notas.length > 30 ? '…' : '') : '—' }}</span>
          </template>
        </Column>

        <Column header="" style="width: 70px; text-align: right">
          <template #body="{ data }">
            <div class="row-actions">
              <i class="pi pi-pencil" @click="openEdit(data)" title="Editar" />
              <i class="pi pi-trash" @click="confirmRemove(data)" title="Quitar" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialog añadir / editar acción -->
    <Dialog v-model:visible="dialogVisible" :header="editTarget ? 'Editar posición' : 'Añadir acción'" modal style="width: 440px">
      <div class="form-fields">
        <div v-if="!editTarget" class="field">
          <label>Acción *</label>
          <Select
            v-model="form.accion_uuid"
            :options="accionOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Busca un símbolo..."
            class="w-full"
            filter
          />
        </div>
        <div v-else class="field">
          <label>Acción</label>
          <span class="readonly-val">{{ editTarget.accion.symbol }} — {{ editTarget.accion.name }}</span>
        </div>

        <div class="field">
          <label>Estado</label>
          <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>

        <div class="field">
          <label>Precio referencia ($)</label>
          <InputNumber v-model="form.precio_referencia" :min="0" :step="0.01" :maxFractionDigits="4" placeholder="0.00" class="w-full" />
        </div>

        <div class="field">
          <label>Notas</label>
          <Textarea v-model="form.notas" rows="3" class="w-full" placeholder="Por qué me interesa..." />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="dialogVisible = false" />
        <Button :label="editTarget ? 'Guardar' : 'Añadir'" :loading="saving" @click="save" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { GetPortafolioUseCase } from '@/Acciones/Application/UseCase/GetPortafolio/GetPortafolioUseCase'
import { AddAccionToPortafolioUseCase } from '@/Acciones/Application/UseCase/AddAccionToPortafolio/AddAccionToPortafolioUseCase'
import { UpdatePortafolioEntryUseCase } from '@/Acciones/Application/UseCase/UpdatePortafolioEntry/UpdatePortafolioEntryUseCase'
import { DeletePortafolioEntryUseCase } from '@/Acciones/Application/UseCase/DeletePortafolioEntry/DeletePortafolioEntryUseCase'
import { ListAccionesUseCase } from '@/Acciones/Application/UseCase/ListAcciones/ListAccionesUseCase'

const route   = useRoute()
const confirm = useConfirm()
const toast   = useToast()

const portafolio    = ref({})
const acciones      = ref([])
const entries       = ref([])
const loading       = ref(true)
const saving        = ref(false)
const dialogVisible = ref(false)
const editTarget    = ref(null)
const activeStatus  = ref(null)

const form = ref({ accion_uuid: null, status: 'watchlist', precio_referencia: null, notas: '' })

const statuses = [
  { value: 'watchlist',  label: 'Watchlist' },
  { value: 'candidato',  label: 'Candidato' },
  { value: 'activo',     label: 'Activo' },
  { value: 'descartado', label: 'Descartado' },
]
const statusOptions = statuses

const accionOptions = computed(() =>
  acciones.value.map(a => ({ label: `${a.symbol} — ${a.name}`, value: a.uuid }))
)

const filtered = computed(() =>
  activeStatus.value ? entries.value.filter(e => e.status === activeStatus.value) : entries.value
)

function countByStatus(status) {
  return entries.value.filter(e => e.status === status).length
}

function statusSeverity(status) {
  return { watchlist: 'info', candidato: 'warn', activo: 'success', descartado: 'secondary' }[status] ?? 'secondary'
}

onMounted(async () => {
  const [data, accionesList] = await Promise.all([
    GetPortafolioUseCase(route.params.uuid),
    ListAccionesUseCase(),
  ])
  portafolio.value = data
  entries.value    = data.acciones ?? []
  acciones.value   = accionesList
  loading.value    = false
})

async function setDefault() {
  try {
    const updated = await UpdatePortafolioEntryUseCase(portafolio.value.uuid, { is_default: true })
    portafolio.value.is_default = true
    toast.add({ severity: 'success', summary: `"${portafolio.value.nombre}" es ahora el portafolio por defecto`, life: 2500 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 3000 })
  }
}

function openAdd() {
  editTarget.value = null
  form.value = { accion_uuid: null, status: 'watchlist', precio_referencia: null, notas: '' }
  dialogVisible.value = true
}

function openEdit(entry) {
  editTarget.value = entry
  form.value = {
    status:           entry.status,
    precio_referencia: entry.precio_referencia,
    notas:            entry.notas ?? '',
  }
  dialogVisible.value = true
}

async function save() {
  saving.value = true
  try {
    if (editTarget.value) {
      const updated = await UpdatePortafolioEntryUseCase(editTarget.value.uuid, {
        status:           form.value.status,
        precio_referencia: form.value.precio_referencia,
        notas:            form.value.notas || null,
      })
      Object.assign(editTarget.value, updated)
    } else {
      if (!form.value.accion_uuid) {
        toast.add({ severity: 'warn', summary: 'Selecciona una acción', life: 2500 })
        return
      }
      const created = await AddAccionToPortafolioUseCase(route.params.uuid, {
        accion_uuid:      form.value.accion_uuid,
        status:           form.value.status,
        precio_referencia: form.value.precio_referencia,
        notas:            form.value.notas || null,
      })
      entries.value.push(created)
    }
    dialogVisible.value = false
    toast.add({ severity: 'success', summary: 'Guardado', life: 2000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.error ?? e.message, life: 4000 })
  } finally {
    saving.value = false
  }
}

function confirmRemove(entry) {
  confirm.require({
    message: `¿Quitar ${entry.accion.symbol} de este portafolio?`,
    header: 'Confirmar',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancelar',
    acceptLabel: 'Quitar',
    acceptClass: 'p-button-danger',
    accept: async () => {
      await DeletePortafolioEntryUseCase(entry.uuid)
      entries.value = entries.value.filter(e => e.uuid !== entry.uuid)
      toast.add({ severity: 'success', summary: 'Quitada', life: 2000 })
    },
  })
}
</script>

<style scoped>
.header-left { display: flex; align-items: center; gap: 1.25rem; }
.back-btn {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: none; border: none; cursor: pointer;
  color: var(--tokyo-fg-dim); font-size: 0.85rem; padding: 4px 0;
}
.back-btn:hover { color: var(--tokyo-fg); }

.set-default-btn {
  background: none; border: 1px solid #475569; border-radius: 4px;
  padding: 2px 8px; cursor: pointer; color: #475569;
  font-size: 0.78rem; display: inline-flex; align-items: center; gap: 0.3rem;
  transition: all 0.15s;
}
.set-default-btn:hover { border-color: var(--tokyo-cyan); color: var(--tokyo-cyan); }

.status-filters { display: flex; gap: 0.5rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
.status-chip {
  padding: 4px 14px; border-radius: 999px;
  border: 1px solid #e2e8f0; background: #fff;
  font-size: 0.82rem; cursor: pointer; color: #475569;
  display: flex; align-items: center; gap: 0.4rem; transition: all 0.15s;
}
.status-chip:hover, .status-chip.active { background: #6366f1; color: #fff; border-color: #6366f1; }
.count { background: rgba(0,0,0,0.1); border-radius: 999px; padding: 0 6px; font-size: 0.75rem; }

.symbol-badge { font-weight: 700; font-size: 0.9rem; letter-spacing: 0.03em; color: var(--tokyo-cyan); }
.name-link { color: var(--tokyo-fg); text-decoration: none; font-size: 0.88rem; }
.name-link:hover { color: var(--tokyo-cyan); text-decoration: underline; }
.sector-text  { font-size: 0.82rem; color: var(--tokyo-fg-dim); }
.exchange-text { font-size: 0.8rem; color: var(--tokyo-fg-dim); font-family: monospace; }
.price-val { font-weight: 600; }

.change-badge {
  display: inline-flex; align-items: center; gap: 0.25rem;
  font-weight: 700; font-size: 0.85rem; border-radius: 4px; padding: 2px 6px;
}
.change-badge.up   { color: #4ade80; background: rgba(74,222,128,0.1); }
.change-badge.down { color: #f87171; background: rgba(248,113,113,0.1); }
.change-badge .pi  { font-size: 0.7rem; }
.amount-up   { font-size: 0.85rem; color: #4ade80; }
.amount-down { font-size: 0.85rem; color: #f87171; }

.notes-text { font-size: 0.82rem; color: var(--tokyo-fg-dim); cursor: default; }

.row-actions { display: flex; gap: 0.75rem; justify-content: flex-end; }
.row-actions i { cursor: pointer; color: #94a3b8; font-size: 0.9rem; transition: color 0.15s; }
.row-actions .pi-pencil:hover { color: var(--tokyo-blue); }
.row-actions .pi-trash:hover  { color: var(--tokyo-red); }

.form-fields { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.field label { font-size: 0.82rem; color: var(--tokyo-fg-dim); }
.readonly-val { font-weight: 600; font-size: 0.9rem; }
.loading-msg { padding: 2rem; text-align: center; color: var(--tokyo-fg-dim); }
</style>
