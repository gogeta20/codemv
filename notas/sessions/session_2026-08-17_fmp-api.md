# Session 2026-08-17 — FMP API key para intrínseco + target de analistas

## Objetivo
Agregar a `AccionesListView.vue` dos columnas nuevas para comparar contra "Precio Real" (fair_value propio):
- Precio objetivo de analistas
- Precio intrínseco calculado por un tercero (para comparar con nuestro propio cálculo)

## API key de Financial Modeling Prep
El usuario obtuvo una key de FMP. Se guardó en `backend/symfony/.env.local` (no versionado) como `FMP_API_KEY`.
Placeholder vacío dejado en `backend/symfony/.env` (versionado) siguiendo la convención Symfony.

**El valor real de la key NO está en este archivo** — está solo en `.env.local`, que es local a esta máquina.

## Endpoints probados (funcionan)
El endpoint legacy `/api/v3/discounted-cash-flow/{symbol}` está descontinuado ("Legacy Endpoint" error).
Hay que usar los nuevos endpoints "stable":

- DCF intrínseco: `GET https://financialmodelingprep.com/stable/discounted-cash-flow?symbol={SYMBOL}&apikey={FMP_API_KEY}`
  → `{ symbol, date, dcf, "Stock Price" }`
  Probado con AAPL: `dcf: 149.22` vs precio real $305.93 (gap grande, esperado — el DCF de FMP es conservador).

- Precio objetivo analistas: `GET https://financialmodelingprep.com/stable/price-target-summary?symbol={SYMBOL}&apikey={FMP_API_KEY}`
  → `{ symbol, lastMonthCount, lastMonthAvgPriceTarget, lastQuarterCount, lastQuarterAvgPriceTarget, lastYearCount, lastYearAvgPriceTarget, allTimeCount, allTimeAvgPriceTarget, publishers }`
  Más granular que el `financialData.targetMeanPrice` de Yahoo (que ya usan en `LookupAccionController.php`) — trae promedio por periodo y conteo de analistas.

## Pendiente
- Crear el fetch en backend (nueva Application/UseCase o extender el existente de precios) que llame a estos dos endpoints y guarde `analyst_target_price` y `intrinsic_value_api` (o nombres similares) por acción.
- Agregar columnas a `AccionesListView.vue`: "Objetivo analistas" y "Intrínseco (API)" al lado de "Precio Real", para comparar los tres.
- Definir frecuencia de refresco (free tier de FMP: revisar límite diario de requests antes de decidir si se hace en cada fetch de precio o en un cron separado).
