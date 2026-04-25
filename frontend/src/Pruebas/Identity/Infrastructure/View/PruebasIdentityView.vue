<template>
  <div class="pruebas-layout">
    <!-- Left: wizard -->
    <div class="pruebas-layout__wizard">
      <div class="page__header">
        <h1 class="page__title">🧪 Pruebas — Identity</h1>
        <span class="page__subtitle">Flujo completo: Crear Orgs → Crear Users → Mover User → Eliminar Org → Verificar AD</span>
      </div>

      <TokenInput />

      <h2 class="section-title">Paso 1: Crear 2 Organizaciones</h2>
      <OrganizationFlowTest @completed="onOrgsCreated" />

      <div v-if="orgsData" class="context-info">
        <span>Org A: <code>{{ orgsData.orgA.code }}</code> ({{ orgsData.orgA.uuid }})</span>
        <span>Org B: <code>{{ orgsData.orgB.code }}</code> ({{ orgsData.orgB.uuid }})</span>
      </div>

      <h2 class="section-title">Paso 2: Verificar Organizaciones en Identity</h2>
      <VerifyOrgsFlowTest
        :orgAUuid="orgsData?.orgA?.uuid"
        :orgBUuid="orgsData?.orgB?.uuid"
        :autoRun="!!orgsData"
        @completed="onOrgsVerified"
      />

      <h2 class="section-title">Paso 3: Crear 3 Usuarios en Org A</h2>
      <CreateUsersFlowTest
        :orgCode="orgsData?.orgA?.code"
        :autoRun="!!orgsData"
        @completed="onUsersCreated"
      />

      <div v-if="usersData" class="context-info">
        <span>User 1: <code>{{ usersData.user1.email }}</code> ({{ usersData.user1.uuid }})</span>
        <span>User 2: <code>{{ usersData.user2.email }}</code> ({{ usersData.user2.uuid }})</span>
        <span>User 3: <code>{{ usersData.user3.email }}</code> ({{ usersData.user3.uuid }})</span>
      </div>

      <h2 class="section-title">Paso 4: Deshabilitar / Rehabilitar User 3</h2>
      <ChangeUserStatusFlowTest
        :userUuid="usersData?.user3?.uuid"
        :autoRun="!!usersData"
        @completed="onUserStatusChanged"
      />

      <h2 class="section-title">Paso 5: Cambiar Password User 3</h2>
      <SetPasswordFlowTest
        :userUuid="usersData?.user3?.uuid"
        :autoRun="!!usersData"
        @completed="onPasswordChanged"
      />

      <h2 class="section-title">Paso 6: Eliminar User 2</h2>
      <DeleteUserFlowTest
        :userUuid="usersData?.user2?.uuid"
        :autoRun="!!usersData"
        @completed="onUser2Deleted"
      />

      <h2 class="section-title">Paso 7: Mover User 1 de Org A → Org B</h2>
      <MoveUserFlowTest
        :userUuid="usersData?.user1?.uuid"
        :targetOrgUuid="orgsData?.orgB?.uuid"
        :autoRun="!!usersData"
        @completed="onUserMoved"
      />

      <h2 class="section-title">Paso 8: Eliminar Org A</h2>
      <DeleteOrgFlowTest
        :orgUuid="orgsData?.orgA?.uuid"
        :autoRun="userMoved"
        @completed="onOrgDeleted"
      />

      <h2 class="section-title">Paso 9: Verificar Estado Final en AD</h2>
      <VerifyAdFlowTest
        :orgACode="orgsData?.orgA?.code"
        :orgBCode="orgsData?.orgB?.code"
        :autoRun="orgDeleted"
        @completed="onAdVerified"
      />

      <h2 class="section-title">Paso 10: Password Recovery Flow</h2>
      <PasswordRecoveryFlowTest />

      <TestReportButton :report="reportData" />
    </div>

    <!-- Right: outbox panel -->
    <div class="pruebas-layout__outbox">
      <OutboxPanel />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import TokenInput from './components/TokenInput.vue'
import OrganizationFlowTest from './components/OrganizationFlowTest.vue'
import CreateUsersFlowTest from './components/CreateUsersFlowTest.vue'
import VerifyOrgsFlowTest from './components/VerifyOrgsFlowTest.vue'
import ChangeUserStatusFlowTest from './components/ChangeUserStatusFlowTest.vue'
import DeleteUserFlowTest from './components/DeleteUserFlowTest.vue'
import SetPasswordFlowTest from './components/SetPasswordFlowTest.vue'
import MoveUserFlowTest from './components/MoveUserFlowTest.vue'
import DeleteOrgFlowTest from './components/DeleteOrgFlowTest.vue'
import VerifyAdFlowTest from './components/VerifyAdFlowTest.vue'
import OutboxPanel from './components/OutboxPanel.vue'
import TestReportButton from '@/Pruebas/Infrastructure/View/components/TestReportButton.vue'
import PasswordRecoveryFlowTest from './components/PasswordRecoveryFlowTest.vue'

const orgsData = ref(null)
const usersData = ref(null)
const userMoved = ref(false)
const orgDeleted = ref(false)
const reportSteps = ref([])

function onOrgsCreated(data) {
  orgsData.value = data
  reportSteps.value.push({
    name: 'Crear Org A',
    status: data.results.orgA.success ? 'ok' : 'fail',
    duration: data.results.orgA.duration,
    data: data.results.orgA.data ?? null,
    error: data.results.orgA.error ?? null,
    meta: { code: data.orgA.code, uuid: data.orgA.uuid },
  })
  reportSteps.value.push({
    name: 'Crear Org B',
    status: data.results.orgB.success ? 'ok' : 'fail',
    duration: data.results.orgB.duration,
    data: data.results.orgB.data ?? null,
    error: data.results.orgB.error ?? null,
    meta: { code: data.orgB.code, uuid: data.orgB.uuid },
  })
}

function onOrgsVerified(data) {
  reportSteps.value.push({
    name: 'Verificar Org A en Identity',
    status: data.results.orgA.success ? 'ok' : 'fail',
    duration: data.results.orgA.duration,
    data: data.results.orgA.data ?? null,
    error: data.results.orgA.error ?? null,
    meta: { uuid: orgsData.value?.orgA?.uuid, code: orgsData.value?.orgA?.code },
  })
  reportSteps.value.push({
    name: 'Verificar Org B en Identity',
    status: data.results.orgB.success ? 'ok' : 'fail',
    duration: data.results.orgB.duration,
    data: data.results.orgB.data ?? null,
    error: data.results.orgB.error ?? null,
    meta: { uuid: orgsData.value?.orgB?.uuid, code: orgsData.value?.orgB?.code },
  })
}

function onUsersCreated(data) {
  usersData.value = data
  reportSteps.value.push({
    name: 'Crear User 1 (→ mover a Org B)',
    status: data.results.user1.success ? 'ok' : 'fail',
    duration: data.results.user1.duration,
    data: data.results.user1.data ?? null,
    error: data.results.user1.error ?? null,
    meta: { email: data.user1.email, uuid: data.user1.uuid, orgCode: orgsData.value?.orgA?.code },
  })
  reportSteps.value.push({
    name: 'Crear User 2 (→ eliminar directo)',
    status: data.results.user2.success ? 'ok' : 'fail',
    duration: data.results.user2.duration,
    data: data.results.user2.data ?? null,
    error: data.results.user2.error ?? null,
    meta: { email: data.user2.email, uuid: data.user2.uuid, orgCode: orgsData.value?.orgA?.code },
  })
  reportSteps.value.push({
    name: 'Crear User 3 (→ queda en Org A)',
    status: data.results.user3.success ? 'ok' : 'fail',
    duration: data.results.user3.duration,
    data: data.results.user3.data ?? null,
    error: data.results.user3.error ?? null,
    meta: { email: data.user3.email, uuid: data.user3.uuid, orgCode: orgsData.value?.orgA?.code },
  })
}

function onPasswordChanged(result) {
  reportSteps.value.push({
    name: 'Cambiar Password User 3',
    status: result.success ? 'ok' : 'fail',
    duration: result.duration,
    data: null,
    error: result.error ?? null,
    meta: { uuid: usersData.value?.user3?.uuid },
  })
}

function onUser2Deleted(result) {
  reportSteps.value.push({
    name: 'Eliminar User 2',
    status: result.success ? 'ok' : 'fail',
    duration: result.duration,
    data: null,
    error: result.error ?? null,
    meta: { uuid: usersData.value?.user2?.uuid, email: usersData.value?.user2?.email },
  })
}

function onUserStatusChanged(data) {
  reportSteps.value.push({
    name: 'Deshabilitar User 3',
    status: data.results.disable?.success ? 'ok' : 'fail',
    duration: data.results.disable?.duration ?? null,
    data: null,
    error: data.results.disable?.error ?? null,
    meta: { uuid: usersData.value?.user3?.uuid, status: 'user.status.disabled' },
  })
  if (data.results.enable !== null) {
    reportSteps.value.push({
      name: 'Rehabilitar User 3',
      status: data.results.enable?.success ? 'ok' : 'fail',
      duration: data.results.enable?.duration ?? null,
      data: null,
      error: data.results.enable?.error ?? null,
      meta: { uuid: usersData.value?.user3?.uuid, status: 'user.status.enabled' },
    })
  }
}

function onUserMoved(result) {
  userMoved.value = result.success
  reportSteps.value.push({
    name: 'Mover User 1 → Org B',
    status: result.success ? 'ok' : 'fail',
    duration: result.duration,
    data: result.data ?? null,
    error: result.error ?? null,
    meta: {
      userUuid: usersData.value?.user1?.uuid,
      targetOrgUuid: orgsData.value?.orgB?.uuid,
    },
  })
}

function onOrgDeleted(result) {
  orgDeleted.value = result.success
  reportSteps.value.push({
    name: 'Eliminar Org A',
    status: result.success ? 'ok' : 'fail',
    duration: result.duration,
    data: result.data ?? null,
    error: result.error ?? null,
    meta: { uuid: orgsData.value?.orgA?.uuid, code: orgsData.value?.orgA?.code },
  })
}

function onAdVerified(data) {
  reportSteps.value.push({
    name: 'AD: Org A no existe',
    status: data.orgA?.success ? 'ok' : 'fail',
    duration: data.orgA?.duration ?? null,
    data: null,
    error: data.orgA?.success ? null : 'Org A aún presente en AD',
    meta: { code: orgsData.value?.orgA?.code, shouldExist: false },
  })
  reportSteps.value.push({
    name: 'AD: Org B existe',
    status: data.orgB?.success ? 'ok' : 'fail',
    duration: data.orgB?.duration ?? null,
    data: null,
    error: data.orgB?.success ? null : 'Org B no encontrada en AD',
    meta: { code: orgsData.value?.orgB?.code, shouldExist: true },
  })
  reportSteps.value.push({
    name: 'AD: Org B tiene usuarios',
    status: data.usersOrgB?.success ? 'ok' : 'fail',
    duration: data.usersOrgB?.duration ?? null,
    data: null,
    error: data.usersOrgB?.success ? null : 'No se encontraron usuarios en Org B',
    meta: { code: orgsData.value?.orgB?.code, shouldExist: true },
  })
}

const reportData = computed(() => ({
  title: 'Pruebas Identity',
  steps: reportSteps.value,
}))
</script>

<style scoped>
.pruebas-layout {
  display: grid;
  grid-template-columns: 1fr 520px;
  gap: 1.5rem;
  align-items: start;
}

.pruebas-layout__wizard {
  min-width: 0;
}

.pruebas-layout__outbox {
  position: sticky;
  top: 1rem;
  height: calc(100vh - 2rem);
  min-height: 500px;
}

.section-title {
  font-size: 1.1rem;
  color: #334155;
  margin: 1.5rem 0 0.75rem;
  padding-bottom: 0.4rem;
  border-bottom: 1px solid #e2e8f0;
}

.context-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.75rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 6px;
  margin-bottom: 1rem;
  font-size: 0.82rem;
}

.context-info code {
  font-weight: 600;
  color: #6366f1;
}
</style>
