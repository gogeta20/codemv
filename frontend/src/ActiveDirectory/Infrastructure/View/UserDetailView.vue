<template>
  <div>
    <div class="flex items-center mb-6">
      <Button icon="pi pi-arrow-left" label="Volver" text size="small" @click="$router.back()" />
    </div>

    <div v-if="loading">Cargando...</div>

    <div v-else-if="!user">
      <p class="text-muted">Usuario no encontrado.</p>
    </div>

    <div v-else>
      <!-- Header -->
      <div class="mb-4">
        <div class="flex items-center gap-3 mb-2">
          <h1 class="page__title">{{ user.displayname || user.cn }}</h1>
          <Tag
            :value="enabled ? 'Activo' : 'Inactivo'"
            :severity="enabled ? 'success' : 'secondary'"
          />
        </div>
        <div class="flex items-center gap-2 text-sm text-muted mb-2">
          <code class="code-inline">{{ user.samaccountname }}</code>
          <span v-if="user.userprincipalname">· {{ user.userprincipalname }}</span>
        </div>
        <!-- Org chip -->
        <div v-if="orgCode" class="flex items-center gap-2">
          <span class="text-xs text-muted">Organización:</span>
          <span class="org-chip" @click="router.push(`/ad/organizations/${orgCode}`)">
            <i class="pi pi-sitemap" style="font-size: 0.7rem" />
            {{ orgCode }}
          </span>
        </div>
      </div>

      <!-- Primary fields -->
      <div class="section-title">Identidad</div>
      <div class="info-grid mb-6">
        <div class="info-item">
          <span class="info-item__label">UUID</span>
          <code class="code-inline text-xs">{{ user.uuid || '—' }}</code>
        </div>
        <div class="info-item">
          <span class="info-item__label">CN</span>
          <span class="info-item__value">{{ user.cn || '—' }}</span>
        </div>
        <div class="info-item">
          <span class="info-item__label">Nombre</span>
          <span class="info-item__value">{{ user.givenname || '—' }}</span>
        </div>
        <div class="info-item">
          <span class="info-item__label">Apellidos</span>
          <span class="info-item__value">{{ user.sn || '—' }}</span>
        </div>
        <div class="info-item">
          <span class="info-item__label">Iniciales</span>
          <span class="info-item__value">{{ user.initials || '—' }}</span>
        </div>
        <div class="info-item">
          <span class="info-item__label">Descripción</span>
          <span class="info-item__value">{{ user.description || '—' }}</span>
        </div>
        <div class="info-item info-item--wide">
          <span class="info-item__label">DN</span>
          <code class="code-inline text-xs">{{ user.dn || user.distinguishedname || '—' }}</code>
        </div>
      </div>

      <!-- Contact & account -->
      <div class="section-title">Cuenta</div>
      <div class="info-grid mb-6">
        <div class="info-item">
          <span class="info-item__label">Email</span>
          <span class="info-item__value">{{ user.mail || '—' }}</span>
        </div>
        <div class="info-item">
          <span class="info-item__label">UPN</span>
          <span class="info-item__value">{{ user.userprincipalname || '—' }}</span>
        </div>
        <div class="info-item">
          <span class="info-item__label">Intentos de contraseña fallidos</span>
          <span class="info-item__value">{{ user.badpwdcount ?? '—' }}</span>
        </div>
        <div class="info-item">
          <span class="info-item__label">Creado</span>
          <span class="info-item__value">{{ formatLdapDate(user.whencreated) }}</span>
        </div>
        <div class="info-item">
          <span class="info-item__label">Modificado</span>
          <span class="info-item__value">{{ formatLdapDate(user.whenchanged) }}</span>
        </div>
        <div class="info-item">
          <span class="info-item__label">Último acceso</span>
          <span class="info-item__value">{{ formatWinFiletime(user.lastlogon) }}</span>
        </div>
        <div class="info-item">
          <span class="info-item__label">Último acceso (timestamp)</span>
          <span class="info-item__value">{{ formatWinFiletime(user.lastlogontimestamp) }}</span>
        </div>
        <div class="info-item">
          <span class="info-item__label">Contraseña cambiada</span>
          <span class="info-item__value">{{ formatWinFiletime(user.pwdlastset) }}</span>
        </div>
      </div>

      <!-- Groups -->
      <div v-if="groups.length" class="mb-6">
        <div class="section-title">Grupos ({{ groups.length }})</div>
        <div class="groups-list">
          <span v-for="group in groups" :key="group" class="group-chip">{{ group }}</span>
        </div>
      </div>

      <!-- Raw data collapsible -->
      <details class="raw-dump">
        <summary class="raw-dump__summary">Raw data</summary>
        <pre>{{ JSON.stringify(user, null, 2) }}</pre>
      </details>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { GetUserUseCase } from '@/ActiveDirectory/Application/UseCase/GetUser/GetUserUseCase'

const route = useRoute()
const router = useRouter()
const user = ref(null)
const loading = ref(true)

onMounted(async () => {
  user.value = await GetUserUseCase(route.params.samaccountname)
  loading.value = false
})

// Compute enabled from useraccountcontrol (bit 1 = disabled)
const enabled = computed(() => {
  const uac = parseInt(user.value?.useraccountcontrol ?? '0')
  return !(uac & 0x0002)
})

// Extract org code from DN: the OU right before "My Organization"
const orgCode = computed(() => {
  const dn = user.value?.dn || user.value?.distinguishedname || ''
  const ous = dn.split(',')
    .filter(p => p.trim().startsWith('OU='))
    .map(p => p.trim().slice(3))
  const idx = ous.findIndex(o => o === 'My Organization')
  return idx > 0 ? ous[idx - 1] : (ous[0] || null)
})

// Extract CN name from each group DN
const groups = computed(() => {
  if (!user.value?.memberof) return []
  const list = Array.isArray(user.value.memberof)
    ? user.value.memberof
    : [user.value.memberof]
  return list.map(dn => {
    const match = dn.match(/^CN=([^,]+)/)
    return match ? match[1] : dn
  })
})

// LDAP generalized time: YYYYMMDDHHmmss[.0]Z
function formatLdapDate(ldap) {
  if (!ldap) return '—'
  const digits = ldap.replace(/\.\d+Z?$/, '').replace('Z', '')
  if (!/^\d{14}$/.test(digits)) return '—'
  return `${digits.slice(0, 4)}-${digits.slice(4, 6)}-${digits.slice(6, 8)}`
}

// Windows FILETIME: 100ns intervals since 1601-01-01
function formatWinFiletime(ft) {
  if (!ft || ft === '0' || ft === '9223372036854775807') return '—'
  try {
    const ms = (BigInt(ft) - BigInt('116444736000000000')) / BigInt(10000)
    const date = new Date(Number(ms))
    if (isNaN(date.getTime()) || date.getFullYear() < 1970) return '—'
    return date.toISOString().slice(0, 10)
  } catch {
    return '—'
  }
}
</script>

<style scoped>
.section-title {
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--tokyo-fg-dim);
  margin-bottom: 0.6rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 0.6rem;
}

.info-item {
  background: var(--tokyo-bg-secondary);
  border: 1px solid var(--tokyo-bg-tertiary);
  border-radius: var(--cmv-radius-lg);
  padding: 0.65rem 0.9rem;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.info-item--wide {
  grid-column: 1 / -1;
}

.info-item__label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--tokyo-fg-dim);
}

.info-item__value {
  font-size: 0.88rem;
  color: var(--tokyo-fg);
}

.org-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--tokyo-blue);
  background: var(--tokyo-bg-tertiary);
  border: 1px solid var(--tokyo-bg-tertiary);
  border-radius: 999px;
  padding: 0.2rem 0.7rem;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
}

.org-chip:hover {
  border-color: var(--tokyo-blue);
}

.groups-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.group-chip {
  font-size: 0.78rem;
  color: var(--tokyo-fg-secondary);
  background: var(--tokyo-bg-secondary);
  border: 1px solid var(--tokyo-bg-tertiary);
  border-radius: 999px;
  padding: 0.15rem 0.65rem;
}

.code-inline {
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
  font-size: 0.82rem;
  color: var(--tokyo-fg);
  background: var(--tokyo-bg-tertiary);
  padding: 1px 6px;
  border-radius: 3px;
  word-break: break-all;
}

.raw-dump {
  margin-top: 2rem;
  border: 1px solid var(--tokyo-bg-tertiary);
  border-radius: var(--cmv-radius-lg);
  overflow: hidden;
}

.raw-dump__summary {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--tokyo-fg-dim);
  padding: 0.6rem 1rem;
  background: var(--tokyo-bg-secondary);
  cursor: pointer;
  user-select: none;
  list-style: none;
}

.raw-dump__summary:hover {
  color: var(--tokyo-fg);
}

.raw-dump pre {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.75rem;
  color: var(--tokyo-fg);
  background: var(--tokyo-bg-secondary);
  white-space: pre-wrap;
  word-break: break-all;
  line-height: 1.6;
  padding: 1rem;
  border-top: 1px solid var(--tokyo-bg-tertiary);
}
</style>
