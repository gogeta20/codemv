<?php

namespace App\Acciones\Infrastructure\Service;

/**
 * Estimates a per-share fair value from the normalized metrics + the
 * company classification produced by ScoringService. The method used
 * depends on the type of company — a single P/E-style formula is not
 * honest across dividend payers, growth-with-losses names and
 * pre-revenue/deteriorating speculative names.
 *
 * When none of the methods below can be trusted (no positive earnings,
 * no dividend, no revenue growth, no net cash cushion), the service
 * returns method = 'not_available' instead of forcing a number.
 */
final class FairValueService
{
    private const DISCOUNT_RATE = 0.09;
    private const MAX_DIVIDEND_GROWTH = 0.05;

    public function compute(array $metrics, array $scoreResult): array
    {
        $graham = $this->tryGrahamNumber($metrics);
        if ($graham !== null) {
            return $graham;
        }

        $ddm = $this->tryDividendDiscount($metrics, $scoreResult);
        if ($ddm !== null) {
            return $ddm;
        }

        $ps = $this->tryPriceToSales($metrics, $scoreResult);
        if ($ps !== null) {
            return $ps;
        }

        $floor = $this->tryNetCashFloor($metrics);
        if ($floor !== null) {
            return $floor;
        }

        return [
            'value'       => null,
            'method'      => 'not_available',
            'method_label'=> 'Sin valoración fundamental confiable',
            'confidence'  => 'low',
            'explanation' => 'La empresa no tiene beneficio ni caja neta positiva ni ingresos crecientes suficientes para aplicar ningún método de valoración honesto. Un número aquí sería inventado.',
        ];
    }

    private function tryGrahamNumber(array $m): ?array
    {
        $eps = $m['eps_ttm'];
        $bvps = $m['book_value_per_share'];

        if ($eps === null || $eps <= 0 || $bvps === null || $bvps <= 0) {
            return null;
        }

        $value = sqrt(22.5 * $eps * $bvps);
        $confidence = ($m['net_income_was_positive'] === false && ($m['net_income_ttm'] ?? 0) > 0) ? 'high' : 'medium';

        return [
            'value'       => round($value, 2),
            'method'      => 'graham_number',
            'method_label'=> 'Número de Graham',
            'confidence'  => $confidence,
            'explanation' => sprintf(
                '√(22.5 × EPS TTM %.2f × valor contable/acción %.2f). Aplica porque la empresa tiene beneficio y valor contable positivos.',
                $eps,
                $bvps
            ),
        ];
    }

    private function tryDividendDiscount(array $m, array $scoreResult): ?array
    {
        $paysDividends = $m['pays_dividends'] ?? false;
        $dps = $m['dividends_per_share_ttm'];
        $isAristocrat = ($scoreResult['investment_type'] ?? null) === 'dividend_aristocrat';
        $trend = $m['dividend_trend'] ?? 'none';

        if (!$paysDividends || $dps === null || $dps <= 0 || !$isAristocrat || $trend === 'cut') {
            return null;
        }

        $growth = $trend === 'growing' ? self::MAX_DIVIDEND_GROWTH : 0.0;
        $value = ($dps * (1 + $growth)) / (self::DISCOUNT_RATE - $growth);

        return [
            'value'       => round($value, 2),
            'method'      => 'dividend_discount',
            'method_label'=> 'Descuento de dividendos (DDM)',
            'confidence'  => 'medium',
            'explanation' => sprintf(
                'Dividendo/acción %.2f × (1+%.0f%%) ÷ (%.0f%% − %.0f%%). Aplica por clasificación de aristócrata del dividendo con tendencia %s.',
                $dps,
                $growth * 100,
                self::DISCOUNT_RATE * 100,
                $growth * 100,
                $trend
            ),
        ];
    }

    private function tryPriceToSales(array $m, array $scoreResult): ?array
    {
        $revTtm = $m['revenues_ttm'];
        $shares = $m['shares_outstanding_ttm'];
        $trend = $m['revenue_trend_3y'] ?? 'stable';
        // 'expansion' = Camino A: FCF negativo explicado por CapEx, buen margen,
        // revenue creciendo — la empresa aún no es rentable en su forma consolidada
        // pero está invirtiendo en crecer, no deteriorándose. Es el único caso donde
        // un múltiplo sobre ventas es más honesto que forzar un EPS negativo.
        $isExpansionGrowth = ($scoreResult['speculative_type'] ?? null) === 'expansion';

        $growing = $trend === 'growing' && $isExpansionGrowth;
        if ($revTtm === null || $revTtm <= 0 || $shares === null || $shares <= 0 || !$growing) {
            return null;
        }

        $grossMargin = $m['gross_profit_ratio_ttm'] ?? 0;
        // Conservative P/S multiple scaled by gross margin, clamped 1x–6x.
        $multiple = max(1.0, min(6.0, ($grossMargin / 100) * 5));

        $revenuePerShare = $revTtm / $shares;
        $value = $revenuePerShare * $multiple;

        return [
            'value'       => round($value, 2),
            'method'      => 'price_to_sales',
            'method_label'=> 'Múltiplo de ventas (P/S)',
            'confidence'  => 'low',
            'explanation' => sprintf(
                'Ingresos TTM/acción %.4f × múltiplo conservador %.1fx (según margen bruto %.1f%%). Aplica porque la empresa crece en ingresos sin beneficio consolidado aún.',
                $revenuePerShare,
                $multiple,
                $grossMargin
            ),
        ];
    }

    private function tryNetCashFloor(array $m): ?array
    {
        $cash = $m['cash_and_short_term'];
        $debt = $m['total_debt'];
        $shares = $m['shares_outstanding_ttm'];

        if ($cash === null || $debt === null || $shares === null || $shares <= 0) {
            return null;
        }

        $netCash = $cash - $debt;
        if ($netCash <= 0) {
            return null;
        }

        return [
            'value'       => round($netCash / $shares, 2),
            'method'      => 'net_cash_floor',
            'method_label'=> 'Piso de caja neta',
            'confidence'  => 'low',
            'explanation' => 'No hay beneficio, dividendo ni ingresos crecientes para aplicar un método de valoración estándar. Este número es solo un piso de liquidación: caja menos deuda total, por acción — no un precio objetivo.',
        ];
    }
}
