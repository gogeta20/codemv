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

    /**
     * "Revenue"/"Revenues" excluding when it's part of "cost of revenue" or "deferred revenue" —
     * both real line items that contain the word "revenue" but aren't the top-line figure. Without
     * this, a plain "Revenues?" pattern can match "Cost of revenue $25,207 $17,677" instead of
     * "Total revenue $131,138 $113,276" simply because it appears earlier in the document.
     */
    // The optional trailing `[^0-9($.]{0,40}` tolerates short descriptive text between the label and
    // the actual figure, e.g. "Total revenues and grant income $54.6 million" (X-Energy) — mirrors
    // the same gap-tolerance already used for the capex line in extractAdjustedFreeCashFlow().
    private const REVENUE_LABEL_PATTERN = '(?<!of )(?<!Deferred )Revenues?(?:[^0-9($.]{0,40})?';

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
     * report "in millions" — and some documents mix both within the same release (e.g. a rounded
     * highlights table in millions, followed by exact GAAP statements in thousands further down).
     * Scanning the whole document for a single scale would misapply an earlier disclaimer to a
     * value from a differently-scaled table, so we look at the nearest declaration *before* the
     * position of the value actually being converted, not just anywhere in the document.
     */
    private function detectTableScale(string $raw, int $beforeOffset = PHP_INT_MAX): float
    {
        $window = $beforeOffset === PHP_INT_MAX ? $raw : substr($raw, 0, $beforeOffset);

        // Not anchored to a literal "in millions"/"in thousands": real headers phrase this many
        // ways ("In USD $ millions", "(Dollars in thousands)", "in millions of U.S. dollars"), so we
        // just look for the bare unit word — the *nearest one before the value* (not a fixed-size
        // window) correctly follows a document from one table's declared unit into the next when
        // they differ, without needing to guess how far apart two sections might be.
        if (preg_match_all('/\b(millions|thousands)\b/iu', $window, $matches) > 0) {
            return strtolower(end($matches[1])) === 'millions' ? 1.0 : 1 / 1000;
        }

        return 1 / 1000;
    }

    /** Converts an inline "million"/"billion" unit word (already in $, so scale is relative to
     * MUSD) straight to a scale factor, without needing to consult detectTableScale() at all. */
    private function scaleFromUnitWord(string $unit): ?float
    {
        return match (strtolower($unit)) {
            'billion' => 1000.0,
            'million' => 1.0,
            default => null,
        };
    }

    /**
     * Matches "<label> <amount>" where amount may carry a leading "$" and/or be wrapped in
     * parentheses to denote a negative (e.g. "Operating loss (20,052)"). Returns the raw amount in
     * whatever unit the table uses, plus the match offset so the caller can look up the *nearby*
     * scale declaration via detectTableScale() instead of a possibly-wrong document-wide one.
     *
     * @return array{value: float, offset: int}|null
     */
    private function extractLabeledAmount(string $raw, string $labelPattern): ?array
    {
        // Wrap the caller's label in a non-capturing group: several callers pass top-level
        // alternation (e.g. "Foo|Bar"), and without the wrapper the trailing amount pattern would
        // only apply to the last alternative, leaving the (?<full>...) group uncaptured for the
        // others.
        if (preg_match('/(?:' . $labelPattern . ')\s*\$?(?<full>\(?\d[\d,.]*\)?)/iu', $raw, $m, PREG_OFFSET_CAPTURE) !== 1 || !isset($m['full'])) {
            return null;
        }

        return ['value' => $this->parseSecNumber($m['full'][0]), 'offset' => $m[0][1]];
    }

    /**
     * Matches a "Label <prior> <current> <change>%" row from a highlights table without dollar
     * signs or dot-leaders, e.g. "Revenues 105.1 582.3 454%". Returns the current-period value, the
     * stated YoY change, and the match offset (for scale lookup) — or null if the row isn't in this
     * shape.
     */
    private function extractHighlightRow(string $raw, string $labelPattern): ?array
    {
        if (preg_match('/(?:' . $labelPattern . ')\s+(?<prior>-?\(?\d[\d,.]*\)?|—)\s+(?<current>-?\(?\d[\d,.]*\)?|—)\s+(?<yoy>-?\d[\d,.]*)%/iu', $raw, $m, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        return [
            'current' => $this->parseSecNumber($m['current'][0]),
            'yoy_pct' => (float) str_replace(',', '', $m['yoy'][0]),
            'offset' => $m[0][1],
        ];
    }

    /**
     * Matches a "Label <prior> <current> ..." row from a statement table (no dollar sign or
     * dot-leaders) and returns the current-period column plus the match offset (for scale lookup),
     * e.g. "Loss from operations (111.2) (175.9)" -> -175.9. Only the first two numbers are used
     * even when the row has more columns (e.g. a trailing six-month comparison), since those always
     * come after prior/current quarter.
     *
     * @return array{value: float, offset: int}|null
     */
    private function extractSecondTableValue(string $raw, string $labelPattern): ?array
    {
        if (preg_match('/(?:' . $labelPattern . ')\s+(?<prior>-?\(?\d[\d,.]*\)?|—)\s+(?<current>-?\(?\d[\d,.]*\)?|—)/iu', $raw, $m, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        return ['value' => $this->parseSecNumber($m['current'][0]), 'offset' => $m[0][1]];
    }

    private function extractMetrics(string $raw): array
    {
        $revenue = $this->extractRevenue($raw);

        return [
            'revenue' => $revenue,
            'eps' => $this->extractEps($raw),
            'net_income' => $this->extractNetIncomeTotal($raw),
            'operating_margin' => $this->extractAdjustedOperatingMargin($raw),
            'operating_expenses' => $this->extractOperatingExpenses($raw, $revenue['actual']),
            'interest_expense' => $this->extractInterestExpense($raw),
            'taxes' => $this->extractTaxes($raw),
            'free_cash_flow' => $this->extractAdjustedFreeCashFlow($raw),
            'guidance_revenue' => $this->extractFullYearGuidanceRevenue($raw),
            'guidance_revenue_yoy' => $this->extractFullYearGuidanceYoy($raw),
            'cash_position' => $this->extractCashPosition($raw),
        ];
    }

    /**
     * Total net income/loss in $ (not per-share). Explicitly excludes "Adjusted net loss" (a
     * non-GAAP figure some issuers report right next to the real one) via a negative lookbehind.
     */
    private function extractNetIncomeTotal(string $raw): array
    {
        if (preg_match('/(?<!Adjusted )Net (?:income|loss)\s*\$?(?<full>\(?\d[\d,.]*\)?|—)/iu', $raw, $m, PREG_OFFSET_CAPTURE) === 1) {
            return [
                'actual' => $this->parseSecNumber($m['full'][0]) * $this->detectTableScale($raw, $m[0][1]),
                'yoy_pct' => null,
            ];
        }

        return ['actual' => null, 'yoy_pct' => null];
    }

    /**
     * Net interest expense in $ — shown as a positive cost figure (magnitude), consistent with how
     * operating_expenses is displayed. The gap between operating income/loss and net income/loss is
     * very often mostly this line, especially for capex-heavy, debt-financed businesses.
     */
    private function extractInterestExpense(string $raw): array
    {
        // ", net" is optional: some issuers report interest expense and interest income netted
        // together on one line ("Interest expense, net"), others report them on separate lines
        // ("Interest expense" / "Interest income") — the bare label covers both.
        if (preg_match('/Interest expense(?:,?\s*net)?\s*\$?(?<full>\(?\d[\d,.]*\)?|—)/iu', $raw, $m, PREG_OFFSET_CAPTURE) === 1) {
            return [
                'actual' => abs($this->parseSecNumber($m['full'][0])) * $this->detectTableScale($raw, $m[0][1]),
                'yoy_pct' => null,
            ];
        }

        return ['actual' => null, 'yoy_pct' => null];
    }

    /**
     * Income tax provision in $ — shown as a positive cost figure. Worth surfacing on its own: a
     * company can show a real tax expense even in a loss-making quarter (foreign subsidiaries,
     * deferred tax valuation allowances, etc.), which is easy to miss when only net income is shown.
     */
    private function extractTaxes(string $raw): array
    {
        if (preg_match('/(?:Provision for income taxes|Income tax expense)\s*\$?(?<full>\(?\d[\d,.]*\)?|—)/iu', $raw, $m, PREG_OFFSET_CAPTURE) === 1) {
            return [
                'actual' => abs($this->parseSecNumber($m['full'][0])) * $this->detectTableScale($raw, $m[0][1]),
                'yoy_pct' => null,
            ];
        }

        return ['actual' => null, 'yoy_pct' => null];
    }

    /**
     * Total operating expenses in $. Derived as revenue - operating income/loss rather than parsed
     * directly: the "Operating expenses" subtotal line frequently omits the "$" that anchors our
     * other patterns and whose column order (current-first vs. prior-first) isn't reliable to guess
     * on its own — but revenue and operating income are both already extracted with a trustworthy
     * "current period" value, so the subtraction is safe. Left null when either input is missing.
     */
    private function extractOperatingExpenses(string $raw, ?float $revenueActual): array
    {
        if ($revenueActual === null) {
            return ['actual' => null, 'yoy_pct' => null];
        }

        $operatingIncomeMatch = $this->extractLabeledAmount($raw, 'Operating (?:income|loss)')
            ?? $this->extractSecondTableValue($raw, 'Loss from operations|Income from operations');
        if ($operatingIncomeMatch === null) {
            return ['actual' => null, 'yoy_pct' => null];
        }

        $operatingIncome = $operatingIncomeMatch['value'] * $this->detectTableScale($raw, $operatingIncomeMatch['offset']);

        return [
            'actual' => round($revenueActual - $operatingIncome, 2),
            'yoy_pct' => null,
        ];
    }

    private function extractRevenue(string $raw): array
    {
        if (preg_match('/Revenue\$?(?<value>\d[\d,]*)\s*Year-over-year growth\s*(?<yoy>\d[\d.]*)\s*%/iu', $raw, $m) === 1) {
            return [
                'actual' => $this->thousandsToMillions($m['value']),
                'yoy_pct' => (float) $m['yoy'],
            ];
        }

        if (preg_match('/Revenue grew\s*(?<yoy>\d[\d.]*)%\s*year-over-year[^$]+\$(?<value>\d[\d.]*)\s*billion/iu', $raw, $m) === 1) {
            return [
                'actual' => (float) $m['value'] * 1000,
                'yoy_pct' => (float) $m['yoy'],
            ];
        }

        // Highlights-bullet prose: "Revenue of $131.1 million, up 16% year-over-year" (or "down").
        // Preferred over the tabular fallbacks below when available: it's an unambiguous,
        // single-number statement instead of a guess about which column/row in a dense table is the
        // real one — that ambiguity is exactly what previously caused a "Cost of revenue" line to be
        // read as if it were total revenue.
        if (preg_match('/Revenue of \$(?<value>\d[\d.]*)\s*million,\s*(?<direction>up|down)\s*(?<yoy>\d[\d.]*)%\s*year-over-year/iu', $raw, $m) === 1) {
            $yoy = (float) $m['yoy'];
            return [
                'actual' => (float) $m['value'],
                'yoy_pct' => strtolower($m['direction']) === 'down' ? -$yoy : $yoy,
            ];
        }

        // Two-column tabular fallback with no space at all between the label and either dollar
        // figure, e.g. "Revenue$2,078 $982" (current quarter, prior-year quarter side by side —
        // common in GAAP income-statement tables that don't spell out a YoY %). Derives yoy_pct
        // ourselves from the two figures instead of leaving it null.
        if (preg_match('/' . self::REVENUE_LABEL_PATTERN . '\s*\$(?<current>\d[\d,.]*|—)\s*\$(?<prior>\d[\d,.]*|—)/iu', $raw, $m, PREG_OFFSET_CAPTURE) === 1) {
            $current = $this->parseSecNumber($m['current'][0]);
            $prior = $this->parseSecNumber($m['prior'][0]);
            return [
                'actual' => $current * $this->detectTableScale($raw, $m[0][1]),
                'yoy_pct' => $prior > 0 ? round((($current - $prior) / $prior) * 100, 2) : null,
            ];
        }

        // Tabular financial-statement fallback: "Revenues .... $61" (dot-leaders already collapsed).
        // Also covers an opening bullet like "Revenues and grant income of $54.6 million" — when the
        // matched figure is immediately followed by "million"/"billion", that beats any document-wide
        // scale lookup, since this can be the very first mention of revenue in the whole release,
        // before any table (and its own scale declaration) has even appeared yet.
        if (preg_match('/' . self::REVENUE_LABEL_PATTERN . '\s*\$(?<value>\d[\d,.]*|—)\s*(?<unit>million|billion)?/iu', $raw, $m, PREG_OFFSET_CAPTURE) === 1) {
            $scale = $this->scaleFromUnitWord($m['unit'][0] ?? '') ?? $this->detectTableScale($raw, $m[0][1]);
            return [
                'actual' => $this->parseSecNumber($m['value'][0]) * $scale,
                'yoy_pct' => null,
            ];
        }

        // Highlights-table fallback (no "$", figures already in the stated table unit): "Revenues
        // 105.1 582.3 454%" -> current=582.3, yoy=454%.
        $highlight = $this->extractHighlightRow($raw, self::REVENUE_LABEL_PATTERN);
        if ($highlight !== null) {
            return [
                'actual' => $highlight['current'] * $this->detectTableScale($raw, $highlight['offset']),
                'yoy_pct' => $highlight['yoy_pct'],
            ];
        }

        return ['actual' => null, 'yoy_pct' => null];
    }

    private function extractEps(string $raw): array
    {
        if (preg_match('/GAAP EPS(?:, Diluted)?\$?(?<value>\d[\d.]*)/iu', $raw, $m) === 1) {
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
        if (preg_match('/Net (?:income|loss) per (?:common )?share attributable to common (?:stockholders|shareholders)[^$]{0,160}\$(?<full>\(?\d[\d.]*\)?)/iu', $raw, $m) === 1) {
            return [
                'actual' => $this->parseSecNumber($m['full']),
                'yoy_pct' => null,
            ];
        }

        // No-space tabular fallback: "Diluted net loss per share$(1.40)" (no gap between the label
        // and "$", common in condensed GAAP statements). Diluted preferred over Basic when both are
        // present, since it's the more conservative/standard figure.
        if (preg_match('/Diluted net (?:income|loss) per share\s*\$(?<full>\(?\d[\d.]*\)?)/iu', $raw, $m) === 1) {
            return [
                'actual' => $this->parseSecNumber($m['full']),
                'yoy_pct' => null,
            ];
        }

        if (preg_match('/Basic net (?:income|loss) per share\s*\$(?<full>\(?\d[\d.]*\)?)/iu', $raw, $m) === 1) {
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
        if (preg_match('/Net income \/ \(loss\) per (?:Class ?A and Class ?B|common)\s*share:.*?Diluted\s+(?<prior>-?\(?\d[\d,.]*\)?|—)\s+(?<current>-?\(?\d[\d,.]*\)?|—)/isu', $raw, $m) === 1) {
            return [
                'actual' => $this->parseSecNumber($m['current']),
                'yoy_pct' => null,
            ];
        }

        return ['actual' => null, 'yoy_pct' => null];
    }

    private function extractAdjustedOperatingMargin(string $raw): array
    {
        if (preg_match('/Adjusted Income from Operations\$?(?<value>\d[\d,]*)\s*(?<margin>\d[\d.]*)\s*%/iu', $raw, $m) === 1) {
            return [
                'actual' => (float) $m['margin'],
                'value_musd' => $this->thousandsToMillions($m['value']),
                'yoy_pct' => null,
            ];
        }

        if (preg_match('/Adjusted income from operations of \$(?<value>\d[\d.]*)\s*billion, representing a\s*(?<margin>\d[\d.]*)%\s*margin/iu', $raw, $m) === 1) {
            return [
                'actual' => (float) $m['margin'],
                'value_musd' => (float) $m['value'] * 1000,
                'yoy_pct' => null,
            ];
        }

        // Direct disclosure fallback: some statements print the margin percentage itself right next
        // to the label, e.g. "Operating loss margin(7)%(3)%" (current, prior). Prefer this over
        // deriving it ourselves whenever it's available.
        if (preg_match('/Operating (?:income|loss) margin\s*(?<full>\(?\d[\d.]*\)?)\s*%/iu', $raw, $m) === 1) {
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
        $operatingIncomeMatch = $this->extractLabeledAmount($raw, 'Operating (?:income|loss)');
        $revenueRaw = $this->extractRawTabularRevenue($raw);
        if ($operatingIncomeMatch !== null && $revenueRaw !== null && $revenueRaw >= 1000) {
            $scale = $this->detectTableScale($raw, $operatingIncomeMatch['offset']);
            return [
                'actual' => round(($operatingIncomeMatch['value'] / $revenueRaw) * 100, 2),
                'value_musd' => $operatingIncomeMatch['value'] * $scale,
                'yoy_pct' => null,
            ];
        }

        // Highlights/statement-table fallback (no "$", e.g. "Loss from operations (111.2) (175.9)"
        // alongside "Revenues 105.1 582.3 454%"). Both values are already in the table's stated
        // unit, so no threshold guard is needed here (unlike the dot-leader case above, this format
        // is used by companies that already report meaningful revenue).
        $operatingResultMatch = $this->extractSecondTableValue($raw, 'Loss from operations|Income from operations');
        $revenueHighlight = $this->extractHighlightRow($raw, self::REVENUE_LABEL_PATTERN);
        if ($operatingResultMatch !== null && $revenueHighlight !== null && $revenueHighlight['current'] > 0) {
            $scale = $this->detectTableScale($raw, $operatingResultMatch['offset']);
            return [
                'actual' => round(($operatingResultMatch['value'] / $revenueHighlight['current']) * 100, 2),
                'value_musd' => $operatingResultMatch['value'] * $scale,
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
        if (preg_match('/' . self::REVENUE_LABEL_PATTERN . '\s+\$(?<value>\d[\d,.]*|—)/iu', $raw, $m) !== 1) {
            return null;
        }

        return $this->parseSecNumber($m['value']);
    }

    private function extractAdjustedFreeCashFlow(string $raw): array
    {
        if (preg_match('/Adjusted Free Cash Flow\$?(?<value>\d[\d,]*)\s*(?<margin>\d[\d.]*)\s*%/iu', $raw, $m) === 1) {
            return [
                'actual' => $this->thousandsToMillions($m['value']),
                'margin_pct' => (float) $m['margin'],
                'yoy_pct' => null,
            ];
        }

        if (preg_match('/Adjusted free cash flow of \$(?<value>\d[\d.]*)\s*billion, representing a\s*(?<margin>\d[\d.]*)%\s*margin/iu', $raw, $m) === 1) {
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
        // "Net cash flows used in..." (with "flows") is just as common as "Net cash used in...".
        $operatingCashFlowMatch = $this->extractLabeledAmount($raw, 'Net cash (?:flows? )?(?:provided by|used in) operating activities');
        $capexMatch = $this->extractLabeledAmount($raw, '(?:Purchases? of property(?:,? plant(?:,? and|,) equipment)?|Capital expenditures)[^0-9($]{0,80}');
        if ($operatingCashFlowMatch !== null && $capexMatch !== null) {
            $scale = $this->detectTableScale($raw, $operatingCashFlowMatch['offset']);
            $fcfRaw = $operatingCashFlowMatch['value'] - abs($capexMatch['value']);
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
        if (preg_match('/raising our revenue guidance to between \$(?<low>\d[\d.]*)\s*[–-]\s*\$(?<high>\d[\d.]*)\s*billion/iu', $raw, $m) === 1) {
            return (((float) $m['low']) + ((float) $m['high'])) / 2 * 1000;
        }

        return null;
    }

    private function extractFullYearGuidanceYoy(string $raw): ?float
    {
        if (preg_match('/Raises FY 2026 Revenue Guidance to\s*(?<yoy>\d[\d.]*)%\s*Y\/Y Growth/iu', $raw, $m) === 1) {
            return (float) $m['yoy'];
        }

        return null;
    }

    private function extractCashPosition(string $raw): ?float
    {
        if (preg_match('/Cash, cash equivalents, and short-term U\.S\. Treasury securities of \$(?<value>\d[\d.]*)\s*billion/iu', $raw, $m) === 1) {
            return (float) $m['value'] * 1000;
        }

        // Tabular fallback: balance sheet "Cash and cash equivalents $X" line (also matches with no
        // gap at all, e.g. "Cash and cash equivalents$2,244").
        if (preg_match('/Cash and cash equivalents\s*\$(?<value>\d[\d,.]*)/iu', $raw, $m, PREG_OFFSET_CAPTURE) === 1) {
            return $this->parseSecNumber($m['value'][0]) * $this->detectTableScale($raw, $m[0][1]);
        }

        // Balance-sheet fallback without "$" (e.g. "Cash and cash equivalents 3,678.1 8,042.1" for
        // prior year-end vs. current period end).
        $balanceSheetCashMatch = $this->extractSecondTableValue($raw, 'Cash and cash equivalents');
        if ($balanceSheetCashMatch !== null) {
            return $balanceSheetCashMatch['value'] * $this->detectTableScale($raw, $balanceSheetCashMatch['offset']);
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

    /**
     * Expenses aren't inherently good or bad on their own — only relative to revenue. 'good' when
     * the business is operating profitably (expenses under revenue), 'bad' when losing money at the
     * operating level, 'warning' when either figure is unknown.
     */
    private function expenseSignal(?float $expenses, ?float $revenue): string
    {
        if ($expenses === null || $revenue === null) {
            return 'warning';
        }

        return $expenses <= $revenue ? 'good' : 'bad';
    }

    /**
     * 'bad' when interest expense alone exceeds operating income — i.e. the business can't cover
     * its debt cost from operations and is relying on financing (more debt, equity, cash reserves)
     * to stay afloat, regardless of how fast revenue is growing.
     */
    private function interestCoverageSignal(?float $interestExpense, ?float $revenue, ?float $operatingExpenses): string
    {
        if ($interestExpense === null || $revenue === null || $operatingExpenses === null) {
            return 'warning';
        }

        $operatingIncome = $revenue - $operatingExpenses;
        return $interestExpense > max($operatingIncome, 0) ? 'bad' : 'good';
    }

    private function buildKpis(array $metrics): array
    {
        return [
            // Resultado del trimestre: el veredicto final — cuánto ganó/perdió la empresa y si esa
            // ganancia se tradujo en caja real.
            ['key' => 'net_income', 'category' => 'result', 'label' => 'Resultado Neto', 'subtitle' => 'Ganancia o pérdida total del trimestre', 'actual' => $metrics['net_income']['actual'], 'actual_unit' => 'MUSD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => $this->comparisonSignal($metrics['net_income']['actual'], 0, strictlyGreater: true), 'why_it_matters' => 'El número absoluto (no por acción) de cuánto ganó o perdió la empresa en dólares — más fácil de comparar contra el revenue y el gasto operativo.'],
            ['key' => 'eps', 'category' => 'result', 'label' => 'EPS diluido', 'subtitle' => 'Ganancia por acción para el accionista', 'actual' => $metrics['eps']['actual'], 'actual_unit' => 'USD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => $this->comparisonSignal($metrics['eps']['actual'], 0, strictlyGreater: true), 'why_it_matters' => 'Resume la rentabilidad atribuible al accionista. Si el quarter vende mucho pero no deja beneficio, la lectura cambia.'],
            ['key' => 'free_cash_flow', 'category' => 'result', 'label' => 'Free Cash Flow', 'subtitle' => 'Caja libre generada por el negocio', 'actual' => $metrics['free_cash_flow']['actual'], 'actual_unit' => 'MUSD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => $this->comparisonSignal($metrics['free_cash_flow']['actual'], 0, strictlyGreater: true), 'why_it_matters' => 'La caja libre reduce el riesgo de que el crecimiento dependa de refinanciación, deuda o dilución. Puede ser negativa aunque el resultado neto sea positivo, o viceversa.'],

            // Ingresos: lo que entra, y lo que la empresa dice que va a entrar.
            ['key' => 'revenue', 'category' => 'income', 'label' => 'Revenue', 'subtitle' => 'Ingresos del trimestre', 'actual' => $metrics['revenue']['actual'], 'actual_unit' => 'MUSD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => $metrics['revenue']['yoy_pct'], 'signal' => $this->comparisonSignal($metrics['revenue']['yoy_pct'], 20), 'why_it_matters' => 'Mide si el negocio principal acelera o se enfría. Sin crecimiento real, el quarter pierde fuerza rápido.'],
            ['key' => 'guidance_revenue', 'category' => 'income', 'label' => 'Guidance FY revenue', 'subtitle' => 'Previsión de ingresos futura', 'actual' => $metrics['guidance_revenue'], 'actual_unit' => 'MUSD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => $metrics['guidance_revenue_yoy'], 'signal' => $metrics['guidance_revenue'] !== null ? 'good' : 'warning', 'why_it_matters' => 'Si la directiva sube guidance, el mercado interpreta que el quarter no fue un simple accidente aislado.'],

            // Gastos: todo lo que se resta entre el revenue y el resultado neto, en el orden en que
            // realmente se resta (operativo -> interés/deuda -> impuestos).
            ['key' => 'operating_expenses', 'category' => 'expense', 'label' => 'Gasto Operativo', 'subtitle' => 'Costos y gastos totales del trimestre', 'actual' => $metrics['operating_expenses']['actual'], 'actual_unit' => 'MUSD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => $this->expenseSignal($metrics['operating_expenses']['actual'], $metrics['revenue']['actual']), 'why_it_matters' => 'Pone el ingreso en contexto: si el gasto crece más rápido que el ingreso, el negocio se vuelve menos eficiente aunque venda más.'],
            ['key' => 'operating_margin', 'category' => 'expense', 'label' => 'Operating Margin', 'subtitle' => 'Rentabilidad operativa sobre ventas', 'actual' => $metrics['operating_margin']['actual'], 'actual_unit' => '%', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => $this->comparisonSignal($metrics['operating_margin']['actual'], 20), 'why_it_matters' => 'Nos dice si el crecimiento está entrando con calidad o si se compra a costa de gastar demasiado.'],
            ['key' => 'interest_expense', 'category' => 'expense', 'label' => 'Gasto por Intereses', 'subtitle' => 'Costo de la deuda del trimestre', 'actual' => $metrics['interest_expense']['actual'], 'actual_unit' => 'MUSD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => $this->interestCoverageSignal($metrics['interest_expense']['actual'], $metrics['revenue']['actual'], $metrics['operating_expenses']['actual']), 'why_it_matters' => 'Si el resultado operativo no alcanza para cubrir esto, la empresa depende de más deuda, capital nuevo o caja acumulada — no de lo que genera el negocio.'],
            ['key' => 'taxes', 'category' => 'expense', 'label' => 'Impuestos', 'subtitle' => 'Provisión de impuestos del trimestre', 'actual' => $metrics['taxes']['actual'], 'actual_unit' => 'MUSD', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => 'warning', 'why_it_matters' => 'Una empresa puede pagar impuestos incluso en un trimestre con pérdida neta (por ejemplo, por subsidiarias rentables en otros países) — vale la pena notarlo, no juzgarlo como bueno o malo por sí solo.'],

            // Contexto de mercado: no es parte del estado de resultados, es sobre cómo está pagando
            // el mercado por lo anterior.
            ['key' => 'valuation_risk', 'category' => 'context', 'label' => 'Valuation Risk', 'subtitle' => 'Riesgo de que la acción ya esté demasiado cara', 'actual' => 1, 'actual_unit' => 'flag', 'estimate' => null, 'surprise_pct' => null, 'yoy_pct' => null, 'signal' => ($metrics['revenue']['yoy_pct'] ?? 0) >= 50 ? 'bad' : 'warning', 'why_it_matters' => 'Un gran quarter no implica automáticamente una gran compra si la acción ya cotiza con expectativas extremas.'],
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
