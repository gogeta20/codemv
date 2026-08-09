<?php

namespace App\Futbol\Application\Jugador\GetAnalisis;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GetJugadorAnalisisUseCase
{
    private const ESPN_OVERVIEW_URL = 'https://www.espn.com/soccer/player/_/id/%s/jugador';
    private const ESPN_STATS_URL    = 'https://www.espn.com/soccer/player/stats/_/id/%s/jugador';

    public function __construct(private readonly HttpClientInterface $httpClient) {}

    public function execute(string $playerId): array
    {
        $playerId = trim($playerId);
        if ($playerId === '') {
            throw new \RuntimeException('playerId es obligatorio');
        }

        $overview = $this->fetchPlayerPayload(self::ESPN_OVERVIEW_URL, $playerId);
        $stats    = $this->fetchPlayerPayload(self::ESPN_STATS_URL, $playerId);

        return [
            'player'           => $this->normalizeHeader($overview['plyrHdr'] ?? []),
            'temporada'        => $this->normalizeTemporada($stats['stat']['tbl'][0] ?? []),
            'ultimos_partidos' => $this->normalizeUltimosPartidos($overview['gmlg']['stats'][0] ?? []),
        ];
    }

    private function fetchPlayerPayload(string $urlTemplate, string $playerId): array
    {
        $url = sprintf($urlTemplate, $playerId);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['User-Agent' => 'Mozilla/5.0'],
                'timeout' => 12,
            ]);

            return $this->extractPlayerPayload($response->getContent());
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf('No se pudo leer ESPN para el jugador %s: %s', $playerId, $e->getMessage()), 0, $e);
        }
    }

    private function extractPlayerPayload(string $html): array
    {
        if (!preg_match("/window\\['__espnfitt__'\\]=(\\{.*?\\});<\\/script>/s", $html, $matches)) {
            throw new \RuntimeException('ESPN cambió el formato embebido de la ficha de jugador.');
        }

        try {
            $payload = json_decode($matches[1], true, 2048, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('No se pudo decodificar la respuesta de ESPN.', 0, $e);
        }

        $player = $payload['page']['content']['player'] ?? null;
        if (!is_array($player)) {
            throw new \RuntimeException('ESPN no devolvió datos para este jugador.');
        }

        return $player;
    }

    private function normalizeHeader(array $hdr): array
    {
        $ath = $hdr['ath'] ?? [];

        return [
            'nombre'      => $ath['dspNm'] ?? null,
            'posicion'    => $ath['pos'] ?? null,
            'nacionalidad' => $ath['ctz'] ?? null,
            'fecha_nacimiento' => $ath['dobRaw'] ?? null,
            'estado'      => $ath['sts'] ?? null,
            'dorsal'      => $ath['dspNum'] ?? null,
            'equipo'      => [
                'nombre' => $ath['tm'] ?? null,
                'logo'   => $ath['logo'] ?? null,
                'href'   => $ath['tmLnk'] ?? null,
            ],
        ];
    }

    /** stat.tbl[0]: col = ['season', 'Team', {data,ttl}, ...] / row = [seasonLabel, teamCell, ...valores en el mismo orden]. */
    private function normalizeTemporada(array $tabla): array
    {
        $columnas = $tabla['col'] ?? [];
        $filas    = $tabla['row'] ?? [];

        return array_map(function (array $fila) use ($columnas): array {
            $normalizado = [
                'temporada' => $fila[0] ?? null,
                'equipo'    => is_array($fila[1] ?? null) ? ($fila[1]['name'] ?? null) : null,
            ];

            foreach ($columnas as $index => $columna) {
                if ($index < 2 || !is_array($columna)) {
                    continue;
                }
                $clave = strtolower((string) ($columna['data'] ?? ('col_' . $index)));
                $normalizado[$clave] = $fila[$index] ?? null;
            }

            return $normalizado;
        }, $filas);
    }

    /** gmlg.stats[0]: headings = [{data,ttl}, ...] / rows = [{id, dt, res, opp, stats: [...valores en orden de headings], comp}, ...]. */
    private function normalizeUltimosPartidos(array $bloque): array
    {
        $headings = $bloque['headings'] ?? [];
        $filas    = $bloque['rows'] ?? [];

        return array_map(function (array $fila) use ($headings): array {
            $valores = $fila['stats'] ?? [];
            $stats   = [];

            foreach ($headings as $index => $columna) {
                $clave = strtolower((string) ($columna['data'] ?? ('col_' . $index)));
                $stats[$clave] = $valores[$index] ?? null;
            }

            return [
                'fecha'       => $fila['dt'] ?? null,
                'competicion' => $fila['comp'] ?? null,
                'resultado'   => $fila['res']['abbr'] ?? null,
                'marcador'    => $fila['res']['score'] ?? null,
                'rival'       => $fila['opp']['name'] ?? null,
                'rival_logo'  => $fila['opp']['logo'] ?? null,
                'stats'       => $stats,
            ];
        }, $filas);
    }
}
