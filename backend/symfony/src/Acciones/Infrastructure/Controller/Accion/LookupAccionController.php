<?php

namespace App\Acciones\Infrastructure\Controller\Accion;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LookupAccionController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {}

    #[Route('/api/acciones/lookup', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 1) {
            return $this->json(['results' => []]);
        }

        try {
            // Search for matching symbols
            $searchRes = $this->httpClient->request('GET',
                'https://query1.finance.yahoo.com/v1/finance/search',
                [
                    'query'   => ['q' => $q, 'quotesCount' => 8, 'newsCount' => 0, 'listsCount' => 0],
                    'headers' => ['User-Agent' => 'Mozilla/5.0'],
                    'timeout' => 5,
                ]
            );
            $quotes = $searchRes->toArray()['quotes'] ?? [];

            $results = [];
            foreach ($quotes as $quote) {
                if (!isset($quote['symbol'])) continue;
                // Only stocks, ETFs — skip indices, futures, crypto
                $type = $quote['quoteType'] ?? '';
                if (!in_array($type, ['EQUITY', 'ETF', 'CRYPTOCURRENCY'])) continue;

                $results[] = [
                    'symbol'   => $quote['symbol'],
                    'name'     => $quote['longname'] ?? $quote['shortname'] ?? $quote['symbol'],
                    'exchange' => $quote['exchDisp'] ?? $quote['exchange'] ?? null,
                    'type'     => match($type) {
                        'ETF'            => 'etf',
                        'CRYPTOCURRENCY' => 'crypto',
                        default          => 'stock',
                    },
                ];
            }

            if (empty($results)) {
                return $this->json(['results' => []]);
            }

            // Fetch sector/industry for top result
            $top = $results[0]['symbol'];
            try {
                $detailRes = $this->httpClient->request('GET',
                    "https://query2.finance.yahoo.com/v10/finance/quoteSummary/{$top}",
                    [
                        'query'   => ['modules' => 'assetProfile'],
                        'headers' => ['User-Agent' => 'Mozilla/5.0'],
                        'timeout' => 5,
                    ]
                );
                $profile = $detailRes->toArray()['quoteSummary']['result'][0]['assetProfile'] ?? [];
                $results[0]['sector']   = $profile['sector'] ?? null;
                $results[0]['industry'] = $profile['industry'] ?? null;
            } catch (\Throwable) {
                // sector is optional
            }

            return $this->json(['results' => $results]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Lookup failed: ' . $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }
}
