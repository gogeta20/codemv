<?php

namespace App\Tests\Acciones\Application\Earnings\GetLatestReport;

use App\Acciones\Application\Earnings\GetLatestReport\GetLatestEarningsReportUseCase;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GetLatestEarningsReportUseCaseTest extends TestCase
{
    private const CIK_PADDED = '0000000123';
    private const AGM_ACCESSION = '0001213900-26-000001';
    private const EARNINGS_ACCESSION = '0001213900-26-000002';

    /**
     * Reproduces the NBIS bug: a foreign private issuer's most recent 6-K (empty `items` metadata,
     * so the old code accepted it outright) is an Annual General Meeting notice, not an earnings
     * release. The actual earnings release is the next 6-K back. The fix must skip the AGM notice by
     * inspecting its content and fall through to the real earnings release.
     */
    public function testSkipsA6KThatIsNotActuallyAnEarningsRelease(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url) {
            if (str_contains($url, 'company_tickers.json')) {
                return new MockResponse(json_encode([
                    ['cik_str' => 123, 'ticker' => 'TEST', 'title' => 'Test Foreign Issuer'],
                ]));
            }

            if (str_contains($url, 'CIK' . self::CIK_PADDED . '.json')) {
                return new MockResponse(json_encode([
                    'filings' => [
                        'recent' => [
                            'form' => ['6-K', '6-K'],
                            'filingDate' => ['2026-07-27', '2026-05-15'],
                            'reportDate' => ['2026-07-27', '2026-05-15'],
                            'accessionNumber' => [self::AGM_ACCESSION, self::EARNINGS_ACCESSION],
                            'primaryDocument' => ['agm.htm', 'earnings.htm'],
                            'items' => ['', ''],
                        ],
                    ],
                ]));
            }

            if (str_contains($url, self::AGM_ACCESSION . '-index.htm')) {
                return new MockResponse($this->indexHtml('agm-exhibit.htm'));
            }

            if (str_contains($url, 'agm-exhibit.htm')) {
                return new MockResponse('<html><body>Notice of Annual General Meeting of Shareholders. Re-appointment of directors. Proxy statement enclosed.</body></html>');
            }

            if (str_contains($url, self::EARNINGS_ACCESSION . '-index.htm')) {
                return new MockResponse($this->indexHtml('earnings-exhibit.htm'));
            }

            if (str_contains($url, 'earnings-exhibit.htm')) {
                return new MockResponse('<html><body>Test Foreign Issuer reports first quarter 2026 results for the quarter ended March 31, 2026. Revenue grew and net income improved.</body></html>');
            }

            return new MockResponse('', ['http_code' => 404]);
        });

        $useCase = new GetLatestEarningsReportUseCase($httpClient);
        $result = $useCase->execute('TEST');

        $this->assertSame(self::EARNINGS_ACCESSION, $this->accessionFromIndexUrl($result['earnings_release']['index_url']));
        $this->assertStringContainsString('earnings-exhibit.htm', $result['earnings_release']['exhibit_99_1']['url']);
    }

    private function indexHtml(string $exhibitFileName): string
    {
        return <<<HTML
        <html><body>
        <table class="tableFile">
        <tr><td>1</td><td>{$exhibitFileName}</td><td><a href="/Archives/edgar/data/123/000121390026000001/{$exhibitFileName}">{$exhibitFileName}</a></td><td>EX-99.1</td></tr>
        </table>
        </body></html>
        HTML;
    }

    private function accessionFromIndexUrl(string $indexUrl): ?string
    {
        if (preg_match('/(\d{10}-\d{2}-\d{6})-index\.htm$/', $indexUrl, $m) === 1) {
            return $m[1];
        }

        return null;
    }
}
