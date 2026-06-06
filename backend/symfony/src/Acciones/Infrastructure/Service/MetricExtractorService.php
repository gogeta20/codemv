<?php

namespace App\Acciones\Infrastructure\Service;

/**
 * Receives raw parsed CSV arrays and extracts normalized metrics for scoring.
 * All monetary values are kept in the same unit as the source CSV (usually thousands).
 */
final class MetricExtractorService
{
    public function extract(array $rawIncome, array $rawBalance, array $rawCashflow): array
    {
        $incomeYears  = $this->getNumericYears($rawIncome);
        $balanceYears = $this->getNumericYears($rawBalance);

        $lastYear  = end($incomeYears)  ?: null;
        $prevYear  = count($incomeYears) >= 2 ? $incomeYears[count($incomeYears) - 2] : null;
        $year3ago  = count($incomeYears) >= 4 ? $incomeYears[count($incomeYears) - 4] : ($incomeYears[0] ?? null);
        $firstYear = $incomeYears[0] ?? null;

        $lastBalYear = end($balanceYears) ?: null;
        $bal3ago     = count($balanceYears) >= 4 ? $balanceYears[count($balanceYears) - 4] : ($balanceYears[0] ?? null);
        $firstBalYear = $balanceYears[0] ?? null;

        // --- Income ---
        $revTtm       = $this->val($rawIncome, 'revenues', 'TTM');
        $revLastYear  = $this->val($rawIncome, 'revenues', $lastYear);
        $revPrevYear  = $this->val($rawIncome, 'revenues', $prevYear);
        $rev3ago      = $this->val($rawIncome, 'revenues', $year3ago);

        // Ratio metrics are meaningless when revenue = 0 (division by 0 artifact in source)
        $hasRevenue   = ($revTtm !== null && $revTtm != 0);
        $gmTtm        = $hasRevenue ? $this->val($rawIncome, 'gross_profit_ratio', 'TTM') : null;
        $gmLastYear   = $hasRevenue ? $this->val($rawIncome, 'gross_profit_ratio', $lastYear) : null;
        $gm3ago       = $hasRevenue ? $this->val($rawIncome, 'gross_profit_ratio', $year3ago) : null;

        $niTtm        = $this->val($rawIncome, 'net_income', 'TTM');
        $niLastYear   = $this->val($rawIncome, 'net_income', $lastYear);
        $niPrevYear   = $this->val($rawIncome, 'net_income', $prevYear);

        $opIncTtm     = $this->val($rawIncome, 'operating_income', 'TTM');
        $opIncLastYear= $this->val($rawIncome, 'operating_income', $lastYear);

        $ebitdaTtm    = $this->val($rawIncome, 'ebitda', 'TTM');

        $rdTtm        = $this->val($rawIncome, 'rd_expenses', 'TTM');
        $rdLastYear   = $this->val($rawIncome, 'rd_expenses', $lastYear);
        $rdPrevYear   = $this->val($rawIncome, 'rd_expenses', $prevYear);
        $rdFirstYear  = $this->val($rawIncome, 'rd_expenses', $firstYear);

        $sgaTtm       = $this->val($rawIncome, 'sga_expenses', 'TTM');

        $sharesLast   = $this->val($rawIncome, 'outstanding_shares', $lastYear);
        $sharesFirst  = $this->val($rawIncome, 'outstanding_shares', $firstYear);

        $costRevTtm   = $this->val($rawIncome, 'cost_of_revenue', 'TTM');
        $costRevLastYear = $this->val($rawIncome, 'cost_of_revenue', $lastYear);
        $costRev3ago  = $this->val($rawIncome, 'cost_of_revenue', $year3ago);
        $rev3agoForCost = $this->val($rawIncome, 'revenues', $year3ago);

        // --- Cashflow ---
        $fcfTtm       = $this->val($rawCashflow, 'free_cash_flow', 'TTM');
        $fcfLastYear  = $this->val($rawCashflow, 'free_cash_flow', $lastYear);
        $fcfPrevYear  = $this->val($rawCashflow, 'free_cash_flow', $prevYear);

        $opsTtm       = $this->val($rawCashflow, 'cash_from_operations', 'TTM');
        $opsLastYear  = $this->val($rawCashflow, 'cash_from_operations', $lastYear);
        $opsPrevYear  = $this->val($rawCashflow, 'cash_from_operations', $prevYear);
        $ops3ago      = $this->val($rawCashflow, 'cash_from_operations', $year3ago);

        $capexTtm     = $this->val($rawCashflow, 'capex', 'TTM');
        $capexLastYear= $this->val($rawCashflow, 'capex', $lastYear);
        $capex3ago    = $this->val($rawCashflow, 'capex', $year3ago);

        $divTtm       = $this->val($rawCashflow, 'dividends_paid', 'TTM');
        $divLastYear  = $this->val($rawCashflow, 'dividends_paid', $lastYear);
        $divPrevYear  = $this->val($rawCashflow, 'dividends_paid', $prevYear);
        $divFirstYear = $this->val($rawCashflow, 'dividends_paid', $firstYear);

        $stockIssuedTtm = $this->val($rawCashflow, 'stock_issued', 'TTM');

        // --- Balance ---
        $cashLast     = $this->val($rawBalance, 'cash_and_short_term', 'Last Report');
        $debtLast     = $this->val($rawBalance, 'total_debt', 'Last Report');
        $debtLastYear = $this->val($rawBalance, 'total_debt', $lastBalYear);
        $debt3ago     = $this->val($rawBalance, 'total_debt', $bal3ago);
        $debtFirst    = $this->val($rawBalance, 'total_debt', $firstBalYear);
        $netDebtLast  = $this->val($rawBalance, 'net_debt', 'Last Report');
        $curAssLast   = $this->val($rawBalance, 'total_current_assets', 'Last Report');
        $curLiabLast  = $this->val($rawBalance, 'total_current_liabilities', 'Last Report');
        $ltDebtLast   = $this->val($rawBalance, 'long_term_debt', 'Last Report');

        // --- Derived metrics ---

        $revenueTrend3y      = $this->trend($rev3ago, $revLastYear, 0.05);
        $revenueGrowth2yPct  = $this->growthPct($revPrevYear, $revLastYear);
        $grossMarginTrend3y  = $this->trendAbsolute($gm3ago, $gmLastYear, 1.0);
        $grossMarginDecline3y = ($gm3ago !== null && $gmLastYear !== null) ? round($gm3ago - $gmLastYear, 2) : null;
        $opsTrend3y          = $this->trend($ops3ago, $opsLastYear, 0.10);
        $debtTrend3y         = $this->trend($debt3ago, $debtLastYear, 0.05);

        $currentRatio   = ($curLiabLast && $curLiabLast != 0) ? round($curAssLast / $curLiabLast, 2) : null;
        $netDebtToEbitda = ($ebitdaTtm && $ebitdaTtm > 0 && $netDebtLast !== null)
            ? round($netDebtLast / $ebitdaTtm, 2)
            : null;

        $fcfPositiveLast2 = ($fcfLastYear !== null && $fcfPrevYear !== null)
            ? ($fcfLastYear >= 0 && $fcfPrevYear >= 0)
            : ($fcfLastYear !== null ? $fcfLastYear >= 0 : false);

        $niWasPositive = $this->wasPositiveBeforeGoingNegative($rawIncome, 'net_income', $incomeYears, $niLastYear);

        $rdVsRevenue   = ($revTtm !== null && $revTtm != 0 && $rdTtm !== null)
            ? round($rdTtm / $revTtm, 4)
            : null;
        $rdIsGrowing   = ($rdLastYear !== null && $rdPrevYear !== null) ? $rdLastYear > $rdPrevYear : false;

        $capexExplainsFcfGap = ($opsTtm !== null && $opsTtm > 0 && $fcfTtm !== null && $fcfTtm < 0);

        $runwayYears = $this->calcRunway($cashLast, $opsLastYear);

        $paysDividends      = ($divTtm !== null && $divTtm < 0); // dividends stored as negative
        $divAbsTtm          = $paysDividends ? abs($divTtm) : 0.0;
        $fcfCoversDiv       = $paysDividends && $fcfTtm !== null ? $fcfTtm > $divAbsTtm : null;
        $payoutRatio        = ($paysDividends && $niTtm !== null && $niTtm > 0)
            ? round($divAbsTtm / $niTtm, 4)
            : null;
        $dividendTrend      = $this->calcDividendTrend($divTtm, $divPrevYear, $divFirstYear);

        $sharesDilutionPct  = ($sharesFirst && $sharesFirst != 0 && $sharesLast !== null)
            ? round(($sharesLast - $sharesFirst) / $sharesFirst * 100, 2)
            : null;
        $sharesTrend        = $this->calcSharesTrend($sharesDilutionPct);

        $sgaPctRevenue      = ($revTtm && $revTtm != 0 && $sgaTtm !== null)
            ? round($sgaTtm / $revTtm * 100, 2)
            : null;

        $costRevRatioTtm    = ($revTtm && $revTtm != 0 && $costRevTtm !== null)
            ? $costRevTtm / $revTtm
            : null;
        $costRevRatio3ago   = ($rev3agoForCost && $rev3agoForCost != 0 && $costRev3ago !== null)
            ? $costRev3ago / $rev3agoForCost
            : null;
        $costRevTrend3y     = $this->trendRatioImproving($costRevRatio3ago, $costRevRatioTtm, 0.01);

        $capexTrend3y       = $this->trendAbsolute(
            $capex3ago !== null ? abs($capex3ago) : null,
            $capexLastYear !== null ? abs($capexLastYear) : null,
            0.10
        );

        $rdTrend = $this->calcRdTrend($rdFirstYear, $rdLastYear, $rdPrevYear, $rdTtm);

        return [
            // Periods info
            'years'            => $incomeYears,
            'last_year'        => $lastYear,
            'first_year'       => $firstYear,

            // Revenue
            'revenues_ttm'         => $revTtm,
            'revenues_last_year'   => $revLastYear,
            'revenue_growth_2y_pct'=> $revenueGrowth2yPct,
            'revenue_trend_3y'     => $revenueTrend3y,

            // Gross margin
            'gross_profit_ratio_ttm'      => $gmTtm,
            'gross_profit_ratio_last_year'=> $gmLastYear,
            'gross_margin_trend_3y'       => $grossMarginTrend3y,
            'gross_margin_decline_3y_pp'  => $grossMarginDecline3y,

            // Net income
            'net_income_ttm'       => $niTtm,
            'net_income_last_year' => $niLastYear,
            'net_income_was_positive' => $niWasPositive,

            // Operating income
            'operating_income_ttm'       => $opIncTtm,
            'operating_income_last_year' => $opIncLastYear,

            // EBITDA
            'ebitda_ttm' => $ebitdaTtm,

            // FCF
            'fcf_ttm'                => $fcfTtm,
            'fcf_last_year'          => $fcfLastYear,
            'fcf_positive_last_2y'   => $fcfPositiveLast2,
            'capex_explains_fcf_gap' => $capexExplainsFcfGap,

            // Operations
            'cash_from_ops_ttm'      => $opsTtm,
            'cash_from_ops_last_year'=> $opsLastYear,
            'cash_from_ops_trend_3y' => $opsTrend3y,

            // CapEx
            'capex_ttm'       => $capexTtm,
            'capex_trend_3y'  => $capexTrend3y,

            // R&D
            'rd_expenses_ttm'  => $rdTtm,
            'rd_vs_revenue'    => $rdVsRevenue,
            'rd_is_growing'    => $rdIsGrowing,
            'rd_trend'         => $rdTrend,

            // Balance
            'cash_and_short_term'  => $cashLast,
            'total_debt'           => $debtLast,
            'net_debt'             => $netDebtLast,
            'long_term_debt'       => $ltDebtLast,
            'total_debt_trend_3y'  => $debtTrend3y,
            'net_debt_to_ebitda'   => $netDebtToEbitda,
            'current_ratio'        => $currentRatio,

            // Runway
            'runway_years' => $runwayYears,

            // Dividends
            'pays_dividends'     => $paysDividends,
            'dividends_paid_ttm' => $divAbsTtm,
            'fcf_covers_dividends'=> $fcfCoversDiv,
            'payout_ratio'       => $payoutRatio,
            'dividend_trend'     => $dividendTrend,

            // Dilution / Buybacks
            'shares_dilution_pct' => $sharesDilutionPct,
            'shares_trend'        => $sharesTrend,
            'stock_issued_ttm'    => $stockIssuedTtm,

            // Expenses
            'sga_pct_revenue'      => $sgaPctRevenue,
            'cost_rev_trend_3y'    => $costRevTrend3y,
        ];
    }

    // --- Helpers ---

    private function val(array $raw, string $metric, ?string $period): ?float
    {
        if ($period === null || !isset($raw[$metric][$period])) {
            return null;
        }
        return $raw[$metric][$period];
    }

    private function getNumericYears(array $raw): array
    {
        if (empty($raw)) {
            return [];
        }
        $first = reset($raw);
        $years = array_filter(array_keys($first), fn($k) => is_numeric($k));
        sort($years);
        return array_values($years);
    }

    /** trend() compares two values with a relative threshold */
    private function trend(?float $from, ?float $to, float $threshold): string
    {
        if ($from === null || $to === null || $from == 0) {
            return 'stable';
        }
        $change = ($to - $from) / abs($from);
        if ($change > $threshold)  return 'growing';
        if ($change < -$threshold) return 'declining';
        return 'stable';
    }

    /** trendAbsolute() for percentage metrics like gross margin (compares in pp) */
    private function trendAbsolute(?float $from, ?float $to, float $thresholdPp): string
    {
        if ($from === null || $to === null) {
            return 'stable';
        }
        $delta = $to - $from;
        if ($delta > $thresholdPp)  return 'improving';
        if ($delta < -$thresholdPp) return 'deteriorating';
        return 'stable';
    }

    /** trendRatioImproving() for cost ratios — lower is better */
    private function trendRatioImproving(?float $from, ?float $to, float $threshold): string
    {
        if ($from === null || $to === null) {
            return 'stable';
        }
        $delta = $to - $from;
        if ($delta < -$threshold) return 'improving';   // ratio went down = better
        if ($delta > $threshold)  return 'deteriorating';
        return 'stable';
    }

    private function growthPct(?float $from, ?float $to): ?float
    {
        if ($from === null || $to === null || $from == 0) {
            return null;
        }
        return round(($to - $from) / abs($from) * 100, 2);
    }

    private function calcRunway(?float $cash, ?float $opsLastYear): ?float
    {
        if ($cash === null || $opsLastYear === null || $opsLastYear >= 0) {
            return null; // no concern when ops are positive
        }
        return round($cash / abs($opsLastYear), 1);
    }

    private function wasPositiveBeforeGoingNegative(
        array $raw,
        string $metric,
        array $years,
        ?float $currentValue
    ): bool {
        if ($currentValue === null || $currentValue >= 0) {
            return false; // currently positive, not applicable
        }
        // Check if it was positive in earlier years
        foreach (array_slice($years, 0, -1) as $year) {
            $v = $raw[$metric][$year] ?? null;
            if ($v !== null && $v > 0) {
                return true;
            }
        }
        return false;
    }

    private function calcDividendTrend(?float $divTtm, ?float $divPrevYear, ?float $divFirstYear): string
    {
        // dividends are stored as negative values
        if ($divTtm === null || $divTtm >= 0) {
            return 'none';
        }
        $absTtm   = abs($divTtm);
        $absPrev  = $divPrevYear !== null ? abs($divPrevYear) : null;
        $absFirst = $divFirstYear !== null ? abs($divFirstYear) : null;

        // Detect cut: current vs peak (use first year or prev year as reference)
        $peak = max(array_filter([$absFirst, $absPrev], fn($v) => $v !== null) ?: [0]);
        if ($peak > 0 && $absTtm < $peak * 0.80) {
            return 'cut';
        }
        if ($absPrev !== null && $absTtm > $absPrev * 1.05) {
            return 'growing';
        }
        return 'stable';
    }

    private function calcSharesTrend(?float $dilutionPct): string
    {
        if ($dilutionPct === null) return 'stable';
        if ($dilutionPct < -2)    return 'buybacks';
        if ($dilutionPct > 5)     return 'diluting';
        return 'stable';
    }

    private function calcRdTrend(
        ?float $rdFirst,
        ?float $rdLast,
        ?float $rdPrev,
        ?float $rdTtm
    ): string {
        // No R&D ever → 'none'
        if (($rdTtm === null || $rdTtm == 0) && ($rdLast === null || $rdLast == 0)) {
            return 'none';
        }
        // Growing if current > previous
        if ($rdTtm !== null && $rdPrev !== null && $rdTtm > $rdPrev) {
            return 'investing';
        }
        // Cutting if R&D existed before but dropped significantly
        if ($rdFirst !== null && $rdFirst > 0 && $rdLast !== null && $rdLast < $rdFirst * 0.80) {
            return 'cutting';
        }
        if ($rdTtm !== null && $rdTtm > 0) {
            return 'investing';
        }
        return 'none';
    }
}
