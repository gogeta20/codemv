<?php

namespace App\Acciones\Application\Earnings\GetAnalysis;

use App\Acciones\Domain\Repository\AccionEarningsReportRepositoryInterface;
use App\Acciones\Domain\Repository\AccionRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionEarningsReport;

final class GetEarningsAnalysisUseCase
{
    public function __construct(
        private readonly AccionRepositoryInterface $accionRepository,
        private readonly AccionEarningsReportRepositoryInterface $earningsReportRepository,
    ) {}

    public function execute(string $accionUuid): ?array
    {
        $accion = $this->accionRepository->findByUuid($accionUuid);
        if ($accion === null) {
            throw new \InvalidArgumentException("Acción no encontrada: {$accionUuid}");
        }

        $report = $this->earningsReportRepository->findLatestByAccion($accion);
        if ($report === null) {
            return null;
        }

        $parsed = $this->normalizeForParsing($report->getRawContent());
        $metrics = $this->extractMetrics($parsed);
        $verdict = $this->buildVerdict($metrics);

        return [
            'report' => [
                'uuid' => $report->getUuid(),
                'symbol' => $report->getAccion()->getSymbol(),
                'company_name' => $report->getMetadata()['company_name'] ?? $report->getAccion()->getName(),
                'form_type' => $report->getFormType(),
                'filing_date' => $report->getFilingDate()?->format('Y-m-d'),
                'report_date' => $this->reportDate($report)?->format('Y-m-d'),
                'source' => $report->getSource(),
                'source_url' => $report->getExhibitUrl() ?? $report->getFilingUrl(),
                'period_label' => $this->periodLabel($report),
            ],
            'verdict' => $verdict,
            'highlights' => [
                'good' => $this->buildGoodHighlights($metrics),
                'bad' => $this->buildBadHighlights($metrics),
            ],
            'kpis' => $this->buildKpis($metrics),
            'cross_checks' => $this->buildCrossChecks($metrics),
            'risks' => $this->buildRisks($metrics),
            'backend_contract' => [
                'expected_endpoint' => 'GET /api/acciones/:uuid/earnings-analysis',
                'minimum_fields' => [
                    'report',
                    'verdict',
                    'highlights.good',
                    'highlights.bad',
                    'kpis',
                    'cross_checks',
                    'risks',
                ],
            ],
        ];
    }

    private function normalizeForParsing(string $raw): string
    {
        $text = str_replace(["\xC2\xA0", "\u{00A0}"], ' ', $raw);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    private function extractMetrics(string $raw): array
    {
        return [
            'revenue' => $this->extractRevenue($raw),
            'eps' => $this->extractEps($raw),
            'operating_margin' => $this->extractAdjustedOperatingMargin($raw),
            'free_cash_flow' => $this->extractAdjustedFreeCashFlow($raw),
            'guidance_revenue' => $this->extractFullYearGuidanceRevenue($raw),
            'guidance_revenue_yoy' => $this->extractFullYearGuidanceYoy($raw),
            'cash_position' => $this->extractCashPosition($raw),
        ];
    }

    private function extractRevenue(string $raw): array
    {
        if (preg_match('/Revenue\$?(?<value>[\d,]+)\s*Year-over-year growth\s*(?<yoy>[\d.]+)\s*%/iu', $raw, $m) === 1) {
            return [
                'actual' => $this->thousandsToMillions($m['value']),
                'yoy_pct' => (float) $m['yoy'],
            ];
        }

        if (preg_match('/Revenue grew\s*(?<yoy>[\d.]+)%\s*year-over-year[^$]+\$(?<value>[\d.]+)\s*billion/iu', $raw, $m) === 1) {
            return [
                'actual' => (float) $m['value'] * 1000,
                'yoy_pct' => (float) $m['yoy'],
            ];
        }

        return ['actual' => null, 'yoy_pct' => null];
    }

    private function extractEps(string $raw): array
    {
        if (preg_match('/GAAP EPS(?:, Diluted)?\$?(?<value>[\d.]+)/iu', $raw, $m) === 1) {
            return [
                'actual' => (float) $m['value'],
                'yoy_pct' => null,
            ];
        }

        return ['actual' => null, 'yoy_pct' => null];
    }

    private function extractAdjustedOperatingMargin(string $raw): array
    {
        if (preg_match('/Adjusted Income from Operations\$?(?<value>[\d,]+)\s*(?<margin>[\d.]+)\s*%/iu', $raw, $m) === 1) {
            return [
                'actual' => (float) $m['margin'],
                'value_musd' => $this->thousandsToMillions($m['value']),
                'yoy_pct' => null,
            ];
        }

        if (preg_match('/Adjusted income from operations of \$(?<value>[\d.]+)\s*billion, representing a\s*(?<margin>[\d.]+)%\s*margin/iu', $raw, $m) === 1) {
            return [
                'actual' => (float) $m['margin'],
                'value_musd' => (float) $m['value'] * 1000,
                'yoy_pct' => null,
            ];
        }

        return ['actual' => null, 'value_musd' => null, 'yoy_pct' => null];
    }

    private function extractAdjustedFreeCashFlow(string $raw): array
    {
        if (preg_match('/Adjusted Free Cash Flow\$?(?<value>[\d,]+)\s*(?<margin>[\d.]+)\s*%/iu', $raw, $m) === 1) {
            return [
                'actual' => $this->thousandsToMillions($m['value']),
                'margin_pct' => (float) $m['margin'],
                'yoy_pct' => null,
            ];
        }

        if (preg_match('/Adjusted free cash flow of \$(?<value>[\d.]+)\s*billion, representing a\s*(?<margin>[\d.]+)%\s*margin/iu', $raw, $m) === 1) {
            return [
                'actual' => (float) $m['value'] * 1000,
                'margin_pct' => (float) $m['margin'],
                'yoy_pct' => null,
            ];
        }

        return ['actual' => null, 'margin_pct' => null, 'yoy_pct' => null];
    }

    private function extractFullYearGuidanceRevenue(string $raw): ?float
    {
        if (preg_match('/raising our revenue guidance to between \$(?<low>[\d.]+)\s*[–-]\s*\$(?<high>[\d.]+)\s*billion/iu', $raw, $m) === 1) {
            return (((float) $m['low']) + ((float) $m['high'])) / 2 * 1000;
        }

        return null;
    }

    private function extractFullYearGuidanceYoy(string $raw): ?float
    {
        if (preg_match('/Raises FY 2026 Revenue Guidance to\s*(?<yoy>[\d.]+)%\s*Y\/Y Growth/iu', $raw, $m) === 1) {
            return (float) $m['yoy'];
        }

        return null;
    }

    private function extractCashPosition(string $raw): ?float
    {
        if (preg_match('/Cash, cash equivalents, and short-term U\.S\. Treasury securities of \$(?<value>[\d.]+)\s*billion/iu', $raw, $m) === 1) {
            return (float) $m['value'] * 1000;
        }

        return null;
    }

    private function buildVerdict(array $metrics): array
    {
        $score = 50;
        if (($metrics['revenue']['yoy_pct'] ?? 0) >= 30) $score += 12;
        if (($metrics['revenue']['yoy_pct'] ?? 0) >= 70) $score += 8;
        if (($metrics['operating_margin']['actual'] ?? 0) >= 20) $score += 8;
        if (($metrics['operating_margin']['actual'] ?? 0) >= 40) $score += 6;
        if (($metrics['free_cash_flow']['margin_pct'] ?? 0) >= 20) $score += 6;
        if (($metrics['guidance_revenue'] ?? 0) > 0) $score += 5;
        if (($metrics['cash_position'] ?? 0) >= 1000) $score += 3;
        if (($metrics['revenue']['yoy_pct'] ?? 0) >= 80) $score -= 4;
        $score = max(0, min(100, $score));

        if ($score >= 75) {
            return [
                'signal' => 'good',
                'score' => $score,
                'label' => 'Reporte fuerte',
                'summary' => 'El trimestre combina crecimiento alto, márgenes sólidos, caja fuerte y guidance al alza. La lectura general es claramente positiva, aunque sigue importando cuánto optimismo ya estaba en precio.',
            ];
        }
        if ($score >= 55) {
            return [
                'signal' => 'mixed',
                'score' => $score,
                'label' => 'Reporte mixto',
                'summary' => 'El trimestre tiene señales constructivas, pero no todo apunta en la misma dirección. El análisis debe enseñar lo bueno y también lo que limita la tesis.',
            ];
        }
        return [
            'signal' => 'bad',
            'score' => $score,
            'label' => 'Reporte débil',
            'summary' => 'El trimestre no muestra suficiente confirmación en crecimiento, rentabilidad o caja. El análisis debería tratarlo como un quarter que no valida la tesis sin matices.',
        ];
    }

    private function buildKpis(array $metrics): array
    {
        return [
            ['key' => 'revenue', 'label' => 'Revenue', 'subtitle' => 'Ingresos del trimestre', 'actual' => $metrics['revenue']['actual'], 'actual_unit' => 'MUSD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => $metrics['revenue']['yoy_pct'], 'signal' => ($metrics['revenue']['yoy_pct'] ?? 0) >= 20 ? 'good' : 'bad', 'why_it_matters' => 'Mide si el negocio principal acelera o se enfría. Sin crecimiento real, el quarter pierde fuerza rápido.'],
            ['key' => 'eps', 'label' => 'EPS diluido', 'subtitle' => 'Ganancia por acción para el accionista', 'actual' => $metrics['eps']['actual'], 'actual_unit' => 'USD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => ($metrics['eps']['actual'] ?? 0) > 0 ? 'good' : 'bad', 'why_it_matters' => 'Resume la rentabilidad atribuible al accionista. Si el quarter vende mucho pero no deja beneficio, la lectura cambia.'],
            ['key' => 'operating_margin', 'label' => 'Operating Margin', 'subtitle' => 'Rentabilidad operativa sobre ventas', 'actual' => $metrics['operating_margin']['actual'], 'actual_unit' => '%', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => ($metrics['operating_margin']['actual'] ?? 0) >= 20 ? 'good' : 'bad', 'why_it_matters' => 'Nos dice si el crecimiento está entrando con calidad o si se compra a costa de gastar demasiado.'],
            ['key' => 'free_cash_flow', 'label' => 'Free Cash Flow', 'subtitle' => 'Caja libre generada por el negocio', 'actual' => $metrics['free_cash_flow']['actual'], 'actual_unit' => 'MUSD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => ($metrics['free_cash_flow']['actual'] ?? 0) > 0 ? 'good' : 'bad', 'why_it_matters' => 'La caja libre reduce el riesgo de que el crecimiento dependa de refinanciación, deuda o dilución.'],
            ['key' => 'guidance_revenue', 'label' => 'Guidance FY revenue', 'subtitle' => 'Previsión de ingresos futura', 'actual' => $metrics['guidance_revenue'], 'actual_unit' => 'MUSD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => $metrics['guidance_revenue_yoy'], 'signal' => $metrics['guidance_revenue'] !== null ? 'good' : 'warning', 'why_it_matters' => 'Si la directiva sube guidance, el mercado interpreta que el quarter no fue un simple accidente aislado.'],
            ['key' => 'valuation_risk', 'label' => 'Valuation Risk', 'subtitle' => 'Riesgo de que la acción ya esté demasiado cara', 'actual' => 1, 'actual_unit' => 'flag', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => ($metrics['revenue']['yoy_pct'] ?? 0) >= 50 ? 'bad' : 'warning', 'why_it_matters' => 'Un gran quarter no implica automáticamente una gran compra si la acción ya cotiza con expectativas extremas.'],
        ];
    }

    private function buildGoodHighlights(array $metrics): array
    {
        $items = [];
        if (($metrics['revenue']['yoy_pct'] ?? 0) > 0) $items[] = sprintf('Revenue crece %.2f%% YoY, una señal clara de aceleración del negocio.', $metrics['revenue']['yoy_pct']);
        if (($metrics['operating_margin']['actual'] ?? 0) > 0) $items[] = sprintf('El margen operativo ajustado ronda %.2f%%, lo que sugiere que el crecimiento llega con calidad.', $metrics['operating_margin']['actual']);
        if (($metrics['free_cash_flow']['actual'] ?? 0) > 0 && ($metrics['free_cash_flow']['margin_pct'] ?? 0) > 0) $items[] = sprintf('El free cash flow sube a %.2f MUSD con margen del %.2f%%, una conversión a caja muy fuerte.', $metrics['free_cash_flow']['actual'], $metrics['free_cash_flow']['margin_pct']);
        if (($metrics['guidance_revenue'] ?? 0) > 0) $items[] = 'La compañía eleva guidance anual, reforzando que la directiva ve continuidad tras el quarter.';
        if (($metrics['cash_position'] ?? 0) > 0) $items[] = sprintf('La caja supera %.2f MUSD, dando mucho colchón financiero para operar e invertir.', $metrics['cash_position']);
        if ($items === []) $items[] = 'Hay señales positivas en el texto del reporte, pero aún faltan métricas claras que el backend debería extraer con más precisión.';
        return $items;
    }

    private function buildBadHighlights(array $metrics): array
    {
        $items = ['Un quarter excelente puede ser difícil de repetir; el mercado castiga fuerte cuando el trimestre siguiente no confirma el ritmo.', 'Si la valoración ya exigía perfección, incluso un reporte muy bueno puede no dejar mucho margen de seguridad.'];
        if (($metrics['revenue']['yoy_pct'] ?? 0) >= 80) $items[] = 'Un crecimiento YoY extremadamente alto endurece las comparativas futuras y eleva el riesgo de desaceleración visible.';
        if (($metrics['guidance_revenue'] ?? null) === null) $items[] = 'Si no hay guidance claro o mejora explícita, el mercado puede dudar de la sostenibilidad del trimestre.';
        return $items;
    }

    private function buildCrossChecks(array $metrics): array
    {
        return [
            ['key' => 'beat_vs_expectations', 'label' => 'Beat vs consenso', 'subtitle' => 'Comparación contra lo que esperaba Wall Street', 'status' => 'mixed', 'detail' => 'El backend debería cruzar revenue y EPS contra consenso externo. En esta primera versión aún no guardamos estimates en la base de datos.'],
            ['key' => 'growth_quality', 'label' => 'Calidad del crecimiento', 'subtitle' => 'Si el crecimiento viene con margen y caja, o solo con volumen', 'status' => ($metrics['operating_margin']['actual'] ?? 0) >= 20 && ($metrics['free_cash_flow']['actual'] ?? 0) > 0 ? 'good' : 'warning', 'detail' => 'Cruza crecimiento de ingresos con margen operativo y caja libre. Si suben ventas pero no margen ni caja, la calidad baja.'],
            ['key' => 'guidance_direction', 'label' => 'Dirección del guidance', 'subtitle' => 'Hacia dónde apunta la propia directiva', 'status' => ($metrics['guidance_revenue'] ?? 0) > 0 ? 'good' : 'warning', 'detail' => 'El backend debe detectar si la directiva sube, mantiene o baja su previsión anual tras el quarter.'],
            ['key' => 'cash_conversion', 'label' => 'Conversión a caja', 'subtitle' => 'Si las ventas se convierten en caja real', 'status' => ($metrics['free_cash_flow']['margin_pct'] ?? 0) >= 10 ? 'good' : 'warning', 'detail' => 'No basta con beneficios contables: hay que ver si el quarter convierte ingresos en caja real.'],
            ['key' => 'balance_sheet', 'label' => 'Balance y liquidez', 'subtitle' => 'Fortaleza financiera y liquidez', 'status' => ($metrics['cash_position'] ?? 0) >= 500 ? 'good' : 'warning', 'detail' => 'La caja disponible reduce riesgo financiero y da margen para soportar ciclos o invertir sin diluir.'],
            ['key' => 'valuation_and_expectations', 'label' => 'Valoración y expectativas', 'subtitle' => 'Si el precio ya exigía demasiado antes del reporte', 'status' => 'warning', 'detail' => 'Aunque el quarter sea fuerte, el backend debería recordar si el mercado ya estaba descontando una ejecución casi perfecta.'],
        ];
    }

    private function buildRisks(array $metrics): array
    {
        $risks = [
            ['label' => 'Valoración exigente', 'subtitle' => 'Riesgo importante que puede cambiar la tesis', 'severity' => 'high', 'detail' => 'Un reporte fuerte no corrige por sí solo el riesgo de pagar múltiplos demasiado altos por crecimiento futuro.'],
            ['label' => 'Sostenibilidad del ritmo', 'subtitle' => ($metrics['revenue']['yoy_pct'] ?? 0) >= 60 ? 'Riesgo real, pero no necesariamente rompe la tesis' : 'Riesgo secundario o de seguimiento', 'severity' => ($metrics['revenue']['yoy_pct'] ?? 0) >= 60 ? 'medium' : 'low', 'detail' => 'Cuanto mayor es la aceleración del quarter, más duras serán las comparativas futuras y más fácil es decepcionar.'],
        ];
        if (($metrics['guidance_revenue'] ?? null) === null) $risks[] = ['label' => 'Falta de guidance estructurado', 'subtitle' => 'Riesgo real, pero no necesariamente rompe la tesis', 'severity' => 'medium', 'detail' => 'Sin guía clara de la directiva, el mercado tiene menos base para confiar en la continuidad del trimestre.'];
        return $risks;
    }

    private function periodLabel(AccionEarningsReport $report): string
    {
        $date = $this->reportDate($report) ?? $report->getFilingDate();
        if ($date === null) return 'Último reporte';
        $month = (int) $date->format('n');
        $quarter = match (true) { $month <= 3 => 'Q1', $month <= 6 => 'Q2', $month <= 9 => 'Q3', default => 'Q4' };
        return sprintf('%s %s', $quarter, $date->format('Y'));
    }

    private function reportDate(AccionEarningsReport $report): ?\DateTimeImmutable
    {
        $periodicReportDate = $report->getMetadata()['periodic_report']['report_date'] ?? null;
        if (is_string($periodicReportDate) && trim($periodicReportDate) !== '') return new \DateTimeImmutable($periodicReportDate);
        return $report->getReportDate();
    }

    private function thousandsToMillions(string $value): float
    {
        return ((float) str_replace(',', '', $value)) / 1000;
    }
}
