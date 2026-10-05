<?php

namespace App\Futbol\Application\Copa\Support;

final class UefaAssociationStrengthCatalog
{
    /**
     * Primera versión: catálogo estático inspirado en la jerarquía UEFA reciente.
     * El score es relativo y se usa solo para comparación rápida en copas.
     */
    private const COUNTRIES = [
        'England'        => ['rank' => 1,  'score' => 100, 'label' => 'élite'],
        'Italy'          => ['rank' => 2,  'score' => 96,  'label' => 'élite'],
        'Spain'          => ['rank' => 3,  'score' => 95,  'label' => 'élite'],
        'Germany'        => ['rank' => 4,  'score' => 92,  'label' => 'élite'],
        'France'         => ['rank' => 5,  'score' => 86,  'label' => 'muy alta'],
        'Netherlands'    => ['rank' => 6,  'score' => 81,  'label' => 'muy alta'],
        'Portugal'       => ['rank' => 7,  'score' => 79,  'label' => 'muy alta'],
        'Belgium'        => ['rank' => 8,  'score' => 74,  'label' => 'alta'],
        'Türkiye'        => ['rank' => 9,  'score' => 72,  'label' => 'alta'],
        'Turkey'         => ['rank' => 9,  'score' => 72,  'label' => 'alta'],
        'Czechia'        => ['rank' => 10, 'score' => 69,  'label' => 'alta'],
        'Czech Republic' => ['rank' => 10, 'score' => 69,  'label' => 'alta'],
        'Norway'         => ['rank' => 11, 'score' => 66,  'label' => 'media-alta'],
        'Greece'         => ['rank' => 12, 'score' => 64,  'label' => 'media-alta'],
        'Poland'         => ['rank' => 13, 'score' => 62,  'label' => 'media-alta'],
        'Austria'        => ['rank' => 14, 'score' => 61,  'label' => 'media'],
        'Scotland'       => ['rank' => 15, 'score' => 60,  'label' => 'media'],
        'Switzerland'    => ['rank' => 16, 'score' => 58,  'label' => 'media'],
        'Denmark'        => ['rank' => 17, 'score' => 57,  'label' => 'media'],
        'Serbia'         => ['rank' => 18, 'score' => 55,  'label' => 'media'],
        'Croatia'        => ['rank' => 19, 'score' => 54,  'label' => 'media'],
        'Ukraine'        => ['rank' => 20, 'score' => 53,  'label' => 'media'],
        'Sweden'         => ['rank' => 21, 'score' => 51,  'label' => 'media'],
        'Hungary'        => ['rank' => 22, 'score' => 49,  'label' => 'media-baja'],
        'Romania'        => ['rank' => 23, 'score' => 48,  'label' => 'media-baja'],
        'Slovakia'       => ['rank' => 24, 'score' => 46,  'label' => 'media-baja'],
        'Slovenia'       => ['rank' => 25, 'score' => 45,  'label' => 'media-baja'],
        'Cyprus'         => ['rank' => 26, 'score' => 43,  'label' => 'baja'],
        'Israel'         => ['rank' => 27, 'score' => 42,  'label' => 'baja'],
        'Bulgaria'       => ['rank' => 28, 'score' => 41,  'label' => 'baja'],
        'Azerbaijan'     => ['rank' => 29, 'score' => 39,  'label' => 'baja'],
        'Kazakhstan'     => ['rank' => 30, 'score' => 38,  'label' => 'baja'],
        'Iceland'        => ['rank' => 31, 'score' => 37,  'label' => 'baja'],
        'Finland'        => ['rank' => 32, 'score' => 36,  'label' => 'baja'],
        'Ireland'        => ['rank' => 33, 'score' => 35,  'label' => 'baja'],
        'Republic of Ireland' => ['rank' => 33, 'score' => 35, 'label' => 'baja'],
        'Wales'          => ['rank' => 34, 'score' => 33,  'label' => 'muy baja'],
        'Northern Ireland' => ['rank' => 35, 'score' => 32, 'label' => 'muy baja'],
        'Bosnia and Herzegovina' => ['rank' => 36, 'score' => 31, 'label' => 'muy baja'],
        'Moldova'        => ['rank' => 37, 'score' => 30,  'label' => 'muy baja'],
        'Armenia'        => ['rank' => 38, 'score' => 28,  'label' => 'muy baja'],
        'Latvia'         => ['rank' => 39, 'score' => 27,  'label' => 'muy baja'],
        'Lithuania'      => ['rank' => 40, 'score' => 26,  'label' => 'muy baja'],
        'Estonia'        => ['rank' => 41, 'score' => 25,  'label' => 'muy baja'],
        'Luxembourg'     => ['rank' => 42, 'score' => 24,  'label' => 'muy baja'],
        'Faroe Islands'  => ['rank' => 43, 'score' => 23,  'label' => 'muy baja'],
        'Malta'          => ['rank' => 44, 'score' => 22,  'label' => 'muy baja'],
        'Kosovo'         => ['rank' => 45, 'score' => 21,  'label' => 'muy baja'],
        'Albania'        => ['rank' => 46, 'score' => 20,  'label' => 'muy baja'],
        'Montenegro'     => ['rank' => 47, 'score' => 19,  'label' => 'muy baja'],
        'North Macedonia'=> ['rank' => 48, 'score' => 18,  'label' => 'muy baja'],
        'Georgia'        => ['rank' => 49, 'score' => 17,  'label' => 'muy baja'],
        'Belarus'        => ['rank' => 50, 'score' => 16,  'label' => 'muy baja'],
        'Andorra'        => ['rank' => 51, 'score' => 14,  'label' => 'muy baja'],
        'Gibraltar'      => ['rank' => 52, 'score' => 13,  'label' => 'muy baja'],
        'San Marino'     => ['rank' => 53, 'score' => 10,  'label' => 'muy baja'],
    ];

    public function forCountry(?string $country): array
    {
        $normalized = $this->normalizeCountry($country);
        $base = self::COUNTRIES[$normalized] ?? ['rank' => 99, 'score' => 15, 'label' => 'desconocida'];

        return [
            'country' => $normalized,
            'rank' => $base['rank'],
            'score' => $base['score'],
            'label' => $base['label'],
        ];
    }

    public function compare(?string $localCountry, ?string $awayCountry): array
    {
        $local = $this->forCountry($localCountry);
        $away  = $this->forCountry($awayCountry);
        $diff  = $local['score'] - $away['score'];

        return [
            'local' => $local,
            'visitante' => $away,
            'diff' => $diff,
            'favorite_side' => $diff === 0 ? 'even' : ($diff > 0 ? 'local' : 'visitante'),
            'confidence' => $this->confidenceLabel(abs($diff)),
            'summary' => $this->summary($local, $away, $diff),
        ];
    }

    private function normalizeCountry(?string $country): string
    {
        $value = trim((string) $country);

        return match ($value) {
            '', 'Unknown' => 'Unknown',
            'Türkiye' => 'Turkey',
            default => $value,
        };
    }

    private function confidenceLabel(int $diff): string
    {
        return match (true) {
            $diff >= 20 => 'muy alta',
            $diff >= 12 => 'alta',
            $diff >= 7 => 'media',
            $diff >= 3 => 'baja',
            default => 'muy baja',
        };
    }

    private function summary(array $local, array $away, int $diff): string
    {
        if ($diff === 0) {
            return sprintf(
                'Fuerza pareja: %s y %s están en el mismo escalón UEFA.',
                $local['country'],
                $away['country'],
            );
        }

        $strong = $diff > 0 ? $local : $away;
        $weak   = $diff > 0 ? $away : $local;

        return sprintf(
            '%s parte por encima de %s por fuerza estructural de liga.',
            $strong['country'],
            $weak['country'],
        );
    }
}
