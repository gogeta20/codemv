<template>
  <div class="page--form">
    <div class="flex items-center gap-4 mb-8">
      <Button icon="pi pi-arrow-left" label="Volver" text size="small" @click="router.push(`/studies/${route.params.uuid}`)" />
      <h2>Editar Study</h2>
    </div>

    <div v-if="loading" style="text-align: center; padding: 3rem 0;">Cargando...</div>

    <div v-else>
      <!-- Category -->
      <div class="form-field">
        <label class="form-label">Categoría</label>
        <Select
          v-model="selectedCategory"
          :options="categoryOptions"
          option-label="name"
          option-value="slug"
          placeholder="Selecciona una categoría"
          class="w-full"
          @change="onCategoryChange"
        />
        <!-- Inline mini-form for new category -->
        <div v-if="showNewCategory" class="panel mt-4">
          <p class="font-semibold mb-3">Nueva categoría</p>
          <div class="form-field">
            <label class="form-label--sm">Nombre</label>
            <InputText
              v-model="newCategory.name"
              placeholder="Ej: DevOps"
              class="w-full"
              @input="autoSlug"
            />
          </div>
          <div class="form-field">
            <label class="form-label--sm">Slug</label>
            <InputText
              v-model="newCategory.slug"
              placeholder="Ej: devops"
              class="w-full"
            />
          </div>
          <div class="form-actions">
            <Button
              label="Crear"
              size="small"
              :loading="creatingCategory"
              :disabled="!newCategory.name || !newCategory.slug"
              @click="createCategory"
            />
            <Button label="Cancelar" size="small" text @click="cancelNewCategory" />
          </div>
          <p v-if="categoryError" class="form-error">{{ categoryError }}</p>
        </div>
      </div>

      <!-- Tags -->
      <div class="form-field">
        <label class="form-label">Tags</label>
        <MultiSelect
          v-model="selectedTags"
          :options="tags"
          option-label="name"
          option-value="slug"
          placeholder="Selecciona tags"
          display="chip"
          filter
          class="w-full"
        />
      </div>

      <!-- Actions -->
      <div class="form-actions">
        <Button
          label="Guardar"
          icon="pi pi-check"
          :loading="saving"
          :disabled="!selectedCategory"
          @click="save"
        />
        <Button label="Cancelar" text @click="router.push(`/studies/${route.params.uuid}`)" />
      </div>

      <p v-if="saveError" class="form-error mt-4">{{ saveError }}</p>
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
import { GetStudyUseCase } from '@/Study/Application/UseCase/GetStudy/GetStudyUseCase'
import { ListCategoriesUseCase } from '@/Study/Application/UseCase/ListCategories/ListCategoriesUseCase'
import { ListTagsUseCase } from '@/Study/Application/UseCase/ListTags/ListTagsUseCase'
import { CreateCategoryUseCase } from '@/Study/Application/UseCase/CreateCategory/CreateCategoryUseCase'
import { UpdateStudyUseCase } from '@/Study/Application/UseCase/UpdateStudy/UpdateStudyUseCase'

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
  const [study, categoriesList, tagsList] = await Promise.all([
    GetStudyUseCase(route.params.uuid),
    ListCategoriesUseCase(),
    ListTagsUseCase(),
  ])

  categories.value = categoriesList
  tags.value = tagsList

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
    const created = await CreateCategoryUseCase(newCategory.value.slug, newCategory.value.name)
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
    await UpdateStudyUseCase(route.params.uuid, selectedCategory.value, selectedTags.value)
    router.push(`/studies/${route.params.uuid}`)
  } catch (err) {
    saveError.value = err.response?.data?.error ?? 'Error al guardar los cambios'
  } finally {
    saving.value = false
  }
}
</script>
