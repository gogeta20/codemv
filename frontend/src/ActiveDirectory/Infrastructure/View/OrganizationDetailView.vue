<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <Button icon="pi pi-arrow-left" label="Volver" text size="small" @click="$router.back()" />
    </div>

    <div v-if="loading">Cargando...</div>

    <div v-else-if="org">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="page__title">{{ org.name }}</h1>
        <div class="text-sm text-muted mt-2">
          <span class="org-code">{{ org.ou }}</span>
          <span class="mx-2">·</span>
          <span>{{ org.distinguishedname }}</span>
        </div>
        <p v-if="org.description" class="text-secondary mt-2">{{ org.description }}</p>
      </div>

      <!-- Stats -->
      <div class="stats-row mb-6">
        <div class="stat-card">
          <span class="stat-card__value">{{ org.sub_ous_count }}</span>
          <span class="stat-card__label">Sub-OUs</span>
        </div>
        <div class="stat-card">
          <span class="stat-card__value">{{ org.users_count }}</span>
          <span class="stat-card__label">Usuarios</span>
        </div>
        <div class="stat-card">
          <span class="stat-card__value">{{ org.groups_count }}</span>
          <span class="stat-card__label">Grupos</span>
        </div>
        <div class="stat-card">
          <span class="stat-card__value">{{ org.computers_count }}</span>
          <span class="stat-card__label">Equipos</span>
        </div>
      </div>

      <!-- Sub-OUs -->
      <section v-if="org.sub_ous?.length" class="section">
        <h2 class="section__title">
          <i class="pi pi-sitemap" />
          Sub-OUs
        </h2>
        <DataTable :value="org.sub_ous" dataKey="ou" size="small" stripedRows>
          <Column field="ou" header="OU" style="width: 140px" />
          <Column field="name" header="Nombre" />
          <Column field="description" header="Descripción">
            <template #body="{ data }">{{ data.description || '—' }}</template>
          </Column>
          <Column field="whencreated" header="Creada" style="width: 120px">
            <template #body="{ data }">
              <span class="text-sm text-muted">{{ formatLdapDate(data.whencreated) }}</span>
            </template>
          </Column>
        </DataTable>
      </section>

      <!-- Users -->
      <section v-if="org.users?.length" class="section">
        <h2 class="section__title">
          <i class="pi pi-users" />
          Usuarios
        </h2>
        <DataTable :value="org.users" dataKey="samaccountname" size="small" stripedRows>
          <Column field="displayname" header="Nombre">
            <template #body="{ data }">
              <span>{{ data.displayname || data.cn }}</span>
            </template>
          </Column>
          <Column field="samaccountname" header="Username">
            <template #body="{ data }">
              <code
                class="code-inline code-inline--link"
                @click.stop="router.push(`/ad/users/${data.samaccountname}`)"
              >{{ data.samaccountname }}</code>
            </template>
          </Column>
          <Column field="mail" header="Email">
            <template #body="{ data }">{{ data.mail || '—' }}</template>
          </Column>
          <Column field="enabled" header="Estado" style="width: 100px">
            <template #body="{ data }">
              <Tag
                :value="data.enabled ? 'Activo' : 'Inactivo'"
                :severity="data.enabled ? 'success' : 'secondary'"
                style="font-size: 0.72rem"
              />
            </template>
          </Column>
          <Column field="lastlogon" header="Último acceso" style="width: 130px">
            <template #body="{ data }">
              <span class="text-sm text-muted">{{ formatLdapDate(data.lastlogon) }}</span>
            </template>
          </Column>
        </DataTable>
      </section>

      <!-- Groups -->
      <section v-if="org.groups?.length" class="section">
        <h2 class="section__title">
          <i class="pi pi-shield" />
          Grupos
        </h2>
        <DataTable :value="org.groups" dataKey="cn" size="small" stripedRows>
          <Column field="cn" header="Nombre" />
          <Column field="description" header="Descripción">
            <template #body="{ data }">{{ data.description || '—' }}</template>
          </Column>
          <Column field="grouptype" header="Tipo" style="width: 110px" />
          <Column field="members_count" header="Miembros" style="width: 100px; text-align: right">
            <template #body="{ data }">
              <span class="font-semibold">{{ data.members_count }}</span>
            </template>
          </Column>
        </DataTable>
      </section>

      <!-- Computers -->
      <section v-if="org.computers?.length" class="section">
        <h2 class="section__title">
          <i class="pi pi-desktop" />
          Equipos
        </h2>
        <DataTable :value="org.computers" dataKey="cn" size="small" stripedRows>
          <Column field="cn" header="Nombre" style="width: 160px" />
          <Column field="dnshostname" header="Hostname">
            <template #body="{ data }">{{ data.dnshostname || '—' }}</template>
          </Column>
          <Column field="operatingsystem" header="Sistema operativo">
            <template #body="{ data }">{{ data.operatingsystem || '—' }}</template>
          </Column>
          <Column field="lastlogon" header="Último acceso" style="width: 130px">
            <template #body="{ data }">
              <span class="text-sm text-muted">{{ formatLdapDate(data.lastlogon) }}</span>
            </template>
          </Column>
        </DataTable>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import { GetOrganizationUseCase } from '@/ActiveDirectory/Application/UseCase/GetOrganization/GetOrganizationUseCase'

const route = useRoute()
const router = useRouter()
const org = ref(null)
const loading = ref(true)

onMounted(async () => {
  org.value = await GetOrganizationUseCase(route.params.code)
  loading.value = false
})

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
.org-code {
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--tokyo-blue);
  background: var(--tokyo-bg-tertiary);
  padding: 2px 8px;
  border-radius: 4px;
}

.mx-2 { margin: 0 0.5rem; }

.mt-2 { margin-top: 0.5rem; }

.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.stat-card {
  background: var(--tokyo-bg-secondary);
  border: 1px solid var(--tokyo-bg-tertiary);
  border-radius: var(--cmv-radius-lg, 10px);
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.stat-card__value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--tokyo-blue);
  line-height: 1;
}

.stat-card__label {
  font-size: 0.8rem;
  color: var(--tokyo-fg-dim);
}

.section {
  margin-bottom: 2rem;
}

.section__title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
  color: var(--tokyo-fg-secondary);
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
