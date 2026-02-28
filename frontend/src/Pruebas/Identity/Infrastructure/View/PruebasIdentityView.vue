<template>
  <div>
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

    <h2 class="section-title">Paso 2: Crear 2 Usuarios en Org A</h2>
    <CreateUsersFlowTest
      :orgCode="orgsData?.orgA?.code"
      :autoRun="!!orgsData"
      @completed="onUsersCreated"
    />

    <div v-if="usersData" class="context-info">
      <span>User 1: <code>{{ usersData.user1.email }}</code> ({{ usersData.user1.uuid }})</span>
      <span>User 2: <code>{{ usersData.user2.email }}</code> ({{ usersData.user2.uuid }})</span>
    </div>

    <h2 class="section-title">Paso 3: Mover User 1 de Org A → Org B</h2>
    <MoveUserFlowTest
      :userUuid="usersData?.user1?.uuid"
      :targetOrgUuid="orgsData?.orgB?.uuid"
      :autoRun="!!usersData"
      @completed="onUserMoved"
    />

    <h2 class="section-title">Paso 4: Eliminar Org A</h2>
    <DeleteOrgFlowTest
      :orgUuid="orgsData?.orgA?.uuid"
      :autoRun="userMoved"
      @completed="onOrgDeleted"
    />

    <h2 class="section-title">Paso 5: Verificar Estado Final en AD</h2>
    <VerifyAdFlowTest
      :orgACode="orgsData?.orgA?.code"
      :orgBCode="orgsData?.orgB?.code"
      :autoRun="orgDeleted"
    />

    <h2 class="section-title">Herramientas sueltas</h2>
    <CreateOrganizationTest />
    <DeleteOrganizationTest />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import TokenInput from './components/TokenInput.vue'
import OrganizationFlowTest from './components/OrganizationFlowTest.vue'
import CreateUsersFlowTest from './components/CreateUsersFlowTest.vue'
import MoveUserFlowTest from './components/MoveUserFlowTest.vue'
import DeleteOrgFlowTest from './components/DeleteOrgFlowTest.vue'
import VerifyAdFlowTest from './components/VerifyAdFlowTest.vue'
import CreateOrganizationTest from './components/CreateOrganizationTest.vue'
import DeleteOrganizationTest from './components/DeleteOrganizationTest.vue'

const orgsData = ref(null)
const usersData = ref(null)
const userMoved = ref(false)
const orgDeleted = ref(false)

function onOrgsCreated(data) {
  orgsData.value = data
}

function onUsersCreated(data) {
  usersData.value = data
}

function onUserMoved() {
  userMoved.value = true
}

function onOrgDeleted() {
  orgDeleted.value = true
}
</script>

<style scoped>
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
