<template>
  <div style="padding: 2rem; max-width: 700px; margin: 0 auto;">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
      <Button icon="pi pi-arrow-left" label="Volver" text size="small" @click="router.push(`/studies/${route.params.uuid}`)" />
      <h2 style="margin: 0;">Editar Study</h2>
    </div>

    <div v-if="loading" style="text-align: center; padding: 3rem 0;">Cargando...</div>

    <div v-else>
      <!-- Category -->
      <div style="margin-bottom: 1.5rem;">
        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Categoría</label>
        <Select
          v-model="selectedCategory"
          :options="categoryOptions"
          option-label="name"
          option-value="slug"
          placeholder="Selecciona una categoría"
          style="width: 100%;"
          @change="onCategoryChange"
        />
        <!-- Inline mini-form for new category -->
        <div v-if="showNewCategory" style="margin-top: 1rem; padding: 1rem; border: 1px solid #ddd; border-radius: 6px; background: #fafafa;">
          <p style="margin: 0 0 0.75rem; font-size: 0.9rem; font-weight: 600;">Nueva categoría</p>
          <div style="margin-bottom: 0.75rem;">
            <label style="display: block; font-size: 0.85rem; margin-bottom: 0.25rem;">Nombre</label>
            <InputText
              v-model="newCategory.name"
              placeholder="Ej: DevOps"
              style="width: 100%;"
              @input="autoSlug"
            />
          </div>
          <div style="margin-bottom: 0.75rem;">
            <label style="display: block; font-size: 0.85rem; margin-bottom: 0.25rem;">Slug</label>
            <InputText
              v-model="newCategory.slug"
              placeholder="Ej: devops"
              style="width: 100%;"
            />
          </div>
          <div style="display: flex; gap: 0.5rem;">
            <Button
              label="Crear"
              size="small"
              :loading="creatingCategory"
              :disabled="!newCategory.name || !newCategory.slug"
              @click="createCategory"
            />
            <Button
              label="Cancelar"
              size="small"
              text
              @click="cancelNewCategory"
            />
          </div>
          <p v-if="categoryError" style="color: #e24c4c; font-size: 0.85rem; margin: 0.5rem 0 0;">{{ categoryError }}</p>
        </div>
      </div>

      <!-- Tags -->
      <div style="margin-bottom: 2rem;">
        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Tags</label>
        <MultiSelect
          v-model="selectedTags"
          :options="tags"
          option-label="name"
          option-value="slug"
          placeholder="Selecciona tags"
          display="chip"
          filter
          style="width: 100%;"
        />
      </div>

      <!-- Actions -->
      <div style="display: flex; gap: 0.75rem;">
        <Button
          label="Guardar"
          icon="pi pi-check"
          :loading="saving"
          :disabled="!selectedCategory"
          @click="save"
        />
        <Button
          label="Cancelar"
          text
          @click="router.push(`/studies/${route.params.uuid}`)"
        />
      </div>

      <p v-if="saveError" style="color: #e24c4c; font-size: 0.85rem; margin-top: 1rem;">{{ saveError }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import InputText from 'primevue/inputtext'
import api from '@/core/http/api'

const NEW_CATEGORY_OPTION = { slug: '__new__', name: '+ Nueva categoría...' }

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const saving = ref(false)
const saveError = ref(null)

const categories = ref([])
const categoryOptions = ref([])
const tags = ref([])

const selectedCategory = ref(null)
const selectedTags = ref([])

const showNewCategory = ref(false)
const creatingCategory = ref(false)
const categoryError = ref(null)
const newCategory = ref({ name: '', slug: '' })

onMounted(async () => {
  const [studyRes, catsRes, tagsRes] = await Promise.all([
    api.get(`/api/studies/${route.params.uuid}`),
    api.get('/api/categories'),
    api.get('/api/tags'),
  ])

  const study = studyRes.data.data
  categories.value = catsRes.data.data
  tags.value = tagsRes.data.data

  buildCategoryOptions()

  selectedCategory.value = study.category?.slug ?? null
  selectedTags.value = (study.tags ?? []).map(t => t.slug)

  loading.value = false
})

function buildCategoryOptions() {
  categoryOptions.value = [...categories.value, NEW_CATEGORY_OPTION]
}

function onCategoryChange() {
  if (selectedCategory.value === '__new__') {
    showNewCategory.value = true
    selectedCategory.value = null
  }
}

function autoSlug() {
  newCategory.value.slug = newCategory.value.name
    .toLowerCase()
    .trim()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9-]/g, '')
}

async function createCategory() {
  categoryError.value = null
  creatingCategory.value = true
  try {
    const { data } = await api.post('/api/categories', {
      slug: newCategory.value.slug,
      name: newCategory.value.name,
    })
    const created = data.data
    categories.value.push(created)
    buildCategoryOptions()
    selectedCategory.value = created.slug
    showNewCategory.value = false
    newCategory.value = { name: '', slug: '' }
  } catch (err) {
    categoryError.value = err.response?.data?.error ?? 'Error al crear la categoría'
  } finally {
    creatingCategory.value = false
  }
}

function cancelNewCategory() {
  showNewCategory.value = false
  categoryError.value = null
  newCategory.value = { name: '', slug: '' }
}

async function save() {
  saveError.value = null
  saving.value = true
  try {
    await api.put(`/api/studies/${route.params.uuid}`, {
      category: selectedCategory.value,
      tags: selectedTags.value,
    })
    router.push(`/studies/${route.params.uuid}`)
  } catch (err) {
    saveError.value = err.response?.data?.error ?? 'Error al guardar los cambios'
  } finally {
    saving.value = false
  }
}
</script>
