<?php

namespace App\Acciones\Application\Earnings\FetchLatestReport;

use App\Acciones\Application\Earnings\GetLatestReport\GetLatestEarningsReportUseCase;
use App\Acciones\Domain\Repository\AccionEarningsReportRepositoryInterface;
use App\Acciones\Domain\Repository\AccionRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionEarningsReport;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FetchLatestEarningsReportUseCase
{
    private const FETCH_HEADERS = [
        'User-Agent' => 'codemv/1.0 earnings-fetch support@codemv.local',
        'From' => 'support@codemv.local',
        'Accept' => 'text/html,application/xhtml+xml,application/json',
        'Accept-Language' => 'en-US,en;q=0.9',
    ];

    public function __construct(
        private readonly AccionRepositoryInterface $accionRepository,
        private readonly AccionEarningsReportRepositoryInterface $reportRepository,
        private readonly GetLatestEarningsReportUseCase $getLatestEarningsReportUseCase,
        private readonly HttpClientInterface $httpClient,
    ) {}

    public function execute(FetchLatestEarningsReportCommand $command): AccionEarningsReport
    {
        $symbol = strtoupper(trim($command->symbol));
        if ($symbol === '') {
            throw new \InvalidArgumentException('symbol es obligatorio');
        }

        $accion = $this->accionRepository->findBySymbol($symbol);
        if ($accion === null) {
            throw new \InvalidArgumentException("Acción no encontrada para symbol {$symbol}");
        }

        $lookup = $this->getLatestEarningsReportUseCase->execute($symbol);
        $release = $lookup['earnings_release'] ?? null;
        if (!is_array($release)) {
            throw new \RuntimeException("No se encontró earnings_release para {$symbol}");
        }

        $accessionNumber = $release['index_url'] !== null
            ? $this->extractAccessionNumberFromIndexUrl((string) $release['index_url'])
            : null;

        if ($accessionNumber !== null) {
            $existing = $this->reportRepository->findByAccionAndAccessionNumber($accion, $accessionNumber);
            if ($existing !== null) {
                return $this->refreshExistingReport($existing, $lookup, $release);
            }
        }

        $documentUrl = $release['exhibit_99_1']['url'] ?? $release['filing_url'] ?? null;
        if (!is_string($documentUrl) || $documentUrl === '') {
            throw new \RuntimeException("No hay URL de documento para {$symbol}");
        }

        $rawDocument = $this->fetchRawDocument($documentUrl);
        $rawContent = $this->normalizeHtmlToText($rawDocument);

        $report = new AccionEarningsReport(
            uuid: Uuid::uuid4()->toString(),
            accion: $accion,
            source: (string) ($lookup['source'] ?? 'sec'),
            formType: (string) ($release['form'] ?? 'unknown'),
            contentFormat: 'text/plain',
            rawContent: $rawContent,
        );

        $this->fillReportMetadata($report, $lookup, $release, $accessionNumber, $documentUrl);
        $this->reportRepository->save($report);

        return $report;
    }

    private function refreshExistingReport(AccionEarningsReport $report, array $lookup, array $release): AccionEarningsReport
    {
        $documentUrl = $release['exhibit_99_1']['url'] ?? $release['filing_url'] ?? null;
        if (!is_string($documentUrl) || $documentUrl === '') {
            throw new \RuntimeException('No hay URL de documento para refrescar el reporte existente.');
        }

        $rawDocument = $this->fetchRawDocument($documentUrl);
        $report->setContentFormat('text/plain');
        $report->setRawContent($this->normalizeHtmlToText($rawDocument));
        $this->fillReportMetadata($report, $lookup, $release, $report->getAccessionNumber(), $documentUrl);
        $this->reportRepository->save($report);

        return $report;
    }

    private function fillReportMetadata(
        AccionEarningsReport $report,
        array $lookup,
        array $release,
        ?string $accessionNumber,
        string $documentUrl,
    ): void {
        $report->setAccesssionNumber($accessionNumber);
        $report->setCik(isset($lookup['cik']) ? (string) $lookup['cik'] : null);
        $report->setFilingDate($this->toDateImmutable($release['filing_date'] ?? null));
        $report->setReportDate($this->toDateImmutable($release['report_date'] ?? null));
        $report->setPrimaryDocumentName(isset($release['primary_document']) ? (string) $release['primary_document'] : null);
        $report->setExhibitDocumentName(isset($release['exhibit_99_1']['name']) ? (string) $release['exhibit_99_1']['name'] : null);
        $report->setFilingUrl(isset($release['filing_url']) ? (string) $release['filing_url'] : null);
        $report->setIndexUrl(isset($release['index_url']) ? (string) $release['index_url'] : null);
        $report->setExhibitUrl(isset($release['exhibit_99_1']['url']) ? (string) $release['exhibit_99_1']['url'] : null);
        $report->setPeriodicReportUrl(isset($lookup['periodic_report']['url']) ? (string) $lookup['periodic_report']['url'] : null);
        $report->setItems(isset($release['items']) ? (string) $release['items'] : null);
        $report->setFetchedAt(new \DateTimeImmutable());
        $report->setMetadata([
            'company_name' => $lookup['company_name'] ?? null,
            'document_url_used' => $documentUrl,
            'periodic_report' => $lookup['periodic_report'] ?? null,
            'earnings_release' => [
                'form' => $release['form'] ?? null,
                'filing_date' => $release['filing_date'] ?? null,
                'report_date' => $release['report_date'] ?? null,
                'items' => $release['items'] ?? null,
                'primary_document' => $release['primary_document'] ?? null,
                'filing_url' => $release['filing_url'] ?? null,
                'index_url' => $release['index_url'] ?? null,
                'exhibit_99_1' => [
                    'name' => $release['exhibit_99_1']['name'] ?? null,
                    'url' => $release['exhibit_99_1']['url'] ?? null,
                    'preview' => $release['exhibit_99_1']['preview'] ?? null,
                    'text_length' => $release['exhibit_99_1']['text_length'] ?? null,
                ],
            ],
        ]);
    }

    private function fetchRawDocument(string $url): string
    {
        $response = $this->httpClient->request('GET', $url, [
            'headers' => self::FETCH_HEADERS,
            'timeout' => 30,
        ]);

        return $response->getContent();
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

    private function extractAccessionNumberFromIndexUrl(string $indexUrl): ?string
    {
        if (preg_match('/(\\d{10}-\\d{2}-\\d{6})-index\\.htm$/', $indexUrl, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function toDateImmutable(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return new \DateTimeImmutable($value);
    }
}
