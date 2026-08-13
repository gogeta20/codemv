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
    Three Months Ended March 31,20262025Revenue$2,078 $982 Operating expenses2,222 1,009 Operating loss$(144)$(27)Operating loss margin(7)%(3)%Interest expense, net$(536)$(264)Net loss$(740)$(315)Net loss margin(36)%(32)%Basic net loss per share$(1.40)$(1.40)Diluted net loss per share$(1.40)$(1.40)
    Cash flows from operating activities:Net loss$(740)$(315)Adjustments to reconcile net loss to net cash provided by operating activitiesDepreciation and amortization1,147 443 Net cash provided by operating activities2,984 61 Cash flows from investing activities:Purchase of property and equipment, including capitalized internal-use software(7,695)(1,407)Maturities and sales of marketable securities12 29
    Balance Sheet (unaudited)March 31,2026December 31,2025AssetsCurrent assetsCash and cash equivalents$2,244 $3,127
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

        $this->assertEqualsWithDelta(2078.0, $revenueKpi['actual'], 0.1, 'Revenue should read the "Revenue$2,078 $982" no-space two-column line.');
        $this->assertEqualsWithDelta(111.61, $revenueKpi['yoy_pct'], 0.1, 'YoY should be derived from current vs. prior columns when no % is stated.');
        $this->assertEqualsWithDelta(-1.40, $epsKpi['actual'], 0.01);
        $this->assertEqualsWithDelta(-7.0, $marginKpi['actual'], 0.1, 'Should read the directly-disclosed "Operating loss margin(7)%" instead of deriving it.');
        // Operating cash flow (2,984) minus capex (7,695, with descriptive text in between the label
        // and the figure) should net to a large negative FCF — CoreWeave's capex-heavy quarter.
        $this->assertEqualsWithDelta(-4711.0, $fcfKpi['actual'], 0.1);
        $this->assertSame('bad', $fcfKpi['signal']);
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
