<template>
  <div>
    <div class="page__header">
      <div>
        <h1 class="page__title">Portafolios</h1>
        <span class="page__subtitle">{{ portafolios.length }} portafolios</span>
      </div>
      <Button label="Nuevo portafolio" icon="pi pi-plus" size="small" @click="openCreate" />
    </div>

    <div v-if="loading" class="loading-msg"><i class="pi pi-spin pi-spinner" /> Cargando...</div>

    <div v-else-if="portafolios.length === 0" class="empty-msg">
      <i class="pi pi-briefcase" style="font-size: 2rem; margin-bottom: 0.5rem" />
      <p>No tienes portafolios todavía.</p>
      <Button label="Crear el primero" icon="pi pi-plus" size="small" @click="openCreate" style="margin-top: 0.75rem" />
    </div>

    <div v-else class="portafolios-grid">
      <div v-for="p in portafolios" :key="p.uuid" class="portafolio-card" :class="{ 'is-default': p.is_default }">
        <div class="card-header">
          <div class="card-title-row">
            <RouterLink :to="`/portafolio/${p.uuid}`" class="card-title">{{ p.nombre }}</RouterLink>
            <i
              v-if="p.is_default"
              class="pi pi-box default-icon"
              v-tooltip.top="'Portafolio por defecto'"
            />
            <button
              v-else
              class="set-default-btn"
              v-tooltip.top="'Marcar como default'"
              @click="setDefault(p)"
            >
              <i class="pi pi-box" />
            </button>
          </div>
          <p v-if="p.descripcion" class="card-desc">{{ p.descripcion }}</p>
          <div class="card-actions">
            <i class="pi pi-pencil" @click="openEdit(p)" title="Editar" />
            <i class="pi pi-trash" @click="confirmDelete(p)" title="Eliminar" />
          </div>
        </div>
        <div class="card-footer">
          <span><i class="pi pi-chart-line" /> {{ p.total }} acción{{ p.total !== 1 ? 'es' : '' }}</span>
          <span class="text-muted">{{ p.created_at?.slice(0, 10) }}</span>
        </div>
      </div>
    </div>

    <!-- Dialog crear / editar -->
    <Dialog v-model:visible="dialogVisible" :header="editTarget ? 'Editar portafolio' : 'Nuevo portafolio'" modal style="width: 420px">
      <div class="form-fields">
        <div class="field">
          <label>Nombre *</label>
          <InputText v-model="form.nombre" placeholder="main, nuclear, crypto..." class="w-full" />
        </div>
        <div class="field">
          <label>Descripción</label>
          <Textarea v-model="form.descripcion" rows="2" class="w-full" placeholder="Para qué es este portafolio..." />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="dialogVisible = false" />
        <Button :label="editTarget ? 'Guardar' : 'Crear'" :loading="saving" @click="save" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { ListPortafolioUseCase } from '@/Acciones/Application/UseCase/ListPortafolio/ListPortafolioUseCase'
import { CreatePortafolioEntryUseCase } from '@/Acciones/Application/UseCase/CreatePortafolioEntry/CreatePortafolioEntryUseCase'
import { UpdatePortafolioEntryUseCase } from '@/Acciones/Application/UseCase/UpdatePortafolioEntry/UpdatePortafolioEntryUseCase'
import { DeletePortafolioEntryUseCase } from '@/Acciones/Application/UseCase/DeletePortafolioEntry/DeletePortafolioEntryUseCase'

const confirm = useConfirm()
const toast   = useToast()

const portafolios   = ref([])
const loading       = ref(true)
const saving        = ref(false)
const dialogVisible = ref(false)
const editTarget    = ref(null)
const form = ref({ nombre: '', descripcion: '' })

onMounted(async () => {
  portafolios.value = await ListPortafolioUseCase()
  loading.value = false
})

function openCreate() {
  editTarget.value = null
  form.value = { nombre: '', descripcion: '' }
  dialogVisible.value = true
}

function openEdit(p) {
  editTarget.value = p
  form.value = { nombre: p.nombre, descripcion: p.descripcion ?? '' }
  dialogVisible.value = true
}

async function save() {
  if (!form.value.nombre.trim()) {
    toast.add({ severity: 'warn', summary: 'El nombre es obligatorio', life: 2500 })
    return
  }
  saving.value = true
  try {
    if (editTarget.value) {
      const updated = await UpdatePortafolioEntryUseCase(editTarget.value.uuid, {
        nombre: form.value.nombre,
        descripcion: form.value.descripcion || null,
      })
      Object.assign(editTarget.value, updated)
    } else {
      const created = await CreatePortafolioEntryUseCase({
        nombre:      form.value.nombre,
        descripcion: form.value.descripcion || null,
      })
      portafolios.value.push(created)
    }
    dialogVisible.value = false
    toast.add({ severity: 'success', summary: 'Guardado', life: 2000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.error ?? e.message, life: 4000 })
  } finally {
    saving.value = false
  }
}

async function setDefault(p) {
  try {
    const updated = await UpdatePortafolioEntryUseCase(p.uuid, { is_default: true })
    portafolios.value.forEach(x => { x.is_default = x.uuid === p.uuid })
    toast.add({ severity: 'success', summary: `"${p.nombre}" es ahora el portafolio por defecto`, life: 2500 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 3000 })
  }
}

function confirmDelete(p) {
  confirm.require({
    message: `¿Eliminar "${p.nombre}"? Se eliminarán también todas sus acciones.`,
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancelar',
    acceptLabel: 'Eliminar',
    acceptClass: 'p-button-danger',
    accept: async () => {
      await DeletePortafolioEntryUseCase(p.uuid)
      portafolios.value = portafolios.value.filter(x => x.uuid !== p.uuid)
      toast.add({ severity: 'success', summary: `"${p.nombre}" eliminado`, life: 2000 })
    },
  })
}
</script>

<style scoped>
.portafolios-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}

.portafolio-card {
  background: var(--tokyo-bg-secondary);
  border: 1px solid var(--tokyo-bg-tertiary);
  border-radius: 10px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  transition: border-color 0.15s;
}
.portafolio-card:hover { border-color: var(--tokyo-blue); }

.portafolio-card.is-default { border-color: var(--tokyo-cyan); }

.card-header { display: flex; flex-direction: column; gap: 0.4rem; }

.card-title-row { display: flex; align-items: center; gap: 0.5rem; }

.card-title {
  font-size: 1.1rem; font-weight: 700;
  color: var(--tokyo-cyan); text-decoration: none;
}
.card-title:hover { text-decoration: underline; }

.default-icon { color: var(--tokyo-cyan); font-size: 1rem; }

.set-default-btn {
  background: none; border: 1px solid #475569; border-radius: 4px;
  padding: 2px 6px; cursor: pointer; color: #475569;
  font-size: 0.8rem; display: inline-flex; align-items: center;
  transition: all 0.15s;
}
.set-default-btn:hover { border-color: var(--tokyo-cyan); color: var(--tokyo-cyan); }

.card-desc { font-size: 0.82rem; color: var(--tokyo-fg-dim); }

.card-actions { display: flex; gap: 0.6rem; }
.card-actions i { cursor: pointer; color: #94a3b8; font-size: 0.9rem; transition: color 0.15s; }
.card-actions .pi-pencil:hover { color: var(--tokyo-blue); }
.card-actions .pi-trash:hover  { color: var(--tokyo-red); }

.card-footer {
  display: flex; justify-content: space-between;
  font-size: 0.8rem; color: var(--tokyo-fg-dim);
  border-top: 1px solid var(--tokyo-bg-tertiary);
  padding-top: 0.6rem;
}

.loading-msg, .empty-msg {
  padding: 3rem; text-align: center;
  color: var(--tokyo-fg-dim);
  display: flex; flex-direction: column; align-items: center;
}

.form-fields { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.field label { font-size: 0.82rem; color: var(--tokyo-fg-dim); }
</style>
