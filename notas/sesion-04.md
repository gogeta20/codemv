# Sesión 04
Fecha: 29/04/2026

---

## Objetivo de la sesión

Afinar las herramientas de análisis de fútbol con dos mejoras concretas:
1. **Corners promedio** — mostrar el promedio de corners de la temporada para cada equipo en la vista detalle
2. **Anotaciones del partido** — snapshot de datos + conclusiones del analista para revisión posterior

---

## Contexto

Partido seleccionado hoy: **Tromso vs SK Brann** (Noruega — Eliteserien)

El problema que queremos resolver: en 2 semanas no recordaremos qué datos teníamos disponibles
en el momento del análisis ni qué conclusiones sacamos. Necesitamos un "snapshot" inmutable
de los datos + un espacio para escribir nuestro análisis antes del partido.

---

## 1. Corners promedio ✅ (completado esta sesión)

### Fuente de datos
ESPN ya tiene `wonCorners` en el boxscore de partidos finalizados.
El endpoint `teams/{id}/schedule` devuelve todos los partidos de la temporada (34 en Serie A).
Calculamos el promedio iterando sobre los partidos finalizados.

### Implementación
- Nueva función en `espn.py`: `get_team_corners_avg(liga, team_id, n_partidos=10)`
  - Llama al schedule del equipo
  - Para cada partido finalizado saca el boxscore
  - Calcula promedio corners `wonCorners`
- `football_daily.py`: llamar para local y visitante al enriquecer cada partido
- Nuevo campo `corners_avg` JSON en `futbol_partidos` (Python DB) + entidad Symfony
- `FutbolPartidoDetalleView.vue`: mostrar los promedios en la sección de apuestas/stats

### Dato de ejemplo
- Atalanta últimos 5 partidos: **6.4 corners promedio**
- ESPN tiene el schedule completo de la temporada (hasta 34 partidos)

### Limitación
Consume ~10 requests extra por partido (2 equipos × 5 partidos de historial).
Con 30 partidos/día en 9 ligas = ~300 requests adicionales. Dentro del límite.

---

## 2. Anotaciones del partido 🔲 (pendiente)

### Concepto
Cada partido seleccionado tendrá:
- **Snapshot** — foto fija de los datos en el momento del análisis (corners avg, forma, odds, H2H, líderes)
- **Análisis** — texto libre del usuario con sus conclusiones antes del partido
- **Predicciones** — campos estructurados: quién gana, O/U, corners (más/menos de X)
- **Verificación** — al día siguiente, contrastar predicciones con resultado real

### Tabla sugerida: `futbol_analisis`
```sql
uuid            UUID PK
partido_uuid    FK → futbol_partidos
snapshot        JSONB    -- copia de todos los datos en el momento
notas           TEXT     -- texto libre del analista
pred_ganador    VARCHAR  -- 'local' | 'visitante' | 'empate'
pred_goles      DECIMAL  -- over/under estimado
pred_corners    DECIMAL  -- corners totales estimados
pred_jugador    VARCHAR  -- jugador destacado a seguir
resultado_ok    BOOLEAN  -- si la predicción fue correcta (se rellena después)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### Vista
- En `FutbolPartidoDetalleView.vue`: panel inferior con textarea + campos de predicción
- Guardado via `POST /api/futbol/analisis`
- Vista de historial para ver predicciones pasadas y su acierto

---

## Pendiente próximas sesiones

- [ ] Anotaciones del partido (implementación completa)
- [ ] Historial de aciertos — % de predicciones correctas por tipo
- [ ] Corners incluidos en el score de analizabilidad (equipos con muchos corners = más mercados disponibles)
- [ ] Resumen semanal automatizado por Telegram (viernes)
- [ ] `tools/claude.py` — consultar Claude API para análisis profundo cuando hay partido interesante
