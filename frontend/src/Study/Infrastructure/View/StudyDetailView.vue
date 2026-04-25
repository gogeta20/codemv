<template>
  <div class="page--detail">
    <ConfirmDialog />
    <div class="flex items-center justify-between mb-4">
      <Button icon="pi pi-arrow-left" label="Volver" text size="small" @click="$router.back()" />
      <div class="flex gap-2 items-center">
        <Button
          v-if="study"
          :icon="copied ? 'pi pi-check' : 'pi pi-copy'"
          size="small"
          text
          :style="{ color: copied ? '#22c55e' : undefined }"
          v-tooltip="'Copiar endpoint /ctx'"
          @click="copyCtx"
        />
        <Button
          v-if="study"
          label="Editar"
          icon="pi pi-pencil"
          size="small"
          @click="router.push(`/studies/${route.params.uuid}/edit`)"
        />
        <Button
          v-if="study"
          label="Eliminar"
          icon="pi pi-trash"
          size="small"
          severity="danger"
          @click="confirmDelete"
        />
      </div>
    </div>

    <div v-if="loading">Cargando...</div>
    <div v-else-if="study">
      <h1>{{ study.title }}</h1>
      <div class="text-sm text-muted mb-4" style="margin-top: 0.25rem;">
        {{ study.category?.name }} · {{ study.created_at }}
        <span v-if="study.is_favorite"> ⭐</span>
      </div>
      <div v-if="study.summary" class="study-summary">
        {{ study.summary }}
      </div>
      <div class="md-body" v-html="renderedContent" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { useMarkdown } from '@/core/composables/useMarkdown'
import { GetStudyUseCase } from '@/Study/Application/UseCase/GetStudy/GetStudyUseCase'
import { DeleteStudyUseCase } from '@/Study/Application/UseCase/DeleteStudy/DeleteStudyUseCase'

const route = useRoute()
const router = useRouter()
const confirm = useConfirm()
const { render } = useMarkdown()
const study = ref(null)
const loading = ref(true)
const copied = ref(false)

const renderedContent = computed(() => render(study.value?.content ?? ''))

onMounted(async () => {
  study.value = await GetStudyUseCase(route.params.uuid)
  loading.value = false
})

async function copyCtx() {
  await navigator.clipboard.writeText(`http://localhost:8280/api/studies/${route.params.uuid}/ctx`)
  copied.value = true
  setTimeout(() => { copied.value = false }, 1500)
}

function confirmDelete() {
  confirm.require({
    message: `¿Eliminar "${study.value.title}"? Esta acción no se puede deshacer.`,
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancelar',
    acceptLabel: 'Eliminar',
    acceptProps: { severity: 'danger' },
    accept: async () => {
      await DeleteStudyUseCase(route.params.uuid)
      router.push('/studies')
    },
  })
}
</script>

<style scoped>
.study-summary {
  background: #f8f8f8;
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1.5rem;
  color: #555;
  font-size: 0.95rem;
  line-height: 1.5;
}
</style>
