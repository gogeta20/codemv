<?php

namespace App\Futbol\Application\Copa\Support;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class EuropeanCupEspnService
{
    private const SCOREBOARD_URL = 'https://site.api.espn.com/apis/site/v2/sports/soccer/%s/scoreboard';
    private const SUMMARY_URL = 'https://site.api.espn.com/apis/site/v2/sports/soccer/%s/summary';
    private const TEAM_URL = 'https://site.api.espn.com/apis/site/v2/sports/soccer/teams/%s';

    private const UEFA_QUALIFYING_ARTICLES = [
        'uefa.champions' => 'https://www.uefa.com/uefachampionsleague/news/02a6-20e5a8be4e63-ae971c582f8c-1000--champions-league-qualifying-fixtures-dates-how-it-works/',
        'uefa.europa' => 'https://www.uefa.com/uefaeuropaleague/news/02a6-20e5db0029dd-8241a8d00925-1000--europa-league-qualifying-fixtures-results-dates-how-it-works/',
        'uefa.europa.conf' => 'https://www.uefa.com/uefaconferenceleague/news/02a6-20e5e911587f-cc10425958b3-1000--conference-league-qualifying-fixtures-dates-how-it-works/',
    ];

    private const UEFA_PATH_HEADINGS = [
        'Champions path',
        'League path',
        'Main path',
    ];

    private array $teamCache = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EuropeanCupCatalog $cupCatalog,
        private readonly UefaAssociationStrengthCatalog $strengthCatalog,
        private readonly UefaClubCountryCatalog $clubCountryCatalog,
    ) {}

    public function listMatches(?\DateTimeImmutable $date = null): array
    {
        $targetDate = $date ?? new \DateTimeImmutable('today');
        $uefaItems = $this->listUefaMatches($targetDate);
        if ($uefaItems !== []) {
            return $uefaItems;
        }

        $items = [];

        foreach ($this->cupCatalog->all() as $competitionCode => $competitionLabel) {
            $data = $this->requestJson(sprintf(self::SCOREBOARD_URL, $competitionCode), ['dates' => $targetDate->format('Ymd')]);
            foreach ($data['events'] ?? [] as $event) {
                $items[] = $this->mapScoreboardEvent($competitionCode, $competitionLabel, $event);
            }
        }

        usort($items, fn(array $a, array $b) => strcmp((string) $a['date'], (string) $b['date']));

        return $items;
    }

    public function getMatchDetail(string $competitionCode, string $eventId): array
    {
        $uefaItem = $this->findUefaMatchByEventId($competitionCode, $eventId);
        if ($uefaItem !== null) {
            $uefaItem['details'] = [
                'headline' => $uefaItem['name'],
                'note' => 'Fuente oficial UEFA qualifying news',
                'season' => $this->seasonLabelFromDate($uefaItem['date']),
                'venue' => [
                    'name' => null,
                    'city' => null,
                    'country' => null,
                ],
                'stage' => $uefaItem['meta']['stage'] ?? null,
                'path' => $uefaItem['meta']['path'] ?? null,
                'source_url' => $uefaItem['meta']['source_url'] ?? null,
            ];

            return $uefaItem;
        }

        $competitionLabel = $this->cupCatalog->labelForCompetition($competitionCode);
        $data = $this->requestJson(sprintf(self::SUMMARY_URL, $competitionCode), ['event' => $eventId]);

        $header = $data['header'] ?? [];
        $competition = $data['format']['competition'] ?? [];
        $event = [
            'id' => $header['id'] ?? $eventId,
            'date' => $header['competitions'][0]['date'] ?? null,
            'name' => $header['competitions'][0]['name'] ?? ($header['season']['displayName'] ?? $competitionLabel),
            'shortName' => $header['competitions'][0]['shortName'] ?? null,
            'season' => $header['season'] ?? [],
            'competitions' => $header['competitions'] ?? [],
            'status' => $data['gameInfo']['status'] ?? ($header['competitions'][0]['status'] ?? []),
        ];

        $mapped = $this->mapScoreboardEvent($competitionCode, $competitionLabel, $event);
        $mapped['details'] = [
            'headline' => $header['headline'] ?? null,
            'note' => $header['note'] ?? null,
            'season' => $header['season']['displayName'] ?? null,
            'venue' => [
                'name' => $data['gameInfo']['venue']['fullName'] ?? null,
                'city' => $data['gameInfo']['venue']['address']['city'] ?? null,
                'country' => $data['gameInfo']['venue']['address']['country'] ?? null,
            ],
            'leg' => $competition['leg'] ?? null,
            'aggregate' => $competition['aggregateScore'] ?? null,
            'broadcasts' => array_map(
                static fn(array $item) => $item['names'][0] ?? null,
                $header['competitions'][0]['broadcasts'] ?? [],
            ),
            'articles' => array_map(
                static fn(array $item) => [
                    'headline' => $item['headline'] ?? '',
                    'description' => $item['description'] ?? '',
                    'links' => $item['links']['web']['href'] ?? null,
                ],
                $data['articles'] ?? [],
            ),
        ];

        return $mapped;
    }

    private function listUefaMatches(\DateTimeImmutable $date): array
    {
        $items = [];

        foreach ($this->cupCatalog->all() as $competitionCode => $competitionLabel) {
            $items = [...$items, ...$this->listUefaMatchesForCompetition($competitionCode, $competitionLabel, $date)];
        }

        usort($items, fn(array $a, array $b) => strcmp((string) $a['date'], (string) $b['date']));

        return $items;
    }

    private function listUefaMatchesForCompetition(string $competitionCode, string $competitionLabel, \DateTimeImmutable $date): array
    {
        $articleUrl = self::UEFA_QUALIFYING_ARTICLES[$competitionCode] ?? null;
        if ($articleUrl === null) {
            return [];
        }

        try {
            $html = $this->requestText($articleUrl);
        } catch (\Throwable) {
            return [];
        }

        $lines = $this->extractUefaLines($html);
        $targetDayHeading = $date->format('l j F');
        $stage = null;
        $path = null;
        $currentDay = null;
        $items = [];

        for ($index = 0, $count = count($lines); $index < $count; $index++) {
            $line = $lines[$index];

            if ($this->isUefaStageHeading($line)) {
                $stage = $line;
                $path = null;
                continue;
            }

            if (in_array($line, self::UEFA_PATH_HEADINGS, true)) {
                $path = $line;
                continue;
            }

            if ($this->isUefaDayHeading($line)) {
                if ($currentDay === $targetDayHeading && $line !== $targetDayHeading) {
                    break;
                }

                $currentDay = $line;
                continue;
            }

            if ($currentDay !== $targetDayHeading) {
                continue;
            }

            if ($this->shouldSkipUefaLine($line)) {
                continue;
            }

            $candidate = $line;
            $nextLine = $lines[$index + 1] ?? null;
            if ($nextLine !== null && preg_match('/^\(\d{1,2}:\d{2}\)$/', $nextLine) === 1) {
                $candidate .= ' ' . $nextLine;
                $index++;
            }

            $parsed = $this->parseUefaMatchLine($candidate);
            if ($parsed === null) {
                continue;
            }

            $items[] = $this->mapUefaMatch(
                $competitionCode,
                $competitionLabel,
                $date,
                $parsed,
                $stage,
                $path,
                $articleUrl,
            );
        }

        return $items;
    }

    private function findUefaMatchByEventId(string $competitionCode, string $eventId): ?array
    {
        if (!preg_match('/^(?<date>\d{8})--/', $eventId, $matches)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Ymd', $matches['date']);
        if (!$date instanceof \DateTimeImmutable) {
            return null;
        }

        $competitionLabel = $this->cupCatalog->labelForCompetition($competitionCode);
        foreach ($this->listUefaMatchesForCompetition($competitionCode, $competitionLabel, $date) as $item) {
            if (($item['event_id'] ?? null) === $eventId) {
                return $item;
            }
        }

        return null;
    }

    private function mapUefaMatch(
        string $competitionCode,
        string $competitionLabel,
        \DateTimeImmutable $date,
        array $parsed,
        ?string $stage,
        ?string $path,
        string $articleUrl,
    ): array {
        $local = $this->mapUefaClub($parsed['local_name'], $parsed['local_score']);
        $away = $this->mapUefaClub($parsed['away_name'], $parsed['away_score']);
        $strength = $this->strengthCatalog->compare(
            $local['domestic']['country'] ?? null,
            $away['domestic']['country'] ?? null,
        );

        $name = sprintf('%s vs %s', $local['name'], $away['name']);

        return [
            'event_id' => sprintf('%s--%s', $date->format('Ymd'), $this->slugify($name)),
            'competition' => [
                'code' => $competitionCode,
                'label' => $competitionLabel,
            ],
            'name' => $name,
            'short_name' => $name,
            'date' => $this->buildEventDate($date, $parsed['time']),
            'status' => $parsed['status'],
            'venue' => [
                'name' => null,
                'city' => null,
                'country' => null,
            ],
            'local' => $local,
            'visitante' => $away,
            'strength' => $strength,
            'meta' => [
                'stage' => $stage,
                'path' => $path,
                'source_url' => $articleUrl,
            ],
        ];
    }

    private function mapUefaClub(string $name, ?int $score): array
    {
        $domestic = $this->clubCountryCatalog->resolve($name);
        $strength = $this->strengthCatalog->forCountry($domestic['country'] ?? null);

        return [
            'team_id' => null,
            'name' => $name,
            'short_name' => $name,
            'abbreviation' => '',
            'logo' => null,
            'form' => null,
            'home_away' => null,
            'winner' => null,
            'score' => $score,
            'shootout_score' => null,
            'domestic' => [
                'country' => $domestic['country'] ?? null,
                'league_code' => null,
                'league_label' => $domestic['league_label'] ?? 'Liga doméstica',
                'strength_rank' => $strength['rank'],
                'strength_score' => $strength['score'],
                'strength_label' => $strength['label'],
            ],
        ];
    }

    private function parseUefaMatchLine(string $line): ?array
    {
        if (preg_match('/^(?<local>.+?) vs (?<away>.+?) \((?<time>\d{1,2}:\d{2})\)$/u', $line, $matches) === 1) {
            return [
                'local_name' => trim($matches['local']),
                'away_name' => trim($matches['away']),
                'local_score' => null,
                'away_score' => null,
                'time' => $matches['time'],
                'status' => [
                    'state' => 'pre',
                    'completed' => false,
                    'description' => 'Pendiente',
                    'detail' => sprintf('%s CET', $matches['time']),
                    'short_detail' => $matches['time'],
                ],
            ];
        }

        if (preg_match('/^(?<local>.+?) (?<ls>\d+)-(?<as>\d+)(?:aet)? (?<away>.+?)(?: \((?<extra>.+)\))?$/u', $line, $matches) === 1) {
            return [
                'local_name' => trim($matches['local']),
                'away_name' => trim($matches['away']),
                'local_score' => (int) $matches['ls'],
                'away_score' => (int) $matches['as'],
                'time' => null,
                'status' => [
                    'state' => 'post',
                    'completed' => true,
                    'description' => 'Finalizado',
                    'detail' => $matches['extra'] ?? 'Final',
                    'short_detail' => 'Final',
                ],
            ];
        }

        if (preg_match('/^(?<local>.+?) vs (?<away>.+)$/u', $line, $matches) === 1) {
            return [
                'local_name' => trim($matches['local']),
                'away_name' => trim($matches['away']),
                'local_score' => null,
                'away_score' => null,
                'time' => null,
                'status' => [
                    'state' => 'pre',
                    'completed' => false,
                    'description' => 'Pendiente',
                    'detail' => 'Horario pendiente',
                    'short_detail' => 'Pend.',
                ],
            ];
        }

        return null;
    }

    private function extractUefaLines(string $html): array
    {
        $withoutScripts = preg_replace('/<(script|style)[^>]*>.*?<\/>/is', ' ', $html) ?? $html;
        $text = strip_tags($withoutScripts);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["Â ", "ï»¿"], ' ', $text);

        $lines = preg_split('/\R/u', $text) ?: [];
        $normalized = [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line) ?? $line);
            if ($line === '') {
                continue;
            }

            $normalized[] = $line;
        }

        return $normalized;
    }

    private function isUefaStageHeading(string $line): bool
    {
        return preg_match('/^(First|Second|Third) qualifying round$/', $line) === 1 || $line === 'Play-off round';
    }

    private function isUefaDayHeading(string $line): bool
    {
        return preg_match('/^(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday) \d{1,2} [A-Z][a-z]+$/', $line) === 1;
    }

    private function shouldSkipUefaLine(string $line): bool
    {
        if ($line === 'All kick-off times CET' || $line === 'Kick-off times in CET') {
            return true;
        }

        return in_array($line, ['First legs', 'Second legs', 'How does it work?', 'How did it work?'], true);
    }

    private function buildEventDate(\DateTimeImmutable $date, ?string $time): string
    {
        $timezone = new \DateTimeZone('Europe/Madrid');
        $base = new \DateTimeImmutable($date->format('Y-m-d'), $timezone);

        if ($time === null) {
            return $base->setTime(0, 0)->format(DATE_ATOM);
        }

        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $base->setTime($hours, $minutes)->format(DATE_ATOM);
    }

    private function seasonLabelFromDate(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        try {
            $parsed = new \DateTimeImmutable($date);
        } catch (\Exception) {
            return null;
        }

        $year = (int) $parsed->format('Y');
        $month = (int) $parsed->format('n');
        if ($month >= 7) {
            return sprintf('%d/%d', $year, $year + 1);
        }

        return sprintf('%d/%d', $year - 1, $year);
    }

    private function slugify(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? $value;

        return trim($value, '-');
    }

    private function mapScoreboardEvent(string $competitionCode, string $competitionLabel, array $event): array
    {
        $competition = $event['competitions'][0] ?? [];
        $status = $competition['status']['type'] ?? $event['status']['type'] ?? [];
        $competitors = $competition['competitors'] ?? [];

        $local = $this->mapCompetitor($competitors[0] ?? []);
        $away  = $this->mapCompetitor($competitors[1] ?? []);
        $strength = $this->strengthCatalog->compare(
            $local['domestic']['country'] ?? null,
            $away['domestic']['country'] ?? null,
        );

        return [
            'event_id' => (string) ($event['id'] ?? ''),
            'competition' => [
                'code' => $competitionCode,
                'label' => $competitionLabel,
            ],
            'name' => $event['name'] ?? null,
            'short_name' => $event['shortName'] ?? null,
            'date' => $competition['date'] ?? $event['date'] ?? null,
            'status' => [
                'state' => $status['state'] ?? null,
                'completed' => (bool) ($status['completed'] ?? false),
                'description' => $status['description'] ?? null,
                'detail' => $status['detail'] ?? null,
                'short_detail' => $status['shortDetail'] ?? null,
            ],
            'venue' => [
                'name' => $competition['venue']['fullName'] ?? null,
                'city' => $competition['venue']['address']['city'] ?? null,
                'country' => $competition['venue']['address']['country'] ?? null,
            ],
            'local' => $local,
            'visitante' => $away,
            'strength' => $strength,
        ];
    }

    private function mapCompetitor(array $competitor): array
    {
        $team = $competitor['team'] ?? [];
        $teamId = (string) ($team['id'] ?? '');
        $teamMeta = $teamId !== '' ? $this->fetchTeamMeta($teamId) : [];

        return [
            'team_id' => $teamId,
            'name' => $team['displayName'] ?? '',
            'short_name' => $team['shortDisplayName'] ?? ($team['displayName'] ?? ''),
            'abbreviation' => $team['abbreviation'] ?? '',
            'logo' => $team['logo'] ?? ($team['logos'][0]['href'] ?? null),
            'form' => $competitor['form'] ?? null,
            'home_away' => $competitor['homeAway'] ?? null,
            'winner' => $competitor['winner'] ?? null,
            'score' => isset($competitor['score']) ? (int) $competitor['score'] : null,
            'shootout_score' => isset($competitor['shootoutScore']) ? (int) $competitor['shootoutScore'] : null,
            'domestic' => $teamMeta,
        ];
    }

    private function fetchTeamMeta(string $teamId): array
    {
        if (isset($this->teamCache[$teamId])) {
            return $this->teamCache[$teamId];
        }

        $data = $this->requestJson(sprintf(self::TEAM_URL, $teamId));
        $team = $data['team'] ?? [];
        $country = $team['venue']['address']['country'] ?? null;
        $leagueCode = $this->extractLeagueCode($team['links'] ?? []);
        $strength = $this->strengthCatalog->forCountry($country);

        return $this->teamCache[$teamId] = [
            'country' => $country,
            'league_code' => $leagueCode,
            'league_label' => $this->cupCatalog->labelForDomesticLeague($leagueCode, $country),
            'strength_rank' => $strength['rank'],
            'strength_score' => $strength['score'],
            'strength_label' => $strength['label'],
        ];
    }

    private function extractLeagueCode(array $links): ?string
    {
        foreach ($links as $link) {
            $rel = $link['rel'] ?? [];
            if (in_array('standings', $rel, true) && isset($link['href'])) {
                if (preg_match('/\/league\/([^\/?#]+)/', $link['href'], $matches) === 1) {
                    return $matches[1];
                }
            }
        }

        return null;
    }

    private function requestJson(string $url, array $query = []): array
    {
        $response = $this->httpClient->request('GET', $url, [
            'query' => $query,
            'headers' => ['User-Agent' => 'curl/8.5.0'],
            'timeout' => 10,
            'http_version' => '1.1',
        ]);

        return $response->toArray(false);
    }

    private function requestText(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => implode("\r\n", [
                    'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.9',
                    'Connection: close',
                ]),
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            throw new \RuntimeException(sprintf('No se pudo descargar %s', $url));
        }

        return $content;
    }
}
