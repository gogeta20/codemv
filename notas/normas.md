# Normas de trabajo de codemv

Este documento es la **primera lectura obligatoria** antes de tocar código, lanzar procesos o crear una rama nueva.

## Orden de lectura recomendado

1. Este archivo: `notas/normas.md`
2. `CLAUDE.md`
3. `notas/arrancar.md`
4. `notas/comandos_agente.md`
5. `frontend/README.md`
6. `notas/sessions/` → abrir la sesión más reciente por nombre

## Qué es este proyecto hoy

`codemv` ya no es solo una KB. Ahora mismo mezcla varios dominios:

- Backend Symfony + PostgreSQL
- Frontend Vue 3 + PrimeVue
- Agente Python en `backend/agent/`
- Verticales principales: `Study`, `ActiveDirectory`, `Acciones`, `Futbol`, `Pruebas`

## Fuente de verdad rápida

- `CLAUDE.md`
  Explica la arquitectura general, las reglas de backend, el patrón CQRS/DDD y la estructura por verticales.

- `frontend/README.md`
  Explica cómo está organizado el frontend, cómo crear `UseCase`, rutas y normas de CSS/componentes.

- `notas/arrancar.md`
  Resume cómo levantar el proyecto, qué procesos existen y qué cron jobs están activos.

- `notas/sessions/`
  A partir de ahora cada sesión nueva se documenta aquí. La más reciente será la de nombre más alto:
  `session_YYYY-MM-DD_HH-MM.md`

- `notas/sesion-01.md` a `notas/sesion-05.md`
  Son historial útil del proyecto. No son la entrada principal nueva, pero siguen aportando contexto sobre la evolución de `Acciones` y `Futbol`.

## Flujo de ramas obligatorio

- La rama base de trabajo es siempre `main`
- Cada tarea nueva sale desde `main`
- Cada tarea vive en su propia rama
- Al terminar una tarea, se vuelve a `main`
- La siguiente tarea vuelve a salir desde `main`

Patrón esperado:

```bash
git switch main
git pull
git switch -c tipo/nombre-corto-de-la-tarea
```

Ejemplos:

```bash
git switch -c fix/futbol-telegram-chat
git switch -c chore/futbol-cron-1am
git switch -c feat/acciones-earnings-panel
```

## Normas de documentación

- Toda sesión nueva va en `notas/sessions/`
- Formato de nombre obligatorio:
  `session_YYYY-MM-DD_HH-MM.md`
- Cada sesión debe dejar:
  - objetivo
  - cambios hechos
  - decisiones tomadas
  - pendientes

## Normas prácticas del agente y cron

- Cron de acciones:
  `07:00`, `15:00`, `18:00`, `22:00` L-V
- Cron de fútbol:
  `01:00` todos los días
- Lookup server:
  `@reboot` + 30s

## Antes de empezar cualquier tarea

- Leer este archivo
- Leer la sesión más reciente de `notas/sessions/`
- Confirmar que estás en `main` o en la rama de tarea correcta
- Si la tarea toca arquitectura, repasar `CLAUDE.md`
- Si toca frontend, repasar `frontend/README.md`
