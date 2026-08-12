<template>
  <div>
    <!-- Header -->
    <div class="page__header">
      <div class="header-left">
        <button class="back-btn" @click="$router.back()"><i class="pi pi-arrow-left" /> Volver</button>
        <div>
          <h1 class="page__title">Análisis fundamental</h1>
          <span class="page__subtitle">{{ symbol }}</span>
        </div>
      </div>
    </div>

    <div class="analisis-layout">

      <!-- FORMULARIO DE CARGA -->
      <section class="detail-section">
        <h2 class="section-title"><i class="pi pi-upload" /> Cargar estados financieros</h2>
        <div class="upload-grid">
          <div class="upload-field">
            <label class="upload-label">Income Statement (CSV)</label>
            <div
              class="drop-zone"
              :class="{ 'has-file': files.income, dragover: drag.income }"
              @dragover.prevent="drag.income = true"
              @dragleave="drag.income = false"
              @drop.prevent="onDrop($event, 'income')"
              @click="$refs.incomeInput.click()"
            >
              <i class="pi" :class="files.income ? 'pi-check-circle' : 'pi-file-excel'" />
              <span>{{ files.income ? files.income.name : 'Arrastra o haz clic' }}</span>
            </div>
            <input ref="incomeInput" type="file" accept=".csv" class="hidden-input" @change="onFile($event, 'income')" />
          </div>

          <div class="upload-field">
            <label class="upload-label">Balance Sheet (CSV)</label>
            <div
              class="drop-zone"
              :class="{ 'has-file': files.balance, dragover: drag.balance }"
              @dragover.prevent="drag.balance = true"
              @dragleave="drag.balance = false"
              @drop.prevent="onDrop($event, 'balance')"
              @click="$refs.balanceInput.click()"
            >
              <i class="pi" :class="files.balance ? 'pi-check-circle' : 'pi-file-excel'" />
              <span>{{ files.balance ? files.balance.name : 'Arrastra o haz clic' }}</span>
            </div>
            <input ref="balanceInput" type="file" accept=".csv" class="hidden-input" @change="onFile($event, 'balance')" />
          </div>

          <div class="upload-field">
            <label class="upload-label">Cash Flow Statement (CSV)</label>
            <div
              class="drop-zone"
              :class="{ 'has-file': files.cashflow, dragover: drag.cashflow }"
              @dragover.prevent="drag.cashflow = true"
              @dragleave="drag.cashflow = false"
              @drop.prevent="onDrop($event, 'cashflow')"
              @click="$refs.cashflowInput.click()"
            >
              <i class="pi" :class="files.cashflow ? 'pi-check-circle' : 'pi-file-excel'" />
              <span>{{ files.cashflow ? files.cashflow.name : 'Arrastra o haz clic' }}</span>
            </div>
            <input ref="cashflowInput" type="file" accept=".csv" class="hidden-input" @change="onFile($event, 'cashflow')" />
          </div>
        </div>

        <div class="upload-footer">
          <div class="price-field">
            <label class="upload-label">Precio actual (opcional)</label>
            <input v-model="price" type="number" step="0.01" placeholder="ej. 185.50" class="price-input" />
          </div>
          <Button
            label="Analizar"
            icon="pi pi-bolt"
            :loading="uploading"
            :disabled="!canUpload"
            @click="runAnalisis"
          />
        </div>

        <div v-if="uploadError" class="upload-error">
          <i class="pi pi-exclamation-triangle" /> {{ uploadError }}
        </div>
      </section>

      <!-- RESULTADOS -->
      <div v-if="loadingPrev" class="loading-msg"><i class="pi pi-spin pi-spinner" /> Cargando análisis previo...</div>

      <template v-else-if="analisis">

        <!-- VEREDICTO -->
        <section class="detail-section">
          <h2 class="section-title"><i class="pi pi-flag" /> Veredicto</h2>
          <div class="veredicto-card" :class="`veredicto--${analisis.score}`">
            <div class="semaforo" :class="`semaforo--${analisis.score}`">
              <i class="pi" :class="scoreIcon(analisis.score)" />
            </div>
            <div class="veredicto-body">
              <div class="veredicto-titulo">{{ scoreLabel(analisis.score) }}</div>
              <div class="veredicto-tags">
                <span v-if="analisis.investment_type" class="badge badge--type">{{ tipoLabel(analisis.investment_type) }}</span>
                <span v-if="analisis.speculative_type" class="badge badge--spec">{{ especulativoLabel(analisis.speculative_type) }}</span>
                <span v-if="analisis.stage" class="badge badge--stage">{{ stageLabel(analisis.stage) }}</span>
                <span v-if="analisis.price_at_analysis" class="badge badge--price">Mercado ${{ analisis.price_at_analysis.toFixed(2) }}</span>
                <span
                  v-if="analisis.fair_value"
                  class="badge"
                  :class="analisis.fair_value_method === 'net_cash_floor' ? 'badge--fair-value-floor' : 'badge--fair-value'"
                  :title="analisis.fair_value_detail?.explanation"
                >
                  <i v-if="analisis.fair_value_method === 'net_cash_floor'" class="pi pi-shield" />
                  {{ fairValueMethodLabel(analisis.fair_value_method) }}: ${{ analisis.fair_value.toFixed(2) }}
                </span>
                <span v-else-if="analisis.fair_value_method === 'not_available'" class="badge badge--fair-value-na" :title="analisis.fair_value_detail?.explanation">
                  Sin valoración fundamental confiable
                </span>
              </div>
              <div class="veredicto-fecha">Analizado el {{ fmtDate(analisis.created_at) }}</div>
            </div>
          </div>
        </section>

        <!-- RETRATO FINANCIERO -->
        <section class="detail-section">
          <h2 class="section-title"><i class="pi pi-align-left" /> Retrato financiero</h2>
          <div class="portrait-box">{{ analisis.portrait }}</div>
        </section>

        <!-- DETALLE POR FASES -->
        <section class="detail-section">
          <h2 class="section-title"><i class="pi pi-list-check" /> Detalle del análisis</h2>
          <div class="fases-layout">

            <!-- Fase 1 -->
            <div class="fase-card">
              <div class="fase-header">Fase 1 — Filtro de calidad</div>
              <div class="fase-body">
                <div class="check-row" :class="analisis.score_detail.fase1.fcf_ok ? 'ok' : 'ko'">
                  <i class="pi" :class="analisis.score_detail.fase1.fcf_ok ? 'pi-check' : 'pi-times'" />
                  <span>Flujo de caja libre (FCF)</span>
                </div>
                <div class="check-row" :class="analisis.score_detail.fase1.ni_ok ? 'ok' : 'ko'">
                  <i class="pi" :class="analisis.score_detail.fase1.ni_ok ? 'pi-check' : 'pi-times'" />
                  <span>Beneficio neto</span>
                </div>
                <div class="check-row" :class="analisis.score_detail.fase1.op_ok ? 'ok' : 'ko'">
                  <i class="pi" :class="analisis.score_detail.fase1.op_ok ? 'pi-check' : 'pi-times'" />
                  <span>Cash operativo</span>
                </div>
                <div v-if="analisis.score_detail.camino" class="camino-chip">
                  Camino {{ analisis.score_detail.camino }}
                </div>
              </div>
            </div>

            <!-- Fase 2 -->
            <div v-if="analisis.score_detail.fase2" class="fase-card">
              <div class="fase-header">Fase 2 — Tendencias de crecimiento</div>
              <div class="fase-body metrics-grid">
                <div v-for="(val, key) in analisis.score_detail.fase2" :key="key" class="metric-row">
                  <span class="metric-label">{{ fase2Label(key) }}</span>
                  <span class="metric-val" :class="trendClass(val.valor)">{{ trendLabel(val.valor) }}</span>
                  <span class="metric-pts">{{ val.puntos }}p</span>
                </div>
              </div>
            </div>

            <!-- Fase 3 -->
            <div v-if="analisis.score_detail.fase3" class="fase-card">
              <div class="fase-header">Fase 3 — Dividendos</div>
              <div class="fase-body">
                <div class="metric-row">
                  <span class="metric-label">Dividendo</span>
                  <span class="metric-val" :class="trendClass(analisis.score_detail.fase3.dividend?.valor)">
                    {{ dividendLabel(analisis.score_detail.fase3.dividend?.valor) }}
                  </span>
                  <span class="metric-pts">{{ analisis.score_detail.fase3.dividend?.puntos }}p</span>
                </div>
              </div>
            </div>

            <!-- Fase 4 -->
            <div v-if="analisis.score_detail.fase4" class="fase-card">
              <div class="fase-header">Fase 4 — Balance y deuda</div>
              <div class="fase-body metrics-grid">
                <div class="metric-row">
                  <span class="metric-label">Ratio corriente (CR)</span>
                  <span class="metric-val" :class="nivelClass(analisis.score_detail.fase4.current_ratio?.nivel)">
                    {{ fmtNum(analisis.score_detail.fase4.current_ratio?.valor) }}
                  </span>
                  <span class="metric-badge" :class="nivelClass(analisis.score_detail.fase4.current_ratio?.nivel)">
                    {{ nivelLabel(analisis.score_detail.fase4.current_ratio?.nivel) }}
                  </span>
                </div>
                <div class="metric-row">
                  <span class="metric-label">NDE / EBITDA</span>
                  <span class="metric-val" :class="nivelClass(analisis.score_detail.fase4.nde_ebitda?.nivel)">
                    {{ fmtNum(analisis.score_detail.fase4.nde_ebitda?.valor) }}x
                  </span>
                  <span class="metric-badge" :class="nivelClass(analisis.score_detail.fase4.nde_ebitda?.nivel)">
                    {{ nivelLabel(analisis.score_detail.fase4.nde_ebitda?.nivel) }}
                  </span>
                </div>
                <div class="metric-row">
                  <span class="metric-label">Tendencia deuda</span>
                  <span class="metric-val" :class="nivelClass(analisis.score_detail.fase4.debt_trend?.nivel)">
                    {{ trendLabel(analisis.score_detail.fase4.debt_trend?.valor) }}
                  </span>
                  <span class="metric-badge" :class="nivelClass(analisis.score_detail.fase4.debt_trend?.nivel)">
                    {{ nivelLabel(analisis.score_detail.fase4.debt_trend?.nivel) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Fase 5 -->
            <div v-if="analisis.score_detail.fase5" class="fase-card">
              <div class="fase-header">Fase 5 — Eficiencia de gastos</div>
              <div class="fase-body metrics-grid">
                <div v-for="(val, key) in analisis.score_detail.fase5" :key="key" class="metric-row">
                  <span class="metric-label">{{ fase5Label(key) }}</span>
                  <span class="metric-val" :class="trendClass(val.valor)">{{ trendLabel(val.valor) }}</span>
                  <span class="metric-pts">{{ val.puntos }}p</span>
                </div>
              </div>
            </div>

            <!-- Puntuación total -->
            <div v-if="analisis.score_detail.total_puntos !== undefined" class="fase-card fase-card--total">
              <div class="fase-header">Puntuación total</div>
              <div class="fase-body">
                <div class="total-score">{{ analisis.score_detail.total_puntos }} <span class="total-max">puntos</span></div>
              </div>
            </div>

          </div>
        </section>

      </template>

      <div v-else-if="!loadingPrev" class="no-analisis">
        <i class="pi pi-chart-bar" />
        <span>Sin análisis previo. Carga los 3 CSVs para generar el primer análisis.</span>
      </div>

      <!-- GLOSARIO -->
      <section class="detail-section glosario-section">
        <h2 class="section-title"><i class="pi pi-book" /> Glosario de términos</h2>
        <div class="glosario-grid">
          <div class="glosario-grupo">
            <div class="glosario-grupo-titulo">Rentabilidad</div>
            <div class="glosario-item"><span class="glosario-term">FCF</span><span class="glosario-def">Free Cash Flow — Flujo de caja libre. Dinero que genera la empresa después de pagar sus inversiones en activos fijos (CapEx).</span></div>
            <div class="glosario-item"><span class="glosario-term">NI</span><span class="glosario-def">Net Income — Beneficio neto. Ganancia final después de impuestos, intereses y todos los gastos.</span></div>
            <div class="glosario-item"><span class="glosario-term">Gross Margin</span><span class="glosario-def">Margen bruto. Porcentaje de ingresos que queda después de restar el coste directo de los productos o servicios vendidos.</span></div>
            <div class="glosario-item"><span class="glosario-term">EBITDA</span><span class="glosario-def">Earnings Before Interest, Taxes, Depreciation and Amortization — Beneficio antes de intereses, impuestos, depreciación y amortización. Proxy del flujo operativo.</span></div>
            <div class="glosario-item"><span class="glosario-term">TTM</span><span class="glosario-def">Trailing Twelve Months — Últimos 12 meses. Se usa para tener la cifra más reciente sin esperar al cierre del año fiscal.</span></div>
          </div>
          <div class="glosario-grupo">
            <div class="glosario-grupo-titulo">Balance y deuda</div>
            <div class="glosario-item"><span class="glosario-term">CR</span><span class="glosario-def">Current Ratio — Ratio corriente. Activo corriente dividido entre pasivo corriente. Mide si la empresa puede pagar sus deudas a corto plazo. Por debajo de 1 es señal de riesgo.</span></div>
            <div class="glosario-item"><span class="glosario-term">NDE</span><span class="glosario-def">Net Debt — Deuda neta. Deuda total menos el efectivo disponible. Indica cuánta deuda real carga la empresa descontando su caja.</span></div>
            <div class="glosario-item"><span class="glosario-term">NDE / EBITDA</span><span class="glosario-def">Ratio de apalancamiento. Indica cuántos años de EBITDA necesitaría la empresa para pagar su deuda neta. Por encima de 4x es zona de riesgo.</span></div>
            <div class="glosario-item"><span class="glosario-term">Runway</span><span class="glosario-def">Pista de aterrizaje financiero. Meses o años que le quedan a la empresa para operar con su caja actual, al ritmo de quema de efectivo actual.</span></div>
          </div>
          <div class="glosario-grupo">
            <div class="glosario-grupo-titulo">Gastos e inversión</div>
            <div class="glosario-item"><span class="glosario-term">SG&A</span><span class="glosario-def">Selling, General & Administrative — Gastos de ventas, generales y administrativos. Costes indirectos: salarios de oficina, marketing, alquileres, etc.</span></div>
            <div class="glosario-item"><span class="glosario-term">R&D</span><span class="glosario-def">Research & Development — Investigación y desarrollo. Inversión en crear nuevos productos o mejorar los existentes. Clave en empresas tecnológicas y farmacéuticas.</span></div>
            <div class="glosario-item"><span class="glosario-term">CapEx</span><span class="glosario-def">Capital Expenditures — Gasto de capital. Dinero invertido en activos físicos: maquinaria, instalaciones, infraestructura. Refleja si la empresa está creciendo o manteniendo.</span></div>
          </div>
          <div class="glosario-grupo">
            <div class="glosario-grupo-titulo">Accionistas</div>
            <div class="glosario-item"><span class="glosario-term">Dilución</span><span class="glosario-def">Aumento en el número de acciones en circulación. Cuando la empresa emite nuevas acciones (para captar capital), cada acción existente vale proporcionalmente menos.</span></div>
            <div class="glosario-item"><span class="glosario-term">Dividend Aristocrat</span><span class="glosario-def">Aristócrata del dividendo. Empresa que ha aumentado su dividendo de forma consecutiva durante muchos años. Señal de solidez y compromiso con el accionista.</span></div>
            <div class="glosario-item"><span class="glosario-term">Recovery Play</span><span class="glosario-def">Apuesta de recuperación. Empresa que está en un ciclo negativo pero con potencial de volver a la rentabilidad si ejecuta bien su plan de reestructuración.</span></div>
            <div class="glosario-item"><span class="glosario-term">Value Trap</span><span class="glosario-def">Trampa de valor. Empresa que parece barata por sus múltiplos pero cuyo negocio está en declive estructural y nunca se recupera.</span></div>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { GetAnalisisUseCase } from '@/Acciones/Application/UseCase/GetAnalisis/GetAnalisisUseCase'
import { UploadAnalisisUseCase } from '@/Acciones/Application/UseCase/UploadAnalisis/UploadAnalisisUseCase'

const route  = useRoute()
const toast  = useToast()

const symbol      = ref(route.params.uuid)
const analisis    = ref(null)
const loadingPrev = ref(true)
const uploading   = ref(false)
const uploadError = ref(null)
const price       = ref('')

const files = ref({ income: null, balance: null, cashflow: null })
const drag  = ref({ income: false, balance: false, cashflow: false })

const canUpload = computed(() => files.value.income && files.value.balance && files.value.cashflow)

function onFile(event, key) {
  files.value[key] = event.target.files[0] ?? null
}

function onDrop(event, key) {
  drag.value[key] = false
  files.value[key] = event.dataTransfer.files[0] ?? null
}

async function runAnalisis() {
  uploading.value  = true
  uploadError.value = null
  try {
    analisis.value = await UploadAnalisisUseCase(
      route.params.uuid,
      files.value.income,
      files.value.balance,
      files.value.cashflow,
      price.value || null,
    )
    toast.add({ severity: 'success', summary: 'Análisis completado', life: 2500 })
  } catch (e) {
    uploadError.value = e.response?.data?.error ?? e.message ?? 'Error al analizar'
  } finally {
    uploading.value = false
  }
}

onMounted(async () => {
  try {
    analisis.value = await GetAnalisisUseCase(route.params.uuid)
  } catch {
    analisis.value = null
  } finally {
    loadingPrev.value = false
  }
})

/* ── Labels ── */
function scoreLabel(s) {
  return { comprar: 'Comprar', vigilar: 'Vigilar', especulativo: 'Especulativo', evitar: 'Evitar' }[s] ?? s
}
function scoreIcon(s) {
  return { comprar: 'pi-check-circle', vigilar: 'pi-eye', especulativo: 'pi-bolt', evitar: 'pi-times-circle' }[s] ?? 'pi-circle'
}
function fairValueMethodLabel(method) {
  return {
    graham_number: 'Número de Graham',
    dividend_discount: 'Descuento de dividendos',
    price_to_sales: 'Múltiplo de ventas',
    net_cash_floor: 'Piso de caja neta',
  }[method] ?? method
}
function tipoLabel(t) {
  return {
    dividend_aristocrat: 'Aristócrata del dividendo',
    growth:              'Crecimiento',
    recovery_play:       'Recuperación',
    value_trap:          'Trampa de valor',
  }[t] ?? t
}
function especulativoLabel(t) {
  return {
    expansion:  'Expansión agresiva',
    id_activo:  'I+D activo',
    pre_revenue:'Pre-ingresos',
  }[t] ?? t
}
function stageLabel(s) {
  return {
    early_stage: 'Etapa temprana',
    growth:      'Crecimiento',
    mature:      'Madura',
    declining:   'En declive',
  }[s] ?? s
}
function trendLabel(v) {
  return {
    growing:      'Creciendo',
    stable:       'Estable',
    declining:    'Descendiendo',
    improving:    'Mejorando',
    deteriorating:'Deteriorando',
    growing_div:  'Creciendo',
    stable_div:   'Estable',
    cut:          'Recortado',
    none:         'Sin dividendo',
    diluting:     'Diluyendo',
    investing:    'Invirtiendo',
    cutting:      'Reduciendo',
  }[v] ?? v ?? '—'
}
function dividendLabel(v) {
  return {
    growing: 'Creciendo', stable: 'Estable', cut: 'Recortado', none: 'Sin dividendo',
  }[v] ?? v ?? '—'
}
function fase2Label(k) {
  return {
    revenue_trend:          'Ingresos',
    gross_margin_trend:     'Margen bruto',
    operating_income_trend: 'Ingreso operativo',
    net_income_trend:       'Beneficio neto',
    shares_trend:           'Dilución accionarial',
  }[k] ?? k
}
function fase5Label(k) {
  return {
    sga_trend:          'Gastos SG&A',
    rd_trend:           'I+D',
    capex_trend:        'CapEx',
    ebitda_margin_trend:'Margen EBITDA',
  }[k] ?? k
}
function nivelLabel(n) {
  return { ok: 'OK', alerta: 'Alerta', debil: 'Débil' }[n] ?? n ?? '—'
}
function trendClass(v) {
  if (['growing', 'improving', 'investing'].includes(v)) return 'trend--up'
  if (['declining', 'deteriorating', 'cut', 'diluting', 'cutting'].includes(v)) return 'trend--down'
  return 'trend--neutral'
}
function nivelClass(n) {
  if (n === 'ok')    return 'nivel--ok'
  if (n === 'alerta') return 'nivel--alerta'
  if (n === 'debil') return 'nivel--debil'
  return ''
}
function fmtNum(v) {
  if (v == null) return '—'
  return Number(v).toFixed(2)
}
function fmtDate(s) {
  if (!s) return '—'
  return s.slice(0, 10)
}
</script>

<style scoped>
.header-left { display: flex; align-items: center; gap: 1.25rem; }
.back-btn {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: none; border: none; cursor: pointer;
  color: var(--tokyo-fg-dim); font-size: 0.85rem; padding: 4px 0;
}
.back-btn:hover { color: var(--tokyo-fg); }

.analisis-layout { display: flex; flex-direction: column; gap: 2rem; }
.detail-section  { display: flex; flex-direction: column; gap: 1rem; }
.section-title {
  font-size: 0.85rem; font-weight: 600; color: var(--tokyo-fg-dim);
  text-transform: uppercase; letter-spacing: 0.05em;
  display: flex; align-items: center; gap: 0.5rem;
  border-bottom: 1px solid var(--tokyo-bg-tertiary); padding-bottom: 0.5rem;
}
.loading-msg { padding: 3rem; text-align: center; color: var(--tokyo-fg-dim); }

/* ── Upload ── */
.upload-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}
@media (max-width: 700px) { .upload-grid { grid-template-columns: 1fr; } }

.upload-field { display: flex; flex-direction: column; gap: 0.4rem; }
.upload-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--tokyo-fg-dim); }

.drop-zone {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 0.5rem; padding: 1.5rem 1rem;
  border: 2px dashed var(--tokyo-bg-tertiary);
  border-radius: 10px; cursor: pointer;
  color: var(--tokyo-fg-dim); font-size: 0.82rem;
  transition: border-color 0.15s, background 0.15s;
}
.drop-zone i { font-size: 1.5rem; }
.drop-zone:hover, .drop-zone.dragover { border-color: var(--tokyo-blue); background: var(--tokyo-bg-secondary); }
.drop-zone.has-file { border-color: #4ade80; color: #4ade80; }
.drop-zone.has-file i { color: #4ade80; }
.hidden-input { display: none; }

.upload-footer { display: flex; align-items: flex-end; gap: 1.5rem; margin-top: 0.5rem; }
.price-field { display: flex; flex-direction: column; gap: 0.4rem; }
.price-input {
  background: var(--tokyo-bg-secondary); border: 1px solid var(--tokyo-bg-tertiary);
  border-radius: 6px; padding: 0.5rem 0.75rem; color: var(--tokyo-fg);
  font-size: 0.9rem; width: 140px;
}
.price-input:focus { outline: none; border-color: var(--tokyo-blue); }
.upload-error {
  display: flex; align-items: center; gap: 0.5rem;
  color: #f87171; font-size: 0.85rem;
  background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.3);
  border-radius: 6px; padding: 0.6rem 0.9rem;
}

/* ── Veredicto ── */
.veredicto-card {
  display: flex; align-items: center; gap: 1.25rem;
  border-radius: 12px; padding: 1.25rem 1.5rem;
  border: 1px solid var(--tokyo-bg-tertiary);
  background: var(--tokyo-bg-secondary);
}
.veredicto--comprar      { border-left: 4px solid #4ade80; }
.veredicto--vigilar      { border-left: 4px solid #fbbf24; }
.veredicto--especulativo { border-left: 4px solid var(--tokyo-cyan, #7dcfff); }
.veredicto--evitar       { border-left: 4px solid #f87171; }

.semaforo {
  width: 56px; height: 56px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.6rem; flex-shrink: 0;
}
.semaforo--comprar      { background: rgba(74, 222, 128, 0.15); color: #4ade80; }
.semaforo--vigilar      { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
.semaforo--especulativo { background: rgba(125, 207, 255, 0.15); color: var(--tokyo-cyan); }
.semaforo--evitar       { background: rgba(248, 113, 113, 0.15); color: #f87171; }

.veredicto-body { display: flex; flex-direction: column; gap: 0.5rem; }
.veredicto-titulo { font-size: 1.6rem; font-weight: 700; }
.veredicto-tags { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.veredicto-fecha { font-size: 0.75rem; color: var(--tokyo-fg-dim); }

.badge {
  font-size: 0.72rem; font-weight: 600; border-radius: 4px;
  padding: 2px 7px; border: 1px solid;
}
.badge--type  { color: var(--tokyo-blue);   border-color: var(--tokyo-blue); }
.badge--spec  { color: var(--tokyo-cyan);    border-color: var(--tokyo-cyan); }
.badge--stage { color: var(--tokyo-fg-dim); border-color: var(--tokyo-bg-tertiary); }
.badge--price { color: #fbbf24;              border-color: #fbbf24; }
.badge--fair-value    { color: #4ade80; border-color: #4ade80; cursor: help; }
.badge--fair-value-na { color: var(--tokyo-fg-dim); border-color: var(--tokyo-bg-tertiary); cursor: help; }
.badge--fair-value-floor { color: var(--tokyo-fg-dim); border-color: var(--tokyo-bg-tertiary); border-style: dotted; cursor: help; display: inline-flex; align-items: center; gap: 0.3rem; }

/* ── Retrato ── */
.portrait-box {
  background: var(--tokyo-bg-secondary); border-radius: 8px;
  padding: 1.25rem; font-size: 0.92rem; line-height: 1.75;
  color: var(--tokyo-fg-dim); white-space: pre-line;
}

/* ── Fases ── */
.fases-layout { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
@media (max-width: 700px) { .fases-layout { grid-template-columns: 1fr; } }

.fase-card {
  background: var(--tokyo-bg-secondary); border: 1px solid var(--tokyo-bg-tertiary);
  border-radius: 10px; overflow: hidden;
}
.fase-card--total { grid-column: 1 / -1; }
.fase-header {
  background: var(--tokyo-bg-tertiary); padding: 0.6rem 1rem;
  font-size: 0.78rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.04em; color: var(--tokyo-fg-dim);
}
.fase-body { padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.5rem; }

.check-row { display: flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; }
.check-row.ok { color: #4ade80; }
.check-row.ko { color: #f87171; }
.camino-chip {
  display: inline-block; margin-top: 0.3rem;
  font-size: 0.72rem; font-weight: 700;
  background: var(--tokyo-bg-tertiary); color: var(--tokyo-fg-dim);
  border-radius: 4px; padding: 2px 8px;
}

.metrics-grid { display: flex; flex-direction: column; gap: 0.4rem; }
.metric-row { display: flex; align-items: center; gap: 0.75rem; font-size: 0.85rem; }
.metric-label { flex: 1; color: var(--tokyo-fg-dim); }
.metric-val { font-weight: 600; }
.metric-pts { font-size: 0.75rem; color: var(--tokyo-fg-dim); min-width: 24px; text-align: right; }
.metric-badge {
  font-size: 0.7rem; font-weight: 600; padding: 1px 6px;
  border-radius: 4px; border: 1px solid currentColor;
}

.trend--up      { color: #4ade80; }
.trend--down    { color: #f87171; }
.trend--neutral { color: var(--tokyo-fg-dim); }
.nivel--ok     { color: #4ade80; }
.nivel--alerta { color: #fbbf24; }
.nivel--debil  { color: #f87171; }

.total-score {
  font-size: 2.5rem; font-weight: 700; color: var(--tokyo-blue);
  display: flex; align-items: baseline; gap: 0.4rem;
}
.total-max { font-size: 1rem; color: var(--tokyo-fg-dim); font-weight: 400; }

/* ── Glosario ── */
.glosario-section { margin-top: 1rem; }
.glosario-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}
@media (max-width: 700px) { .glosario-grid { grid-template-columns: 1fr; } }

.glosario-grupo {
  background: var(--tokyo-bg-secondary);
  border: 1px solid var(--tokyo-bg-tertiary);
  border-radius: 10px; overflow: hidden;
}
.glosario-grupo-titulo {
  background: var(--tokyo-bg-tertiary); padding: 0.5rem 1rem;
  font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.05em; color: var(--tokyo-fg-dim);
}
.glosario-item {
  display: flex; gap: 0.75rem;
  padding: 0.55rem 1rem;
  border-bottom: 1px solid var(--tokyo-bg-tertiary);
  font-size: 0.82rem; line-height: 1.5;
}
.glosario-item:last-child { border-bottom: none; }
.glosario-term {
  font-weight: 700; color: var(--tokyo-cyan);
  min-width: 100px; flex-shrink: 0; font-family: monospace; font-size: 0.8rem;
}
.glosario-def { color: var(--tokyo-fg-dim); }

/* ── Sin análisis ── */
.no-analisis {
  display: flex; flex-direction: column; align-items: center; gap: 0.75rem;
  padding: 3rem; color: var(--tokyo-fg-dim); font-size: 0.9rem; text-align: center;
}
.no-analisis i { font-size: 2.5rem; opacity: 0.4; }
</style>
