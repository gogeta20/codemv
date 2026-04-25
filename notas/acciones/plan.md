# Plan — Herramienta de Tesis de Inversión

## Concepto

No es un screener de fundamentales. Es un **mapa de tesis**.

El inversor define una hipótesis macro (ej: "la IA va a colapsar la demanda energética")
y la herramienta organiza los símbolos que juegan esa tesis, agrupados por su rol
en la cadena de valor, con precios actualizados.

### El modelo mental

El mundo tiene problemas estructurales que se mueven lento pero son predecibles.
La bolsa siempre reacciona tarde. La ventana de oportunidad es cuando el problema
ya existe pero el mercado aún no lo ha priced completamente.

```
2020 → salud / vacunas / biotech
2022 → chips / semiconductores
2024 → energía (causa: IA + data centers)
202? → agua? cobre? grid eléctrico?
```

---

## Lo que hace la herramienta

### Input
El usuario define una tesis con:
- Nombre
- Descripción libre
- Keywords asociados
- Lista de símbolos agrupados por rol en la cadena

### Output
Por cada tesis: una vista con todos sus símbolos, precio actual,
variación 1D / 1M / YTD, market cap.

De un vistazo sabes si tu hipótesis está "caliente" o "fría" en el mercado.

---

## Ejemplo de tesis — "Energía para IA"

```
nombre:      Energía para IA
hipótesis:   La IA dispara la demanda energética. Las instalaciones no dan abasto.
             El nuclear es la única fuente que puede escalar en tiempo y densidad.
keywords:    nuclear, energy, data center, uranium, SMR

cadena de valor:
  combustible:      CCJ, UEC, DNN, NXE, UUUU
  reactores / SMR:  OKLO, SMR, NNE
  infraestructura:  WULF, CLCO, VST
  habilitadores:    ORCL, GEV, ETN
```

---

## Decisiones de diseño

- **Mapeo tesis → símbolos: manual/curado** (no automático)
  El valor está en el razonamiento del inversor, no en el volumen de tickers.
  La API solo trae precios.

- **Stack**: Symfony + Vue + PostgreSQL (ya disponible en codemv)

- **Mercados a cubrir**: NASDAQ, NYSE, TSX (Canada — crítico para mineras)

---

## Arquitectura (borrador)

### Backend (nueva vertical `Acciones/`)
```
Acciones/
├── Domain/
│   ├── Tesis.php
│   ├── Symbol.php
│   └── TesisRepository.php (interface)
└── Application/
    ├── CreateTesis/
    ├── AddSymbol/
    ├── GetTesis/
    └── GetTesisWithPrices/   ← llama API externa de precios
```

### Frontend (nueva vertical `Acciones/`)
```
Acciones/
├── Application/UseCase/
│   ├── GetTesis/
│   ├── CreateTesis/
│   └── GetPrices/
└── Infrastructure/View/
    └── TesisView.vue         ← vista principal
```

### API de precios
- Candidato principal: **Financial Modeling Prep (FMP)** — free tier viable
- Alternativa: **Yahoo Finance** (yfinance via endpoint propio)
- Datos por símbolo: precio, cambio %, market cap

---

## Estado

- [ ] Definir modelo de datos (Tesis + Symbol)
- [ ] Elegir API de precios y probar free tier
- [ ] Crear vertical backend `Acciones/`
- [ ] Crear vertical frontend `Acciones/`
- [ ] Vista de tesis con precios en tiempo real (o quasi)
- [ ] Primera tesis cargada: "Energía para IA"

---

## Módulo 2 — Radar de IPOs

### Concepto

Un feed de empresas que acaban de salir a bolsa (o van a salir pronto).
No todas interesan — solo las que encajan con alguna tesis activa o abren una nueva.

Ejemplo: si hoy sale una empresa de fusión fría, quiero verla. Si sale la IPO número
500 de un banco regional, no me importa.

### Fuentes de datos

- **SEC EDGAR** — S-1 recién presentados (gratis, tiempo real)
  Endpoint: `https://efts.sec.gov/LATEST/search-index?q=%22S-1%22&dateRange=...`
- **IPO Monitor / Renaissance Capital** — calendario de próximas IPOs
- El S-1 contiene: descripción del negocio, sector, quién invierte, precio estimado

### Flujo de uso

```
1. Herramienta muestra lista de IPOs recientes / próximas
   - Nombre, ticker (si ya tiene), sector, exchange, fecha
2. Tú lees el nombre/sector y decides si vale investigar
3. Si sí → la vinculas a una tesis existente o creas una nueva
4. A partir de ahí entra en tu mapa de tesis normal
```

### Filtro inteligente (futuro)

Cruzar keywords de tus tesis activas contra la descripción del S-1.
Si tu tesis tiene "nuclear, energy, SMR" y un S-1 menciona esas palabras → alerta.

---

## Módulo 3 — Agente autónomo + alertas móvil

### Concepto

Un agente que corre solo, todos los días, sin que tú hagas nada.
Actualiza la BD, detecta movimientos relevantes y te manda un mensaje al móvil.
Tú puedes responderle para pedir más detalle o lanzar un análisis más profundo.

### Canal de notificaciones: Telegram

- **Gratis, sin límites, API oficial**
- WhatsApp descartado: API de pago + restricciones
- Telegram Bot API: creas un bot en 2 minutos con @BotFather, tienes un token, listo
- Puedes **responderle** y el bot ejecuta acciones en función de tu respuesta

### Flujo diario del agente

```
08:00 — Cron se dispara
        ↓
Fetcher (script PHP o Python)
  - Precios actuales de todos los símbolos en tesis activas
  - IPOs nuevas en SEC EDGAR desde ayer
  - Guarda todo en PostgreSQL (codemv)
        ↓
Analizador (modelo local — Qwen 2.5 via Ollama)
  - ¿Algún símbolo movió >5% hoy?
  - ¿Hay IPOs nuevas con keywords de mis tesis?
  - Genera resumen breve
        ↓
Notificador (Telegram Bot)
  - Envía mensaje si hay algo relevante
  - Si no hay nada → silencio (no spam)
```

### Mensajes de alerta (ejemplos)

```
⚡ OKLO +7.3% hoy | Tesis: Energía para IA
   Volumen x3.2 vs media 20d

🆕 IPO detectada: "Helion Energy" (fusión nuclear)
   Exchange: NASDAQ | Sector: Energy
   Keywords match: nuclear, energy, SMR
   → ¿La añado a tu tesis?

📊 Resumen semanal tesis "Energía para IA":
   OKLO +12% | SMR -3% | WULF +8% | CCJ +2%
```

### Interacción bidireccional

Tú respondes al bot y él actúa:

```
Tú:    "analiza OKLO"
Bot:   llama Claude API (Sonnet) → análisis real → te responde

Tú:    "añade NNE a energía IA"
Bot:   escribe en BD → confirmado

Tú:    "muéstrame las IPOs de esta semana"
Bot:   consulta BD → lista formateada
```

### Modelo para cada tarea

| Tarea | Modelo | Coste |
|-------|--------|-------|
| Fetch + guardar datos | Script puro (sin IA) | $0 |
| Filtrar / detectar anomalías | Qwen 2.5 local (Ollama) | $0 |
| Análisis profundo cuando tú lo pides | Claude Sonnet via API | ~céntimos |
| Debates / hipótesis complejas | Claude Sonnet / Opus | bajo demanda |

### Infraestructura

- Ollama en Docker (segundo PC o mismo servidor)
- Cron en el host o Makefile target con `make agent:run`
- Telegram Bot token en `.env`
- PostgreSQL ya existe en codemv

---

## Notas abiertas

- ¿Añadir campo "convicción" por símbolo (alta/media/baja)?
- ¿Alertas cuando un símbolo de una tesis activa mueve >X% en un día?
- ¿Múltiples tesis activas en paralelo?
- ¿Histórico de cuándo se añadió cada símbolo a la tesis? (para saber el precio de entrada a la idea)
