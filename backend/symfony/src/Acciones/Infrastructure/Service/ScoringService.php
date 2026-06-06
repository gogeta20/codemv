<?php

namespace App\Acciones\Infrastructure\Service;

/**
 * Applies the 6-phase scoring algorithm to a normalized metrics array.
 * Returns score, speculative_type, investment_type, stage, and score_detail.
 */
final class ScoringService
{
    public function score(array $metrics): array
    {
        $fase1 = $this->runFase1($metrics);

        if (!$fase1['passed']) {
            return $this->evalSubCriteria($metrics, $fase1);
        }

        return $this->evalFases2to5($metrics, $fase1);
    }

    // -------------------------------------------------------------------------
    // FASE 1
    // -------------------------------------------------------------------------

    private function runFase1(array $m): array
    {
        $fcfOk = (bool) $m['fcf_positive_last_2y'];
        $niOk  = $m['net_income_last_year'] !== null && $m['net_income_last_year'] > 0;
        $opOk  = $m['operating_income_last_year'] !== null && $m['operating_income_last_year'] > 0;

        $passed = $fcfOk && $niOk && $opOk;

        return [
            'passed' => $passed,
            'fcf_ok' => $fcfOk,
            'ni_ok'  => $niOk,
            'op_ok'  => $opOk,
        ];
    }

    // -------------------------------------------------------------------------
    // SUB-EVALUACIÓN (cuando Fase 1 falla)
    // -------------------------------------------------------------------------

    private function evalSubCriteria(array $m, array $fase1): array
    {
        $detail = ['fase1' => $fase1, 'sub_criteria' => []];

        // Order matters: most forgiving → most severe
        if ($this->isCaminoA($m)) {
            $detail['sub_criteria'] = $this->buildCaminoADetail($m);
            return $this->buildResult('especulativo', 'expansion', null, 'expansion_infra', $detail);
        }

        if ($this->isCaminoB2($m)) {
            $detail['sub_criteria'] = $this->buildCaminoB2Detail($m);
            return $this->buildResult('especulativo', 'pre_revenue', null, 'pre_revenue_id', $detail);
        }

        if ($this->isCaminoB1($m)) {
            $detail['sub_criteria'] = $this->buildCaminoB1Detail($m);
            return $this->buildResult('especulativo', 'id_activo', null, 'pionera_id', $detail);
        }

        if ($this->isCaminoD($m)) {
            $detail['sub_criteria'] = $this->buildCaminoDDetail($m);
            return $this->buildResult('evitar', null, null, 'deterioro', $detail);
        }

        // Default: Camino C (zombie / sin futuro claro)
        $detail['sub_criteria'] = $this->buildCaminoCDetail($m);
        return $this->buildResult('evitar', null, null, 'sin_futuro', $detail);
    }

    // --- Camino A — Expansión de infraestructura ---

    private function isCaminoA(array $m): bool
    {
        $opsPositive   = $m['cash_from_ops_ttm'] !== null && $m['cash_from_ops_ttm'] > 0;
        $fcfNegative   = $m['fcf_ttm'] !== null && $m['fcf_ttm'] < 0;
        $capexExplains = (bool) $m['capex_explains_fcf_gap'];
        $goodMargin    = $m['gross_profit_ratio_ttm'] !== null && $m['gross_profit_ratio_ttm'] > 40;
        $revenueGrowth = $m['revenue_growth_2y_pct'] !== null && $m['revenue_growth_2y_pct'] > 30;

        return $opsPositive && $fcfNegative && $capexExplains && $goodMargin && $revenueGrowth;
    }

    private function buildCaminoADetail(array $m): array
    {
        return [
            'camino'            => 'A — Expansión infraestructura',
            'ops_positive'      => $m['cash_from_ops_ttm'],
            'fcf_negative'      => $m['fcf_ttm'],
            'gross_margin'      => $m['gross_profit_ratio_ttm'],
            'revenue_growth_2y' => $m['revenue_growth_2y_pct'],
            'capex_ttm'         => $m['capex_ttm'],
        ];
    }

    // --- Camino B2 — Pre-revenue puro ---

    private function isCaminoB2(array $m): bool
    {
        $noRevenue    = $m['revenues_ttm'] !== null && $m['revenues_ttm'] == 0;
        $rdGrowing    = (bool) $m['rd_is_growing'];
        $runwayOk     = $m['runway_years'] !== null && $m['runway_years'] > 5;
        $minimalDebt  = $m['cash_and_short_term'] !== null
            && $m['total_debt'] !== null
            && ($m['total_debt'] == 0 || $m['cash_and_short_term'] > $m['total_debt'] * 10);

        return $noRevenue && $rdGrowing && $runwayOk && $minimalDebt;
    }

    private function buildCaminoB2Detail(array $m): array
    {
        return [
            'camino'        => 'B2 — Pre-revenue puro',
            'revenues_ttm'  => $m['revenues_ttm'],
            'rd_ttm'        => $m['rd_expenses_ttm'],
            'rd_is_growing' => $m['rd_is_growing'],
            'runway_years'  => $m['runway_years'],
            'cash'          => $m['cash_and_short_term'],
            'total_debt'    => $m['total_debt'],
        ];
    }

    // --- Camino B1 — Pionera I+D con algo de facturación ---

    private function isCaminoB1(array $m): bool
    {
        $hasRevenue   = $m['revenues_ttm'] !== null && $m['revenues_ttm'] > 0;
        $rdHigh       = $m['rd_vs_revenue'] !== null && $m['rd_vs_revenue'] > 0.15;
        $runwayOk     = $m['runway_years'] === null || $m['runway_years'] > 2;

        return $hasRevenue && $rdHigh && $runwayOk;
    }

    private function buildCaminoB1Detail(array $m): array
    {
        return [
            'camino'          => 'B1 — Pionera I+D con facturación',
            'revenues_ttm'    => $m['revenues_ttm'],
            'rd_ttm'          => $m['rd_expenses_ttm'],
            'rd_vs_revenue'   => $m['rd_vs_revenue'],
            'runway_years'    => $m['runway_years'],
            'cash'            => $m['cash_and_short_term'],
        ];
    }

    // --- Camino D — Deterioro ---

    private function isCaminoD(array $m): bool
    {
        $wasPositive   = (bool) $m['net_income_was_positive'];
        $nowNegative   = $m['net_income_last_year'] !== null && $m['net_income_last_year'] < 0;
        $revenueDown   = $m['revenue_trend_3y'] === 'declining';
        $marginEroding = $m['gross_margin_trend_3y'] === 'deteriorating';

        // Camino D: was profitable, now losing, AND (revenue falling OR margins eroding)
        return $wasPositive && $nowNegative && ($revenueDown || $marginEroding);
    }

    private function buildCaminoDDetail(array $m): array
    {
        return [
            'camino'               => 'D — Deterioro',
            'was_positive'         => $m['net_income_was_positive'],
            'net_income_last_year' => $m['net_income_last_year'],
            'revenue_trend_3y'     => $m['revenue_trend_3y'],
            'gross_margin_trend_3y'=> $m['gross_margin_trend_3y'],
            'gross_margin_decline_3y_pp' => $m['gross_margin_decline_3y_pp'],
            'dividend_trend'       => $m['dividend_trend'],
        ];
    }

    // --- Camino C — Sin futuro claro (default EVITAR) ---

    private function buildCaminoCDetail(array $m): array
    {
        return [
            'camino'               => 'C — Sin futuro claro',
            'cash_from_ops_ttm'    => $m['cash_from_ops_ttm'],
            'gross_profit_ratio'   => $m['gross_profit_ratio_ttm'],
            'revenue_trend_3y'     => $m['revenue_trend_3y'],
            'rd_trend'             => $m['rd_trend'],
            'capex_ttm'            => $m['capex_ttm'],
        ];
    }

    // -------------------------------------------------------------------------
    // FASES 2–5
    // -------------------------------------------------------------------------

    private function evalFases2to5(array $m, array $fase1): array
    {
        $fase2 = $this->runFase2($m);
        $fase3 = $this->runFase3($m);
        $fase4 = $this->runFase4($m);
        $fase5 = $this->runFase5($m);

        $detail = [
            'fase1' => $fase1,
            'fase2' => $fase2,
            'fase3' => $fase3,
            'fase4' => $fase4,
            'fase5' => $fase5,
        ];

        $score          = $this->calcFinalScore($fase2, $fase4, $fase5);
        $investmentType = $this->calcInvestmentType($m, $fase3, $fase2, $score);
        $stage          = $this->calcStage($m);

        return $this->buildResult($score, null, $investmentType, $stage, $detail);
    }

    // -------------------------------------------------------------------------
    // FASE 2 — Tendencias
    // -------------------------------------------------------------------------

    private function runFase2(array $m): array
    {
        $scores = [
            'revenue'      => $this->scoreRevenueTrend($m['revenue_trend_3y']),
            'gross_margin' => $this->scoreGmTrend($m['gross_margin_trend_3y']),
            'ops'          => $this->scoreOpsTrend($m['cash_from_ops_trend_3y']),
            'debt'         => $this->scoreDebtTrend($m['total_debt_trend_3y'], $m['cash_from_ops_ttm']),
            'shares'       => $this->scoreSharesTrend($m['shares_trend']),
        ];

        $positives = count(array_filter($scores, fn($v) => $v === 'positive'));
        $negatives = count(array_filter($scores, fn($v) => $v === 'negative'));

        if ($positives >= 3 && $negatives <= 1) $result = 'positiva';
        elseif ($negatives >= 2)                 $result = 'negativa';
        else                                     $result = 'neutral';

        return [
            'result'          => $result,
            'scores'          => $scores,
            'shares_dilution' => $m['shares_dilution_pct'],
        ];
    }

    private function scoreRevenueTrend(string $trend): string
    {
        return match($trend) {
            'growing'   => 'positive',
            'stable'    => 'neutral',
            'declining' => 'negative',
            default     => 'neutral',
        };
    }

    private function scoreGmTrend(string $trend): string
    {
        return match($trend) {
            'improving'     => 'positive',
            'stable'        => 'neutral',
            'deteriorating' => 'negative',
            default         => 'neutral',
        };
    }

    private function scoreOpsTrend(string $trend): string
    {
        return match($trend) {
            'growing'   => 'positive',
            'stable'    => 'neutral',
            'declining' => 'negative',
            default     => 'neutral',
        };
    }

    private function scoreDebtTrend(string $trend, ?float $ops): string
    {
        if ($trend === 'stable' || $trend === 'declining') {
            return 'positive';
        }
        // Growing debt is neutral (not negative) when ops are strong — franchise model
        if ($ops !== null && $ops > 1_000_000) {
            return 'neutral';
        }
        return 'negative';
    }

    private function scoreSharesTrend(string $trend): string
    {
        return match($trend) {
            'buybacks' => 'positive',
            'stable'   => 'neutral',
            'diluting' => 'negative',
            default    => 'neutral',
        };
    }

    // -------------------------------------------------------------------------
    // FASE 3 — Dividendos
    // -------------------------------------------------------------------------

    private function runFase3(array $m): array
    {
        if (!$m['pays_dividends']) {
            return ['result' => 'no_aplica', 'pays' => false];
        }

        $fcfCovers  = (bool) $m['fcf_covers_dividends'];
        $payout     = $m['payout_ratio'];

        $payoutSignal = match(true) {
            $payout === null        => 'no_calculable',
            $payout <= 0.60         => 'sano',
            $payout <= 0.80         => 'elevado',
            default                 => 'riesgoso',
        };

        $result = ($fcfCovers && $payoutSignal !== 'riesgoso') ? 'buena' : 'debil';

        return [
            'result'         => $result,
            'pays'           => true,
            'fcf_covers'     => $fcfCovers,
            'payout_ratio'   => $payout,
            'payout_signal'  => $payoutSignal,
            'dividend_trend' => $m['dividend_trend'],
            'dividends_ttm'  => $m['dividends_paid_ttm'],
        ];
    }

    // -------------------------------------------------------------------------
    // FASE 4 — Balance Sheet
    // -------------------------------------------------------------------------

    private function runFase4(array $m): array
    {
        $cr        = $m['current_ratio'];
        $nde       = $m['net_debt_to_ebitda'];
        $debtTrend = $m['total_debt_trend_3y'];
        $opsStrong = $m['cash_from_ops_ttm'] !== null && $m['cash_from_ops_ttm'] > 0;

        // Current ratio
        $crScore = match(true) {
            $cr === null            => 'alerta',
            $cr < 1.0               => 'debil',     // always debil, no exceptions
            $cr < 1.5 && $opsStrong => 'alerta',    // franchise exception
            $cr < 1.5               => 'debil',
            default                 => 'fuerte',
        };

        // Net Debt / EBITDA
        $ndeScore = match(true) {
            $nde === null  => 'alerta',
            $nde < 2.0     => 'fuerte',
            $nde <= 4.0    => 'alerta',
            default        => 'debil',
        };

        // Debt trend
        $debtScore = match(true) {
            $debtTrend === 'growing' && !$opsStrong => 'debil',
            $debtTrend === 'growing'                => 'alerta',
            default                                 => 'fuerte',
        };

        $debilCount = count(array_filter([$crScore, $ndeScore, $debtScore], fn($v) => $v === 'debil'));
        $fuerteCount = count(array_filter([$crScore, $ndeScore, $debtScore], fn($v) => $v === 'fuerte'));

        $result = match(true) {
            $debilCount >= 2                 => 'debil',
            $crScore === 'debil'             => 'debil',  // CR<1.0 alone = debil
            $fuerteCount === 3               => 'fuerte',
            default                          => 'alerta',
        };

        return [
            'result'             => $result,
            'current_ratio'      => $cr,
            'current_ratio_score'=> $crScore,
            'net_debt_to_ebitda' => $nde,
            'nde_score'          => $ndeScore,
            'debt_trend_score'   => $debtScore,
            'cash_and_short_term'=> $m['cash_and_short_term'],
            'total_debt'         => $m['total_debt'],
        ];
    }

    // -------------------------------------------------------------------------
    // FASE 5 — Gastos
    // -------------------------------------------------------------------------

    private function runFase5(array $m): array
    {
        // O: cost of revenue ratio trend
        $costRevOk = $m['cost_rev_trend_3y'] !== 'deteriorating';

        // P: R&D — 'cutting' is bad; 'none' is ok for mature non-tech; 'investing' is good
        $rdOk = $m['rd_trend'] !== 'cutting';

        // Q: SGA % of revenues
        $sgaOk = $m['sga_pct_revenue'] === null || $m['sga_pct_revenue'] < 30;

        // R: CapEx — 'deteriorating' = cutting investment = bad
        $capexOk = $m['capex_trend_3y'] !== 'deteriorating';

        $okCount = (int)$costRevOk + (int)$rdOk + (int)$sgaOk + (int)$capexOk;

        $result = match(true) {
            $okCount >= 3 => 'buena',
            $okCount === 2 => 'regular',
            default        => 'mala',
        };

        return [
            'result'           => $result,
            'cost_rev_trend'   => $m['cost_rev_trend_3y'],
            'rd_trend'         => $m['rd_trend'],
            'sga_pct_revenue'  => $m['sga_pct_revenue'],
            'capex_trend'      => $m['capex_trend_3y'],
            'ok_count'         => $okCount,
        ];
    }

    // -------------------------------------------------------------------------
    // Final score + type + stage
    // -------------------------------------------------------------------------

    private function calcFinalScore(array $fase2, array $fase4, array $fase5): string
    {
        $tendenciasOk = $fase2['result'] !== 'negativa';
        $balanceOk    = $fase4['result'] !== 'debil';
        $gastosOk     = $fase5['result'] !== 'mala';

        return ($tendenciasOk && $balanceOk && $gastosOk) ? 'comprar' : 'vigilar';
    }

    private function calcInvestmentType(array $m, array $fase3, array $fase2, string $score): ?string
    {
        if ($score === 'comprar') {
            if ($fase3['result'] === 'buena' && $m['dividend_trend'] !== 'cut') {
                return 'dividend_aristocrat';
            }
            if (!$m['pays_dividends'] && $m['revenue_trend_3y'] === 'growing') {
                return 'growth';
            }
        }

        if ($score === 'vigilar') {
            return $fase2['result'] === 'negativa' ? 'value_trap' : 'recovery_play';
        }

        return null;
    }

    private function calcStage(array $m): string
    {
        if ($m['pays_dividends']) {
            return 'madura';
        }
        if ($m['revenue_trend_3y'] === 'growing') {
            return 'growth_rentable';
        }
        return 'madura';
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildResult(
        string $score,
        ?string $speculativeType,
        ?string $investmentType,
        string $stage,
        array $detail
    ): array {
        return [
            'score'           => $score,
            'speculative_type'=> $speculativeType,
            'investment_type' => $investmentType,
            'stage'           => $stage,
            'score_detail'    => $detail,
        ];
    }
}
