<?php

namespace App\Futbol\Application\Liga\GetEquiposLiga;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GetEquiposLigaUseCase
{
    private const ESPN_URL = 'https://site.api.espn.com/apis/v2/sports/soccer/%s/standings';

    public function __construct(private readonly HttpClientInterface $httpClient) {}

    public function execute(string $codigo): array
    {
        $response = $this->httpClient->request('GET', sprintf(self::ESPN_URL, $codigo), [
            'headers' => ['User-Agent' => 'Mozilla/5.0'],
            'timeout' => 10,
        ]);

        $data = $response->toArray();
        $equipos = $this->parseStandings($data);

        if (empty($equipos)) {
            return ['equipos' => [], 'liga' => $codigo, 'total' => 0];
        }

        usort($equipos, fn($a, $b) => $a['total_gpm'] <=> $b['total_gpm']);

        return [
            'equipos' => $equipos,
            'liga'    => $codigo,
            'total'   => count($equipos),
        ];
    }

    private function parseStandings(array $data): array
    {
        $entries = $data['standings']['entries'] ?? [];

        // ESPN a veces anida en groups/children
        if (empty($entries)) {
            foreach ($data['children'] ?? [] as $group) {
                $entries = array_merge($entries, $group['standings']['entries'] ?? []);
            }
        }

        $equipos = [];
        foreach ($entries as $i => $entry) {
            $team  = $entry['team'] ?? [];
            $stats = [];
            foreach ($entry['stats'] ?? [] as $s) {
                $stats[$s['name']] = $s['value'] ?? null;
            }

            $pj = (int) ($stats['gamesPlayed'] ?? 0);
            if ($pj === 0) continue;

            $gf  = (int) ($stats['pointsFor'] ?? 0);
            $gc  = (int) ($stats['pointsAgainst'] ?? 0);
            $pts = (int) ($stats['points'] ?? 0);

            $gf_pj    = round($gf / $pj, 2);
            $gc_pj    = round($gc / $pj, 2);
            $total_gpm = round(($gf + $gc) / $pj, 2);

            $equipos[] = [
                'espn_team_id' => (string) ($team['id'] ?? ''),
                'pos'       => $i + 1,
                'equipo'    => $team['displayName'] ?? '',
                'abrev'     => $team['abbreviation'] ?? '',
                'logo'      => $team['logos'][0]['href'] ?? null,
                'pj'        => $pj,
                'pts'       => $pts,
                'gf'        => $gf,
                'gc'        => $gc,
                'gf_pj'    => $gf_pj,
                'gc_pj'    => $gc_pj,
                'total_gpm' => $total_gpm,
            ];
        }

        return $equipos;
    }
}
