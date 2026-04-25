<template>
  <div class="flow-test">
    <div class="flow-test__step">
      <h3>1. Request Recovery</h3>
      <div class="flow-test__controls">
        <input
          v-model="email"
          type="email"
          placeholder="Email (ej: linuxlite20@gmail.com)"
          class="flow-test__input"
        />
        <button @click="requestRecovery" :disabled="isRunning" class="flow-test__button">
          {{ isRunning ? 'Ejecutando...' : 'Generar Token' }}
        </button>
      </div>

      <div v-if="requestResult" class="flow-test__result" :class="{ 'flow-test__result--error': !requestResult.success }">
        <span v-if="requestResult.success">✅ Token generado ({{ requestResult.duration }}ms)</span>
        <span v-else>❌ Error: {{ requestResult.error }}</span>
        <div v-if="requestResult.data" class="token-display">
          <strong>Raw Token:</strong>
          <code>{{ requestResult.data.rawToken }}</code>
          <button @click="copyToken(requestResult.data.rawToken)" class="btn-copy">📋 Copiar</button>
        </div>
      </div>
    </div>

    <div class="flow-test__step" v-if="requestResult?.success">
      <h3>2. Reset Password</h3>
      <div class="flow-test__controls">
        <input
          v-model="newPassword"
          type="text"
          placeholder="Nueva password (ej: gbcv1234567MV@#)"
          class="flow-test__input"
        />
        <button @click="resetPassword" :disabled="isRunning || !requestResult?.data?.rawToken" class="flow-test__button">
          Resetear Password
        </button>
      </div>

      <div v-if="resetResult" class="flow-test__result" :class="{ 'flow-test__result--error': !resetResult.success }">
        <span v-if="resetResult.success">✅ Password reseteada ({{ resetResult.duration }}ms)</span>
        <span v-else>❌ Error: {{ resetResult.error }}</span>
      </div>
    </div>

    <div class="flow-test__step">
      <h3>3. Tokens Activos</h3>
      <button @click="loadTokens" :disabled="isRunning" class="flow-test__button">
        🔄 Refrescar Lista
      </button>

      <div v-if="tokensResult" class="tokens-list">
        <div v-if="tokensResult.data.count === 0" class="flow-test__info">
          No hay tokens activos
        </div>
        <table v-else class="tokens-table">
          <thead>
            <tr>
              <th>User UUID</th>
              <th>Creado</th>
              <th>Expira</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(token, index) in tokensResult.data.tokens" :key="index">
              <td><code>{{ token.userUuid.substring(0, 8) }}...</code></td>
              <td>{{ token.createdAt }}</td>
              <td>{{ token.timeLeft }}</td>
              <td>
                <span :class="token.status === 'active' ? 'status-active' : 'status-expired'">
                  {{ token.status === 'active' ? '✅ Activo' : '❌ Expirado' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RequestPasswordRecoveryUseCase } from '@/Pruebas/Identity/Application/UseCase/PasswordRecovery/RequestPasswordRecovery/RequestPasswordRecoveryUseCase'
import { ResetPasswordUseCase } from '@/Pruebas/Identity/Application/UseCase/PasswordRecovery/ResetPassword/ResetPasswordUseCase'
import { ListTokensUseCase } from '@/Pruebas/Identity/Application/UseCase/PasswordRecovery/ListTokens/ListTokensUseCase'

const email = ref('linuxlite20@gmail.com')
const newPassword = ref('gbcv1234567MV@#')
const isRunning = ref(false)

const requestResult = ref(null)
const resetResult = ref(null)
const tokensResult = ref(null)

async function requestRecovery() {
  isRunning.value = true
  requestResult.value = null
  resetResult.value = null

  requestResult.value = await RequestPasswordRecoveryUseCase(email.value)
  isRunning.value = false
}

async function resetPassword() {
  isRunning.value = true
  resetResult.value = null

  const token = requestResult.value.data.rawToken
  resetResult.value = await ResetPasswordUseCase(token, newPassword.value)

  isRunning.value = false

  // Refrescar lista de tokens
  await loadTokens()
}

async function loadTokens() {
  isRunning.value = true
  tokensResult.value = await ListTokensUseCase(false)
  isRunning.value = false
}

function copyToken(token) {
  navigator.clipboard.writeText(token)
  alert('Token copiado al portapapeles')
}

// Cargar tokens al montar
loadTokens()
</script>

<style scoped>
.flow-test {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.flow-test__step {
  padding: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.flow-test__step h3 {
  margin: 0 0 1rem;
  font-size: 1rem;
  color: #334155;
}

.flow-test__controls {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.flow-test__input {
  flex: 1;
  padding: 0.5rem 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.875rem;
}

.flow-test__button {
  padding: 0.5rem 1rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  cursor: pointer;
  white-space: nowrap;
}

.flow-test__button:hover:not(:disabled) {
  background: #2563eb;
}

.flow-test__button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.flow-test__result {
  padding: 0.75rem;
  background: #dcfce7;
  border: 1px solid #86efac;
  border-radius: 6px;
  font-size: 0.875rem;
}

.flow-test__result--error {
  background: #fee2e2;
  border-color: #fca5a5;
}

.token-display {
  margin-top: 0.75rem;
  padding: 0.75rem;
  background: white;
  border-radius: 4px;
}

.token-display code {
  display: block;
  margin: 0.5rem 0;
  padding: 0.5rem;
  background: #f1f5f9;
  border-radius: 4px;
  font-family: monospace;
  font-size: 0.75rem;
  word-break: break-all;
}

.btn-copy {
  padding: 0.25rem 0.5rem;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  font-size: 0.75rem;
  cursor: pointer;
}

.tokens-list {
  margin-top: 0.75rem;
}

.tokens-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}

.tokens-table th {
  text-align: left;
  padding: 0.5rem;
  background: #f1f5f9;
  border-bottom: 2px solid #cbd5e1;
}

.tokens-table td {
  padding: 0.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.status-active {
  color: #16a34a;
}

.status-expired {
  color: #dc2626;
}

.flow-test__info {
  padding: 0.75rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 6px;
  font-size: 0.875rem;
  color: #1e40af;
}
</style>
