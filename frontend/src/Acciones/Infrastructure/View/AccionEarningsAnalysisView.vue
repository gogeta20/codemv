<template>
  <div>
    <div class="page__header">
      <div class="header-left">
        <button class="back-btn" @click="$router.back()"><i class="pi pi-arrow-left" /> Volver</button>
        <div>
          <h1 class="page__title">Análisis de earnings</h1>
          <span class="page__subtitle">{{ analysis?.report?.symbol || route.params.uuid }}</span>
        </div>
      </div>
      <div v-if="analysis?.report?.source_url" class="header-right">
        <Button
          label="Fuente oficial"
          icon="pi pi-external-link"
          size="small"
          severity="secondary"
          outlined
          @click="openSource"
        />
      </div>
    </div>

    <div v-if="loading" class="loading-msg"><i class="pi pi-spin pi-spinner" /> Cargando análisis de earnings...</div>
    <div v-else-if="error" class="section-empty">{{ error }}</div>

    <div v-else-if="analysis" class="earnings-layout">
      <section class="detail-section hero-section">
        <div class="hero-top">
          <div class="hero-copy">
            <div class="report-meta">
              <span class="meta-chip">{{ analysis.report.period_label }}</span>
              <span class="meta-chip">{{ analysis.report.form_type }}</span>
              <span class="meta-chip">{{ fmtDate(analysis.report.filing_date) }}</span>
              <span
                v-if="analysis.report.is_stale"
                class="meta-chip meta-chip--stale"
                :title="analysis.report.stale_reason"
              ><i class="pi pi-clock" /> Reporte desactualizado</span>
            </div>
            <h2 class="hero-title">{{ analysis.verdict.label }}</h2>
            <p class="hero-title-sub">Veredicto general del trimestre: si el mercado recibió señales de fortaleza, debilidad o mezcla.</p>
            <p class="hero-summary">{{ analysis.verdict.summary }}</p>
          </div>

          <div class="hero-score" :class="`hero-score--${analysis.verdict.signal}`">
            <div class="score-label">Score</div>
            <div class="score-label-sub">Puntuación global del reporte</div>
            <div v-if="analysis.verdict.signal === 'insufficient_data'" class="score-value score-value--na" title="No hay suficientes métricas extraídas del filing para calcular un score confiable.">N/D</div>
            <div v-else class="score-value">{{ analysis.verdict.score }}</div>
            <div class="score-signal">{{ signalLabel(analysis.verdict.signal) }}</div>
          </div>
        </div>
      </section>

      <section class="detail-section">
        <h2 class="section-title"><i class="pi pi-table" /> KPIs que deciden si el reporte fue bueno o malo</h2>
        <p class="section-subtitle">KPI significa <strong>Key Performance Indicator</strong>: una métrica clave del negocio que ayuda a juzgar el trimestre.</p>
        <div class="kpi-grid">
          <article v-for="kpi in analysis.kpis" :key="kpi.key" class="kpi-card" :class="`kpi-card--${kpi.signal}`">
            <div class="kpi-head">
              <div>
                <span class="kpi-title">{{ kpi.label }}</span>
                <div class="kpi-title-sub">{{ kpi.subtitle || technicalSpanish(kpi.key) }}</div>
              </div>
              <span class="signal-pill" :class="`signal-pill--${kpi.signal}`">{{ signalLabel(kpi.signal) }}</span>
            </div>
            <div v-if="kpi.actual == null" class="kpi-actual kpi-actual--na" title="No se pudo extraer este dato del filing (puede que no esté disponible en el formato de este reporte).">N/D</div>
            <div v-else class="kpi-actual">{{ formatMetric(kpi.actual, kpi.actual_unit) }}</div>
            <div class="kpi-subrows">
              <div v-if="kpi.estimate != null" class="kpi-subrow">
                <span>Estimado</span>
                <strong>{{ formatMetric(kpi.estimate, kpi.actual_unit) }}</strong>
              </div>
              <div v-if="kpi.surprise_pct != null" class="kpi-subrow">
                <span>Sorpresa</span>
                <strong :class="pctClass(kpi.surprise_pct)">{{ signedPct(kpi.surprise_pct) }}</strong>
              </div>
              <div v-if="kpi.yoy_pct != null" class="kpi-subrow">
                <span>YoY</span>
                <strong :class="pctClass(kpi.yoy_pct)">{{ signedPct(kpi.yoy_pct) }}</strong>
              </div>
            </div>
            <p class="kpi-why">{{ kpi.why_it_matters }}</p>
          </article>
        </div>
      </section>

      <section class="dual-section">
        <article class="detail-section reasons-card reasons-card--good">
          <h2 class="section-title"><i class="pi pi-thumbs-up" /> Por qué es bueno</h2>
          <p class="section-subtitle">Factores que empujan la lectura del trimestre hacia una reacción positiva.</p>
          <ul class="reason-list">
            <li v-for="item in analysis.highlights.good" :key="item">{{ item }}</li>
          </ul>
        </article>

        <article class="detail-section reasons-card reasons-card--bad">
          <h2 class="section-title"><i class="pi pi-exclamation-circle" /> Por qué puede ser malo</h2>
          <p class="section-subtitle">Riesgos y matices que el backend no debería esconder aunque el quarter salga fuerte.</p>
          <ul class="reason-list">
            <li v-for="item in analysis.highlights.bad" :key="item">{{ item }}</li>
          </ul>
        </article>
      </section>

      <section class="detail-section">
        <h2 class="section-title"><i class="pi pi-check-square" /> Qué debe cruzar el backend para decidir</h2>
        <p class="section-subtitle">Aquí se ve la lógica esperada del análisis: no basta con leer el titular, hay que cruzar contexto, consenso, guidance y caja.</p>
        <div class="cross-grid">
          <article v-for="item in analysis.cross_checks" :key="item.key" class="cross-card">
            <div class="cross-head">
              <div>
                <span class="cross-title">{{ item.label }}</span>
                <div class="cross-title-sub">{{ item.subtitle || crossSpanish(item.key) }}</div>
              </div>
              <span class="status-chip" :class="`status-chip--${item.status}`">{{ signalLabel(item.status) }}</span>
            </div>
            <p class="cross-detail">{{ item.detail }}</p>
          </article>
        </div>
      </section>

      <section class="detail-section">
        <h2 class="section-title"><i class="pi pi-shield" /> Riesgos que no debe ocultar el análisis</h2>
        <p class="section-subtitle">Incluso un reporte muy fuerte puede seguir teniendo riesgos de sostenibilidad, valoración o dependencia de una narrativa concreta.</p>
        <div class="risk-list">
          <article v-for="risk in analysis.risks" :key="risk.label" class="risk-card">
            <div class="risk-head">
              <div>
                <span class="risk-title">{{ risk.label }}</span>
                <div class="risk-title-sub">{{ risk.subtitle || severityMeaning(risk.severity) }}</div>
              </div>
              <span class="severity-chip" :class="`severity-chip--${risk.severity}`">{{ severityLabel(risk.severity) }}</span>
            </div>
            <p class="risk-detail">{{ risk.detail }}</p>
          </article>
        </div>
      </section>

      <section class="detail-section contract-section">
        <h2 class="section-title"><i class="pi pi-code" /> Contrato esperado del endpoint</h2>
        <p class="section-subtitle">Esto es lo mínimo que el backend debería devolver para que la pantalla pueda justificar por qué el reporte fue bueno o malo.</p>
        <div class="contract-box">
          <p><strong>Endpoint:</strong> <code>{{ analysis.backend_contract.expected_endpoint }}</code></p>
          <p><strong>Mínimo que debería devolver:</strong></p>
          <ul class="reason-list compact">
            <li v-for="field in analysis.backend_contract.minimum_fields" :key="field"><code>{{ field }}</code></li>
          </ul>
        </div>
      </section>

      <section class="detail-section glossary-section">
        <h2 class="section-title"><i class="pi pi-book" /> Glosario rápido</h2>
        <p class="section-subtitle">Pie de página con los términos más usados en reportes trimestrales. La idea es que puedas leer la pantalla sin tener que traducir mentalmente cada concepto.</p>
        <div class="glossary-grid">
          <article v-for="item in glossary" :key="item.term" class="glossary-card">
            <div class="glossary-term">{{ item.term }}</div>
            <div class="glossary-subterm">{{ item.subtitle }}</div>
            <p class="glossary-def">{{ item.definition }}</p>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import Button from 'primevue/button'
import { GetEarningsAnalysisUseCase } from '@/Acciones/Application/UseCase/GetEarningsAnalysis/GetEarningsAnalysisUseCase'

const route = useRoute()

const loading = ref(true)
const error = ref(null)
const analysis = ref(null)

const glossary = [
  {
    term: 'Revenue',
    subtitle: 'Ingresos / ventas totales',
    definition: 'Dinero que entra por vender productos o servicios. Es la línea superior del negocio.'
  },
  {
    term: 'EPS',
    subtitle: 'Beneficio por acción',
    definition: 'Ganancia atribuible a cada acción. Es una de las cifras que más mira el mercado tras resultados.'
  },
  {
    term: 'Guidance',
    subtitle: 'Previsión oficial de la empresa',
    definition: 'Estimación que da la directiva sobre próximos trimestres o el año completo. Si sube, suele ser señal positiva.'
  },
  {
    term: 'YoY',
    subtitle: 'Comparación interanual',
    definition: 'Year over Year. Compara una cifra con el mismo trimestre del año anterior para ver el ritmo real de crecimiento.'
  },
  {
    term: 'Beat / Miss',
    subtitle: 'Bate o falla el consenso',
    definition: 'Si la empresa sale mejor de lo esperado por analistas, bate. Si sale peor, falla.'
  },
  {
    term: 'Operating Margin',
    subtitle: 'Margen operativo',
    definition: 'Porcentaje de ingresos que queda tras los gastos operativos. Ayuda a medir la calidad del crecimiento.'
  },
  {
    term: 'Free Cash Flow',
    subtitle: 'Flujo de caja libre',
    definition: 'Caja que queda después de operar e invertir. Es clave para saber si el negocio genera dinero real.'
  },
  {
    term: 'Valuation',
    subtitle: 'Valoración bursátil',
    definition: 'Cuánto está pagando el mercado por la empresa. Un reporte bueno no siempre significa que la acción sea buena compra.'
  }
]

onMounted(async () => {
  try {
    analysis.value = await GetEarningsAnalysisUseCase(route.params.uuid)
  } catch (e) {
    error.value = e.response?.data?.error ?? e.message ?? 'No se pudo cargar el análisis de earnings.'
  } finally {
    loading.value = false
  }
})

function openSource() {
  window.open(analysis.value.report.source_url, '_blank', 'noopener,noreferrer')
}

function fmtDate(value) {
  if (!value) return '—'
  return new Date(`${value}T00:00:00`).toLocaleDateString('es-ES')
}

function signalLabel(signal) {
  if (signal === 'good') return 'Bueno'
  if (signal === 'bad') return 'Malo'
  if (signal === 'warning') return 'Vigilar'
  if (signal === 'insufficient_data') return 'Sin datos suficientes'
  return 'Mixto'
}

function severityLabel(severity) {
  if (severity === 'high') return 'Alta'
  if (severity === 'medium') return 'Media'
  return 'Baja'
}

function severityMeaning(severity) {
  if (severity === 'high') return 'Riesgo importante que puede cambiar la tesis'
  if (severity === 'medium') return 'Riesgo real, pero no necesariamente rompe la tesis'
  return 'Riesgo secundario o de seguimiento'
}

function technicalSpanish(key) {
  const labels = {
    revenue: 'Ingresos del trimestre',
    eps: 'Ganancia por acción para el accionista',
    operating_margin: 'Rentabilidad operativa sobre ventas',
    free_cash_flow: 'Caja libre generada por el negocio',
    guidance_revenue: 'Previsión de ingresos futura',
    valuation_risk: 'Riesgo de que la acción ya esté demasiado cara'
  }
  return labels[key] ?? 'Métrica clave para juzgar el trimestre'
}

function crossSpanish(key) {
  const labels = {
    beat_vs_expectations: 'Comparación contra lo que esperaba Wall Street',
    growth_quality: 'Si el crecimiento viene con margen y caja, o solo con volumen',
    guidance_direction: 'Hacia dónde apunta la propia directiva',
    cash_conversion: 'Si las ventas se convierten en caja real',
    balance_sheet: 'Fortaleza financiera y liquidez',
    valuation_and_expectations: 'Si el precio ya exigía demasiado antes del reporte'
  }
  return labels[key] ?? 'Cruce de contexto relevante para interpretar resultados'
}

function formatMetric(value, unit) {
  if (value == null) return '—'
  if (unit === 'MUSD') return `$${Number(value).toLocaleString('es-ES', { maximumFractionDigits: 2 })}M`
  if (unit === '%') return `${Number(value).toLocaleString('es-ES', { maximumFractionDigits: 2 })}%`
  if (unit === 'USD') return `$${Number(value).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
  if (unit === 'flag') return value ? 'Sí' : 'No'
  return Number(value).toLocaleString('es-ES', { maximumFractionDigits: 2 })
}

function signedPct(value) {
  const n = Number(value)
  return `${n >= 0 ? '+' : ''}${n.toLocaleString('es-ES', { maximumFractionDigits: 2 })}%`
}

function pctClass(value) {
  return Number(value) >= 0 ? 'pct-up' : 'pct-down'
}
</script>

<style scoped>
.earnings-layout {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.hero-section {
  background: linear-gradient(135deg, #0f172a 0%, #132238 48%, #1f3b4d 100%);
  color: #f8fafc;
  border: 1px solid rgba(148, 163, 184, 0.18);
}

.hero-top {
  display: flex;
  justify-content: space-between;
  gap: 1.5rem;
  align-items: stretch;
}

.hero-copy {
  flex: 1;
  min-width: 0;
}

.report-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.9rem;
}

.meta-chip {
  padding: 0.32rem 0.65rem;
  border-radius: 999px;
  font-size: 0.78rem;
  background: rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
}

.meta-chip--stale {
  background: rgba(251, 191, 36, 0.14);
  color: #fbbf24;
  cursor: help;
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
}

.hero-title {
  margin: 0 0 0.45rem;
  font-size: 1.9rem;
  line-height: 1.1;
}

.hero-title-sub,
.section-subtitle,
.kpi-title-sub,
.cross-title-sub,
.risk-title-sub,
.score-label-sub,
.glossary-subterm {
  color: var(--tokyo-fg-dim);
  font-size: 0.88rem;
  line-height: 1.45;
}

.hero-title-sub {
  color: #cbd5e1;
  margin: 0 0 0.7rem;
}

.section-subtitle {
  margin: -0.35rem 0 1rem;
}

.hero-summary {
  margin: 0;
  color: #d8e4ee;
  max-width: 70ch;
  line-height: 1.6;
}

.hero-score {
  width: 210px;
  padding: 1rem;
  border-radius: 20px;
  align-self: flex-start;
  text-align: center;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.06);
}

.hero-score--good {
  box-shadow: inset 0 0 0 1px rgba(74, 222, 128, 0.24);
}

.hero-score--bad {
  box-shadow: inset 0 0 0 1px rgba(248, 113, 113, 0.24);
}

.hero-score--warning,
.hero-score--mixed {
  box-shadow: inset 0 0 0 1px rgba(251, 191, 36, 0.24);
}

.hero-score--insufficient_data {
  box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.24);
}

.score-label {
  color: #cbd5e1;
  font-size: 0.82rem;
}

.score-label-sub {
  color: #cbd5e1;
  margin-top: 0.2rem;
}

.score-value {
  font-size: 3rem;
  font-weight: 800;
  line-height: 1;
  margin: 0.45rem 0;
}

.score-value--na {
  color: #cbd5e1;
  cursor: help;
}

.score-signal {
  font-size: 0.92rem;
  color: #e2e8f0;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.kpi-card {
  border: 1px solid var(--tokyo-border);
  border-radius: 18px;
  padding: 1rem;
  background: var(--tokyo-bg-soft);
}

.kpi-card--good {
  border-color: rgba(34, 197, 94, 0.35);
}

.kpi-card--bad {
  border-color: rgba(239, 68, 68, 0.28);
}

.kpi-card--warning {
  border-color: rgba(245, 158, 11, 0.28);
  border-style: dashed;
}

.kpi-head,
.cross-head,
.risk-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.75rem;
}

.kpi-title,
.cross-title,
.risk-title,
.glossary-term {
  font-weight: 700;
  color: var(--tokyo-fg);
}

.kpi-title-sub,
.cross-title-sub,
.risk-title-sub {
  margin-top: 0.22rem;
}

.signal-pill,
.status-chip,
.severity-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.22rem 0.58rem;
  border-radius: 999px;
  font-size: 0.74rem;
  font-weight: 700;
  white-space: nowrap;
}

.signal-pill--good,
.status-chip--good {
  background: rgba(34, 197, 94, 0.14);
  color: #166534;
}

.signal-pill--bad,
.status-chip--bad {
  background: rgba(239, 68, 68, 0.14);
  color: #991b1b;
}

.signal-pill--warning,
.status-chip--warning {
  background: rgba(245, 158, 11, 0.14);
  color: #92400e;
}

.signal-pill--mixed,
.status-chip--mixed {
  background: rgba(148, 163, 184, 0.14);
  color: #475569;
}

.severity-chip--high {
  background: rgba(239, 68, 68, 0.14);
  color: #991b1b;
}

.severity-chip--medium {
  background: rgba(245, 158, 11, 0.14);
  color: #92400e;
}

.severity-chip--low {
  background: rgba(59, 130, 246, 0.14);
  color: #1d4ed8;
}

.kpi-actual {
  margin: 0.85rem 0 0.75rem;
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--tokyo-fg-strong);
}

.kpi-actual--na {
  color: var(--tokyo-fg-dim);
  cursor: help;
}

.kpi-subrows {
  display: flex;
  flex-direction: column;
  gap: 0.38rem;
  margin-bottom: 0.85rem;
}

.kpi-subrow {
  display: flex;
  justify-content: space-between;
  gap: 0.8rem;
  font-size: 0.9rem;
  color: var(--tokyo-fg-dim);
}

.kpi-why,
.cross-detail,
.risk-detail,
.glossary-def {
  margin: 0;
  line-height: 1.55;
  color: var(--tokyo-fg-dim);
}

.pct-up {
  color: #16a34a;
}

.pct-down {
  color: #dc2626;
}

.dual-section {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.25rem;
}

.reasons-card--good {
  border-color: rgba(34, 197, 94, 0.28);
}

.reasons-card--bad {
  border-color: rgba(239, 68, 68, 0.22);
}

.reason-list {
  margin: 0;
  padding-left: 1.1rem;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  color: var(--tokyo-fg);
  line-height: 1.55;
}

.reason-list.compact {
  gap: 0.4rem;
}

.cross-grid,
.risk-list,
.glossary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
}

.cross-card,
.risk-card,
.contract-box,
.glossary-card {
  border: 1px solid var(--tokyo-border);
  border-radius: 18px;
  padding: 1rem;
  background: var(--tokyo-bg-soft);
}

.glossary-subterm {
  margin: 0.18rem 0 0.5rem;
}

.contract-box p {
  margin: 0 0 0.75rem;
  color: var(--tokyo-fg);
}

.contract-box code {
  font-size: 0.88rem;
}

@media (max-width: 900px) {
  .hero-top,
  .dual-section {
    grid-template-columns: 1fr;
    display: grid;
  }

  .hero-score {
    width: 100%;
  }
}
</style>
