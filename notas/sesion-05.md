# Sesión 05
Fecha: 10/05/2026

---

## Objetivo de la sesión

Mejorar el módulo de fútbol en dos frentes:
1. Nuevo enfoque de análisis — detectar partidos con **menos goles esperados** (under)
2. Nueva pantalla de referencia — **ligas del mundo por goles por partido**

---

## 1. Score "Under" — partidos con menos goles esperados

### Concepto
Es más fácil predecir que un partido no tendrá 5 goles que predecir quién gana o si habrá más de 1 gol. Se añadió una tercera selección diaria con este enfoque.

### Cambios en `match_scorer.py`
Nueva función `score_under(partido)` con 4 señales:

| Señal | Dato | Lógica |
|---|---|---|
| xG estimado | `gf`/`gc` de standings | `(ataque_local + defensa_visit)/2 + (ataque_visit + defensa_local)/2` |
| O/U bookmaker | `over_under` | línea ≤2.5 = positivo, ≥3.5 = penalización |
| Probabilidad empate | `prob_empate` | alta = partido cerrado |
| H2H bajo marcador | `h2h_detalle` | % de partidos históricos con ≤2 goles totales |

También `rank_partidos_under()` y helper `_total_goles_h2h()`.

### Cambios en `football_daily.py`
- Se añaden `gf_local`, `gc_local`, `gf_visitante`, `gc_visitante` al dict del partido (desde standings)
- Nueva selección `seleccionados_under` guardada con `tipo='under'`
- Telegram incluye sección `🔒 Under` con O/U, xG estimado y % empate

### Cambios en `db.py`
- `save_seleccion_diaria` acepta nuevo param `razones_key` para guardar el detalle correcto por tipo
- Para under se pasa `razones_key='score_under_detalle'`

### Cambios en backend Symfony
- `GetSeleccionDiariaUseCase.php` devuelve también `under[]`

### Cambios en frontend
- `PartidoCardUnder.vue` — card nueva con métricas de under: xG (coloreado), O/U, empate%, H2H ratio
- `FutbolSeleccionView.vue` — tercer tab `🔒 Under — pocos goles esperados`

---

## 2. Ligas del mundo — Goles por partido

### Concepto
Vista de referencia que muestra qué ligas del mundo tienen más y menos goles por partido.
Datos en tiempo real calculados desde ESPN standings.

**Fórmula:** `sum(gf de todos los equipos) / (sum(pj) / 2)`

### Hallazgos del ranking (30 ligas analizadas)
- **Más goles:** Bolivia (3.24), Bundesliga (3.21), MLS (3.20), Eredivisie (3.17)
- **Menos goles:** Argentina (2.05), Ecuador (2.06), Serie A (2.40), Uruguay (2.45)

### `steps/ligas_gpm.py` (nuevo)
- Analiza 30 ligas de 4 continentes
- Guarda en tabla `futbol_ligas_gpm`
- **Ejecutar 1 vez por semana o al mes** — no tiene sentido diario

### Backend Symfony (stack completo CQRS)
- Migración: tabla `futbol_ligas_gpm`
- `FutbolLigaGpm` entity + `DoctrineFutbolLigaGpmRepository`
- `GetLigasGpmUseCase` → `GET /api/futbol/ligas/gpm`

### Vista drill-down por liga — Equipos
Al hacer clic en cualquier liga se navega a `/futbol/ligas/{codigo}/equipos` con el desglose por equipo.

**Backend:** `GetEquiposLigaUseCase` llama ESPN standings en tiempo real via **Symfony HttpClient** (sin nueva tabla). Calcula `gf_pj`, `gc_pj`, `total_gpm` por equipo y ordena ascendente (más "under" primero).

**Frontend:** `FutbolLigaEquiposView.vue`
- Card top 5 más "under" (azul) + top 5 más goleadores (rojo)
- Tabla completa con GF/pj, GC/pj, total/pj, barra y colores
- Top 3 / bottom 3 resaltados

Ejemplo real — **Argentina** (2.05 gpm):
- Más under: Deportivo Riestra (1.06 gpm), Platense (1.56), Aldosivi (1.56)
- Más goles: Independiente Rivadavia (2.75 gpm)

---

## 3. Navegación añadida
- `FutbolSeleccionView.vue` — botón "Ligas — goles/partido" en el header
- `FutbolLigasGpmView.vue` — filas clickeables (extremos + tabla completa)

---

## Rutas nuevas

| Ruta | Vista |
|---|---|
| `/futbol/ligas/gpm` | Ranking mundial de ligas por gpm |
| `/futbol/ligas/:codigo/equipos` | Equipos de una liga ordenados por gpm |

## Endpoints nuevos

| Endpoint | Descripción |
|---|---|
| `GET /api/futbol/ligas/gpm` | Lista de ligas con gpm desde DB |
| `GET /api/futbol/ligas/{codigo}/equipos` | Equipos de una liga en tiempo real (ESPN) |

---

## Pendiente próximas sesiones

- [ ] Nombre de liga visible en `FutbolLigaEquiposView` (ahora muestra el código ESPN)
- [ ] Cruzar equipos bajo marcador con la selección diaria de partidos
- [ ] Resumen semanal fútbol por Telegram (viernes)
- [ ] `tools/claude.py` — análisis profundo cuando hay partido interesante
