<template>
  <div>
    <div class="page__header">
      <h1 class="page__title">Organizaciones</h1>
      <span class="page__subtitle">{{ organizations.length }} entradas</span>
    </div>

    <!-- Search bar -->
    <form class="search-bar mb-5" @submit.prevent="handleSearch">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText
          v-model="searchInput"
          placeholder="Buscar por código o descripción..."
          class="search-input"
          @keyup.enter="handleSearch"
        />
      </IconField>
      <Button
        type="submit"
        label="Buscar"
        icon="pi pi-search"
        :loading="loading"
        :disabled="!searchInput.trim()"
      />
      <Button
        v-if="activeSearch"
        icon="pi pi-times"
        text
        severity="secondary"
        v-tooltip.bottom="'Limpiar búsqueda'"
        @click="clearSearch"
      />
    </form>

    <!-- Active search badge -->
    <div v-if="activeSearch" class="active-search mb-4">
      <i class="pi pi-filter-fill" style="font-size: 0.75rem" />
      Mostrando resultados para: <strong>{{ activeSearch }}</strong>
    </div>

    <DataTable
      :value="organizations"
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Button from 'primevue/button'
import { ListOrganizationsUseCase } from '@/ActiveDirectory/Application/UseCase/ListOrganizations/ListOrganizationsUseCase'
import { SearchOrganizationsUseCase } from '@/ActiveDirectory/Application/UseCase/SearchOrganizations/SearchOrganizationsUseCase'

const router = useRouter()
const organizations = ref([])
const loading = ref(true)
const searchInput = ref('')
const activeSearch = ref('')

onMounted(async () => {
  organizations.value = await ListOrganizationsUseCase()
  loading.value = false
})

async function handleSearch() {
  const q = searchInput.value.trim()
  if (!q) return

  loading.value = true
  activeSearch.value = q
  organizations.value = await SearchOrganizationsUseCase(q)
  loading.value = false
}

async function clearSearch() {
  searchInput.value = ''
  activeSearch.value = ''
  loading.value = true
  organizations.value = await ListOrganizationsUseCase()
  loading.value = false
}

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
.search-bar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.search-input { width: 320px; }

.active-search {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.82rem;
  color: var(--p-primary-500, #6366f1);
  background: var(--p-primary-50, #eef2ff);
  border: 1px solid var(--p-primary-200, #c7d2fe);
  border-radius: 6px;
  padding: 0.25rem 0.75rem;
}

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
