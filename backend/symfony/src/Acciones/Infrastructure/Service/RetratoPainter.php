<?php

namespace App\Acciones\Infrastructure\Service;

/**
 * Generates the human-readable financial portrait from extracted metrics.
 */
final class RetratoPainter
{
    public function paint(array $metrics, array $scoreResult): string
    {
        $stage   = $scoreResult['stage'];
        $lines   = [];

        $lines[] = $this->stageBlock($metrics, $stage);
        $lines[] = $this->lossBlock($metrics);
        $lines[] = $this->cashBlock($metrics);
        $lines[] = $this->revenueBlock($metrics);

        if ($metrics['revenues_ttm'] !== null && $metrics['revenues_ttm'] > 0) {
            $lines[] = $this->marginBlock($metrics);
        }

        $lines[] = $this->rdBlock($metrics);
        $lines[] = $this->capexBlock($metrics);
        $lines[] = $this->dilutionBlock($metrics);

        if ($metrics['stock_issued_ttm'] !== null && $metrics['stock_issued_ttm'] > 0) {
            $lines[] = $this->capitalRaiseBlock($metrics);
        }

        return implode("\n", array_filter($lines));
    }

    // -------------------------------------------------------------------------

    private function stageBlock(array $m, string $stage): string
    {
        $label = match($stage) {
            'pre_revenue_id'   => 'Pre-revenue / I+D puro',
            'pionera_id'       => 'Pionera (I+D activo)',
            'expansion_infra'  => 'Expansión de infraestructura',
            'growth_rentable'  => 'Growth (rentable)',
            'madura'           => 'Madura',
            'deterioro'        => 'En deterioro',
            'sin_futuro'       => 'Sin futuro claro',
            default            => ucfirst($stage),
        };

        $note = $this->stageNote($m, $stage);
        return "Etapa: {$label}" . ($note ? "\n         ({$note})" : '');
    }

    private function stageNote(array $m, string $stage): string
    {
        return match($stage) {
            'pre_revenue_id'  => 'Revenue $0 — tecnología sin comercializar aún',
            'pionera_id'      => sprintf('R&D %s > Revenue %s', $this->fmt($m['rd_expenses_ttm']), $this->fmt($m['revenues_ttm'])),
            'expansion_infra' => sprintf('CapEx %s, ops ya rentables', $this->fmt(abs($m['capex_ttm'] ?? 0))),
            'deterioro'       => 'Net Income fue positivo, ahora negativo',
            default           => '',
        };
    }

    private function lossBlock(array $m): string
    {
        $ni = $m['net_income_last_year'];
        if ($ni === null) {
            return "En pérdidas: Datos no disponibles";
        }
        if ($ni >= 0) {
            $niTtm = $m['net_income_ttm'];
            return "En pérdidas: NO" . ($niTtm !== null ? " — Net Income TTM: " . $this->fmt($niTtm) : '');
        }
        // Determine loss trend by comparing TTM vs last year
        $niTtm = $m['net_income_ttm'];
        $trend = 'Pérdidas activas';
        if ($niTtm !== null) {
            if ($niTtm < $ni) {
                $trend = 'Pérdidas creciendo (TTM peor que último año)';
            } elseif ($niTtm > $ni * 0.9) {
                $trend = 'Pérdidas mejorando';
            } else {
                $trend = 'Pérdidas estables';
            }
        }
        return "En pérdidas: SÍ — {$this->fmt($ni)} (último año)\n            Tendencia: {$trend}";
    }

    private function cashBlock(array $m): string
    {
        $cash = $m['cash_and_short_term'];
        $debt = $m['total_debt'];
        $runway = $m['runway_years'];

        $cashStr  = $cash !== null ? $this->fmt($cash) : 'N/D';
        $debtStr  = $debt !== null ? $this->fmt($debt) : 'N/D';
        $debtNote = ($debt !== null && $debt == 0) ? ' (sin deuda)' : '';

        $runwayStr = $runway !== null
            ? "Runway: {$runway} años al ritmo de burn actual"
            : "Runway: N/A — operaciones generan caja";

        return "Caja:   {$cashStr}\nDeuda:  {$debtStr}{$debtNote}\n{$runwayStr}";
    }

    private function revenueBlock(array $m): string
    {
        $rev = $m['revenues_ttm'];
        if ($rev === null) return '';
        if ($rev == 0) return "Revenue: \$0 — empresa pre-revenue";

        $trend = match($m['revenue_trend_3y']) {
            'growing'   => 'creciendo',
            'stable'    => 'estable',
            'declining' => 'CAYENDO',
            default     => '',
        };
        $growth = $m['revenue_growth_2y_pct'] !== null
            ? sprintf(' (%+.1f%% últimos 2 años)', $m['revenue_growth_2y_pct'])
            : '';

        return "Revenue: {$this->fmt($rev)} TTM — {$trend}{$growth}";
    }

    private function marginBlock(array $m): string
    {
        $gm = $m['gross_profit_ratio_ttm'];
        if ($gm === null) return '';

        $trend = match($m['gross_margin_trend_3y']) {
            'improving'     => 'mejorando',
            'stable'        => 'estable',
            'deteriorating' => 'DETERIORANDO',
            default         => '',
        };
        $decline = $m['gross_margin_decline_3y_pp'] !== null && $m['gross_margin_decline_3y_pp'] > 1
            ? sprintf(' (-%'.'.1fpp en 3 años)', $m['gross_margin_decline_3y_pp'])
            : '';

        return sprintf("Gross Margin: %.1f%% — %s%s", $gm, $trend, $decline);
    }

    private function rdBlock(array $m): string
    {
        $rd = $m['rd_expenses_ttm'];
        if ($rd === null || $rd == 0) {
            return "R&D: \$0 — sin inversión en I+D";
        }
        $trend = match($m['rd_trend']) {
            'investing' => 'creciendo',
            'cutting'   => 'RECORTANDO',
            default     => 'activo',
        };
        return "R&D: {$this->fmt($rd)} TTM — {$trend}";
    }

    private function capexBlock(array $m): string
    {
        $capex = $m['capex_ttm'];
        $ops   = $m['cash_from_ops_ttm'];
        if ($capex === null) return '';

        $capexAbs = $this->fmt(abs($capex));
        if ($ops !== null && $ops > 0) {
            return "CapEx vs Ops: {$capexAbs} vs {$this->fmt($ops)} — " .
                ($m['capex_explains_fcf_gap'] ? 'CapEx explica el FCF negativo' : 'ratio manejable');
        }
        return "CapEx: {$capexAbs} TTM";
    }

    private function dilutionBlock(array $m): string
    {
        $pct = $m['shares_dilution_pct'];
        if ($pct === null) return '';

        $trend = $m['shares_trend'];
        $note  = match($trend) {
            'buybacks' => sprintf('%.1f%% acum. — RECOMPRAS (positivo para el accionista)', abs($pct)),
            'diluting' => sprintf('+%.1f%% acum. — DILUCIÓN ALTA (riesgo)', $pct),
            default    => sprintf('%+.1f%% acum.', $pct),
        };
        return "Dilución: {$note}";
    }

    private function capitalRaiseBlock(array $m): string
    {
        $issued = $m['stock_issued_ttm'];
        return "Señal capital: {$this->fmt($issued)} levantados emitiendo acciones (TTM)";
    }

    // -------------------------------------------------------------------------

    private function fmt(?float $value): string
    {
        if ($value === null) return 'N/D';
        // CSV values are in thousands — convert to actual dollars
        $actual = $value * 1_000;
        $abs    = abs($actual);
        $sign   = $actual < 0 ? '-' : '';

        if ($abs >= 1_000_000_000) {
            return sprintf('%s$%.2fB', $sign, $abs / 1_000_000_000);
        }
        if ($abs >= 1_000_000) {
            return sprintf('%s$%.1fM', $sign, $abs / 1_000_000);
        }
        if ($abs >= 1_000) {
            return sprintf('%s$%.1fK', $sign, $abs / 1_000);
        }
        return sprintf('%s$%.0f', $sign, $abs);
    }
}
