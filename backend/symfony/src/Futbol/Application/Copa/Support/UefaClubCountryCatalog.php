<?php

namespace App\Futbol\Application\Copa\Support;

final class UefaClubCountryCatalog
{
    private const CLUBS = [
        'ajax' => ['country' => 'Netherlands', 'league_label' => 'Eredivisie'],
        'anderlecht' => ['country' => 'Belgium', 'league_label' => 'Belgian Pro League'],
        'auda' => ['country' => 'Latvia', 'league_label' => 'Virsliga'],
        'austria wien' => ['country' => 'Austria', 'league_label' => 'Austrian Bundesliga'],
        'aarhus' => ['country' => 'Denmark', 'league_label' => 'Danish Superliga'],
        'atalanta' => ['country' => 'Italy', 'league_label' => 'Serie A'],
        'brighton' => ['country' => 'England', 'league_label' => 'Premier League'],
        'benfica' => ['country' => 'Portugal', 'league_label' => 'Primeira Liga'],
        'besiktas' => ['country' => 'Turkey', 'league_label' => 'Super Lig'],
        'beitar' => ['country' => 'Israel', 'league_label' => 'Israeli Premier League'],
        'bodo glimt' => ['country' => 'Norway', 'league_label' => 'Eliteserien'],
        'bohemians' => ['country' => 'Ireland', 'league_label' => 'League of Ireland Premier Division'],
        'borac' => ['country' => 'Bosnia and Herzegovina', 'league_label' => 'Premier League of Bosnia and Herzegovina'],
        'braga' => ['country' => 'Portugal', 'league_label' => 'Primeira Liga'],
        'celtic' => ['country' => 'Scotland', 'league_label' => 'Scottish Premiership'],
        'cfr cluj' => ['country' => 'Romania', 'league_label' => 'Liga I'],
        'cska sofia' => ['country' => 'Bulgaria', 'league_label' => 'First Professional Football League'],
        'crvena zvezda' => ['country' => 'Serbia', 'league_label' => 'Serbian SuperLiga'],
        'dac 1904' => ['country' => 'Slovakia', 'league_label' => 'Slovak Super Liga'],
        'dinamo city' => ['country' => 'Albania', 'league_label' => 'Kategoria Superiore'],
        'dinamo minsk' => ['country' => 'Belarus', 'league_label' => 'Belarusian Premier League'],
        'drita' => ['country' => 'Kosovo', 'league_label' => 'Football Superleague of Kosovo'],
        'dynamo kyiv' => ['country' => 'Ukraine', 'league_label' => 'Ukrainian Premier League'],
        'egnatia' => ['country' => 'Albania', 'league_label' => 'Kategoria Superiore'],
        'ferencvaros' => ['country' => 'Hungary', 'league_label' => 'NB I'],
        'fenerbahce' => ['country' => 'Turkey', 'league_label' => 'Super Lig'],
        'flora tallinn' => ['country' => 'Estonia', 'league_label' => 'Meistriliiga'],
        'gent' => ['country' => 'Belgium', 'league_label' => 'Belgian Pro League'],
        'getafe' => ['country' => 'Spain', 'league_label' => 'La Liga'],
        'cska sofia' => ['country' => 'Bulgaria', 'league_label' => 'First Professional Football League'],
        'crvena zvezda' => ['country' => 'Serbia', 'league_label' => 'Serbian SuperLiga'],
        'gnk dinamo' => ['country' => 'Croatia', 'league_label' => 'HNL'],
        'gornik zabrze' => ['country' => 'Poland', 'league_label' => 'Ekstraklasa'],
        'gyori eto' => ['country' => 'Hungary', 'league_label' => 'NB I'],
        'hajduk split' => ['country' => 'Croatia', 'league_label' => 'HNL'],
        'hammarby' => ['country' => 'Sweden', 'league_label' => 'Allsvenskan'],
        'hearts' => ['country' => 'Scotland', 'league_label' => 'Scottish Premiership'],
        'hibernian' => ['country' => 'Scotland', 'league_label' => 'Scottish Premiership'],
        'hjk' => ['country' => 'Finland', 'league_label' => 'Veikkausliiga'],
        'hradec kralove' => ['country' => 'Czechia', 'league_label' => 'Czech First League'],
        'iberia tbilisi' => ['country' => 'Georgia', 'league_label' => 'Erovnuli Liga'],
        'ifk goteborg' => ['country' => 'Sweden', 'league_label' => 'Allsvenskan'],
        'ilves' => ['country' => 'Finland', 'league_label' => 'Veikkausliiga'],
        'inter club d escaldes' => ['country' => 'Andorra', 'league_label' => 'Primera Divisio'],
        'inter turku' => ['country' => 'Finland', 'league_label' => 'Veikkausliiga'],
        'jablonec' => ['country' => 'Czechia', 'league_label' => 'Czech First League'],
        'jagiellonia bialystok' => ['country' => 'Poland', 'league_label' => 'Ekstraklasa'],
        'kauno zalgiris' => ['country' => 'Lithuania', 'league_label' => 'A Lyga'],
        'klaksvik' => ['country' => 'Faroe Islands', 'league_label' => 'Faroe Islands Premier League'],
        'kups kuopio' => ['country' => 'Finland', 'league_label' => 'Veikkausliiga'],
        'lask' => ['country' => 'Austria', 'league_label' => 'Austrian Bundesliga'],
        'lech poznan' => ['country' => 'Poland', 'league_label' => 'Ekstraklasa'],
        'levski sofia' => ['country' => 'Bulgaria', 'league_label' => 'First Professional Football League'],
        'lincoln red imps' => ['country' => 'Gibraltar', 'league_label' => 'National League'],
        'lugano' => ['country' => 'Switzerland', 'league_label' => 'Swiss Super League'],
        'lyon' => ['country' => 'France', 'league_label' => 'Ligue 1'],
        'monaco' => ['country' => 'France', 'league_label' => 'Ligue 1'],
        'maccabi tel aviv' => ['country' => 'Israel', 'league_label' => 'Israeli Premier League'],
        'hapoel tel aviv' => ['country' => 'Israel', 'league_label' => 'Israeli Premier League'],
        'kairat almaty' => ['country' => 'Kazakhstan', 'league_label' => 'Kazakhstan Premier League'],
        'lyon' => ['country' => 'France', 'league_label' => 'Ligue 1'],
        'monaco' => ['country' => 'France', 'league_label' => 'Ligue 1'],
        'midtjylland' => ['country' => 'Denmark', 'league_label' => 'Danish Superliga'],
        'ml vitebsk' => ['country' => 'Belarus', 'league_label' => 'Belarusian Premier League'],
        'motherwell' => ['country' => 'Scotland', 'league_label' => 'Scottish Premiership'],
        'n e c' => ['country' => 'Netherlands', 'league_label' => 'Eredivisie'],
        'nordsjaelland' => ['country' => 'Denmark', 'league_label' => 'Danish Superliga'],
        'noah' => ['country' => 'Armenia', 'league_label' => 'Armenian Premier League'],
        'olympiacos' => ['country' => 'Greece', 'league_label' => 'Super League Greece'],
        'omonia' => ['country' => 'Cyprus', 'league_label' => 'Cyprus First Division'],
        'pafos' => ['country' => 'Cyprus', 'league_label' => 'Cyprus First Division'],
        'partizan' => ['country' => 'Serbia', 'league_label' => 'Serbian SuperLiga'],
        'paok' => ['country' => 'Greece', 'league_label' => 'Super League Greece'],
        'qarabag' => ['country' => 'Azerbaijan', 'league_label' => 'Azerbaijan Premier League'],
        'rakow' => ['country' => 'Poland', 'league_label' => 'Ekstraklasa'],
        'rangers' => ['country' => 'Scotland', 'league_label' => 'Scottish Premiership'],
        'rfs' => ['country' => 'Latvia', 'league_label' => 'Virsliga'],
        'riga' => ['country' => 'Latvia', 'league_label' => 'Virsliga'],
        'rijeka' => ['country' => 'Croatia', 'league_label' => 'HNL'],
        'runavik' => ['country' => 'Faroe Islands', 'league_label' => 'Faroe Islands Premier League'],
        'rapid wien' => ['country' => 'Austria', 'league_label' => 'Austrian Bundesliga'],
        'sk rapid' => ['country' => 'Austria', 'league_label' => 'Austrian Bundesliga'],
        'maccabi tel aviv' => ['country' => 'Israel', 'league_label' => 'Israeli Premier League'],
        'hapoel tel aviv' => ['country' => 'Israel', 'league_label' => 'Israeli Premier League'],
        'kairat almaty' => ['country' => 'Kazakhstan', 'league_label' => 'Kazakhstan Premier League'],
        'lyon' => ['country' => 'France', 'league_label' => 'Ligue 1'],
        'monaco' => ['country' => 'France', 'league_label' => 'Ligue 1'],
        'sabah' => ['country' => 'Azerbaijan', 'league_label' => 'Azerbaijan Premier League'],
        'salzburg' => ['country' => 'Austria', 'league_label' => 'Austrian Bundesliga'],
        'sabah baku' => ['country' => 'Azerbaijan', 'league_label' => 'Azerbaijan Premier League'],
        'shelbourne' => ['country' => 'Ireland', 'league_label' => 'League of Ireland Premier Division'],
        'shamrock rovers' => ['country' => 'Ireland', 'league_label' => 'League of Ireland Premier Division'],
        'sheriff' => ['country' => 'Moldova', 'league_label' => 'Moldovan Super Liga'],
        'shkendija' => ['country' => 'North Macedonia', 'league_label' => 'Macedonian First Football League'],
        'sion' => ['country' => 'Switzerland', 'league_label' => 'Swiss Super League'],
        'slovan bratislava' => ['country' => 'Slovakia', 'league_label' => 'Slovak Super Liga'],
        'sparta praha' => ['country' => 'Czechia', 'league_label' => 'Czech First League'],
        'st gallen' => ['country' => 'Switzerland', 'league_label' => 'Swiss Super League'],
        'sturm graz' => ['country' => 'Austria', 'league_label' => 'Austrian Bundesliga'],
        'thun' => ['country' => 'Switzerland', 'league_label' => 'Swiss Super League'],
        'tobol' => ['country' => 'Kazakhstan', 'league_label' => 'Kazakhstan Premier League'],
        'tre fiori' => ['country' => 'San Marino', 'league_label' => 'Campionato Sammarinese'],
        'tromso' => ['country' => 'Norway', 'league_label' => 'Eliteserien'],
        'twente' => ['country' => 'Netherlands', 'league_label' => 'Eredivisie'],
        'union sg' => ['country' => 'Belgium', 'league_label' => 'Belgian Pro League'],
        'universitatea craiova' => ['country' => 'Romania', 'league_label' => 'Liga I'],
        'vaduz' => ['country' => 'Liechtenstein', 'league_label' => 'Swiss Challenge League'],
        'valur' => ['country' => 'Iceland', 'league_label' => 'Urvalsdeild'],
        'viking' => ['country' => 'Norway', 'league_label' => 'Eliteserien'],
        'viktoria plzen' => ['country' => 'Czechia', 'league_label' => 'Czech First League'],
        'vikingur reykjavik' => ['country' => 'Iceland', 'league_label' => 'Urvalsdeild'],
        'zalgiris' => ['country' => 'Lithuania', 'league_label' => 'A Lyga'],
    ];

    public function resolve(?string $teamName): array
    {
        $normalized = $this->normalize($teamName);

        return self::CLUBS[$normalized] ?? [
            'country' => null,
            'league_label' => 'Liga doméstica',
        ];
    }

    private function normalize(?string $teamName): string
    {
        $value = trim((string) $teamName);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}
