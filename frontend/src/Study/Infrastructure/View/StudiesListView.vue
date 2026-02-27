<template>
  <div>
    <div class="page__header">
      <h1 class="page__title">Studies</h1>
      <span class="page__subtitle">{{ studies.length }} entradas</span>
    </div>

    <div class="flex flex-col gap-3 mb-5">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText v-model="search" placeholder="Buscar..." class="filters__search" />
      </IconField>

      <div class="filters__cats">
        <button
          v-for="cat in categories"
          :key="cat.slug"
          class="cat-chip"
          :class="{ active: activeCat === cat.slug }"
          @click="activeCat = activeCat === cat.slug ? null : cat.slug"
        >
          {{ cat.name }}
        </button>
        <button v-if="activeCat" class="cat-chip cat-chip--clear" @click="activeCat = null">
          <i class="pi pi-times" /> Limpiar
        </button>
      </div>
    </div>

    <DataTable
      :value="filtered"
      :loading="loading"
      stripedRows
      paginator
      :rows="20"
      :rowsPerPageOptions="[10, 20, 50]"
      dataKey="uuid"
      class="studies-table"
      @row-click="goToStudy"
    >
      <template #empty>No hay estudios que coincidan.</template>

      <Column field="is_favorite" header="" style="width: 2.5rem; text-align: center">
        <template #body="{ data }">
          <i
            class="pi"
            :class="data.is_favorite ? 'pi-star-fill' : 'pi-star'"
            :style="{ color: data.is_favorite ? '#f59e0b' : '#cbd5e1', cursor: 'pointer' }"
            @click.stop="toggleFav(data)"
          />
        </template>
      </Column>

      <Column field="title" header="Título" sortable style="min-width: 280px">
        <template #body="{ data }">
          <span class="study-title">{{ data.title }}</span>
          <p v-if="data.summary" class="study-summary">{{ data.summary }}</p>
        </template>
      </Column>

      <Column field="category" header="Categoría" sortable style="width: 160px">
        <template #body="{ data }">
          <Tag :value="data.category" severity="secondary" />
        </template>
      </Column>

      <Column field="tags" header="Tags" style="min-width: 200px">
        <template #body="{ data }">
          <div class="tag-list">
            <Tag
              v-for="tag in data.tags.slice(0, 4)"
              :key="tag"
              :value="tag"
              severity="contrast"
              style="font-size: 0.72rem; margin: 2px"
            />
            <span v-if="data.tags.length > 4" class="tag-more">+{{ data.tags.length - 4 }}</span>
          </div>
        </template>
      </Column>

      <Column field="status" header="" style="width: 90px">
        <template #body="{ data }">
          <Tag
            :value="data.status"
            :severity="data.status === 'published' ? 'success' : 'warn'"
            style="font-size: 0.72rem"
          />
        </template>
      </Column>

      <Column field="created_at" header="Fecha" sortable style="width: 110px">
        <template #body="{ data }">
          <span class="text-sm text-muted">{{ formatDate(data.created_at) }}</span>
        </template>
      </Column>

      <Column header="" style="width: 2.5rem; text-align: center">
        <template #body="{ data }">
          <i
            class="pi"
            :class="copied === data.uuid ? 'pi-check' : 'pi-copy'"
            :style="{ cursor: 'pointer', color: copied === data.uuid ? '#22c55e' : '#94a3b8', fontSize: '0.9rem' }"
            @click.stop="copyCtx(data.uuid)"
          />
        </template>
      </Column>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import { ListStudiesUseCase } from '@/Study/Application/UseCase/ListStudies/ListStudiesUseCase'
import { ListCategoriesUseCase } from '@/Study/Application/UseCase/ListCategories/ListCategoriesUseCase'
import { ToggleFavoriteUseCase } from '@/Study/Application/UseCase/ToggleFavorite/ToggleFavoriteUseCase'

const router = useRouter()
const studies    = ref([])
const categories = ref([])
const loading    = ref(true)
const search     = ref('')
const activeCat  = ref(null)
const copied     = ref(null)

const filtered = computed(() => {
  let list = studies.value

  if (activeCat.value) {
    list = list.filter(s => s.category === activeCat.value)
  }

  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(s =>
      s.title.toLowerCase().includes(q) ||
      s.summary?.toLowerCase().includes(q) ||
      s.tags.some(t => t.includes(q))
    )
  }

  return list
})

onMounted(async () => {
  const [studiesList, categoriesList] = await Promise.all([
    ListStudiesUseCase(),
    ListCategoriesUseCase(),
  ])
  studies.value    = studiesList
  categories.value = categoriesList
  loading.value    = false
})

function goToStudy({ data }) {
  router.push(`/studies/${data.uuid}`)
}

async function toggleFav(study) {
  await ToggleFavoriteUseCase(study.uuid)
  study.is_favorite = !study.is_favorite
}

function formatDate(dt) {
  return dt?.slice(0, 10) ?? ''
}

async function copyCtx(uuid) {
  await navigator.clipboard.writeText(`http://localhost:8280/api/studies/${uuid}/ctx`)
  copied.value = uuid
  setTimeout(() => { copied.value = null }, 1500)
}
</script>

<style scoped>
.filters__search { width: 320px; }

.filters__cats {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.cat-chip {
  padding: 4px 12px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-size: 0.82rem;
  cursor: pointer;
  color: #475569;
  transition: all 0.15s;
}
.cat-chip:hover, .cat-chip.active {
  background: #6366f1;
  color: #fff;
  border-color: #6366f1;
}
.cat-chip--clear { border-color: #fca5a5; color: #ef4444; }
.cat-chip--clear:hover { background: #ef4444; color: #fff; border-color: #ef4444; }

.studies-table { cursor: pointer; }

.study-title { font-weight: 500; display: block; }
.study-summary {
  font-size: 0.8rem;
  color: #94a3b8;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 360px;
}

.tag-list { display: flex; flex-wrap: wrap; }
.tag-more  { font-size: 0.72rem; color: #94a3b8; align-self: center; margin-left: 2px; }
</style>
