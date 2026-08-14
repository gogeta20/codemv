<?php

namespace App\Acciones\Application\Earnings\GetLatestReport;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GetLatestEarningsReportUseCase
{
    private const SEC_BASE_URL = 'https://www.sec.gov';
    private const SEC_SUBMISSIONS_BASE_URL = 'https://data.sec.gov/submissions';
    private const SEC_HEADERS = [
        'User-Agent' => 'codemv/1.0 earnings-lookup support@codemv.local',
        'From' => 'support@codemv.local',
        'Accept' => 'application/json, text/html,application/xhtml+xml',
        'Accept-Language' => 'en-US,en;q=0.9',
    ];
    private const EARNINGS_FORMS = ['8-K', '8-K/A', '6-K', '6-K/A'];
    private const PERIODIC_FORMS = ['10-Q', '10-K', '20-F', '40-F', '6-K', '6-K/A'];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {}

    public function execute(string $symbol): array
    {
        $symbol = strtoupper(trim($symbol));
        if ($symbol === '') {
            throw new \RuntimeException('symbol es obligatorio');
        }

        $company = $this->findCompanyByTicker($symbol);
        $cikPadded = str_pad((string) $company['cik_str'], 10, '0', STR_PAD_LEFT);
        $cikRaw = ltrim($cikPadded, '0');

        $submissions = $this->fetchJson(sprintf('%s/CIK%s.json', self::SEC_SUBMISSIONS_BASE_URL, $cikPadded));
        if (!isset($submissions['filings']['recent']) || !is_array($submissions['filings']['recent'])) {
            throw new \RuntimeException('SEC no devolvió filings recientes para este ticker.');
        }

        $recent = $this->normalizeRecentFilings($submissions['filings']['recent']);
        $earningsMatch = $this->findLatestEarningsFiling($recent, $cikRaw);
        if ($earningsMatch === null) {
            throw new \RuntimeException(sprintf('No encontré un filing reciente de earnings para %s en SEC.', $symbol));
        }

        $earningsFiling = $earningsMatch['filing'];
        $exhibit = $earningsMatch['exhibit'];
        $preview = $earningsMatch['preview'];
        $periodicFiling = $this->findLatestPeriodicFiling($recent, $earningsFiling['filingDate']);

        return [
            'source' => 'sec',
            'symbol' => $symbol,
            'company_name' => $company['title'],
            'cik' => $cikPadded,
            'earnings_release' => [
                'form' => $earningsFiling['form'],
                'filing_date' => $earningsFiling['filingDate'],
                'report_date' => $earningsFiling['reportDate'],
                'items' => $earningsFiling['items'],
                'filing_url' => $this->buildPrimaryDocumentUrl($cikRaw, $earningsFiling['accessionNumber'], $earningsFiling['primaryDocument']),
                'index_url' => $this->buildFilingIndexUrl($cikRaw, $earningsFiling['accessionNumber']),
                'primary_document' => $earningsFiling['primaryDocument'],
                'exhibit_99_1' => $exhibit !== null ? [
                    'name' => $exhibit['name'],
                    'url' => $exhibit['url'],
                    'preview' => $preview['preview'] ?? null,
                    'text_length' => $preview['text_length'] ?? null,
                ] : null,
            ],
            'periodic_report' => $periodicFiling !== null ? [
                'form' => $periodicFiling['form'],
                'filing_date' => $periodicFiling['filingDate'],
                'report_date' => $periodicFiling['reportDate'],
                'url' => $this->buildPrimaryDocumentUrl($cikRaw, $periodicFiling['accessionNumber'], $periodicFiling['primaryDocument']),
                'primary_document' => $periodicFiling['primaryDocument'],
            ] : null,
        ];
    }

    private function findCompanyByTicker(string $symbol): array
    {
        $companies = $this->fetchJson(self::SEC_BASE_URL . '/files/company_tickers.json');
        foreach ($companies as $company) {
            if (($company['ticker'] ?? null) === $symbol) {
                return $company;
            }
        }

        throw new \RuntimeException(sprintf('Ticker %s no encontrado en company_tickers.json de SEC.', $symbol));
    }

    private function fetchJson(string $url): array
    {
        $response = $this->httpClient->request('GET', $url, [
            'headers' => self::SEC_HEADERS,
            'timeout' => 20,
        ]);

        return $response->toArray();
    }

    private function fetchHtml(string $url): string
    {
        $response = $this->httpClient->request('GET', $url, [
            'headers' => self::SEC_HEADERS,
            'timeout' => 20,
        ]);

        return $response->getContent();
    }

    private function normalizeRecentFilings(array $recent): array
    {
        $forms = $recent['form'] ?? [];
        $rows = [];

        foreach ($forms as $index => $form) {
            $rows[] = [
                'form' => $form,
                'filingDate' => $recent['filingDate'][$index] ?? null,
                'reportDate' => $recent['reportDate'][$index] ?? null,
                'accessionNumber' => $recent['accessionNumber'][$index] ?? null,
                'primaryDocument' => $recent['primaryDocument'][$index] ?? null,
                'items' => $recent['items'][$index] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * Returns the first candidate filing whose content actually reads like an earnings release.
     * The SEC `items` metadata field is not reliable for this: foreign private issuers filing 6-K
     * almost never populate it, so a structurally-eligible filing (right form, right items) can
     * still be something unrelated to earnings (e.g. an Annual General Meeting notice). We fetch a
     * preview of the document that would end up being stored and validate its content before
     * accepting it, moving on to the next candidate otherwise.
     */
    private function findLatestEarningsFiling(array $recent, string $cikRaw): ?array
    {
        foreach ($recent as $filing) {
            if (!in_array($filing['form'], self::EARNINGS_FORMS, true)) {
                continue;
            }

            $items = (string) ($filing['items'] ?? '');
            if ($items !== '' && !str_contains($items, '2.02')) {
                continue;
            }

            if (!is_string($filing['accessionNumber']) || !is_string($filing['primaryDocument'])) {
                continue;
            }

            $exhibit = $this->findExhibit99Document($cikRaw, $filing['accessionNumber']);
            $documentUrl = $exhibit['url'] ?? $this->buildPrimaryDocumentUrl($cikRaw, $filing['accessionNumber'], $filing['primaryDocument']);
            $preview = $this->fetchDocumentPreview($documentUrl);

            if (!$this->looksLikeEarningsRelease($preview['preview'] ?? '')) {
                continue;
            }

            return ['filing' => $filing, 'exhibit' => $exhibit, 'preview' => $preview];
        }

        return null;
    }

    private function looksLikeEarningsRelease(string $previewText): bool
    {
        $lower = strtolower($previewText);

        $governanceSignals = ['annual general meeting', 'notice of annual meeting', 'notice of meeting', 'proxy statement'];
        foreach ($governanceSignals as $signal) {
            if (str_contains($lower, $signal)) {
                return false;
            }
        }

        $periodSignals = ['quarter', 'three months ended', 'fiscal year', 'fiscal quarter'];
        $resultSignals = ['revenue', 'net income', 'net loss', 'financial results'];

        return $this->containsAny($lower, $periodSignals) && $this->containsAny($lower, $resultSignals);
    }

    /** @param string[] $needles */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function findLatestPeriodicFiling(array $recent, ?string $earningsFilingDate): ?array
    {
        $bestCandidate = null;

        foreach ($recent as $filing) {
            if (!in_array($filing['form'], self::PERIODIC_FORMS, true)) {
                continue;
            }

            if (!is_string($filing['accessionNumber']) || !is_string($filing['primaryDocument'])) {
                continue;
            }

            if ($earningsFilingDate !== null && $filing['filingDate'] !== null) {
                $distance = abs((new \DateTimeImmutable($earningsFilingDate))->diff(new \DateTimeImmutable($filing['filingDate']))->days);
                if ($distance <= 10) {
                    return $filing;
                }
            }

            $bestCandidate ??= $filing;
        }

        return $bestCandidate;
    }

    private function findExhibit99Document(string $cikRaw, string $accessionNumber): ?array
    {
        $indexHtml = $this->fetchHtml($this->buildFilingIndexUrl($cikRaw, $accessionNumber));

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($indexHtml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//table[contains(@class, "tableFile")]//tr') as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length < 4) {
                continue;
            }

            $type = trim($cells->item(3)?->textContent ?? '');
            if (!in_array($type, ['EX-99.1', '99.1'], true)) {
                continue;
            }

            $link = $cells->item(2)?->getElementsByTagName('a')->item(0);
            $name = trim($link?->textContent ?? '');
            $href = trim($link?->getAttribute('href') ?? '');
            if ($href === '') {
                continue;
            }

            return [
                'name' => $name,
                'url' => str_starts_with($href, 'http') ? $href : self::SEC_BASE_URL . $href,
            ];
        }

        return null;
    }

    private function fetchDocumentPreview(string $url): array
    {
        $html = $this->fetchHtml($url);
        $text = $this->normalizeHtmlToText($html);

        return [
            'preview' => mb_substr($text, 0, 3000),
            'text_length' => mb_strlen($text),
        ];
    }

    private function normalizeHtmlToText(string $html): string
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//script|//style|//noscript') as $node) {
            $node->parentNode?->removeChild($node);
        }

        $text = html_entity_decode($dom->textContent ?? '', ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('/\R+/u', "\n", $text) ?? $text;
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function buildFilingIndexUrl(string $cikRaw, string $accessionNumber): string
    {
        $accessionCompact = str_replace('-', '', $accessionNumber);

        return sprintf(
            '%s/Archives/edgar/data/%s/%s/%s-index.htm',
            self::SEC_BASE_URL,
            $cikRaw,
            $accessionCompact,
            $accessionNumber
        );
    }

    private function buildPrimaryDocumentUrl(string $cikRaw, string $accessionNumber, string $primaryDocument): string
    {
        $accessionCompact = str_replace('-', '', $accessionNumber);

        return sprintf(
            '%s/Archives/edgar/data/%s/%s/%s',
            self::SEC_BASE_URL,
            $cikRaw,
            $accessionCompact,
            $primaryDocument
        );
    }
}
