<?php

namespace App\Tests\Acciones\Application\Earnings\GetAnalysis;

use App\Acciones\Application\Earnings\GetAnalysis\GetEarningsAnalysisUseCase;
use App\Acciones\Domain\Repository\AccionEarningsRepositoryInterface;
use App\Acciones\Domain\Repository\AccionEarningsReportRepositoryInterface;
use App\Acciones\Domain\Repository\AccionRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionEarnings;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionEarningsReport;
use PHPUnit\Framework\TestCase;

class GetEarningsAnalysisUseCaseTest extends TestCase
{
    // Mirrors the real Fervo Energy (FRVO) 8-K earnings exhibit: a dot-leader financial-statement
    // table, not the prose style ("Revenue$X Year-over-year growth Y%") the original regexes expected.
    private const TABULAR_RAW = <<<'TEXT'
    Fervo Energy Reports First Quarter 2026 Results reported financial and operational results for
    the first quarter ended March 31, 2026.
    UNAUDITED CONDENSED CONSOLIDATED STATEMENTS OF OPERATIONS
    (Dollars and shares in thousands, except per share amounts)
    Three months ended March 31, 20262025Revenues .......................................................$61$—Costs and expenses:Operation and maintenance ....................................................................................482252Operating loss ......................................................................................................................(20,052)(9,931)Net loss .....................................................................................................................$(31,830)$(9,146)Net loss per share information:Net loss ...................................................................................................................$(31,830)$(9,146)Net loss attributable to common shares, basic and diluted ....................................(35,264)(9,146)Weighted average shares, basic and diluted (1) ...................................................9,4678,961Net loss per share attributable to common stockholders, basic and diluted (1) ...$(3.72)$(1.02)
    CONDENSED CONSOLIDATED BALANCE SHEETS (UNAUDITED)
    (Dollars and shares in thousands)
    As of March 31, As of December 31,20262025ASSETSCurrent assets:Cash and cash equivalents .....................................................................................$280,776$461,836
    TEXT;

    // Mirrors the real Nebius (NBIS) 6-K earnings exhibit: a "Label prior current change%"
    // highlights table with no "$" signs, plus a non-breaking space between "June" and "30" in the
    // period statement (as it actually appears in the SEC-hosted document).
    private const HIGHLIGHTS_RAW = <<<TEXT
    Nebius reports second quarter 2026 financial results Amsterdam, August 12, 2026 - Nebius Group N.V.
    today announced its unaudited financial results for the three and six months ended June\u{A0}30, 2026.
    Q2 2026 Financial Highlights Consolidated results
    Three months ended June 30 Six months ended June 30
    In USD $ millions 2025 2026 Change 2025 2026 Change
    Revenues 105.1 582.3 454% 156.0 981.3 529%
    Consolidated Statements of Operations (in millions of U.S. dollars, except share and per share data)
    Loss from operations (111.2) (175.9) (231.5) (303.9)
    Net income / (loss) per ClassA and ClassB share: Basic 2.45 (0.68) 1.98 1.60 Diluted 2.38 (0.68) 1.94 1.53
    Cash and cash equivalents 3,678.1 8,042.1
    TEXT;

    // Mirrors the real CoreWeave (CRWV) 8-K earnings exhibit: a no-space "Label$current $prior"
    // income-statement style, plus a cash-flow statement where the capex line has extra descriptive
    // text between the label and the actual figure.
    private const NO_SPACE_RAW = <<<'TEXT'
    CoreWeave Reports Strong First Quarter 2026 Results for the quarter ended March 31, 2026.
    Condensed Consolidated Statements of Operations (in millions, except share and per share amounts)
    Three Months Ended March 31,20262025Revenue$2,078 $982 Operating expenses2,222 1,009 Operating loss$(144)$(27)Operating loss margin(7)%(3)%Interest expense, net$(536)$(264)Loss before income taxes(656)(269)Provision for income taxes84 46 Net loss$(740)$(315)Net loss margin(36)%(32)%Basic net loss per share$(1.40)$(1.40)Diluted net loss per share$(1.40)$(1.40)
    Cash flows from operating activities:Net loss$(740)$(315)Adjustments to reconcile net loss to net cash provided by operating activitiesDepreciation and amortization1,147 443 Net cash provided by operating activities2,984 61 Cash flows from investing activities:Purchase of property and equipment, including capitalized internal-use software(7,695)(1,407)Maturities and sales of marketable securities12 29
    Balance Sheet (unaudited)March 31,2026December 31,2025AssetsCurrent assetsCash and cash equivalents$2,244 $3,127
    TEXT;

    // Mirrors the real Cellebrite (CLBT) 6-K earnings exhibit: two traps found by manual review.
    // (1) "Cost of revenue" and "non-GAAP operating income, non-GAAP net income," both contain the
    // words "revenue"/"net income" as a substring — a naive label match reads the wrong line.
    // (2) A bare comma right after a label (as in the same "non-GAAP ... ," boilerplate) can satisfy
    // a `[\d,]+` character class with zero actual digits, silently extracting 0 instead of failing.
    private const TRAP_RAW = <<<'TEXT'
    Cellebrite Reports Second-Quarter 2026 Results. Revenue of $131.1 million, up 16% year-over-year.
    Cellebrite believes that the use of non-GAAP cost of revenue, non-GAAP operating expenses, non-GAAP operating income, non-GAAP net income, non-GAAP EPS and adjusted EBITDA is helpful to investors.
    Three months ended June 30, 2026 2025 Total revenue 131,138 113,276 Cost of revenue: Subscription services 15,981 8,522 Total cost of revenue 25,207 17,677
    Gross profit margin 80.8% 84.4% Operating income 6,949 14,417 Operating margin 5.3% 12.7% Net income 6,371 19,476 Cash flow from operating activities 17,589 32,583
    TEXT;

    // Mirrors the real X-Energy (XE) 8-K earnings exhibit: a rounded highlights table declared "in
    // millions" near the top, followed by the exact GAAP statements declared "(in thousands...)"
    // further down — the same document uses two different scales for two different tables.
    private const MIXED_SCALE_RAW = <<<'TEXT'
    X-energy Reports Second Quarter 2026 Results •Revenues and grant income of $54.6 million, compared to revenues and grant income of $21.5 million in 2Q 2025
    Financial Results (Dollars in millions) Three Months Ended June 30, 2026 2025 % Change Total revenues and grant income 54.6 21.5 154%
    X-ENERGY, INC. CONDENSED CONSOLIDATED STATEMENTS OF OPERATIONS (in thousands, except share and per share amounts) (unaudited) Three Months Ended June 30, 2026 2025
    Net loss (105,333 ) (88,848 )
    X-ENERGY, INC. CONDENSED CONSOLIDATED STATEMENTS OF CASH FLOWS (in thousands) (unaudited) Six Months Ended June 30, 2026 2025
    Net cash used in operating activities (97,300 ) (20,000 )
    TEXT;

    // Mirrors the real Cerebras (CBRS) 8-K earnings exhibit: the operating cash flow line says
    // "Net cash flows used in..." (with "flows"), not the "Net cash used in..." phrasing the pattern
    // was originally written for.
    private const FLOWS_WORD_RAW = <<<'TEXT'
    Cerebras Systems announces second quarter 2026 results (in thousands)
    Cash flows from operating activities: Net income (loss)$(464,534)$285,645
    Net cash flows used in operating activities$(47,488)$(123,843)
    Cash flows from investing activities: Purchases of property and equipment$(548,873)$(185,094)
    TEXT;

    private AccionRepositoryInterface $accionRepository;
    private AccionEarningsReportRepositoryInterface $earningsReportRepository;
    private AccionEarningsRepositoryInterface $accionEarningsRepository;
    private GetEarningsAnalysisUseCase $useCase;

    protected function setUp(): void
    {
        $this->accionRepository = $this->createMock(AccionRepositoryInterface::class);
        $this->earningsReportRepository = $this->createMock(AccionEarningsReportRepositoryInterface::class);
        $this->accionEarningsRepository = $this->createMock(AccionEarningsRepositoryInterface::class);

        $this->useCase = new GetEarningsAnalysisUseCase(
            $this->accionRepository,
            $this->earningsReportRepository,
            $this->accionEarningsRepository,
        );
    }

    private function buildReport(Accion $accion, string $rawContent, ?\DateTimeImmutable $reportDate = null): AccionEarningsReport
    {
        $report = new AccionEarningsReport(
            uuid: 'rrrrrrrr-rrrr-rrrr-rrrr-rrrrrrrrrrrr',
            accion: $accion,
            source: 'sec',
            formType: '8-K',
            contentFormat: 'text/plain',
            rawContent: $rawContent,
        );
        $report->setFilingDate(new \DateTimeImmutable('2026-06-22'));
        $report->setReportDate($reportDate ?? new \DateTimeImmutable('2026-03-31'));

        return $report;
    }

    public function testExtractsMetricsFromTabularFinancialStatementFiling(): void
    {
        $accion = new Accion('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'FRVO', 'Fervo Energy Company', 'stock');
        $report = $this->buildReport($accion, self::TABULAR_RAW);

        $this->accionRepository->method('findByUuid')->willReturn($accion);
        $this->earningsReportRepository->method('findLatestByAccion')->willReturn($report);
        $this->accionEarningsRepository->method('findByAccion')->willReturn(null);

        $result = $this->useCase->execute($accion->getUuid());

        $revenueKpi = $this->kpiByKey($result['kpis'], 'revenue');
        $epsKpi = $this->kpiByKey($result['kpis'], 'eps');
        $marginKpi = $this->kpiByKey($result['kpis'], 'operating_margin');

        $this->assertEqualsWithDelta(0.061, $revenueKpi['actual'], 0.0001, 'Revenue should be parsed from the "$61" thousands table cell.');
        $this->assertEqualsWithDelta(-3.72, $epsKpi['actual'], 0.0001, 'EPS should be parsed from the net-loss-per-share table row.');
        // Revenue is negligible relative to the operating loss (pre-revenue company), so the derived
        // margin is intentionally left null instead of showing a meaningless -30000%-style figure.
        $this->assertNull($marginKpi['actual']);
        $this->assertSame('warning', $marginKpi['signal'], 'A null (unknown) metric must not render as "bad".');

        $this->assertNotSame('insufficient_data', $result['verdict']['signal']);
    }

    public function testExtractsMetricsFromHighlightsTableFiling(): void
    {
        $accion = new Accion('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee', 'NBIS', 'Nebius Group N.V.', 'stock');
        $report = $this->buildReport($accion, self::HIGHLIGHTS_RAW);
        $earnings = new AccionEarnings($accion, new \DateTimeImmutable('2026-08-12'));

        $this->accionRepository->method('findByUuid')->willReturn($accion);
        $this->earningsReportRepository->method('findLatestByAccion')->willReturn($report);
        $this->accionEarningsRepository->method('findByAccion')->willReturn($earnings);

        $result = $this->useCase->execute($accion->getUuid());

        $revenueKpi = $this->kpiByKey($result['kpis'], 'revenue');
        $epsKpi = $this->kpiByKey($result['kpis'], 'eps');
        $marginKpi = $this->kpiByKey($result['kpis'], 'operating_margin');
        $cashKpi = null;
        foreach ($result['cross_checks'] as $check) {
            if ($check['key'] === 'balance_sheet') {
                $cashKpi = $check;
            }
        }

        $this->assertEqualsWithDelta(582.3, $revenueKpi['actual'], 0.01, 'Revenue should be parsed from "Revenues 105.1 582.3 454%".');
        $this->assertEqualsWithDelta(454.0, $revenueKpi['yoy_pct'], 0.01);
        $this->assertEqualsWithDelta(-0.68, $epsKpi['actual'], 0.01, 'EPS should read the diluted current-quarter value, not the "Basic" one nor the "continuing operations" partial total.');
        $this->assertEqualsWithDelta(-30.21, $marginKpi['actual'], 0.01, 'Margin should be derived from operating loss / revenue.');
        // Cash and cash equivalents (8,042.1) is well above the balance-sheet health threshold.
        $this->assertSame('good', $cashKpi['status']);

        // A non-breaking space between "June" and "30" must not break period-end parsing: the
        // release is for the quarter ended 2026-06-30, only 43 days before the accion's current
        // earnings_date (2026-08-12) — well inside the staleness threshold.
        $this->assertSame('2026-06-30', $result['report']['report_date']);
        $this->assertFalse($result['report']['is_stale']);
    }

    public function testReturnsInsufficientDataWhenNoMetricsCanBeExtracted(): void
    {
        $accion = new Accion('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', 'KEEL', 'Keel Infrastructure Corp.', 'stock');
        $report = $this->buildReport($accion, 'Notice of Annual General Meeting of Shareholders. Agenda: re-appointment of directors.');

        $this->accionRepository->method('findByUuid')->willReturn($accion);
        $this->earningsReportRepository->method('findLatestByAccion')->willReturn($report);
        $this->accionEarningsRepository->method('findByAccion')->willReturn(null);

        $result = $this->useCase->execute($accion->getUuid());

        $this->assertSame('insufficient_data', $result['verdict']['signal']);
        $this->assertNull($result['verdict']['score'], 'Score must not default to a fabricated 50 when nothing was extracted.');
    }

    public function testFlagsReportAsStaleWhenItPredatesTheCurrentEarningsDate(): void
    {
        $accion = new Accion('cccccccc-cccc-cccc-cccc-cccccccccccc', 'NBIS', 'Nebius Group N.V.', 'stock');
        $report = $this->buildReport($accion, self::TABULAR_RAW, new \DateTimeImmutable('2026-03-31'));
        $earnings = new AccionEarnings($accion, new \DateTimeImmutable('2026-08-12'));

        $this->accionRepository->method('findByUuid')->willReturn($accion);
        $this->earningsReportRepository->method('findLatestByAccion')->willReturn($report);
        $this->accionEarningsRepository->method('findByAccion')->willReturn($earnings);

        $result = $this->useCase->execute($accion->getUuid());

        $this->assertTrue($result['report']['is_stale']);
        $this->assertNotNull($result['report']['stale_reason']);
    }

    public function testDoesNotFlagStalenessWhenEarningsDateIsTheNextUpcomingRelease(): void
    {
        // Mirrors CRWV: the stored report is the last one filed (Q1 2026, ended March 31), and
        // `earnings_date` already points at the *next* scheduled call, four-plus months out. That
        // gap is normal quarterly cadence, not a sign that a newer report is being missed.
        $accion = new Accion('ffffffff-ffff-ffff-ffff-ffffffffffff', 'CRWV', 'CoreWeave, Inc.', 'stock');
        $report = $this->buildReport($accion, self::NO_SPACE_RAW, new \DateTimeImmutable('2026-03-31'));
        $earnings = new AccionEarnings($accion, new \DateTimeImmutable('2026-11-11'));

        $this->accionRepository->method('findByUuid')->willReturn($accion);
        $this->earningsReportRepository->method('findLatestByAccion')->willReturn($report);
        $this->accionEarningsRepository->method('findByAccion')->willReturn($earnings);

        $result = $this->useCase->execute($accion->getUuid());

        $this->assertFalse($result['report']['is_stale']);
    }

    public function testExtractsMetricsFromNoSpaceIncomeStatementFiling(): void
    {
        $accion = new Accion('99999999-9999-9999-9999-999999999999', 'CRWV', 'CoreWeave, Inc.', 'stock');
        $report = $this->buildReport($accion, self::NO_SPACE_RAW);

        $this->accionRepository->method('findByUuid')->willReturn($accion);
        $this->earningsReportRepository->method('findLatestByAccion')->willReturn($report);
        $this->accionEarningsRepository->method('findByAccion')->willReturn(null);

        $result = $this->useCase->execute($accion->getUuid());

        $revenueKpi = $this->kpiByKey($result['kpis'], 'revenue');
        $epsKpi = $this->kpiByKey($result['kpis'], 'eps');
        $marginKpi = $this->kpiByKey($result['kpis'], 'operating_margin');
        $fcfKpi = $this->kpiByKey($result['kpis'], 'free_cash_flow');
        $opexKpi = $this->kpiByKey($result['kpis'], 'operating_expenses');
        $netIncomeKpi = $this->kpiByKey($result['kpis'], 'net_income');

        $this->assertEqualsWithDelta(2078.0, $revenueKpi['actual'], 0.1, 'Revenue should read the "Revenue$2,078 $982" no-space two-column line.');
        $this->assertEqualsWithDelta(111.61, $revenueKpi['yoy_pct'], 0.1, 'YoY should be derived from current vs. prior columns when no % is stated.');
        $this->assertEqualsWithDelta(-1.40, $epsKpi['actual'], 0.01);
        $this->assertEqualsWithDelta(-7.0, $marginKpi['actual'], 0.1, 'Should read the directly-disclosed "Operating loss margin(7)%" instead of deriving it.');
        // Operating expenses derived as revenue (2,078) minus operating loss (-144) = 2,222.
        $this->assertEqualsWithDelta(2222.0, $opexKpi['actual'], 0.1);
        $this->assertSame('bad', $opexKpi['signal'], 'Expenses exceeding revenue should read as "bad".');
        $this->assertEqualsWithDelta(-740.0, $netIncomeKpi['actual'], 0.1, 'Should read the total "Net loss$(740)$(315)" figure, not the Adjusted or per-share ones.');
        // Operating cash flow (2,984) minus capex (7,695, with descriptive text in between the label
        // and the figure) should net to a large negative FCF — CoreWeave's capex-heavy quarter.
        $this->assertEqualsWithDelta(-4711.0, $fcfKpi['actual'], 0.1);
        $this->assertSame('bad', $fcfKpi['signal']);

        $interestKpi = $this->kpiByKey($result['kpis'], 'interest_expense');
        $taxesKpi = $this->kpiByKey($result['kpis'], 'taxes');
        $this->assertEqualsWithDelta(536.0, $interestKpi['actual'], 0.1, 'Interest expense should read as a positive cost figure, not the raw negative table value.');
        // Operating income is negative (-144), so ANY interest expense can't be covered by
        // operations — the company depends on financing, not operating profit, to pay it.
        $this->assertSame('bad', $interestKpi['signal']);
        $this->assertEqualsWithDelta(84.0, $taxesKpi['actual'], 0.1, 'Should read the tax provision even though the company reported a net loss.');

        // Each KPI should be grouped for the frontend's "Resultado / Ingresos / Gastos" layout.
        $this->assertSame('result', $netIncomeKpi['category']);
        $this->assertSame('income', $revenueKpi['category']);
        $this->assertSame('expense', $opexKpi['category']);
        $this->assertSame('expense', $interestKpi['category']);
    }

    public function testDoesNotFlagStalenessWhenReportMatchesTheCurrentQuarter(): void
    {
        $accion = new Accion('dddddddd-dddd-dddd-dddd-dddddddddddd', 'FRVO', 'Fervo Energy Company', 'stock');
        // No "quarter/months ended <date>" phrase here, so reportDate() falls back to the
        // explicitly-set report_date column (2026-07-20) instead of scanning the raw text.
        $report = $this->buildReport($accion, 'Fervo Energy Reports Results. Revenue and operating figures follow.', new \DateTimeImmutable('2026-07-20'));
        $earnings = new AccionEarnings($accion, new \DateTimeImmutable('2026-08-12'));

        $this->accionRepository->method('findByUuid')->willReturn($accion);
        $this->earningsReportRepository->method('findLatestByAccion')->willReturn($report);
        $this->accionEarningsRepository->method('findByAccion')->willReturn($earnings);

        $result = $this->useCase->execute($accion->getUuid());

        $this->assertFalse($result['report']['is_stale']);
    }

    public function testDoesNotMistakeCostOfRevenueOrBoilerplateCommasForRealFigures(): void
    {
        $accion = new Accion('88888888-8888-8888-8888-888888888888', 'CLBT', 'Cellebrite DI Ltd.', 'stock');
        $report = $this->buildReport($accion, self::TRAP_RAW);

        $this->accionRepository->method('findByUuid')->willReturn($accion);
        $this->earningsReportRepository->method('findLatestByAccion')->willReturn($report);
        $this->accionEarningsRepository->method('findByAccion')->willReturn(null);

        $result = $this->useCase->execute($accion->getUuid());

        $revenueKpi = $this->kpiByKey($result['kpis'], 'revenue');
        $netIncomeKpi = $this->kpiByKey($result['kpis'], 'net_income');
        $opexKpi = $this->kpiByKey($result['kpis'], 'operating_expenses');

        // Must read the "Revenue of $131.1 million" prose bullet, not "Total cost of revenue
        // 25,207" (a real line in the same document that also contains the word "revenue").
        $this->assertEqualsWithDelta(131.1, $revenueKpi['actual'], 0.01);
        $this->assertEqualsWithDelta(16.0, $revenueKpi['yoy_pct'], 0.01);

        // Must read the tabular "Net income 6,371" line, not silently extract 0 from the bare
        // comma in "non-GAAP operating income, non-GAAP net income," boilerplate.
        $this->assertEqualsWithDelta(6.371, $netIncomeKpi['actual'], 0.001);
        $this->assertNotEquals(0.0, $netIncomeKpi['actual']);

        // Operating expenses derived from the real revenue (131.1) minus real operating income
        // (6.949), not from a comma-corrupted zero.
        $this->assertEqualsWithDelta(124.151, $opexKpi['actual'], 0.01);
    }

    public function testUsesTheNearestScaleDeclarationInsteadOfADocumentWideOne(): void
    {
        $accion = new Accion('77777777-7777-7777-7777-777777777777', 'XE', 'X-Energy, Inc.', 'stock');
        $report = $this->buildReport($accion, self::MIXED_SCALE_RAW);

        $this->accionRepository->method('findByUuid')->willReturn($accion);
        $this->earningsReportRepository->method('findLatestByAccion')->willReturn($report);
        $this->accionEarningsRepository->method('findByAccion')->willReturn(null);

        $result = $this->useCase->execute($accion->getUuid());

        $netIncomeKpi = $this->kpiByKey($result['kpis'], 'net_income');
        $revenueKpi = $this->kpiByKey($result['kpis'], 'revenue');

        // The "(Dollars in millions)" declaration near the top of the document belongs to the
        // highlights table, not to the GAAP statements further down that declare "(in thousands...)"
        // right before their own figures. A document-wide scale scan would treat -105,333 (thousands)
        // as if it were already in millions and report a net loss of -$105 BILLION.
        $this->assertEqualsWithDelta(-105.333, $netIncomeKpi['actual'], 0.001);

        // Revenue is first mentioned in the opening bullet, before ANY scale declaration appears in
        // the document at all — but the bullet spells out "$54.6 million" inline, which must be used
        // directly instead of falling back to a (nonexistent-yet) nearby table declaration.
        $this->assertEqualsWithDelta(54.6, $revenueKpi['actual'], 0.01);
    }

    public function testReadsOperatingCashFlowWhenLabeledWithTheWordFlows(): void
    {
        $accion = new Accion('66666666-6666-6666-6666-666666666666', 'CBRS', 'Cerebras Systems Inc.', 'stock');
        $report = $this->buildReport($accion, self::FLOWS_WORD_RAW);

        $this->accionRepository->method('findByUuid')->willReturn($accion);
        $this->earningsReportRepository->method('findLatestByAccion')->willReturn($report);
        $this->accionEarningsRepository->method('findByAccion')->willReturn(null);

        $result = $this->useCase->execute($accion->getUuid());

        $fcfKpi = $this->kpiByKey($result['kpis'], 'free_cash_flow');

        // Operating cash flow (-47,488) minus capex (548,873) = -596,361 (thousands) = -596.361M.
        // Previously stayed N/D entirely because "Net cash flows used in..." didn't match a pattern
        // written only for "Net cash used in...".
        $this->assertEqualsWithDelta(-596.361, $fcfKpi['actual'], 0.01);
    }

    private function kpiByKey(array $kpis, string $key): array
    {
        foreach ($kpis as $kpi) {
            if ($kpi['key'] === $key) {
                return $kpi;
            }
        }

        $this->fail("KPI '{$key}' not found in result.");
    }
}
