<?php

namespace App\Acciones\Infrastructure\Service;

final class CsvParserService
{
    private const INCOME_MAP = [
        'Revenues'                         => 'revenues',
        'Cost Of Revenue'                  => 'cost_of_revenue',
        'Gross Profit'                     => 'gross_profit',
        'Gross Profit Ratio'               => 'gross_profit_ratio',
        'Net Income'                       => 'net_income',
        'Net Income Ratio'                 => 'net_income_ratio',
        'Diluted EPS'                      => 'eps_diluted',
        'Ebitda'                           => 'ebitda',
        'Ebitda Ratio'                     => 'ebitda_ratio',
        'Outstanding Shares'               => 'outstanding_shares',
        'Outstanding Shares Diluted'       => 'outstanding_shares_diluted',
        'Selling General & Admin Expenses' => 'sga_expenses',
        'R&D Expenses'                     => 'rd_expenses',
        'Operating Expenses'               => 'operating_expenses',
        'Operating Income'                 => 'operating_income',
        'Operating Income Ratio'           => 'operating_income_ratio',
        'Interest Expense'                 => 'interest_expense',
        'Interest Income'                  => 'interest_income',
        'Income Before Tax'                => 'income_before_tax',
        'Income Tax Expense'               => 'income_tax_expense',
        'Cost And Expenses'                => 'cost_and_expenses',
    ];

    private const CASHFLOW_MAP = [
        'Cash from Operations'       => 'cash_from_operations',
        'Depreciation & Amortization'=> 'depreciation',
        'Change In Working Capital'  => 'change_working_capital',
        'Capital Expenditure'        => 'capex',
        'Cash Acquisitions'          => 'cash_acquisitions',
        'Cash from Investing'        => 'cash_from_investing',
        'Dividends Paid'             => 'dividends_paid',
        'Common Stock Repurchased'   => 'stock_repurchased',
        'Common Stock Issued'        => 'stock_issued',
        'Debt Repayment'             => 'debt_repayment',
        'Free Cash Flow'             => 'free_cash_flow',
        'Stock Based Compensation'   => 'stock_based_compensation',
    ];

    private const BALANCE_MAP = [
        'Total Current Assets'          => 'total_current_assets',
        'Total Assets'                  => 'total_assets',
        'Total Current Liabilities'     => 'total_current_liabilities',
        'Total Liabilities'             => 'total_liabilities',
        'Total Non-Current Assets'      => 'total_non_current_assets',
        'Total Non-Current Liabilities' => 'total_non_current_liabilities',
        'Long Term Debt'                => 'long_term_debt',
        'Total Debt'                    => 'total_debt',
        'Net Debt'                      => 'net_debt',
        'Cash And Cash Equivalents'     => 'cash_equivalents',
        'Short Term Investments'        => 'short_term_investments',
        'Cash And Short Term Investments' => 'cash_and_short_term',
        'Employees'                     => 'employees',
    ];

    public function parseIncome(string $csvContent): array
    {
        return $this->parse($csvContent, self::INCOME_MAP);
    }

    public function parseBalance(string $csvContent): array
    {
        return $this->parse($csvContent, self::BALANCE_MAP);
    }

    public function parseCashflow(string $csvContent): array
    {
        return $this->parse($csvContent, self::CASHFLOW_MAP);
    }

    private function parse(string $csvContent, array $fieldMap): array
    {
        // Normalize line endings
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($csvContent));
        $rows = array_map(static fn(string $line) => str_getcsv($line, ',', '"', ''), explode("\n", $normalized));

        if (count($rows) < 2) {
            return [];
        }

        // Header row: ["", "2016", "2017", ..., "TTM", "Total"]
        // Skip index 0 (empty) and last index (Total)
        $headers = $rows[0];
        $years = [];
        for ($i = 1; $i < count($headers) - 1; $i++) {
            $years[$i] = trim($headers[$i]);
        }

        $result = [];
        $lastMappedKey = null;

        foreach (array_slice($rows, 1) as $row) {
            if (empty($row) || !isset($row[0])) {
                continue;
            }

            $fieldName = trim($row[0]);

            if ($this->isSectionRow($row)) {
                continue;
            }

            if ($fieldName === 'Growth') {
                if ($lastMappedKey !== null) {
                    $result['growth_' . $lastMappedKey] = $this->extractValues($row, $years);
                }
                continue;
            }

            if (!isset($fieldMap[$fieldName])) {
                continue;
            }

            $key = $fieldMap[$fieldName];
            $lastMappedKey = $key;
            $result[$key] = $this->extractValues($row, $years);
        }

        return $result;
    }

    private function isSectionRow(array $row): bool
    {
        for ($i = 1; $i < count($row); $i++) {
            if (trim($row[$i]) !== '') {
                return false;
            }
        }
        return true;
    }

    private function extractValues(array $row, array $years): array
    {
        $values = [];
        foreach ($years as $colIndex => $year) {
            $raw = $row[$colIndex] ?? '';
            $values[$year] = $this->parseNumber($raw);
        }
        return $values;
    }

    private function parseNumber(string $raw): ?float
    {
        $cleaned = trim($raw);

        if ($cleaned === '' || $cleaned === '-' || $cleaned === 'N/A') {
            return null;
        }

        // Remove currency symbol, thousands separators, percent sign
        $cleaned = str_replace(['$', ',', '%'], '', $cleaned);

        if (!is_numeric($cleaned)) {
            return null;
        }

        return (float) $cleaned;
    }
}
