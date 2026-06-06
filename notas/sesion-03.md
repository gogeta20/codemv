# Sesión 03
Fecha: 26/04/2026

---

## Objetivo de la sesión

Completar el agente autónomo: Telegram, descubrimiento de acciones nuevas, cron.
Completar el frontend: vista detalle de acción, portafolio detail, mejoras de tabla.

---

## Frontend completado

### Vista detalle de acción (`AccionDetailView`)
Tres secciones:
1. **Evolución del precio** — 4 tarjetas: Hoy / 1 semana / 1 mes / 1 año con % vs hoy
2. **Sobre la empresa** — sector, industry, ciudad, empleados, market cap + badge de tamaño, web, descripción larga
3. **Noticias recientes** — mezcla live (yfinance) + DB, sin duplicados, orden cronológico inverso

**Market cap badge:**
- Mega cap >$200B (morado), Large cap >$10B (azul), Mid cap >$2B (cyan)
- Small cap >$300M (amarillo), Micro cap >$50M (rojo), Nano cap (gris)

**Header derecho:**
- Muestra el portafolio al que pertenece + link + status
- Botón lápiz para cambiar de portafolio (quita del actual, añade al nuevo)
- Si no tiene portafolio: botón "Añadir a portafolio"

### Vista portafolio detail (`PortafolioDetailView`)
Igualada a la tabla principal con: símbolo, nombre (link a detalle), sector, mercado, tipo, precio actual, % con flecha, dif.$, status, notas truncadas con tooltip.

### Vista portafolio list (`PortafolioListView`)
- Cards en grid, default con borde cyan + icono `pi-box` relleno
- Resto con botón `pi-box` vacío para marcar como default
- `setDefault()` hace clearDefault + marca el nuevo

### Otras mejoras
- `ConfirmDialog` + `Toast` añadidos al `App.vue` root (arregla el botón eliminar)
- Tabla acciones: columna "Portafolio" con link directo al portafolio al que pertenece
- Botón crear acción: sector/industry se autocompletan, no se muestran como inputs

---

## Agente completado

### lookup_server.py — nuevos endpoints
- `GET /history?symbol=NKE` → precios clave: hoy, 1 semana, 1 mes, 1 año
- `GET /description?symbol=NKE` → longBusinessSummary + facts de yfinance
- `GET /news?symbol=NKE` → noticias frescas via yfinance

### Telegram (`tools/telegram.py`)
- Bot: `@AgenteMVM_bot`
- Chat personal (ID: 1677110457): resumen diario + alertas de acciones conocidas
- Grupo "Investigación" (ID: -5286640533): alertas de acciones nuevas descubiertas
- `send()` → chat principal
- `send_discover()` → grupo investigación (fallback a principal si no configurado)
- `send_daily_summary()` → tabla de todas las acciones con iconos
- `send_news_alert()` → alerta individual con titulares y análisis del agente

### discover_movers.py (`steps/discover_movers.py`)
El módulo más importante — radar de oportunidades desconocidas:

```
yfinance.screen('day_gainers') + yfinance.screen('day_losers')
        ↓
Filtra: precio > $1, cambio > 5%, símbolo sin punto (no extranjeros)
        ↓
¿Ya está en nuestra DB?
  SÍ → skip
  NO → guarda en acciones (source=discovered)
       guarda precio en acciones_precios
       añade al portafolio "investigar"
       busca noticias
       envía alerta al grupo Investigación
```

### main.py — orquestador completo
```
1. Fetchea precios de acciones conocidas (activas en DB)
2. Guarda precios en DB
3. Detecta movers >threshold% (decisión Python, no modelo)
4. Envía resumen diario a Telegram (chat personal)
5. Para cada mover → news_check → alerta Telegram con titulares
6. discover_movers → nuevas oportunidades → grupo Investigación
```

### Cron configurado
```
0 9,15,18,22 * * 1-5  cd /backend/agent && python3 main.py >> /tmp/agente_mvm.log
```
Lunes a viernes a las 9:00, 15:30 (apertura US), 18:00 (mitad sesión), 22:00 (cierre US).

### Portafolios creados
- `default` (is_default=true) — acciones añadidas manualmente desde el front
- `main` — portafolio principal
- `investigar` (UUID: 4978653c-e34d-43bd-9f32-3271964c3b11) — acciones descubiertas por el agente

### .env del agente
```
TELEGRAM_TOKEN=...
TELEGRAM_CHAT_ID=1677110457
TELEGRAM_DISCOVER_CHAT_ID=-5286640533
PORTAFOLIO_INVESTIGAR_UUID=4978653c-e34d-43bd-9f32-3271964c3b11
```

---

## Para arrancar el entorno completo

```bash
make up-all                                          # Docker: API + DB + Frontend
cd backend/agent && python3 lookup_server.py &       # Lookup server (port 5001)
# El agente corre solo via cron (09:00 / 15:30 / 18:00 / 22:00 L-V)
# Para ejecutar manualmente:
cd backend/agent && python3 main.py
```

---

## Primera prueba real
Mañana 27/04/2026 a las 09:00 el agente arranca solo por primera vez.

---

## Pendiente — próximas sesiones

### Agente — hacer más inteligente
- [ ] `tools/claude.py` — consultar Claude API cuando el agente ve algo importante
  (ej: empresa nueva con +20%, el agente pide análisis profundo a Claude)
- [ ] Mejorar análisis del modelo 3B con mejor system prompt
- [ ] Filtros más inteligentes en discover_movers (volumen mínimo, excluir ETFs, etc.)
- [ ] Resumen semanal los viernes (qué descubrimos, qué movió más)

### Frontend — mejoras pendientes
- [ ] Vista de noticias global (todas las alertas recientes en una pantalla)
- [ ] Historial de precios guardados visible en el detalle de acción
- [ ] Marcar acciones de "investigar" como "descartada" o "seguir" desde el front
