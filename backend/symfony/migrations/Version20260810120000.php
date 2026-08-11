<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed futbol_ligas catalog with all supported ESPN leagues (inactive by default)';
    }

    /** @var array<int, array{codigo: string, nombre: string, pais: string, division: int}> */
    private const LIGAS = [
        ['codigo' => 'ger.1', 'nombre' => 'Alemania — Bundesliga',       'pais' => 'Alemania',       'division' => 1],
        ['codigo' => 'ger.2', 'nombre' => 'Alemania — 2. Bundesliga',    'pais' => 'Alemania',       'division' => 2],
        ['codigo' => 'arg.1', 'nombre' => 'Argentina — Liga Profesional','pais' => 'Argentina',      'division' => 1],
        ['codigo' => 'aus.1', 'nombre' => 'Australia — A-League',        'pais' => 'Australia',      'division' => 1],
        ['codigo' => 'bel.1', 'nombre' => 'Bélgica — First Division A',  'pais' => 'Bélgica',        'division' => 1],
        ['codigo' => 'bol.1', 'nombre' => 'Bolivia — Liga Profesional',  'pais' => 'Bolivia',        'division' => 1],
        ['codigo' => 'bra.1', 'nombre' => 'Brasil — Série A',            'pais' => 'Brasil',         'division' => 1],
        ['codigo' => 'den.1', 'nombre' => 'Dinamarca — Superliga',       'pais' => 'Dinamarca',      'division' => 1],
        ['codigo' => 'sco.1', 'nombre' => 'Escocia — Premiership',       'pais' => 'Escocia',        'division' => 1],
        ['codigo' => 'esp.1', 'nombre' => 'España — La Liga',            'pais' => 'España',         'division' => 1],
        ['codigo' => 'esp.2', 'nombre' => 'España — Segunda División',   'pais' => 'España',         'division' => 2],
        ['codigo' => 'fra.1', 'nombre' => 'Francia — Ligue 1',           'pais' => 'Francia',        'division' => 1],
        ['codigo' => 'eng.1', 'nombre' => 'Inglaterra — Premier League', 'pais' => 'Inglaterra',     'division' => 1],
        ['codigo' => 'eng.2', 'nombre' => 'Inglaterra — Championship',   'pais' => 'Inglaterra',     'division' => 2],
        ['codigo' => 'ita.1', 'nombre' => 'Italia — Serie A',            'pais' => 'Italia',         'division' => 1],
        ['codigo' => 'ita.2', 'nombre' => 'Italia — Serie B',            'pais' => 'Italia',         'division' => 2],
        ['codigo' => 'mex.1', 'nombre' => 'México — Liga MX',            'pais' => 'México',         'division' => 1],
        ['codigo' => 'usa.1', 'nombre' => 'MLS',                         'pais' => 'Estados Unidos', 'division' => 1],
        ['codigo' => 'nor.1', 'nombre' => 'Noruega — Eliteserien',       'pais' => 'Noruega',        'division' => 1],
        ['codigo' => 'ned.1', 'nombre' => 'Países Bajos — Eredivisie',   'pais' => 'Países Bajos',   'division' => 1],
        ['codigo' => 'por.1', 'nombre' => 'Portugal — Primeira Liga',    'pais' => 'Portugal',       'division' => 1],
        ['codigo' => 'swe.1', 'nombre' => 'Suecia — Allsvenskan',        'pais' => 'Suecia',         'division' => 1],
        ['codigo' => 'tur.1', 'nombre' => 'Turquía — Süper Lig',         'pais' => 'Turquía',        'division' => 1],
    ];

    public function up(Schema $schema): void
    {
        foreach (self::LIGAS as $liga) {
            $this->addSql(
                'INSERT INTO futbol_ligas (uuid, nombre, codigo_espn, pais, division, activa, created_at)
                 VALUES (gen_random_uuid()::text, :nombre, :codigo, :pais, :division, false, NOW())
                 ON CONFLICT (codigo_espn) DO NOTHING',
                [
                    'nombre'   => $liga['nombre'],
                    'codigo'   => $liga['codigo'],
                    'pais'     => $liga['pais'],
                    'division' => $liga['division'],
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $codigos = array_map(fn($liga) => $liga['codigo'], self::LIGAS);
        $this->addSql('DELETE FROM futbol_ligas WHERE codigo_espn IN (:codigos) AND activa = false', ['codigos' => $codigos], ['codigos' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY]);
    }
}
