<?php

namespace App\Futbol\Application\Equipo\GetAnalisis;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GetEquipoAnalisisUseCase
{
    private const ESPN_TEAM_STATS_URL = 'https://www.espn.com/soccer/team/stats/_/id/%s/league/%s%s/view/%s';

    public function __construct(private readonly HttpClientInterface $httpClient) {}

    public function execute(string $teamId, string $liga, ?int $season = null): array
    {
        $liga = strtoupper(trim($liga));
        if ($teamId === '' || $liga === '') {
            throw new \RuntimeException('teamId y liga son obligatorios');
        }

        $scoring = $this->fetchStatsPage($teamId, $liga, $season, 'scoring');
        $fallbackApplied = false;
        $requestedSeason = $season;
        $usedSeason = $this->resolveUsedSeason($scoring, $season);

        if ($season === null && $this->hasNoRows($scoring)) {
            $fallback = $this->findFallbackSeasonWithRows($teamId, $liga, $scoring);
            if ($fallback !== null) {
                $scoring = $fallback['stats'];
                $usedSeason = $fallback['season'];
                $fallbackApplied = true;
            }
        }

        $usedSeason ??= (int) ($scoring['season']['year'] ?? 0) ?: null;

        $discipline = $this->fetchStatsPage($teamId, $liga, $usedSeason, 'discipline');
        $performance = $this->fetchStatsPage($teamId, $liga, $usedSeason, 'performance');

        $scoringTables = $this->normalizeTables($scoring['tables'] ?? [], $scoring['tableRows'] ?? []);
        $disciplineTables = $this->normalizeTables($discipline['tables'] ?? [], $discipline['tableRows'] ?? []);
        $performanceTables = $this->normalizeTables($performance['tables'] ?? [], $performance['tableRows'] ?? []);

        $topScorers = $scoringTables['top_scorers'] ?? [];
        $topAssists = $scoringTables['top_assists'] ?? [];
        $disciplineRows = $disciplineTables['discipline'] ?? [];

        return [
            'team' => $this->normalizeTeam($scoring['team'] ?? []),
            'league' => [
                'code' => $liga,
                'name' => $scoring['soccerLeague'] ?? $discipline['soccerLeague'] ?? $performance['soccerLeague'] ?? $liga,
                'available_competitions' => $this->normalizeLeagues($scoring['dropdownLeagues'] ?? []),
            ],
            'season' => [
                'requested' => $requestedSeason,
                'used' => $usedSeason,
                'label' => $this->findSeasonLabel($scoring['seasonYears'] ?? [], $usedSeason)
                    ?? $scoring['seasonDisplayName']
                    ?? $discipline['seasonDisplayName']
                    ?? null,
                'fallback_applied' => $fallbackApplied,
                'available' => $this->normalizeSeasons($scoring['seasonYears'] ?? []),
            ],
            'coverage' => [
                'available_metrics' => [
                    'top_scorer',
                    'top_assister',
                    'best_goals_per_match',
                    'most_yellow_cards',
                    'most_red_cards',
                    'discipline_points',
                    'team_performance_highlights',
                ],
                'unavailable_metrics' => [
                    [
                        'key' => 'shots_on_target',
                        'reason' => 'La vista publica de ESPN no expone tiros a puerta por jugador en este endpoint.',
                    ],
                    [
                        'key' => 'fouls_committed',
                        'reason' => 'La vista publica de ESPN no expone faltas cometidas por jugador en este endpoint.',
                    ],
                    [
                        'key' => 'goalkeeper_saves_per_match',
                        'reason' => 'La vista publica de ESPN no expone paradas del portero por partido en este endpoint.',
                    ],
                ],
            ],
            'available_views' => $this->normalizeViews($scoring['views']['options'] ?? []),
            'leaders' => [
                'top_scorer' => $this->buildScoringLeader($topScorers[0] ?? null, 'totalGoals', 'goals'),
                'top_assister' => $this->buildScoringLeader($topAssists[0] ?? null, 'goalAssists', 'assists'),
                'best_goals_per_match' => $this->findBestGoalsPerMatch($topScorers),
                'most_yellow_cards' => $this->buildDisciplineLeader($this->maxBy($disciplineRows, 'yellowCards'), 'yellowCards', 'yellow_cards'),
                'most_red_cards' => $this->buildDisciplineLeader($this->maxBy($disciplineRows, 'redCards'), 'redCards', 'red_cards'),
                'discipline_points' => $this->buildDisciplineLeader($this->maxBy($disciplineRows, 'points'), 'points', 'points'),
            ],
            'tables' => [
                'top_scorers' => array_slice($topScorers, 0, 10),
                'top_assists' => array_slice($topAssists, 0, 10),
                'discipline' => array_slice($disciplineRows, 0, 10),
                'performance' => $performanceTables,
            ],
        ];
    }

    private function fetchStatsPage(string $teamId, string $liga, ?int $season, string $view): array
    {
        $seasonPath = $season !== null ? sprintf('/season/%d', $season) : '';
        $url = sprintf(self::ESPN_TEAM_STATS_URL, $teamId, $liga, $seasonPath, $view);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['User-Agent' => 'Mozilla/5.0'],
                'timeout' => 12,
            ]);

            return $this->extractStatsPayload($response->getContent());
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf('No se pudo leer ESPN para %s: %s', $view, $e->getMessage()), 0, $e);
        }
    }

    private function extractStatsPayload(string $html): array
    {
        if (!preg_match("/window\\['__espnfitt__'\\]=(\\{.*?\\});<\\/script>/s", $html, $matches)) {
            throw new \RuntimeException('ESPN cambió el formato embebido del análisis de equipo.');
        }

        try {
            $payload = json_decode($matches[1], true, 2048, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('No se pudo decodificar la respuesta de ESPN.', 0, $e);
        }

        $stats = $payload['page']['content']['stats'] ?? null;
        if (!is_array($stats)) {
            throw new \RuntimeException('ESPN no devolvió datos de estadísticas para este equipo.');
        }

        return $stats;
    }

    private function resolveUsedSeason(array $stats, ?int $requestedSeason): ?int
    {
        if ($requestedSeason !== null) {
            return $requestedSeason;
        }

        $currentSeason = (int) ($stats['currentSeason'] ?? 0);
        $pageSeason = (int) ($stats['season']['year'] ?? 0);

        if (!$this->hasNoRows($stats) && $pageSeason > 0) {
            return $pageSeason;
        }

        return $currentSeason > 0 ? $currentSeason : ($pageSeason > 0 ? $pageSeason : null);
    }

    private function findFallbackSeasonWithRows(string $teamId, string $liga, array $stats): ?array
    {
        $pageSeason = (int) ($stats['season']['year'] ?? 0);

        foreach ($stats['seasonYears'] ?? [] as $seasonOption) {
            $candidateSeason = (int) ($seasonOption['value'] ?? 0);
            if ($candidateSeason <= 0 || $candidateSeason === $pageSeason) {
                continue;
            }

            $candidateStats = $this->fetchStatsPage($teamId, $liga, $candidateSeason, 'scoring');
            if (!$this->hasNoRows($candidateStats)) {
                return [
                    'season' => $candidateSeason,
                    'stats' => $candidateStats,
                ];
            }
        }

        return null;
    }

    private function hasNoRows(array $stats): bool
    {
        foreach ($stats['tableRows'] ?? [] as $rows) {
            if (!empty($rows)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeTables(array $tables, array $tableRows): array
    {
        $normalized = [];

        foreach ($tables as $index => $table) {
            $key = $this->slugify((string) ($table['title'] ?? ('table_' . $index)));
            $headers = $table['headers'] ?? [];
            $rows = $tableRows[$index] ?? [];

            $normalized[$key] = array_map(
                fn(array $row) => $this->normalizeRow($headers, $row),
                array_filter($rows, 'is_array')
            );
        }

        return $normalized;
    }

    private function normalizeRow(array $headers, array $row): array
    {
        $normalized = [];

        foreach ($headers as $index => $header) {
            $key = (string) ($header['type'] ?? ('col_' . $index));
            $normalized[$key] = $this->normalizeCell($row[$index] ?? null);
        }

        return $normalized;
    }

    private function normalizeCell(mixed $cell): mixed
    {
        if (!is_array($cell)) {
            return $cell;
        }

        if (isset($cell['name'])) {
            return [
                'name' => $cell['name'] ?? null,
                'short_name' => $cell['shortName'] ?? null,
                'href' => $cell['href'] ?? null,
                'uid' => $cell['uid'] ?? null,
            ];
        }

        if (array_key_exists('isStats', $cell) && array_key_exists('value', $cell)) {
            return $cell['value'];
        }

        if (isset($cell['homeTeam'], $cell['vsTeam'])) {
            return [
                'home_team' => $this->normalizeMatchTeam($cell['homeTeam']),
                'away_team' => $this->normalizeMatchTeam($cell['vsTeam']),
            ];
        }

        if (array_key_exists('value', $cell)) {
            return [
                'value' => $cell['value'],
                'format' => $cell['format'] ?? null,
            ];
        }

        return $cell;
    }

    private function normalizeMatchTeam(array $team): array
    {
        return [
            'name' => $team['name'] ?? null,
            'abbreviation' => $team['abbreviation'] ?? null,
            'score' => $team['score'] ?? null,
            'logo' => $team['logo'] ?? null,
            'link' => $team['link'] ?? null,
        ];
    }

    private function normalizeTeam(array $team): array
    {
        return [
            'id' => (string) ($team['id'] ?? ''),
            'name' => $team['displayName'] ?? '',
            'short_name' => $team['shortDisplayName'] ?? '',
            'abbreviation' => $team['abbrev'] ?? '',
            'logo' => $team['logo'] ?? null,
            'color' => $team['teamColor'] ?? null,
            'record_summary' => $team['recordSummary'] ?? null,
            'standing_summary' => $team['standingSummary'] ?? null,
        ];
    }

    private function normalizeViews(array $views): array
    {
        return array_map(function (array $view): array {
            $url = (string) ($view['url'] ?? '');

            return [
                'title' => $view['title'] ?? '',
                'view' => preg_match('#/view/([^/]+)$#', $url, $match) ? $match[1] : null,
                'url' => $url,
            ];
        }, $views);
    }

    private function normalizeSeasons(array $seasons): array
    {
        return array_map(fn(array $season): array => [
            'value' => $season['value'] ?? null,
            'label' => $season['label'] ?? $season['title'] ?? null,
            'url' => $season['url'] ?? null,
        ], $seasons);
    }

    private function normalizeLeagues(array $leagues): array
    {
        return array_map(fn(array $league): array => [
            'value' => $league['value'] ?? null,
            'label' => $league['label'] ?? $league['title'] ?? null,
            'url' => $league['url'] ?? null,
        ], $leagues);
    }

    private function findSeasonLabel(array $seasons, ?int $season): ?string
    {
        if ($season === null) {
            return null;
        }

        foreach ($seasons as $seasonOption) {
            if ((int) ($seasonOption['value'] ?? 0) === $season) {
                return $seasonOption['label'] ?? $seasonOption['title'] ?? null;
            }
        }

        return null;
    }

    private function buildScoringLeader(?array $row, string $statKey, string $label): ?array
    {
        if ($row === null) {
            return null;
        }

        $appearances = (int) ($row['appearances'] ?? 0);
        $value = (int) ($row[$statKey] ?? 0);

        return [
            'player' => $row['athlete']['name'] ?? null,
            'short_name' => $row['athlete']['short_name'] ?? null,
            'player_href' => $row['athlete']['href'] ?? null,
            'appearances' => $appearances,
            $label => $value,
            'per_match' => $appearances > 0 ? round($value / $appearances, 2) : null,
        ];
    }

    private function buildDisciplineLeader(?array $row, string $statKey, string $label): ?array
    {
        if ($row === null) {
            return null;
        }

        return [
            'player' => $row['athlete']['name'] ?? null,
            'short_name' => $row['athlete']['short_name'] ?? null,
            'player_href' => $row['athlete']['href'] ?? null,
            'appearances' => (int) ($row['appearances'] ?? 0),
            $label => (int) ($row[$statKey] ?? 0),
        ];
    }

    private function findBestGoalsPerMatch(array $rows): ?array
    {
        $best = null;
        $bestAverage = -1.0;

        foreach ($rows as $row) {
            $appearances = (int) ($row['appearances'] ?? 0);
            $goals = (int) ($row['totalGoals'] ?? 0);
            if ($appearances <= 0) {
                continue;
            }

            $average = $goals / $appearances;
            if ($average > $bestAverage) {
                $bestAverage = $average;
                $best = [
                    'player' => $row['athlete']['name'] ?? null,
                    'short_name' => $row['athlete']['short_name'] ?? null,
                    'player_href' => $row['athlete']['href'] ?? null,
                    'appearances' => $appearances,
                    'goals' => $goals,
                    'goals_per_match' => round($average, 2),
                ];
            }
        }

        return $best;
    }

    private function maxBy(array $rows, string $key): ?array
    {
        $best = null;
        $bestValue = null;

        foreach ($rows as $row) {
            $value = (int) ($row[$key] ?? 0);
            if ($best === null || $value > $bestValue) {
                $best = $row;
                $bestValue = $value;
            }
        }

        return $best;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;

        return trim($value, '_');
    }
}
