<?php

namespace App\Futbol\Application\Favorito\GetTeamsByLiga;

use App\Futbol\Application\Source\UkrainianPremierLeagueOfficialSource;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GetTeamsByLigaUseCase
{
    private const ESPN_TEAMS = 'https://site.api.espn.com/apis/site/v2/sports/soccer/%s/teams';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UkrainianPremierLeagueOfficialSource $uplSource,
    ) {}

    public function execute(string $ligaCode): array
    {
        if ($this->uplSource->supports($ligaCode)) {
            return $this->uplSource->fetchTeams();
        }

        $response = $this->httpClient->request('GET', sprintf(self::ESPN_TEAMS, $ligaCode), [
            'headers' => ['User-Agent' => 'curl/8.5.0'],
            'timeout' => 8,
        ]);

        $data = $response->toArray();
        $rawTeams = $data['sports'][0]['leagues'][0]['teams'] ?? [];

        $teams = [];
        foreach ($rawTeams as $entry) {
            $t = $entry['team'] ?? [];
            if (empty($t['id']) || empty($t['displayName'])) continue;
            $teams[] = [
                'espn_team_id' => (string) $t['id'],
                'team_name' => $t['displayName'],
                'abrev' => $t['abbreviation'] ?? '',
            ];
        }

        usort($teams, fn($a, $b) => strcmp($a['team_name'], $b['team_name']));

        return $teams;
    }
}
