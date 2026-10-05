<?php

namespace App\Futbol\Application\Source;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UkrainianPremierLeagueOfficialSource
{
    private const LIGA_CODE = 'ukr.1';
    private const SEASON_ID = '432';
    private const BASE_URL = 'https://upl.ua';
    private const TABLE_URL = self::BASE_URL . '/en/tournaments/championship/' . self::SEASON_ID . '/table?id=' . self::SEASON_ID . '&tab=table';
    private const FIXTURES_URL = self::BASE_URL . '/en/tournaments/championship/' . self::SEASON_ID . '/calendar?id=' . self::SEASON_ID . '&tab=calendar';

    public function __construct(private readonly HttpClientInterface $httpClient) {}

    public function supports(string $ligaCode): bool
    {
        return $ligaCode === self::LIGA_CODE;
    }

    public function fetchTeams(): array
    {
        $html = $this->fetch(self::FIXTURES_URL);
        $xpath = $this->createXPath($html);
        $nodes = $xpath->query("//li[contains(@class, 'item-team')]/a");

        $teams = [];
        foreach ($nodes as $node) {
            $href = (string) $node->getAttribute('href');
            if (!preg_match('#/clubs/view/(\d+)#', $href, $matches)) {
                continue;
            }

            $nameNode = $xpath->query(".//div[contains(@class, 'name-team')]", $node)?->item(0);
            $name = $this->cleanName($nameNode?->textContent ?? '');
            if ($name === '') {
                continue;
            }

            $teams[] = [
                'espn_team_id' => 'upl:' . $matches[1],
                'team_name' => $name,
                'abrev' => '',
            ];
        }

        usort($teams, fn(array $a, array $b) => strcmp($a['team_name'], $b['team_name']));

        return $teams;
    }

    public function fetchStandings(): array
    {
        $html = $this->fetch(self::TABLE_URL);
        $xpath = $this->createXPath($html);
        $rows = $xpath->query("//table[contains(@class, 'table-gray') and contains(@class, 'table-num')]/tbody/tr");

        $equipos = [];
        foreach ($rows as $row) {
            $cells = $xpath->query('./td', $row);
            if ($cells->length < 10) {
                continue;
            }

            $link = $xpath->query('.//a[contains(@href, "/clubs/view/")]', $cells->item(1))?->item(0);
            if (!$link) {
                continue;
            }

            $href = (string) $link->getAttribute('href');
            if (!preg_match('#/clubs/view/(\d+)#', $href, $matches)) {
                continue;
            }

            $logo = $xpath->query('.//img', $cells->item(1))?->item(0);
            $pj = (int) trim($cells->item(2)->textContent);
            if ($pj === 0) {
                continue;
            }
            $gf = (int) trim($cells->item(6)->textContent);
            $gc = (int) trim($cells->item(7)->textContent);
            $pts = (int) trim($cells->item(9)->textContent);

            $equipos[] = [
                'espn_team_id' => 'upl:' . $matches[1],
                'pos' => (int) trim($cells->item(0)->textContent),
                'equipo' => $this->cleanName($link->textContent),
                'abrev' => '',
                'logo' => $logo ? self::BASE_URL . $logo->getAttribute('src') : null,
                'pj' => $pj,
                'pts' => $pts,
                'gf' => $gf,
                'gc' => $gc,
                'gf_pj' => round($gf / $pj, 2),
                'gc_pj' => round($gc / $pj, 2),
                'total_gpm' => round(($gf + $gc) / $pj, 2),
            ];
        }

        return $equipos;
    }

    public function findNextMatch(string $teamId, \DateTimeImmutable $now): ?array
    {
        $teamsByName = $this->teamIdsByName();
        $fixtures = $this->fetchFixtures($teamsByName);
        $today = $now->setTimezone(new \DateTimeZone('Europe/Kyiv'))->format('Y-m-d');

        foreach ($fixtures as $fixture) {
            if ($fixture['fecha'] < $today) {
                continue;
            }

            if ($fixture['team_id_local'] !== $teamId && $fixture['team_id_visit'] !== $teamId) {
                continue;
            }

            return [
                'espn_event_id' => $fixture['event_id'],
                'fecha' => $fixture['fecha'],
                'hora_utc' => null,
                'equipo_local' => $fixture['equipo_local'],
                'equipo_visit' => $fixture['equipo_visit'],
                'estadio' => null,
                'es_local' => $fixture['team_id_local'] === $teamId,
            ];
        }

        return null;
    }

    private function teamIdsByName(): array
    {
        $map = [];
        foreach ($this->fetchTeams() as $team) {
            $map[$this->normalizeName($team['team_name'])] = $team['espn_team_id'];
        }

        return $map;
    }

    private function fetchFixtures(array $teamsByName): array
    {
        $html = $this->fetch(self::FIXTURES_URL);
        $xpath = $this->createXPath($html);
        $tourTables = $xpath->query("//div[contains(@class, 'table-tour')]");
        $fixtures = [];

        foreach ($tourTables as $table) {
            $currentDate = null;
            foreach ($xpath->query("./*[contains(@class, 'tour-date') or contains(@class, 'tour-match')]", $table) as $node) {
                $class = ' ' . $node->getAttribute('class') . ' ';
                if (str_contains($class, ' tour-date ')) {
                    $currentDate = $this->parseDate(trim($node->textContent));
                    continue;
                }

                if (!$currentDate) {
                    continue;
                }

                $localName = $this->cleanName($xpath->query(".//div[contains(@class, 'first-team')]", $node)?->item(0)?->textContent ?? '');
                $visitName = $this->cleanName($xpath->query(".//div[contains(@class, 'second-team')]", $node)?->item(0)?->textContent ?? '');
                if ($localName === '' || $visitName === '') {
                    continue;
                }

                $reportLink = $xpath->query(".//div[contains(@class, 'resualt')]//a", $node)?->item(0);
                $reportHref = $reportLink?->getAttribute('href') ?? '';
                $eventId = 'upl:' . $currentDate . ':' . $this->slug($localName) . ':' . $this->slug($visitName);
                if (preg_match('#/report/view/(\d+)#', $reportHref, $matches)) {
                    $eventId = 'upl:report:' . $matches[1];
                }

                $fixtures[] = [
                    'event_id' => $eventId,
                    'fecha' => $currentDate,
                    'equipo_local' => $localName,
                    'equipo_visit' => $visitName,
                    'team_id_local' => $teamsByName[$this->normalizeName($localName)] ?? null,
                    'team_id_visit' => $teamsByName[$this->normalizeName($visitName)] ?? null,
                ];
            }
        }

        usort($fixtures, fn(array $a, array $b) => strcmp($a['fecha'], $b['fecha']));

        return $fixtures;
    }

    private function fetch(string $url): string
    {
        return $this->httpClient->request('GET', $url, [
            'headers' => ['User-Agent' => 'curl/8.5.0'],
            'timeout' => 12,
        ])->getContent();
    }

    private function createXPath(string $html): \DOMXPath
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        return new \DOMXPath($dom);
    }

    private function parseDate(string $value): ?string
    {
        $dt = \DateTimeImmutable::createFromFormat('d.m.Y', $value, new \DateTimeZone('Europe/Kyiv'));
        return $dt ? $dt->format('Y-m-d') : null;
    }

    private function cleanName(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(['«', '»'], '', $value);
        $value = preg_replace('/\s+/u', ' ', trim($value));
        return $value ?? '';
    }

    private function normalizeName(string $value): string
    {
        return mb_strtolower($this->cleanName($value));
    }

    private function slug(string $value): string
    {
        $value = $this->normalizeName($value);
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value);
        return trim($value ?? '', '-');
    }
}
