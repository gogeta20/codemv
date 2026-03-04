<template>
  <div>
    <div class="page__header">
      <h1 class="page__title">Usuarios</h1>
      <span v-if="users.length" class="page__subtitle">{{ users.length }} resultados</span>
    </div>

    <!-- Search bar -->
    <form class="search-bar mb-5" @submit.prevent="handleSearch">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText
          v-model="searchInput"
          placeholder="Buscar por nombre, username o email..."
          class="search-input"
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

    <!-- Empty state: no search yet -->
    <div v-if="!activeSearch && !loading" class="empty-state">
      <i class="pi pi-search empty-state__icon" />
      <p class="empty-state__title">Busca un usuario</p>
      <p class="empty-state__desc">Ingresa un nombre, username o email para encontrar usuarios en el directorio.</p>
    </div>

    <!-- Table -->
    <DataTable
      v-if="activeSearch"
      :value="users"
      :loading="loading"
      stripedRows
      paginator
      :rows="20"
      :rowsPerPageOptions="[10, 20, 50]"
      dataKey="samaccountname"
      class="users-table"
    >
      <template #empty>No se encontraron usuarios.</template>

      <Column field="displayname" header="Nombre" sortable style="min-width: 200px">
        <template #body="{ data }">
          <div class="user-name">
            <span class="user-name__display">{{ data.displayname || data.cn }}</span>
            <span v-if="data.givenname || data.sn" class="user-name__sub text-muted text-xs">
              {{ [data.givenname, data.sn].filter(Boolean).join(' ') }}
            </span>
          </div>
        </template>
      </Column>

      <Column field="samaccountname" header="Username" sortable style="width: 160px">
        <template #body="{ data }">
          <code
            class="code-inline code-inline--link"
            @click.stop="router.push(`/ad/users/${data.samaccountname}`)"
          >{{ data.samaccountname }}</code>
        </template>
      </Column>

      <Column field="mail" header="Email" style="min-width: 200px">
        <template #body="{ data }">
          <span class="text-sm">{{ data.mail || '—' }}</span>
        </template>
      </Column>

      <Column field="enabled" header="Estado" sortable style="width: 100px">
        <template #body="{ data }">
          <Tag
            :value="data.enabled ? 'Activo' : 'Inactivo'"
            :severity="data.enabled ? 'success' : 'secondary'"
            style="font-size: 0.72rem"
          />
        </template>
      </Column>

      <Column field="lastlogon" header="Último acceso" sortable style="width: 140px">
        <template #body="{ data }">
          <span class="text-sm text-muted">{{ formatLdapDate(data.lastlogon) }}</span>
        </template>
      </Column>

    </DataTable>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { SearchUsersUseCase } from '@/ActiveDirectory/Application/UseCase/SearchUsers/SearchUsersUseCase'

const router = useRouter()
const users = ref([])
const loading = ref(false)
const searchInput = ref('')
const activeSearch = ref('')

async function handleSearch() {
  const q = searchInput.value.trim()
  if (!q) return

  loading.value = true
  activeSearch.value = q
  users.value = await SearchUsersUseCase(q)
  loading.value = false
}

function clearSearch() {
  searchInput.value = ''
  activeSearch.value = ''
  users.value = []
}

function formatLdapDate(ldap) {
  if (!ldap) return '—'
  // LDAP format: YYYYMMDDHHmmssZ
  if (!/^\d{14}Z?$/.test(ldap)) return '—'
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

.search-input { width: 340px; }

.active-search {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.82rem;
  color: var(--tokyo-blue);
  background: var(--tokyo-bg-secondary);
  border: 1px solid var(--tokyo-bg-tertiary);
  border-radius: 6px;
  padding: 0.25rem 0.75rem;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 5rem 2rem;
  text-align: center;
  color: var(--tokyo-fg-dim);
}

.empty-state__icon {
  font-size: 2.5rem;
  opacity: 0.3;
}

.empty-state__title {
  font-size: 1.1rem;
  font-weight: 600;
}

.empty-state__desc {
  font-size: 0.875rem;
  line-height: 1.6;
  max-width: 360px;
}

.users-table { cursor: default; }

.user-name {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.user-name__display {
  font-weight: 500;
}

.code-inline {
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
  font-size: 0.82rem;
  color: var(--tokyo-fg);
  background: var(--tokyo-bg-tertiary);
  padding: 1px 6px;
  border-radius: 3px;
}

.code-inline--link {
  cursor: pointer;
  color: var(--tokyo-blue);
  transition: background 0.15s;
}

.code-inline--link:hover {
  background: var(--tokyo-bg-secondary);
  text-decoration: underline;
}
</style>
