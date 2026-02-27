<template>
  <div>
    <div class="page__header">
      <h1 class="page__title">Organizaciones</h1>
      <span class="page__subtitle">{{ organizations.length }} entradas</span>
    </div>

    <div class="mb-5">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText v-model="search" placeholder="Buscar por nombre, código..." class="search-input" />
      </IconField>
    </div>

    <DataTable
      :value="filtered"
      :loading="loading"
      stripedRows
      paginator
      :rows="20"
      :rowsPerPageOptions="[10, 20, 50]"
      dataKey="code"
      class="orgs-table"
      @row-click="goToDetail"
    >
      <template #empty>No hay organizaciones que coincidan.</template>

      <Column field="code" header="Código" sortable style="width: 130px">
        <template #body="{ data }">
          <span class="org-code">{{ data.code }}</span>
        </template>
      </Column>

      <Column field="name" header="Nombre" sortable style="min-width: 220px" />

      <Column field="description" header="Descripción" style="min-width: 260px">
        <template #body="{ data }">
          <span class="text-secondary text-sm truncate" style="max-width: 320px; display: block;">
            {{ data.description || '—' }}
          </span>
        </template>
      </Column>

      <Column field="whencreated" header="Creada" sortable style="width: 130px">
        <template #body="{ data }">
          <span class="text-sm text-muted">{{ formatLdapDate(data.whencreated) }}</span>
        </template>
      </Column>

      <Column field="whenchanged" header="Modificada" sortable style="width: 130px">
        <template #body="{ data }">
          <span class="text-sm text-muted">{{ formatLdapDate(data.whenchanged) }}</span>
        </template>
      </Column>

      <Column header="" style="width: 2.5rem; text-align: center">
        <template #body>
          <i class="pi pi-chevron-right" style="color: #cbd5e1; font-size: 0.8rem;" />
        </template>
      </Column>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import { ListOrganizationsUseCase } from '@/ActiveDirectory/Application/UseCase/ListOrganizations/ListOrganizationsUseCase'

const router = useRouter()
const organizations = ref([])
const loading = ref(true)
const search = ref('')

const filtered = computed(() => {
  if (!search.value.trim()) return organizations.value
  const q = search.value.toLowerCase()
  return organizations.value.filter(o =>
    o.code.toLowerCase().includes(q) ||
    o.name?.toLowerCase().includes(q) ||
    o.description?.toLowerCase().includes(q)
  )
})

onMounted(async () => {
  organizations.value = await ListOrganizationsUseCase()
  loading.value = false
})

function goToDetail({ data }) {
  router.push(`/ad/organizations/${data.code}`)
}

function formatLdapDate(ldap) {
  if (!ldap) return '—'
  // LDAP format: YYYYMMDDHHmmssZ
  const y = ldap.slice(0, 4)
  const m = ldap.slice(4, 6)
  const d = ldap.slice(6, 8)
  return `${y}-${m}-${d}`
}
</script>

<style scoped>
.search-input { width: 320px; }

.orgs-table { cursor: pointer; }

.org-code {
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--p-primary-500, #6366f1);
  background: var(--p-primary-50, #eef2ff);
  padding: 2px 8px;
  border-radius: 4px;
}
</style>
