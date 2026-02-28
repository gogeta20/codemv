<template>
  <div class="token-panel">
    <div class="token-panel__row">
      <div class="token-panel__field">
        <label>Identity URL</label>
        <InputText v-model="baseUrl" placeholder="https://localhost:443" class="token-panel__input" />
      </div>
      <div class="token-panel__field token-panel__field--wide">
        <label>Bearer Token</label>
        <InputText
          v-model="token"
          type="password"
          placeholder="Pega aquí el access_token..."
          class="token-panel__input"
        />
      </div>
      <div class="token-panel__actions">
        <Button label="Guardar" icon="pi pi-save" severity="secondary" size="small" @click="save" />
        <Tag :value="saved ? '✅ Guardado' : '⚠ Sin guardar'" :severity="saved ? 'success' : 'warn'" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { IdentityHttpClient } from '@/Pruebas/Identity/Domain/IdentityHttpClient'

const baseUrl = ref('')
const token = ref('')
const saved = ref(false)

onMounted(() => {
  baseUrl.value = IdentityHttpClient.getBaseUrl()
  token.value = IdentityHttpClient.getToken()
  saved.value = !!token.value
})

function save() {
  IdentityHttpClient.setBaseUrl(baseUrl.value)
  IdentityHttpClient.setToken(token.value)
  saved.value = true
}
</script>

<style scoped>
.token-panel {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.token-panel__row {
  display: flex;
  gap: 1rem;
  align-items: flex-end;
}

.token-panel__field {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.token-panel__field label {
  font-size: 0.78rem;
  font-weight: 500;
  color: #64748b;
}

.token-panel__field--wide {
  flex: 1;
}

.token-panel__input {
  font-size: 0.85rem;
}

.token-panel__actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}
</style>
