<?php

namespace App\Futbol\Application\Copa\Support;

final class EuropeanCupCatalog
{
    public const COMPETITIONS = [
        'uefa.champions' => 'UEFA Champions League',
        'uefa.europa' => 'UEFA Europa League',
        'uefa.europa.conf' => 'UEFA Conference League',
    ];

    private const LEAGUE_LABELS = [
        'eng.1' => 'Premier League',
        'eng.2' => 'Championship',
        'esp.1' => 'La Liga',
        'esp.2' => 'Segunda',
        'ita.1' => 'Serie A',
        'ita.2' => 'Serie B',
        'ger.1' => 'Bundesliga',
        'ger.2' => '2. Bundesliga',
        'fra.1' => 'Ligue 1',
        'ned.1' => 'Eredivisie',
        'por.1' => 'Primeira Liga',
        'bel.1' => 'Belgian Pro League',
        'sco.1' => 'Scottish Premiership',
        'nor.1' => 'Eliteserien',
        'gre.1' => 'Super League Greece',
        'tur.1' => 'Süper Lig',
        'cyp.1' => 'Cyprus First Division',
        'aut.1' => 'Austrian Bundesliga',
        'den.1' => 'Danish Superliga',
        'sui.1' => 'Swiss Super League',
        'pol.1' => 'Ekstraklasa',
        'cze.1' => 'Czech First League',
        'srb.1' => 'Serbian SuperLiga',
        'cro.1' => 'HNL',
        'ukr.1' => 'Ukrainian Premier League',
        'rom.1' => 'Liga I',
        'hun.1' => 'NB I',
        'isl.1' => 'Úrvalsdeild',
    ];

    public function all(): array
    {
        return self::COMPETITIONS;
    }

    public function labelForCompetition(string $code): string
    {
        return self::COMPETITIONS[$code] ?? $code;
    }

    public function labelForDomesticLeague(?string $code, ?string $country = null): string
    {
        if ($code && isset(self::LEAGUE_LABELS[$code])) {
            return self::LEAGUE_LABELS[$code];
        }

        if ($country) {
            return sprintf('Liga doméstica de %s', $country);
        }

        return $code ?: 'Liga doméstica';
    }
}
