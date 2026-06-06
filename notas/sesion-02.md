# Sesión 02
Fecha: 25/04/2026

---

## Objetivo de la sesión

Construir las herramientas del agente local (Ollama llama3.2:3b) y definir el diseño
de base de datos para el radar de acciones.

---

## Lo que hicimos

### Prueba del agente — funciona

Creamos `backend/agent/` con una prueba real:
- `agent.py` — orquestador: llama Ollama, gestiona el loop de tool calling
- `tools/prices.py` — obtiene precio real via yfinance
- `requirements.txt` — yfinance, requests

El agente llamó `get_price(BTC)` y `get_price(ETH)` correctamente y generó un análisis
con datos reales. Primera prueba superada.

**Resultado:**
```
[tool] get_price({'symbol': 'BTC'})
[result] {'symbol': 'BTC', 'price': 77509.68, 'prev_close': 78268.95, 'change_pct': -0.97}

[tool] get_price({'symbol': 'ETH'})
[result] {'symbol': 'ETH', 'price': 2318.72, 'prev_close': 2331.51, 'change_pct': -0.55}
```

---

## Decisiones de arquitectura

### Python es el comunicador físico con Ollama
PHP podría llamar a Ollama (es HTTP), pero Python gana por el ecosistema de datos
(yfinance, pandas). PHP queda descartado para el agente.

### Estructura del agente (definitiva)
```
backend/agent/
├── main.py                  ← orquestador (cron lo llama)
├── steps/
│   ├── morning_analysis.py  ← paso 1: precios + análisis conocidas
│   ├── discover_movers.py   ← paso 2: top gainers del mercado (desconocidas)
│   ├── news_check.py        ← paso 3: si >5% busca noticias
│   ├── summary.py           ← paso 4: genera resumen
│   └── alert.py             ← paso 5: envía notificación Telegram
├── tools/
│   ├── prices.py            ← hecho ✓
│   ├── news.py
│   ├── memory.py
│   └── claude.py            ← fallback a Claude API
├── context.py               ← carga instrucciones desde codemv
├── memory/
│   └── repository.py
└── config.py
```

### El front y la DB son Symfony + Vue + PostgreSQL
Python solo habla con el agente y llama endpoints propios para leer/escribir datos.
El front Vue permite gestionar la lista de acciones y ver resultados.

---

## Diseño de base de datos

### `acciones`
```
id                    UUID PK
symbol                VARCHAR(20)          -- "OKLO", "BTC-USD"
name                  VARCHAR(100)
type                  ENUM(crypto, stock, etf)
source                ENUM(manual, discovered)  -- manual=tú la añades, discovered=el agente la encontró
is_active             BOOLEAN DEFAULT true
alert_threshold_pct   DECIMAL(5,2) DEFAULT 5.0
created_at            TIMESTAMP
updated_at            TIMESTAMP
```

### `acciones_precios`
```
id            UUID PK
accion_id     FK → acciones
date          DATE
price_open    DECIMAL(15,4)
price_close   DECIMAL(15,4)
price_high    DECIMAL(15,4)
price_low     DECIMAL(15,4)
volume        BIGINT
prev_close    DECIMAL(15,4)
change_pct    DECIMAL(8,4)
change_amount DECIMAL(15,4)
created_at    TIMESTAMP
UNIQUE(accion_id, date)
```

### `portafolio`
```
id                UUID PK
accion_id         FK → acciones
status            ENUM(watchlist, candidato, activo, descartado)
precio_referencia DECIMAL(15,4)   -- precio cuando se añadió
notas             TEXT
created_at        TIMESTAMP
updated_at        TIMESTAMP
```

---

## Flujo del agente (definitivo)

```
08:00 Cron
  ↓
1. Carga contexto desde codemv (instrucciones del agente)
  ↓
2. Fetchea precios de acciones conocidas (is_active=true)
   → guarda en acciones_precios
  ↓
3. Fetchea top gainers del mercado (yfinance Screener)
   → filtra price > 1€ y change_pct > umbral
   → para desconocidas: crea en acciones con source=discovered
  ↓
4. Para cualquier acción que movió > umbral:
   → busca noticias
   → investiga la empresa si es nueva
  ↓
5. Genera resumen + alerta Telegram si hay algo relevante
  ↓
6. Guarda estado del día en DB
```

### Objetivo real del proyecto
Descubrir empresas **antes** de que sean famosas.
Ejemplo: OKLO en 2021 era desconocida. Si hubiera movido 8% con volumen raro,
el agente la detecta, investiga y avisa. El usuario decide si la sigue o descarta.
Filtro: precio > 1€ (sin penny stocks).

---

## Lo completado en esta sesión

### Backend Symfony
- Vertical `Acciones/` creada con estructura DDD completa
- 4 entidades Doctrine: `Accion`, `AccionPrecio`, `Portafolio`, `AccionNoticia`
- 4 repositorios (interfaces + implementaciones)
- Registrados en `services.yaml`
- Migraciones ejecutadas — tablas en PostgreSQL
- Docker levantado con hot-reload (volumen montado)
- Datos insertados: IREN, OKLO, SMR

### Agente Python (`backend/agent/`)
- `tools/prices.py` — yfinance OHLCV completo
- `tools/news.py` — Yahoo Finance RSS por ticker
- `db.py` — conexión PostgreSQL, save_precio, save_noticias
- `steps/morning_analysis.py` — fetchea precios, guarda en DB, análisis del agente
- `steps/news_check.py` — busca noticias para movers >5%, guarda en DB con análisis

### Prueba real
- OKLO -7.14%, SMR -5.97% detectados y analizados
- 10 noticias guardadas en `acciones_noticias`
- Título clave detectado: "Oklo Alliance With NVIDIA And Los Alamos Puts AI Power In Focus"

---

## Completado en sesión ampliada

### Frontend Acciones
- Lista con precio, cambio % (flecha verde/roja), sector, mercado, columna portafolio con link
- Dialog crear: autocomplete con lookup (Python server port 5001), preview card, selección portafolio
- Fetch precio automático al crear + añadir al portafolio seleccionado en paralelo
- Eliminar con confirm dialog (ConfirmDialog + Toast añadidos al App.vue root)

### Frontend Portafolio
- Lista en grid de tarjetas con badge/botón de default (icono pi-box)
- Detail view con botón de marcar como default en el header
- CRUD completo de acciones dentro del portafolio

### Backend
- Campo `exchange` en acciones + lookup proxy a Yahoo Finance
- lookup_server.py (Flask, port 5001): /lookup y /price endpoints con yfinance
- Campo `is_default` en portafolios con clearDefault lógica (un solo default)
- ListAcciones incluye precio + portafolio al que pertenece cada acción (una query)
- Portafolio "default" creado con is_default=true

### Para arrancar el entorno completo
```bash
make up                                    # Docker: API + DB
make up-all                                # Docker: API + DB + Frontend
cd backend/agent && python3 lookup_server.py &  # Lookup server (port 5001)
```

## Pendiente — próxima sesión

### Agente Python
- [ ] `steps/discover_movers.py` — top gainers/losers del mercado (yfinance Screener, precio >1€)
- [ ] `main.py` — orquestador que encadena todos los steps
- [ ] `tools/claude.py` — consulta Claude API cuando el agente ve algo importante
- [ ] Telegram bot — alerta cuando hay movimiento relevante o noticia clave
- [ ] `config.py` — umbrales, configuración centralizada

### Backend Symfony + Vue
- [ ] Endpoints API: CRUD acciones, listar precios, listar noticias
- [ ] Vista Vue: lista de acciones editable
- [ ] Vista Vue: noticias recientes con enlaces
