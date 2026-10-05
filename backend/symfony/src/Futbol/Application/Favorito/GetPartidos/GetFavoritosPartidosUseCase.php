<?php

namespace App\Futbol\Application\Favorito\GetPartidos;

use App\Futbol\Application\Source\UkrainianPremierLeagueOfficialSource;
use App\Futbol\Domain\Repository\FutbolFavoritoRepositoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GetFavoritosPartidosUseCase
{
    private const ESPN_SCHEDULE = 'https://site.api.espn.com/apis/site/v2/sports/soccer/%s/teams/%s/schedule';

    public function __construct(
        private readonly FutbolFavoritoRepositoryInterface $repository,
        private readonly HttpClientInterface $httpClient,
        private readonly UkrainianPremierLeagueOfficialSource $uplSource,
    ) {}

    public function execute(): array
    {
        $favoritos = $this->repository->findAll();
        $now       = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $results   = [];

        foreach ($favoritos as $favorito) {
            if ($this->uplSource->supports($favorito->getEspnLigaCode())) {
                $results[] = [
                    'favorito' => $favorito->toArray(),
                    'proximo_partido' => $this->uplSource->findNextMatch($favorito->getEspnTeamId(), $now),
                ];
                continue;
            }

            $url = sprintf(self::ESPN_SCHEDULE, $favorito->getEspnLigaCode(), $favorito->getEspnTeamId());

            try {
                $response = $this->httpClient->request('GET', $url, [
                    'headers' => ['User-Agent' => 'curl/8.5.0'],
                    'timeout' => 8,
                ]);
                $data   = $response->toArray();
                $events = $data['events'] ?? [];
            } catch (\Throwable) {
                $results[] = ['favorito' => $favorito->toArray(), 'proximo_partido' => null];
                continue;
            }

            $proximo = null;
            foreach ($events as $ev) {
                $rawDate = $ev['date'] ?? null;
                if (!$rawDate) continue;

                try {
                    $evDate = new \DateTimeImmutable($rawDate);
                } catch (\Throwable) {
                    continue;
                }

                if ($evDate < $now) continue;

                $comp        = $ev['competitions'][0] ?? [];
                $competitors = $comp['competitors'] ?? [];
                $home        = null;
                $away        = null;
                foreach ($competitors as $c) {
                    if ($c['homeAway'] === 'home') $home = $c;
                    if ($c['homeAway'] === 'away') $away = $c;
                }

                $proximo = [
                    'espn_event_id'  => $ev['id'],
                    'fecha'          => $evDate->format('Y-m-d'),
                    'hora_utc'       => $evDate->format(\DateTimeInterface::ATOM),
                    'equipo_local'   => $home['team']['displayName'] ?? '',
                    'equipo_visit'   => $away['team']['displayName'] ?? '',
                    'estadio'        => $comp['venue']['fullName'] ?? null,
                    'es_local'       => ($home['team']['id'] ?? '') === $favorito->getEspnTeamId(),
                ];
                break;
            }

            $results[] = [
                'favorito'        => $favorito->toArray(),
                'proximo_partido' => $proximo,
            ];
        }

        return $results;
    }
}
