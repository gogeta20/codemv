# Pantalla: Análisis de Fundamentales

**Feature branch:** `agente` (continuar aquí)
**Creado:** 2026-05-18
**Actualizado:** 2026-05-19
**Estado:** Algoritmo validado con 5 empresas — listo para empezar código

---

## Objetivo general

Dar una **primera impresión útil** de una empresa basándose solo en sus CSVs financieros.
No reemplaza el análisis profundo. Su función es responder en segundos:
"¿Vale la pena investigar esto más, o paso?"

El riesgo principal a evitar: que una empresa buena pero no convencional (IREN, OKLO, SMR)
aparezca como descarte cuando en realidad es una oportunidad legítima.

---

## Formato de los CSV (validado con SMR e IREN)

- Fila 1: cabecera — primera celda vacía, resto son años + última columna `Total`
- Columna final de datos: `TTM` (income/cashflow) o `Last Report` (balance) — dato más reciente
- Columna `Total` = cambio % acumulado — ignorar para scoring
- Filas de sección: primera celda con nombre, resto vacías — ignorar al parsear
- Filas `Growth`: variación porcentual — guardar en raw pero no usar en scoring
- Números sucios: comas en miles (`"1,272,572"`), artefacto `.001`, prefijo `$`, sufijo `%`
- Columna `Employees` puede tener celdas vacías — tratar como null

---

## Métricas a extraer por archivo

### income_statement_table.csv
| Campo en CSV | Clave interna |
|---|---|
| Revenues | revenues |
| Gross Profit | gross_profit |
| Gross Profit Ratio | gross_profit_ratio |
| Net Income | net_income |
| Net Income Ratio | net_income_ratio |
| Diluted EPS | eps_diluted |
| Ebitda | ebitda |
| Ebitda Ratio | ebitda_ratio |
| Outstanding Shares | outstanding_shares |
| Selling General & Admin Expenses | sga_expenses |
| R&D Expenses | rd_expenses |
| Operating Income | operating_income |
| Operating Income Ratio | operating_income_ratio |
| Interest Expense | interest_expense |
| Income Before Tax | income_before_tax |
| Cost Of Revenue | cost_of_revenue |

### cash_flow_table.csv
| Campo en CSV | Clave interna |
|---|---|
| Cash from Operations | cash_from_operations |
| Depreciation & Amortization | depreciation |
| Change In Working Capital | change_working_capital |
| Capital Expenditure | capex |
| Cash Acquisitions | cash_acquisitions |
| Cash from Investing | cash_from_investing |
| Dividends Paid | dividends_paid |
| Common Stock Repurchased | stock_repurchased |
| Common Stock Issued | stock_issued |
| Debt Repayment | debt_repayment |
| Free Cash Flow | free_cash_flow |

### balance_sheet_table.csv
| Campo en CSV | Clave interna |
|---|---|
| Total Current Assets | total_current_assets |
| Total Assets | total_assets |
| Total Current Liabilities | total_current_liabilities |
| Total Liabilities | total_liabilities |
| Long Term Debt | long_term_debt |
| Total Debt | total_debt |
| Net Debt | net_debt |
| Cash And Cash Equivalents | cash_equivalents |
| Short Term Investments | short_term_investments |
| Cash And Short Term Investments | cash_and_short_term |
| Employees | employees |

---

## Retrato Financiero (siempre se genera, antes del scoring)

No juzga — describe. Permite entender qué tipo de empresa tenemos antes de ver el veredicto.
Resuelve el problema de que dos empresas muy distintas pueden fallar Fase 1 por razones opuestas.

### Campos del Retrato

| Campo | Cómo calcularlo | Qué comunica |
|---|---|---|
| **Etapa** | Ver tabla de etapas abajo | Clasifica el tipo de empresa |
| **En pérdidas** | Net Income TTM/último año < 0 | Sí/No + magnitud |
| **Tendencia pérdidas** | Net Income últimos 3 años | Creciendo / Mejorando / Revertida |
| **Caja** | Cash & Short Term Investments | $ disponibles |
| **Deuda** | Total Debt | $ + si es 0 destacarlo |
| **Runway** | Cash / \|Cash from Operations anual\| | Meses/años si ops son negativas |
| **Revenue** | TTM + tendencia 3 años | $ + Creciendo / Estable / Cayendo |
| **Gross Margin** | Gross Profit Ratio TTM | % — señal de calidad del negocio |
| **R&D vs Revenue** | R&D / Revenue | Si R&D > Revenue → empresa de investigación pura |
| **CapEx vs Ops** | CapEx vs Cash from Operations | Si CapEx >> Ops → FCF drag es inversión, no pérdida |
| **Dilución anual** | (Shares actual - Shares anterior) / Shares anterior | % — >20% es riesgo alto |
| **Señal capital** | Common Stock Issued alto | Levantaron capital externo recientemente |

### Tabla de etapas

| Etapa | Criterio |
|---|---|
| Pre-revenue / I+D | R&D > Revenue |
| Pionera (I+D activo) | R&D > 15% de Revenue + Revenue crece |
| Expansión infraestructura | R&D mínimo, CapEx > Cash from Ops, Revenue crece >30%, Gross Margin >40% |
| Growth (rentable) | Revenue crece >20%, Net Income positivo reciente |
| Madura | Net Income positivo últimos 3+ años, Revenue estable o creciendo |
| En deterioro | Net Income fue positivo, ahora negativo; Revenue cayendo |
| Sin futuro claro | Net Income negativo, sin R&D, sin crecimiento, Gross Margin bajo |

### Ejemplo — SMR (NuScale, 2025)

```
RETRATO FINANCIERO — SMR
────────────────────────────────────
Etapa:         Pre-revenue / I+D
               (R&D $46M supera ingresos $31M)

En pérdidas:   SÍ — $355M netos | $689M operativos
Tendencia:     Pérdidas creciendo

Caja:          $1.25B
Deuda:         $0 (balance limpio)
Runway:        ~2.7 años al ritmo actual

Revenue:       $31M (2024) — declinando en TTM
Gross Margin:  86% cuando factura
R&D:           $46M > Revenue — investigación pura

Dilución:      +75% en 2025 — riesgo alto
Señal:         $1.3B levantados emitiendo acciones
────────────────────────────────────
```

### Ejemplo — IREN (2025)

```
RETRATO FINANCIERO — IREN
────────────────────────────────────
Etapa:         Expansión de infraestructura
               (CapEx $1.37B, ops ya rentables)

En pérdidas:   NO en 2025 — Net Income +$86M, TTM +$205M
Tendencia:     Pérdidas revertidas en 2025

Caja:          $1.25B (Last Report)
Deuda:         $964M — creció fuerte (financiando CapEx)
Runway:        N/A — operaciones generan caja

Revenue:       $501M (2025) — creciendo +168% YoY
Gross Margin:  68% — unit economics sólidos
Cash from Ops: +$245M — operaciones rentables
FCF:           -$1.1B — drag 100% por CapEx, no por ops

Dilución:      +115% en 2025 — riesgo muy alto
Señal:         $602M levantados en acciones + deuda para construir
────────────────────────────────────
```

---

## Algoritmo de scoring — 6 Fases

### FASE 1: Filtro inicial

Evalúa los 3 indicadores de salud fundamental:

| ID | Métrica | Fuente | Criterio PASA | Criterio FALLA |
|---|---|---|---|---|
| A | Free Cash Flow | cash_flow | Positivo últimos 2 años | Negativo 2+ años seguidos |
| B | Net Income | income | Positivo último año | Negativo último año |
| C | Operating Income | income | Positivo último año | Negativo último año |

**Si pasa los 3** → continuar a Fase 2.

**Si falla alguno** → evaluar sub-criterios para determinar veredicto:

#### Sub-evaluación cuando Fase 1 falla

**Camino A — Expansión de infraestructura → ESPECULATIVO**
- FCF negativo PERO Cash from Operations positivo o mejorando fuerte
- Gross Profit Ratio > 40%
- Revenue creciendo >30% últimos 2 años
- CapEx explica la diferencia entre Ops y FCF (CapEx > gap FCF-Ops)

*Ejemplo: IREN — ops +$245M, CapEx -$1.37B, gross margin 68%, revenue +168%*

**Camino B — Pionera en I+D → ESPECULATIVO**

Sub-caso B1 — Pre-revenue con algo de facturación:
- R&D > Revenue (o R&D > 15% de Revenue)
- Revenue pequeño pero existente
- Cash from Operations negativo aceptable si runway > 2 años

*Ejemplo: SMR — R&D $46M > Revenue $31M, caja $1.25B, runway 2.7 años*

Sub-caso B2 — Pre-revenue puro (Revenue = $0):
- R&D creciendo año a año (señal de inversión activa en tecnología)
- Sin revenue por diseño (tecnología no comercializada aún)
- Runway > 5 años (exigencia mayor porque no hay ninguna señal comercial)
- Deuda mínima o cero

*Ejemplo: OKLO — R&D $0 → $58.9M, revenue $0, caja $2.2B, runway ~14 años, deuda $2.6M*

> Ambos sub-casos son ESPECULATIVO, pero B2 requiere más convicción que B1.
> El runway es el criterio diferenciador más importante en empresas sin revenue.

**Camino C — Sin futuro claro → EVITAR**
- Cash from Operations negativo
- Gross Profit Ratio bajo (<30%) o negativo
- Revenue plano o cayendo
- R&D mínimo o cero
- Sin CapEx significativo (no está invirtiendo en nada)

*Ejemplo: empresa zombie — pierde dinero y no hace nada al respecto*

**Camino D — Deterioro → EVITAR**
- Net Income fue positivo históricamente, ahora negativo
- Revenue cayendo **O** revenue creciendo pero Gross Margin colapsando ≥5pp en 3 años
  ("crecimiento hueco" — venden más pero destruyen margen en cada venta)
- Operating Income negativo mientras revenue crece = señal de costes fuera de control
- R&D recortando o nunca hubo (sin apuesta al futuro)
- Dividendo recortado >30% = la empresa misma admite que no puede sostenerlo

*Ejemplo WBA: Revenue +16% en 2 años, Gross Margin 21% → 17%, Net Income +$4B → -$8.6B*
*El revenue creciente es una trampa — no salva a una empresa que destruye márgenes.*

---

### FASE 2: Tendencias (solo si pasó Fase 1)

| ID | Métrica | Positivo | Neutral | Negativo |
|---|---|---|---|---|
| D | Revenues | Creciendo | Estable | Cayendo |
| E | Gross Profit Ratio | Mejorando vs 3 años | Sin cambio | Deteriorando |
| F | Cash from Operations | Creciendo | Estable | Cayendo |
| G | Total Debt | Bajando o estable | Sin cambio | Creciendo |
| S | Outstanding Shares | Bajando (buybacks) | Sin cambio | Subiendo >5% acum. (dilución) |

- Deuda creciente con Cash from Ops fuerte y creciente → señal de alerta, **no veto**
  (modelos franchise/capital-intensivos usan deuda estructural — ver Fase 4)

Resultado: Positiva / Neutral / Negativa

### FASE 3: Dividendos

| ID | Métrica | Criterio |
|---|---|---|
| H | Dividends Paid | Pagando vs No paga |
| I | FCF cubre dividendos | FCF > Dividends Paid |
| J | Payout Ratio | Sano 30-60% / Riesgoso >80% |

Resultado: Buena para dividendos / No aplica

### FASE 4: Balance Sheet

| ID | Métrica | Sano | Alerta | Débil |
|---|---|---|---|---|
| K | Total Debt tendencia | Estable/Bajando | Subiendo con FCF fuerte | Subiendo sin FCF |
| L | Cash & Short Term Inv | Reportar | — | — |
| M | Net Debt / EBITDA | < 2.0x | 2.0x–4.0x | > 4.0x |
| N | Current Ratio | > 1.5 | 1.0–1.5 con Cash from Ops positivo | < 1.0 → riesgo inmediato de liquidez |

> **Nota franchise/capital-intensivo:** empresas maduras con ingresos recurrentes predecibles
> (MCD, JNJ, COST) operan con Current Ratio bajo y deuda alta de forma intencional.
> Si Cash from Ops > $1B y creciente, un CR entre 1.0–1.5 es **Alerta**, no Débil.
> Usar Net Debt / EBITDA en lugar de Net Debt / Revenue para estas empresas.
>
> **Current Ratio < 1.0** es cualitativamente diferente: la empresa no puede cubrir sus
> deudas corrientes con activos corrientes. Siempre marcar como Débil, sin excepción.

Resultado: Fuerte / Alerta / Débil

### FASE 5: Gastos

| ID | Métrica | Buena | Mala |
|---|---|---|---|
| O | Cost of Revenue vs Revenues | Ajustado (mejora ratio) | Rígido (empeora ratio) |
| P | R&D Expenses | Invirtiendo | Recortando (si antes había) |
| Q | SG&A % de Revenues | Razonable (<30%) | Alto (>30%) |
| R | CapEx | Invirtiendo | Recortando |

> **Nota R&D:** R&D = 0 en empresas maduras sin producto tecnológico (franquicias, retail)
> es **normal** — no penalizar. Solo penalizar si R&D existía y se recortó agresivamente.

Resultado: Buena / Regular / Mala eficiencia operativa

### FASE 6: Valoración

**Si tiene ganancias (Net Income > 0):**
- PER = Precio / EPS
- Dividend Yield
- Payout sostenible

**Si tiene pérdidas (Net Income < 0):**
- Book Value per Share
- P/B Ratio
- P/S Ratio
- EV/EBITDA

> El precio actual NO viene en los CSVs. Se puede tomar de `accion_precios` si existe,
> o el usuario lo ingresa en el formulario. Si no hay precio, Fase 6 se omite.

---

## Tipos de resultado final

| Tipo | Cuándo | Descripción |
|---|---|---|
| `dividend_aristocrat` | Pasa Fase 1 + paga dividendos + FCF cubre dividendo | Estable, ingresos confiables — balance puede ser Alerta si FCF es fuerte |
| `growth` | Pasa Fase 1 + revenue creciendo + reinvierte | Crecimiento, sin dividendos |
| `recovery_play` | Pasa Fase 1 pero con señales mixtas | Turnaround, especulativo clásico |
| `value_trap` | Pasa Fase 1 pero tendencias malas | Evitar — métricas engañosas |

**Veredictos posibles:** `COMPRAR` / `VIGILAR` / `ESPECULATIVO` / `EVITAR`

| Veredicto | Cuándo |
|---|---|
| COMPRAR | Pasa Fase 1 + Fases 2-5 positivas |
| VIGILAR | Pasa Fase 1 + señales mixtas |
| ESPECULATIVO — expansión | Camino A: ops rentables, FCF negativo solo por CapEx masivo (ej: IREN) |
| ESPECULATIVO — I+D activo | Camino B1: R&D > Revenue, algo de facturación, runway > 2 años (ej: SMR) |
| ESPECULATIVO — pre-revenue | Camino B2: Revenue $0, R&D creciendo, runway > 5 años (ej: OKLO) |
| EVITAR | Falla Fase 1 sin justificación de futuro, deterioro claro (ej: WBA) |

---

## Casos de prueba validados hasta ahora

| Empresa | Tipo real | Veredicto esperado | Validado |
|---|---|---|---|
| SMR (NuScale) | Pionera I+D, pre-revenue | ESPECULATIVO | ✅ CSV leído |
| IREN | Infra expansión, ops rentables | ESPECULATIVO | ✅ CSV leído |
| MCD | Madura, dividendos, buybacks | COMPRAR (dividend_aristocrat) | ✅ CSV validado — ajustes aplicados en Fase 2/4/5 |
| WBA (Walgreens) | Deterioro — crecimiento hueco, márgenes colapsando | EVITAR (Camino D) | ✅ CSV validado — ajuste Camino D + CR<1.0 aplicados |
| OKLO | Pre-revenue puro, I+D, caja masiva | ESPECULATIVO — pre-revenue (B2) | ✅ CSV validado — sub-caso B2 añadido |

---

## Pendiente antes de escribir código

- [ ] Leer CSV de empresa madura sólida (MCD, JNJ, COST) → validar camino COMPRAR
- [ ] Leer CSV de empresa en deterioro real → validar camino EVITAR
- [ ] Leer CSV de OKLO o NNE → confirmar diferencia I+D puro vs IREN
- [ ] Revisar si el umbral 15% de R&D es correcto o necesita ajuste
- [ ] Decidir cómo manejar empresas con datos incompletos (pocos años de historia)
- [ ] Confirmar si ESPECULATIVO necesita sub-tipos visibles en la UI o solo en el detalle

---

## Arquitectura técnica (borrador — no implementar aún)

### Backend (Symfony)

**Nueva entidad:** `AccionAnalisis`

```
accion_analisis
├── id (int, PK)
├── uuid (varchar 36, unique)
├── accion_id (FK → acciones)
├── raw_income    (JSONB)
├── raw_balance   (JSONB)
├── raw_cashflow  (JSONB)
├── metrics       (JSONB)  — métricas normalizadas
├── score         (varchar) — "comprar" | "vigilar" | "especulativo" | "evitar"
├── score_detail  (JSONB)  — resultado por fase
├── investment_type (varchar, nullable)
├── stage         (varchar, nullable) — etapa del retrato financiero
├── price_at_analysis (decimal, nullable)
├── source_files  (JSONB)
├── created_at
└── updated_at
```

**Servicios nuevos:**
```
Infrastructure/Service/
├── CsvParserService.php       — parsea los 3 CSVs al formato interno
├── MetricExtractorService.php — extrae métricas clave del raw
└── ScoringService.php         — aplica las 6 fases + sub-evaluación Fase 1
```

**Controller:** `POST /api/acciones/{uuid}/analisis` — multipart/form-data con 3 archivos

### Frontend (Vue 3)

**Flujo de la pantalla:**
1. Seleccionar acción (dropdown)
2. Drop zone × 3 archivos (income / balance / cash flow)
3. Campo opcional: precio actual (para Fase 6)
4. Botón "Analizar"
5. Resultado: Retrato Financiero + tarjetas por fase + veredicto con semáforo

---

## Notas de diseño

- El Retrato Financiero siempre se muestra, aunque el veredicto sea simple
- ESPECULATIVO no es negativo — es informativo. La UI debe comunicarlo como "requiere convicción"
- EVITAR es el único veredicto que debería tener tono de advertencia real
- Los años disponibles varían por empresa — almacenar como `{año: valor}` en JSONB
- Limpiar números al parsear: quitar comas, `$`, `%`, redondear `.001` a int
- Filas Growth → guardar en raw pero calcular desde valores reales en scoring
- Columna Total → ignorar siempre para scoring
