<template>
  <div>
    <div class="page__header">
      <h1 class="page__title">Acciones</h1>
      <span class="page__subtitle">{{ acciones.length }} en seguimiento</span>
    </div>

    <div class="toolbar">
      <IconField class="flex-1">
        <InputIcon class="pi pi-search" />
        <InputText v-model="search" placeholder="Buscar símbolo, nombre o sector..." class="w-full" />
      </IconField>
      <Button
        :label="onlyWithReports ? 'Solo con reporte' : 'Todos'"
        :icon="onlyWithReports ? 'pi pi-filter-fill' : 'pi pi-filter'"
        size="small"
        severity="secondary"
        outlined
        @click="onlyWithReports = !onlyWithReports"
      />
      <Button label="Nueva acción" icon="pi pi-plus" size="small" @click="openCreate" />
    </div>

    <DataTable :value="filtered" :loading="loading" stripedRows dataKey="uuid" class="acciones-table" tableStyle="table-layout: fixed; width: 100%" paginator :rows="100" :rowsPerPageOptions="[50, 100, 250]">
      <template #empty>No hay acciones. Añade la primera.</template>

      <Column field="symbol" header="Símbolo" sortable style="width: 90px">
        <template #body="{ data }">
          <span class="symbol-badge">{{ data.symbol }}</span>
        </template>
      </Column>

      <Column field="name" header="Nombre" sortable style="width: 170px">
        <template #body="{ data }">
          <RouterLink :to="`/acciones/${data.uuid}`" class="name-link">{{ data.name }}</RouterLink>
        </template>
      </Column>

      <Column field="sector" header="Sector" sortable>
        <template #body="{ data }">
          <span class="sector-text">{{ data.sector ?? '—' }}</span>
        </template>
      </Column>

      <Column field="exchange" header="Mercado" style="width: 100px">
        <template #body="{ data }">
          <span class="exchange-text">{{ data.exchange ?? '—' }}</span>
        </template>
      </Column>

      <Column field="type" header="Tipo" style="width: 80px">
        <template #body="{ data }">
          <Tag :value="data.type" severity="secondary" />
        </template>
      </Column>

      <Column field="precio" header="Precio" sortable style="width: 95px; text-align: right">
        <template #body="{ data }">
          <span class="price-val">{{ data.precio != null ? '$' + data.precio.toFixed(2) : '—' }}</span>
        </template>
      </Column>

      <Column field="fair_value" header="Precio Real" sortable style="width: 110px; text-align: right">
        <template #body="{ data }">
          <span
            v-if="data.fair_value != null && data.fair_value_method === 'net_cash_floor'"
            class="fair-value-val fair-value-val--floor"
            :title="fairValueTooltip(data)"
          ><i class="pi pi-shield" />${{ data.fair_value.toFixed(2) }}</span>
          <span
            v-else-if="data.fair_value != null"
            class="fair-value-val"
            :class="fairValueClass(data.precio, data.fair_value)"
            :title="fairValueTooltip(data)"
          >${{ data.fair_value.toFixed(2) }}</span>
          <span v-else-if="data.fair_value_method === 'not_available'" class="text-muted" title="Sin valoración fundamental confiable (sin beneficio, dividendo ni caja neta positiva)">N/D</span>
          <span v-else class="text-muted">—</span>
        </template>
      </Column>

      <Column field="change_pct" header="%" sortable style="width: 95px; text-align: right">
        <template #body="{ data }">
          <span v-if="data.change_pct != null" class="change-badge" :class="data.change_pct >= 0 ? 'up' : 'down'">
            <i :class="data.change_pct >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" />
            {{ Math.abs(data.change_pct).toFixed(2) }}%
          </span>
          <span v-else class="text-muted">—</span>
        </template>
      </Column>

      <Column field="change_amount" header="Dif." sortable style="width: 85px; text-align: right">
        <template #body="{ data }">
          <span v-if="data.change_amount != null" :class="data.change_amount >= 0 ? 'amount-up' : 'amount-down'">
            {{ data.change_amount >= 0 ? '+' : '' }}{{ data.change_amount?.toFixed(2) }}
          </span>
          <span v-else class="text-muted">—</span>
        </template>
      </Column>

      <Column field="portafolio" header="Portafolio" style="width: 120px">
        <template #body="{ data }">
          <RouterLink
            v-if="data.portafolio"
            :to="`/portafolio/${data.portafolio.uuid}`"
            class="portafolio-link"
          >{{ data.portafolio.nombre }}</RouterLink>
          <span v-else class="text-muted">—</span>
        </template>
      </Column>

      <Column field="earnings_date" header="Rep. Gan." sortable style="width: 100px; text-align: center">
        <template #body="{ data }">
          <span v-if="data.earnings_date" :class="isTomorrow(data.earnings_date) ? 'earnings-tomorrow' : 'earnings-normal'">
            {{ formatEarningsDate(data.earnings_date) }}
          </span>
          <span v-else class="text-muted">—</span>
        </template>
      </Column>

      <Column field="has_earnings_report" header="Reporte" style="width: 120px; text-align: center">
        <template #body="{ data }">
          <Button
            v-if="data.has_earnings_report"
            label="Ver"
            icon="pi pi-megaphone"
            size="small"
            severity="secondary"
            outlined
            @click="$router.push(`/acciones/${data.uuid}/earnings`)"
          />
          <span v-else class="text-muted">—</span>
        </template>
      </Column>

      <Column field="is_active" header="Activa" style="width: 70px; text-align: center">
        <template #body="{ data }">
          <ToggleSwitch :modelValue="data.is_active" @update:modelValue="toggleActive(data)" />
        </template>
      </Column>

      <Column header="" style="width: 75px; text-align: right">
        <template #body="{ data }">
          <div class="row-actions">
            <i class="pi pi-pencil" @click="openEdit(data)" title="Editar" />
            <i class="pi pi-trash" @click="confirmDelete(data)" title="Eliminar" />
          </div>
        </template>
      </Column>
    </DataTable>

    <!-- Dialog crear / editar -->
    <Dialog v-model:visible="dialogVisible" :header="editTarget ? 'Editar acción' : 'Nueva acción'" modal style="width: 460px">
      <div class="form-fields">

        <!-- CREATE: autocomplete con lookup -->
        <div v-if="!editTarget" class="field">
          <label>Buscar símbolo o nombre *</label>
          <AutoComplete
            v-model="lookupQuery"
            :suggestions="lookupResults"
            optionLabel="label"
            placeholder="Nike, NKE, OKLO..."
            class="w-full"
            :delay="350"
            forceSelection
            @complete="onLookupSearch"
            @option-select="onLookupSelect"
          >
            <template #option="{ option }">
              <div class="lookup-option">
                <span class="lookup-symbol">{{ option.symbol }}</span>
                <span class="lookup-name">{{ option.name }}</span>
                <span class="lookup-exchange">{{ option.exchange }}</span>
              </div>
            </template>
          </AutoComplete>
          <span v-if="lookupLoading" class="hint"><i class="pi pi-spin pi-spinner" /> Buscando...</span>
        </div>

        <!-- Preview card cuando hay resultado seleccionado -->
        <div v-if="!editTarget && form.symbol" class="preview-card">
          <div class="preview-row">
            <span class="preview-symbol">{{ form.symbol }}</span>
            <span class="preview-exchange">{{ form.exchange }}</span>
            <Tag :value="form.type" severity="secondary" />
          </div>
          <div class="preview-name">{{ form.name }}</div>
          <div class="preview-sector">{{ form.sector }} · {{ form.industry }}</div>
        </div>

        <!-- Portafolio (solo al crear) -->
        <div v-if="!editTarget" class="field">
          <label>Añadir a portafolio</label>
          <Select
            v-model="form.portafolioUuid"
            :options="portafolioOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </div>

        <!-- EDIT: campos editables -->
        <template v-if="editTarget">
          <div class="field">
            <label>Nombre</label>
            <InputText v-model="form.name" class="w-full" />
          </div>
          <div class="field">
            <label>Tipo</label>
            <Select v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="field">
            <label>Sector <span class="hint">(editable)</span></label>
            <InputText v-model="form.sector" placeholder="Nuclear, Technology..." class="w-full" />
          </div>
          <div class="field">
            <label>Industry <span class="hint">(editable)</span></label>
            <InputText v-model="form.industry" placeholder="Small Modular Reactors..." class="w-full" />
          </div>
        </template>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="closeDialog" />
        <Button :label="editTarget ? 'Guardar' : 'Añadir'" :loading="saving" :disabled="!editTarget && !form.symbol" @click="save" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import AutoComplete from 'primevue/autocomplete'
import ToggleSwitch from 'primevue/toggleswitch'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { ListAccionesUseCase } from '@/Acciones/Application/UseCase/ListAcciones/ListAccionesUseCase'
import { CreateAccionUseCase } from '@/Acciones/Application/UseCase/CreateAccion/CreateAccionUseCase'
import { UpdateAccionUseCase } from '@/Acciones/Application/UseCase/UpdateAccion/UpdateAccionUseCase'
import { DeleteAccionUseCase } from '@/Acciones/Application/UseCase/DeleteAccion/DeleteAccionUseCase'
import { LookupAccionUseCase } from '@/Acciones/Application/UseCase/LookupAccion/LookupAccionUseCase'
import { FetchAccionPriceUseCase } from '@/Acciones/Application/UseCase/FetchAccionPrice/FetchAccionPriceUseCase'
import { ListPortafolioUseCase } from '@/Acciones/Application/UseCase/ListPortafolio/ListPortafolioUseCase'
import { AddAccionToPortafolioUseCase } from '@/Acciones/Application/UseCase/AddAccionToPortafolio/AddAccionToPortafolioUseCase'

const confirm = useConfirm()
const toast   = useToast()

const acciones      = ref([])
const loading       = ref(true)
const search        = ref('')
const onlyWithReports = ref(false)
const saving        = ref(false)
const dialogVisible = ref(false)
const editTarget    = ref(null)

const lookupQuery   = ref('')
const lookupResults = ref([])
const lookupLoading = ref(false)

const portafolios       = ref([])
const portafolioOptions = ref([])

const form = ref({ symbol: '', name: '', type: 'stock', sector: '', industry: '', exchange: '', portafolioUuid: null })

const typeOptions = [
  { label: 'Stock',  value: 'stock' },
  { label: 'ETF',    value: 'etf' },
  { label: 'Crypto', value: 'crypto' },
]

function isTomorrow(dateStr) {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  return dateStr === tomorrow.toISOString().slice(0, 10)
}

function formatEarningsDate(dateStr) {
  const [y, m, d] = dateStr.split('-')
  return `${d}/${m}/${y.slice(2)}`
}

const FAIR_VALUE_METHOD_LABELS = {
  graham_number: 'Número de Graham',
  dividend_discount: 'Descuento de dividendos',
  price_to_sales: 'Múltiplo de ventas (P/S)',
  net_cash_floor: 'Piso de caja neta',
}

function fairValueClass(precio, fairValue) {
  if (precio == null) return ''
  return fairValue >= precio ? 'undervalued' : 'overvalued'
}

function fairValueTooltip(data) {
  const label = FAIR_VALUE_METHOD_LABELS[data.fair_value_method] ?? data.fair_value_method
  if (data.fair_value_method === 'net_cash_floor') {
    return `${label} — NO es un precio objetivo, es lo que quedaría por acción si la empresa liquidara hoy (caja neta / acciones). No hay beneficio, dividendo ni ingresos crecientes para valorarla de otra forma.`
  }
  if (data.precio == null) return label
  const diffPct = ((data.fair_value - data.precio) / data.precio) * 100
  const direction = diffPct >= 0 ? 'por debajo de' : 'por encima de'
  return `${label} — precio de mercado ${Math.abs(diffPct).toFixed(1)}% ${direction} el valor estimado`
}

const filtered = computed(() => {
  const q = search.value.toLowerCase().trim()
  let rows = acciones.value

  if (onlyWithReports.value) {
    rows = rows.filter(a => a.has_earnings_report)
  }

  if (!q) return rows

  return rows.filter(a =>
    a.symbol.toLowerCase().includes(q) ||
    a.name.toLowerCase().includes(q) ||
    a.sector?.toLowerCase().includes(q)
  )
})

onMounted(async () => {
  const [accionesList, portafoliosList] = await Promise.all([
    ListAccionesUseCase(),
    ListPortafolioUseCase(),
  ])
  acciones.value   = accionesList
  portafolios.value = portafoliosList
  portafolioOptions.value = portafoliosList.map(p => ({
    label: p.is_default ? `${p.nombre} (default)` : p.nombre,
    value: p.uuid,
  }))
  loading.value = false
})

async function onLookupSearch(event) {
  const q = event.query?.trim()
  if (!q || q.length < 1) { lookupResults.value = []; return }
  lookupLoading.value = true
  try {
    const results = await LookupAccionUseCase(q)
    lookupResults.value = results.map(r => ({ ...r, label: `${r.symbol} — ${r.name}` }))
  } finally {
    lookupLoading.value = false
  }
}

function onLookupSelect(event) {
  const r = event.value
  form.value.symbol   = r.symbol
  form.value.name     = r.name
  form.value.type     = r.type ?? 'stock'
  form.value.exchange = r.exchange ?? ''
  form.value.sector   = r.sector ?? ''
  form.value.industry = r.industry ?? ''
}

function openCreate() {
  editTarget.value = null
  lookupQuery.value = ''
  lookupResults.value = []
  const defaultPortafolio = portafolios.value.find(p => p.is_default)
  form.value = {
    symbol: '', name: '', type: 'stock', sector: '', industry: '', exchange: '',
    portafolioUuid: defaultPortafolio?.uuid ?? portafolios.value[0]?.uuid ?? null,
  }
  dialogVisible.value = true
}

function closeDialog() {
  dialogVisible.value = false
  lookupQuery.value = ''
}

function openEdit(accion) {
  editTarget.value = accion
  form.value = {
    symbol:   accion.symbol,
    name:     accion.name,
    type:     accion.type,
    exchange: accion.exchange ?? '',
    sector:   accion.sector ?? '',
    industry: accion.industry ?? '',
  }
  dialogVisible.value = true
}

async function save() {
  if (!form.value.symbol || !form.value.name) {
    toast.add({ severity: 'warn', summary: 'Faltan campos', detail: 'Símbolo y nombre son obligatorios', life: 3000 })
    return
  }
  saving.value = true
  try {
    if (editTarget.value) {
      const updated = await UpdateAccionUseCase(editTarget.value.uuid, {
        name:     form.value.name,
        sector:   form.value.sector || null,
        industry: form.value.industry || null,
      })
      Object.assign(editTarget.value, updated)
    } else {
      const created = await CreateAccionUseCase({
        symbol:   form.value.symbol.toUpperCase(),
        name:     form.value.name,
        type:     form.value.type,
        exchange: form.value.exchange || null,
        sector:   form.value.sector || null,
        industry: form.value.industry || null,
      })

      // Fetch price y añadir al portafolio en paralelo
      await Promise.allSettled([
        FetchAccionPriceUseCase(created.symbol, created.uuid).then(price => {
          created.precio        = price.price
          created.change_pct    = price.change_pct
          created.change_amount = price.change_amount
        }),
        form.value.portafolioUuid
          ? AddAccionToPortafolioUseCase(form.value.portafolioUuid, { accion_uuid: created.uuid, status: 'watchlist' })
          : Promise.resolve(),
      ])

      acciones.value.push(created)
    }
    closeDialog()
    toast.add({ severity: 'success', summary: 'Guardado', life: 2000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.error ?? e.message, life: 4000 })
  } finally {
    saving.value = false
  }
}

async function toggleActive(accion) {
  try {
    const updated = await UpdateAccionUseCase(accion.uuid, { is_active: !accion.is_active })
    accion.is_active = updated.is_active
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 3000 })
  }
}

function confirmDelete(accion) {
  confirm.require({
    message: `¿Eliminar ${accion.symbol}? Se borrarán también sus precios y noticias.`,
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancelar',
    acceptLabel: 'Eliminar',
    acceptClass: 'p-button-danger',
    accept: () => deleteAccion(accion),
  })
}

async function deleteAccion(accion) {
  try {
    await DeleteAccionUseCase(accion.uuid)
    acciones.value = acciones.value.filter(a => a.uuid !== accion.uuid)
    toast.add({ severity: 'success', summary: `${accion.symbol} eliminada`, life: 2000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 3000 })
  }
}
</script>

<style scoped>
.toolbar { display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1.25rem; }

.symbol-badge { font-weight: 700; font-size: 0.9rem; letter-spacing: 0.03em; color: var(--tokyo-cyan); }

.name-link { color: var(--tokyo-fg); text-decoration: none; font-size: 0.9rem; }
.name-link:hover { color: var(--tokyo-cyan); text-decoration: underline; }

.exchange-text { font-size: 0.8rem; color: var(--tokyo-fg-dim); font-family: monospace; }

.portafolio-link { font-size: 0.82rem; color: var(--tokyo-purple, #bb9af7); text-decoration: none; }
.portafolio-link:hover { text-decoration: underline; }

.lookup-option { display: flex; align-items: center; gap: 0.6rem; padding: 2px 0; }
.lookup-symbol { font-weight: 700; color: var(--tokyo-cyan); min-width: 60px; font-size: 0.9rem; }
.lookup-name   { flex: 1; font-size: 0.85rem; }
.lookup-exchange { font-size: 0.75rem; color: var(--tokyo-fg-dim); white-space: nowrap; }

.preview-card {
  background: var(--tokyo-bg-tertiary);
  border-radius: 8px; padding: 0.75rem 1rem;
  display: flex; flex-direction: column; gap: 0.2rem;
}
.preview-row { display: flex; align-items: center; gap: 0.75rem; }
.preview-symbol { font-weight: 700; font-size: 1rem; color: var(--tokyo-cyan); }
.preview-exchange { font-size: 0.78rem; color: var(--tokyo-fg-dim); font-family: monospace; }
.preview-name { font-size: 0.9rem; font-weight: 500; }
.preview-sector { font-size: 0.78rem; color: var(--tokyo-fg-dim); }

.sector-text { font-size: 0.82rem; color: var(--tokyo-fg-dim); }

.price-val { font-weight: 600; font-size: 0.9rem; }
.fair-value-val { font-weight: 600; font-size: 0.9rem; cursor: help; border-bottom: 1px dashed currentColor; }
.fair-value-val.undervalued { color: #4ade80; }
.fair-value-val.overvalued  { color: #f87171; }
.fair-value-val--floor { color: var(--tokyo-fg-dim); border-bottom-style: dotted; display: inline-flex; align-items: center; gap: 0.3rem; }
.fair-value-val--floor .pi { font-size: 0.78rem; }

.change-badge {
  display: inline-flex; align-items: center; gap: 0.25rem;
  font-weight: 700; font-size: 0.85rem; border-radius: 4px;
  padding: 2px 6px;
}
.change-badge.up   { color: #4ade80; background: rgba(74, 222, 128, 0.1); }
.change-badge.down { color: #f87171; background: rgba(248, 113, 113, 0.1); }
.change-badge .pi  { font-size: 0.7rem; }

.amount-up   { font-size: 0.85rem; color: #4ade80; }
.amount-down { font-size: 0.85rem; color: #f87171; }

.row-actions { display: flex; gap: 0.6rem; justify-content: flex-end; align-items: center; }
.row-actions i, .row-actions a { cursor: pointer; color: #94a3b8; font-size: 0.9rem; transition: color 0.15s; text-decoration: none; }
.row-actions .pi-pencil:hover { color: var(--tokyo-blue); }
.row-actions .pi-trash:hover  { color: var(--tokyo-red); }
.row-actions a:hover i        { color: var(--tokyo-cyan); }

.form-fields { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.field label { font-size: 0.82rem; color: var(--tokyo-fg-dim); }
.hint { font-size: 0.75rem; color: var(--tokyo-fg-dim); opacity: 0.7; }

.earnings-tomorrow { font-size: 0.82rem; font-weight: 700; color: #f87171; }
.earnings-normal   { font-size: 0.82rem; color: var(--tokyo-fg-dim); }
</style>

