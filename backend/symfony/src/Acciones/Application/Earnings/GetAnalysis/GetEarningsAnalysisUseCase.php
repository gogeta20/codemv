<?php

namespace App\Acciones\Application\Earnings\GetAnalysis;

use App\Acciones\Domain\Repository\AccionEarningsRepositoryInterface;
use App\Acciones\Domain\Repository\AccionEarningsReportRepositoryInterface;
use App\Acciones\Domain\Repository\AccionRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionEarningsReport;

final class GetEarningsAnalysisUseCase
{
    /** A report whose period is more than this many days behind the accion's current earnings_date
     * is very likely a past quarter, not the one that just moved the market. */
    private const STALE_THRESHOLD_DAYS = 70;

    public function __construct(
        private readonly AccionRepositoryInterface $accionRepository,
        private readonly AccionEarningsReportRepositoryInterface $earningsReportRepository,
        private readonly AccionEarningsRepositoryInterface $accionEarningsRepository,
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
        $staleness = $this->staleness($report, $accion);

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
                'is_stale' => $staleness !== null,
                'stale_reason' => $staleness,
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
        // Dot-leader table rows (e.g. "Revenues .......... $61") collapse to a single space so
        // tabular financial-statement filings parse like prose.
        $text = preg_replace('/\.{3,}/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    /**
     * Parses a SEC filing style amount: "$1,234", "(1,234)" (negative), "1,234.56", "—" (zero/none).
     */
    private function parseSecNumber(string $raw): float
    {
        $isNegative = str_contains($raw, '(');
        $clean = trim(str_replace(['$', ',', '(', ')', '—'], '', $raw));
        if ($clean === '') {
            return 0.0;
        }

        return $isNegative ? -abs((float) $clean) : (float) $clean;
    }

    /**
     * Financial-statement tables in SEC filings are almost always reported "in thousands"; a few
     * report "in millions". We look for that disclaimer near the table headers to scale amounts to
     * MUSD correctly instead of guessing.
     */
    private function detectTableScale(string $raw): float
    {
        if (preg_match('/in\s+millions/iu', $raw) === 1) {
            return 1.0;
        }

        return 1 / 1000;
    }

    /**
     * Matches "<label> <amount>" where amount may carry a leading "$" and/or be wrapped in
     * parentheses to denote a negative (e.g. "Operating loss (20,052)"). Returns the raw amount in
     * whatever unit the table uses (caller decides on scaling).
     */
    private function extractLabeledAmount(string $raw, string $labelPattern): ?float
    {
        // Wrap the caller's label in a non-capturing group: several callers pass top-level
        // alternation (e.g. "Foo|Bar"), and without the wrapper the trailing amount pattern would
        // only apply to the last alternative, leaving the (?<full>...) group uncaptured for the
        // others.
        if (preg_match('/(?:' . $labelPattern . ')\s*\$?(?<full>\(?[\d,]+\)?)/iu', $raw, $m) !== 1 || !isset($m['full'])) {
            return null;
        }

        return $this->parseSecNumber($m['full']);
    }

    /**
     * Matches a "Label <prior> <current> <change>%" row from a highlights table without dollar
     * signs or dot-leaders, e.g. "Revenues 105.1 582.3 454%". Returns the current-period value and
     * the stated YoY change, or null if the row isn't in this shape.
     */
    private function extractHighlightRow(string $raw, string $labelPattern): ?array
    {
        if (preg_match('/(?:' . $labelPattern . ')\s+(?<prior>-?\(?[\d,.]+\)?|—)\s+(?<current>-?\(?[\d,.]+\)?|—)\s+(?<yoy>-?[\d,.]+)%/iu', $raw, $m) !== 1) {
            return null;
        }

        return [
            'current' => $this->parseSecNumber($m['current']),
            'yoy_pct' => (float) str_replace(',', '', $m['yoy']),
        ];
    }

    /**
     * Matches a "Label <prior> <current> ..." row from a statement table (no dollar sign or
     * dot-leaders) and returns the current-period column, e.g. "Loss from operations (111.2)
     * (175.9)" -> -175.9. Only the first two numbers are used even when the row has more columns
     * (e.g. a trailing six-month comparison), since those always come after prior/current quarter.
     */
    private function extractSecondTableValue(string $raw, string $labelPattern): ?float
    {
        if (preg_match('/(?:' . $labelPattern . ')\s+(?<prior>-?\(?[\d,.]+\)?|—)\s+(?<current>-?\(?[\d,.]+\)?|—)/iu', $raw, $m) !== 1) {
            return null;
        }

        return $this->parseSecNumber($m['current']);
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

        // Two-column tabular fallback with no space at all between the label and either dollar
        // figure, e.g. "Revenue$2,078 $982" (current quarter, prior-year quarter side by side —
        // common in GAAP income-statement tables that don't spell out a YoY %). Derives yoy_pct
        // ourselves from the two figures instead of leaving it null.
        if (preg_match('/Revenues?\s*\$(?<current>[\d,]+|—)\s*\$(?<prior>[\d,]+|—)/iu', $raw, $m) === 1) {
            $current = $this->parseSecNumber($m['current']);
            $prior = $this->parseSecNumber($m['prior']);
            return [
                'actual' => $current * $this->detectTableScale($raw),
                'yoy_pct' => $prior > 0 ? round((($current - $prior) / $prior) * 100, 2) : null,
            ];
        }

        // Tabular financial-statement fallback: "Revenues .... $61" (dot-leaders already collapsed).
        if (preg_match('/Revenues?\s*\$(?<value>[\d,]+|—)/iu', $raw, $m) === 1) {
            return [
                'actual' => $this->parseSecNumber($m['value']) * $this->detectTableScale($raw),
                'yoy_pct' => null,
            ];
        }

        // Highlights-table fallback (no "$", figures already in the stated table unit): "Revenues
        // 105.1 582.3 454%" -> current=582.3, yoy=454%.
        $highlight = $this->extractHighlightRow($raw, 'Revenues?');
        if ($highlight !== null) {
            return [
                'actual' => $highlight['current'] * $this->detectTableScale($raw),
                'yoy_pct' => $highlight['yoy_pct'],
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

        // Tabular fallback: "Net loss per share attributable to common stockholders ... $(3.72)".
        // Deliberately requires "attributable to common" so it doesn't match a section header like
        // "Net loss per share information: Net loss ... $(31,830)" (that's the aggregate dollar
        // figure, not a per-share one — and its comma-grouped amount would otherwise get truncated
        // by the decimal-only character class into a wrong number).
        if (preg_match('/Net (?:income|loss) per (?:common )?share attributable to common (?:stockholders|shareholders)[^$]{0,160}\$(?<full>\(?[\d.]+\)?)/iu', $raw, $m) === 1) {
            return [
                'actual' => $this->parseSecNumber($m['full']),
                'yoy_pct' => null,
            ];
        }

        // No-space tabular fallback: "Diluted net loss per share$(1.40)" (no gap between the label
        // and "$", common in condensed GAAP statements). Diluted preferred over Basic when both are
        // present, since it's the more conservative/standard figure.
        if (preg_match('/Diluted net (?:income|loss) per share\s*\$(?<full>\(?[\d.]+\)?)/iu', $raw, $m) === 1) {
            return [
                'actual' => $this->parseSecNumber($m['full']),
                'yoy_pct' => null,
            ];
        }

        if (preg_match('/Basic net (?:income|loss) per share\s*\$(?<full>\(?[\d.]+\)?)/iu', $raw, $m) === 1) {
            return [
                'actual' => $this->parseSecNumber($m['full']),
                'yoy_pct' => null,
            ];
        }

        // Highlights-table fallback: "Net income / (loss) per ClassA and ClassB share: Basic 2.45
        // (0.68) 1.98 1.60 Diluted 2.38 (0.68) 1.94 1.53" -> diluted current quarter = -0.68 (2nd
        // value after "Diluted"). Deliberately anchored on the aggregate "Net income / (loss) per
        // ... share" label (not "... from continuing operations" / "... from discontinued
        // operations", which report the same shape but only part of the total).
        if (preg_match('/Net income \/ \(loss\) per (?:Class ?A and Class ?B|common)\s*share:.*?Diluted\s+(?<prior>-?\(?[\d,.]+\)?|—)\s+(?<current>-?\(?[\d,.]+\)?|—)/isu', $raw, $m) === 1) {
            return [
                'actual' => $this->parseSecNumber($m['current']),
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

        // Direct disclosure fallback: some statements print the margin percentage itself right next
        // to the label, e.g. "Operating loss margin(7)%(3)%" (current, prior). Prefer this over
        // deriving it ourselves whenever it's available.
        if (preg_match('/Operating (?:income|loss) margin\s*(?<full>\(?[\d.]+\)?)\s*%/iu', $raw, $m) === 1) {
            return [
                'actual' => $this->parseSecNumber($m['full']),
                'value_musd' => null,
                'yoy_pct' => null,
            ];
        }

        // Tabular fallback: derive margin from "Operating income/loss" over revenue. Skipped when
        // revenue is too small relative to the table's unit to produce a meaningful percentage
        // (e.g. a pre-revenue company with a few thousand dollars of revenue and a multi-million
        // loss would otherwise show a nonsensical -30000% "margin").
        $operatingIncome = $this->extractLabeledAmount($raw, 'Operating (?:income|loss)');
        $revenueRaw = $this->extractRawTabularRevenue($raw);
        if ($operatingIncome !== null && $revenueRaw !== null && $revenueRaw >= 1000) {
            $scale = $this->detectTableScale($raw);
            return [
                'actual' => round(($operatingIncome / $revenueRaw) * 100, 2),
                'value_musd' => $operatingIncome * $scale,
                'yoy_pct' => null,
            ];
        }

        // Highlights/statement-table fallback (no "$", e.g. "Loss from operations (111.2) (175.9)"
        // alongside "Revenues 105.1 582.3 454%"). Both values are already in the table's stated
        // unit, so no threshold guard is needed here (unlike the dot-leader case above, this format
        // is used by companies that already report meaningful revenue).
        $operatingResult = $this->extractSecondTableValue($raw, 'Loss from operations|Income from operations');
        $revenueHighlight = $this->extractHighlightRow($raw, 'Revenues?');
        if ($operatingResult !== null && $revenueHighlight !== null && $revenueHighlight['current'] > 0) {
            $scale = $this->detectTableScale($raw);
            return [
                'actual' => round(($operatingResult / $revenueHighlight['current']) * 100, 2),
                'value_musd' => $operatingResult * $scale,
                'yoy_pct' => null,
            ];
        }

        return ['actual' => null, 'value_musd' => null, 'yoy_pct' => null];
    }

    /**
     * Raw (unscaled) revenue value as it appears in a tabular filing, used only to sanity-check
     * derived-margin calculations. Returns null when the tabular pattern doesn't match.
     */
    private function extractRawTabularRevenue(string $raw): ?float
    {
        if (preg_match('/Revenues?\s+\$(?<value>[\d,]+|—)/iu', $raw, $m) !== 1) {
            return null;
        }

        return $this->parseSecNumber($m['value']);
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

        // Tabular fallback: operating cash flow minus capex, when both appear in the cash-flow
        // statement section. Not every filing includes a full cash-flow statement in the earnings
        // exhibit (e.g. some only show income statement + balance sheet + narrative bullets), in
        // which case this stays null rather than being guessed from unrelated figures.
        // The capex line often has extra descriptive text between the label and the actual figure
        // (e.g. "Purchase of property and equipment, including capitalized internal-use
        // software(7,695)"), so the label pattern tolerates a short non-numeric gap before the
        // amount instead of requiring it immediately after.
        $operatingCashFlow = $this->extractLabeledAmount($raw, 'Net cash (?:provided by|used in) operating activities');
        $capex = $this->extractLabeledAmount($raw, '(?:Purchases? of property(?:,? plant(?:,? and|,) equipment)?|Capital expenditures)[^0-9($]{0,80}');
        if ($operatingCashFlow !== null && $capex !== null) {
            $scale = $this->detectTableScale($raw);
            $fcfRaw = $operatingCashFlow - abs($capex);
            return [
                'actual' => $fcfRaw * $scale,
                'margin_pct' => null,
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

        // Tabular fallback: balance sheet "Cash and cash equivalents $X" line (also matches with no
        // gap at all, e.g. "Cash and cash equivalents$2,244").
        if (preg_match('/Cash and cash equivalents\s*\$(?<value>[\d,]+)/iu', $raw, $m) === 1) {
            return $this->parseSecNumber($m['value']) * $this->detectTableScale($raw);
        }

        // Balance-sheet fallback without "$" (e.g. "Cash and cash equivalents 3,678.1 8,042.1" for
        // prior year-end vs. current period end).
        $balanceSheetCash = $this->extractSecondTableValue($raw, 'Cash and cash equivalents');
        if ($balanceSheetCash !== null) {
            return $balanceSheetCash * $this->detectTableScale($raw);
        }

        return null;
    }

    private function buildVerdict(array $metrics): array
    {
        $dataPoints = 0;
        foreach (['revenue', 'eps', 'operating_margin', 'free_cash_flow'] as $key) {
            if (($metrics[$key]['actual'] ?? null) !== null) {
                $dataPoints++;
            }
        }

        if ($dataPoints === 0) {
            return [
                'signal' => 'insufficient_data',
                'score' => null,
                'label' => 'Datos insuficientes',
                'summary' => 'No se pudieron extraer métricas de este filing: puede ser que el documento no sea realmente el earnings release (revisar la fuente) o que use un formato de texto que el extractor todavía no reconoce. El score no se calcula para evitar mostrar un veredicto inventado.',
            ];
        }

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

    /**
     * A null value means "we don't know", not "it's bad" — surface that as 'warning' instead of
     * silently defaulting through `?? 0` into a failing comparison.
     */
    private function comparisonSignal(?float $value, float $threshold, bool $strictlyGreater = false): string
    {
        if ($value === null) {
            return 'warning';
        }

        $meetsThreshold = $strictlyGreater ? $value > $threshold : $value >= $threshold;
        return $meetsThreshold ? 'good' : 'bad';
    }

    private function buildKpis(array $metrics): array
    {
        return [
            ['key' => 'revenue', 'label' => 'Revenue', 'subtitle' => 'Ingresos del trimestre', 'actual' => $metrics['revenue']['actual'], 'actual_unit' => 'MUSD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => $metrics['revenue']['yoy_pct'], 'signal' => $this->comparisonSignal($metrics['revenue']['yoy_pct'], 20), 'why_it_matters' => 'Mide si el negocio principal acelera o se enfría. Sin crecimiento real, el quarter pierde fuerza rápido.'],
            ['key' => 'eps', 'label' => 'EPS diluido', 'subtitle' => 'Ganancia por acción para el accionista', 'actual' => $metrics['eps']['actual'], 'actual_unit' => 'USD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => $this->comparisonSignal($metrics['eps']['actual'], 0, strictlyGreater: true), 'why_it_matters' => 'Resume la rentabilidad atribuible al accionista. Si el quarter vende mucho pero no deja beneficio, la lectura cambia.'],
            ['key' => 'operating_margin', 'label' => 'Operating Margin', 'subtitle' => 'Rentabilidad operativa sobre ventas', 'actual' => $metrics['operating_margin']['actual'], 'actual_unit' => '%', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => $this->comparisonSignal($metrics['operating_margin']['actual'], 20), 'why_it_matters' => 'Nos dice si el crecimiento está entrando con calidad o si se compra a costa de gastar demasiado.'],
            ['key' => 'free_cash_flow', 'label' => 'Free Cash Flow', 'subtitle' => 'Caja libre generada por el negocio', 'actual' => $metrics['free_cash_flow']['actual'], 'actual_unit' => 'MUSD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => $this->comparisonSignal($metrics['free_cash_flow']['actual'], 0, strictlyGreater: true), 'why_it_matters' => 'La caja libre reduce el riesgo de que el crecimiento dependa de refinanciación, deuda o dilución.'],
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
        // Prefer the quarter-end date stated in the release itself ("...results for the second
        // quarter ended June 30, 2026"). The stored periodic_report metadata points at the most
        // recent 10-Q/20-F *filed before* this earnings release, which usually lags a full quarter
        // behind (the 10-Q for the quarter just announced isn't filed yet) and would otherwise mark
        // a same-day report as stale relative to the accion's current earnings_date.
        $periodEnd = $this->extractPeriodEndDate($report->getRawContent());
        if ($periodEnd !== null) {
            return $periodEnd;
        }

        $periodicReportDate = $report->getMetadata()['periodic_report']['report_date'] ?? null;
        if (is_string($periodicReportDate) && trim($periodicReportDate) !== '') return new \DateTimeImmutable($periodicReportDate);
        return $report->getReportDate();
    }

    private function extractPeriodEndDate(string $raw): ?\DateTimeImmutable
    {
        // Non-breaking spaces (common in filings copy-pasted from Word/PDF, e.g. "June\u{A0}30,
        // 2026") aren't reliably matched by \s across PCRE builds — normalize them to a plain space
        // first instead of depending on that.
        $raw = str_replace(["\xC2\xA0", "\u{00A0}"], ' ', $raw);

        // Covers both "...results for the second quarter ended June 30, 2026" and "...results for
        // the three and six months ended June 30, 2026" (the latter phrasing is common in
        // foreign-private-issuer releases, e.g. Nebius' 6-K).
        if (preg_match('/(?:quarter|months?)\s+ended\s+(?<date>[A-Za-z]+\s+\d{1,2},\s*\d{4})/iu', $raw, $m) === 1) {
            try {
                return new \DateTimeImmutable($m['date']);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }

    /**
     * Returns a human-readable reason when the stored report's period predates the accion's current
     * `earnings_date` by more than STALE_THRESHOLD_DAYS — a sign that a newer earnings release likely
     * exists but hasn't been fetched yet, and this report shouldn't be read as "the one that moved
     * the market" on the current earnings_date.
     */
    private function staleness(AccionEarningsReport $report, Accion $accion): ?string
    {
        $reportPeriod = $this->reportDate($report);
        if ($reportPeriod === null) {
            return null;
        }

        $earnings = $this->accionEarningsRepository->findByAccion($accion);
        if ($earnings === null) {
            return null;
        }

        $earningsDate = $earnings->getEarningsDate();

        // `earnings_date` can point at the *next* scheduled release (still in the future). In that
        // case the stored report is presumably the latest one available and simply predates the
        // upcoming quarter by the normal reporting cadence (~90 days quarter + filing lag) — that's
        // not staleness, it's just waiting for the next earnings season.
        if ($earningsDate > new \DateTimeImmutable('today')) {
            return null;
        }

        $daysBehind = $reportPeriod->diff($earningsDate)->days;
        if ($reportPeriod >= $earningsDate || $daysBehind <= self::STALE_THRESHOLD_DAYS) {
            return null;
        }

        return 'Este reporte es de un trimestre anterior; el más reciente puede no estar disponible todavía.';
    }

    private function thousandsToMillions(string $value): float
    {
        return ((float) str_replace(',', '', $value)) / 1000;
    }
}
