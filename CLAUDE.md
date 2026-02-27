# codemv - Knowledge Base v2

## Stack
Symfony 7.2 + PostgreSQL 16 + Vue 3 + PrimeVue. Docker Compose en raíz.

## API base
- http://localhost:8280

## Contexto esencial (leer antes de tocar código)
Cómo crear y consumir studies:
  GET http://localhost:8280/api/studies/fe6e6e36-58aa-4d92-ad4e-80b015f8e1f4/ctx

Diseño de la base de datos:
  GET http://localhost:8280/api/studies/73264950-f59f-4685-a4c7-0e02f18d787c/ctx

## Reglas por contexto

### Al crear o modificar un caso de uso en Application/
Leer las normas de backend antes de empezar:
  GET http://localhost:8280/api/studies/43083eb0-8cff-4e83-9f04-29626cc7db59/ctx

## Arquitectura actual
CQRS + DDD aplicado. Estructura completa en Application/ con UseCases, Commands, Queries y Handlers.

## Rutas clave
- Backend: backend/symfony/src/Study/
- Backend (nueva vertical): backend/symfony/src/ActiveDirectory/
- Frontend (verticals): frontend/src/Study/ | frontend/src/ActiveDirectory/
- Frontend (core): frontend/src/core/
- Devops: devops/dockerfiles/ | devops/mk/

## Normas generales
- Migraciones Doctrine siempre, nunca SQL directo en PHP
- No commitear sin aprobación
- Comentarios en inglés

## Normas backend — cómo crear un caso de uso (OBLIGATORIO seguir este patrón)

### Estructura de carpetas
Cada acción tiene su propia carpeta en `Application/`:
```
Application/
└── <Action>/
    ├── <Action>Command.php         (o Query)
    ├── <Action>UseCase.php
    └── <Action>CommandHandler.php  (o QueryHandler)
```

### Reglas estrictas
1. **1 controller = 1 acción = 1 Command/Query = 1 Handler**
   - Nunca compartir un Command o Query entre dos controllers
   - Si dos controllers necesitan datos similares, cada uno tiene su propio Query
2. **Handler = thin adapter** — solo llama al UseCase, captura excepciones, retorna resultado
3. **UseCase = lógica de negocio** — busca entidades, aplica cambios, llama repositorios
4. **Controller sin try/catch** para commands — el Handler retorna `StudyCommandResult`
5. **Registrar el Command/Query** en `config/packages/messenger.yaml` routing (transport: sync)
6. **Registrar interfaces** de repositorios nuevas en `config/services.yaml`

### Tipos de retorno por capa
| Capa | Command handler | Query handler |
|------|----------------|---------------|
| Handler devuelve | `StudyCommandResult` | `?Entity` o `array` |
| Controller mapea | `$result->statusCode` + `$result->data` | null → 404, entity → json |

### StudyCommandResult — métodos disponibles
- `::created(array)` → 201
- `::ok(array)` → 200
- `::deleted()` → 204
- `::notFound()` → 404
- `::badRequest(string)` → 400

### Flush en repositorios
- `StudyRepository::save()` hace `persist + flush` — el ID queda disponible inmediatamente
- `TagRepository::save()` solo hace `persist` — el flush lo hace el `StudyRepository::save()` posterior
- `StudyRepository::delete()` hace `remove + flush`
- El middleware `doctrine_transaction` envuelve todo en BEGIN/COMMIT para atomicidad

## Verticals del proyecto

### Study/ — implementada y activa
- Backend: `backend/symfony/src/Study/`
- Frontend: `frontend/src/Study/`
- Referencia de patrón para todas las verticales nuevas

### ActiveDirectory/ — en desarrollo
- Backend: `backend/symfony/src/ActiveDirectory/`
- Frontend: `frontend/src/ActiveDirectory/`
- Misma estructura que Study/, mismas normas de backend y frontend

## Normas frontend — cómo trabajar en una vertical (OBLIGATORIO seguir este patrón)

Ver detalle completo en `frontend/README.md`.

### Estructura de carpetas por vertical
```
frontend/src/<Vertical>/
├── Application/
│   └── UseCase/
│       └── <Action>/
│           ├── <Action>UseCase.js
│           └── mock.json          (si la acción retorna datos)
└── Infrastructure/
    ├── View/
    │   └── <Page>View.vue
    └── Router/
        └── index.js               (sub-router de la vertical)
```

### Reglas estrictas frontend
1. **1 vista = 1 acción = 1 UseCase** por cada llamada HTTP
2. **Nunca** llamar a `httpClient` directamente desde una vista o componente
3. **UseCase = InMemory + Api + función principal** — la función principal elige según `UtilHelper.checkEnvironment()`
4. **InMemory** simula la respuesta con `mock.json` + `UtilHelper.wait()`
5. **Api** llama a `httpClient` y retorna los datos **desenvueltos** (sin `.data.data`)
6. **Sub-router** registrado en `router/index.js` principal mediante spread (`...verticalRoutes`)
7. **CSS**: usar clases de `core/styles/base.css` antes de escribir CSS scoped; nunca inline styles
