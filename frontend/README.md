# codemv — Frontend

Vue 3 + PrimeVue 4 + Vite + pnpm.

## Stack

| Herramienta | Versión | Uso |
|---|---|---|
| Vue 3 | ^3.4 | Framework reactivo |
| PrimeVue 4 (Aura) | ^4.0 | Componentes UI |
| Vue Router 4 | ^4.3 | Navegación |
| Axios | ^1.6 | Cliente HTTP |
| Vite | ^5.1 | Build tool |
| pnpm | ^10 | Package manager (obligatorio) |

## Arrancar en local

```bash
pnpm install
pnpm dev          # http://localhost:5173
```

Activar mocks (sin backend):
```bash
VITE_USE_MOCKS=true pnpm dev
```

---

## Estructura del proyecto

```
src/
├── core/                          # Utilidades transversales
│   ├── http/
│   │   └── HttpClient.js          # Instancia axios global
│   ├── utilities/
│   │   └── UtilHelper.js          # checkEnvironment(), wait(), generateUUID()
│   ├── composables/
│   │   └── useMarkdown.js         # Renderizado markdown-it + highlight.js
│   └── styles/
│       ├── base.css               # Reset, tokens, utilidades CSS
│       └── markdown.css           # Estilos .md-body
│
├── <Vertical>/                    # Una carpeta por contexto DDD (ej: Study, ActiveDirectory)
│   ├── Application/
│   │   └── UseCase/
│   │       └── <Action>/
│   │           ├── <Action>UseCase.js
│   │           └── mock.json
│   └── Infrastructure/
│       ├── View/
│       │   └── <Page>View.vue
│       └── Router/
│           └── index.js
│
└── router/
    └── index.js                   # Router principal — importa sub-routers de cada vertical
```

---

## Cómo crear una nueva vertical

### 1. Crear el sub-router

```js
// src/<Vertical>/Infrastructure/Router/index.js
export default [
  { path: '/<vertical>', component: () => import('../View/<Vertical>ListView.vue') },
  { path: '/<vertical>/:uuid', component: () => import('../View/<Vertical>DetailView.vue') },
]
```

### 2. Registrarlo en el router principal

```js
// src/router/index.js
import verticalRoutes from '@/<Vertical>/Infrastructure/Router'

const routes = [
  { path: '/', redirect: '/<vertical>' },
  ...studyRoutes,
  ...verticalRoutes,   // ← añadir aquí
]
```

### 3. Crear los use cases

Cada llamada HTTP es un UseCase independiente. Estructura obligatoria:

```js
// src/<Vertical>/Application/UseCase/<Action>/<Action>UseCase.js
import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import Mock from './mock.json'          // solo si la acción retorna datos

async function InMemory(/* params */) {
  await UtilHelper.wait(300)
  return Mock                           // datos desenvueltos, sin wrapper
}

async function Api(/* params */) {
  const response = await httpClient.get('/api/<endpoint>')
  return response.data.data             // desenvolver aquí, no en la vista
}

async function <Action>UseCase(/* params */) {
  return UtilHelper.checkEnvironment() ? await InMemory(/* params */) : await Api(/* params */)
}

export { <Action>UseCase }
```

**Reglas:**
- `InMemory` retorna datos del `mock.json` con un delay simulado
- `Api` retorna los datos ya desenvueltos (`response.data.data` para colecciones, etc.)
- La vista recibe siempre el dato limpio, nunca el envelope de axios
- Para acciones sin respuesta de datos (toggle, delete): retornar `{ status: response.status }`

### 4. Consumir en la vista

```vue
<script setup>
import { ListThingsUseCase } from '@/<Vertical>/Application/UseCase/ListThings/ListThingsUseCase'

const items = ref([])
onMounted(async () => {
  items.value = await ListThingsUseCase()
})
</script>
```

---

## Use cases existentes en Study/

| UseCase | Método | Endpoint |
|---|---|---|
| `ListStudiesUseCase` | GET | `/api/studies` |
| `GetStudyUseCase(uuid)` | GET | `/api/studies/:uuid` |
| `ToggleFavoriteUseCase(uuid)` | POST | `/api/studies/:uuid/favorite` |
| `UpdateStudyUseCase(uuid, cat, tags)` | PUT | `/api/studies/:uuid` |
| `ListCategoriesUseCase` | GET | `/api/categories` |
| `CreateCategoryUseCase(slug, name)` | POST | `/api/categories` |
| `ListTagsUseCase` | GET | `/api/tags` |

---

## CSS — normas

### Antes de escribir CSS propio, usar `base.css`

```
core/styles/base.css    ← importado globalmente en main.js
core/styles/markdown.css ← estilos .md-body, importado globalmente
```

### Clases disponibles en base.css

**Layout**
```
.page--detail      max-width 900px, centrado
.page--form        max-width 700px, centrado
.page__header      flex + gap para cabecera de página
.page__title       h1 de página
.page__subtitle    texto secundario junto al título
.page__actions     flex de botones de acción
.panel             caja con borde, fondo blanco, border-radius
```

**Flex**
```
.flex  .flex-col  .flex-wrap
.items-center  .items-start  .items-baseline
.justify-between  .justify-end
.flex-1  .ml-auto
```

**Gap**
```
.gap-1 (0.25rem)  .gap-2 (0.5rem)  .gap-3 (0.75rem)
.gap-4 (1rem)     .gap-6 (1.5rem)  .gap-8 (2rem)
```

**Spacing**
```
.mb-2  .mb-3  .mb-4  .mb-5  .mb-6  .mb-8
.mt-2  .mt-4
```

**Form**
```
.form-field      flex-col + gap para label + input
.form-label      label principal (bold)
.form-label--sm  label secundario (más pequeño)
.form-actions    flex de botones de formulario
.form-error      texto de error rojo
```

**Tipografía**
```
.text-sm  .text-xs
.text-muted  .text-secondary  .text-error  .text-success
.font-semibold  .font-bold
.truncate
```

**Otros**
```
.w-full
```

### Cuándo usar CSS scoped

Solo para estilos específicos del componente que no tienen equivalente en `base.css`:
- Colores, tamaños o comportamientos propios del componente (ej: `.cat-chip`, `.study-summary`)
- Variaciones de componentes de PrimeVue dentro de ese contexto

**Nunca** escribir inline styles en templates.

---

## Variables de entorno

| Variable | Default | Descripción |
|---|---|---|
| `VITE_API_BASE_URL` | `http://localhost:8280` | URL base del backend |
| `VITE_USE_MOCKS` | `false` | `true` para usar InMemory en lugar de API real |
