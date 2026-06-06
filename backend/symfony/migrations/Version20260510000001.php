<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510000001 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE futbol_ligas_gpm (
                id          SERIAL PRIMARY KEY,
                uuid        VARCHAR(36)    NOT NULL UNIQUE,
                codigo_espn VARCHAR(30)    NOT NULL UNIQUE,
                liga        VARCHAR(100)   NOT NULL,
                pais        VARCHAR(80)    NOT NULL,
                tier        SMALLINT       NOT NULL DEFAULT 1,
                gpm         NUMERIC(4, 2)  NOT NULL,
                partidos    INTEGER        NOT NULL,
                equipos     SMALLINT       NOT NULL,
                updated_at  TIMESTAMP      NOT NULL DEFAULT NOW()
            )
        SQL);

        $this->addSql('CREATE INDEX idx_ligas_gpm_gpm ON futbol_ligas_gpm (gpm DESC)');
        $this->addSql('CREATE INDEX idx_ligas_gpm_pais ON futbol_ligas_gpm (pais)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS futbol_ligas_gpm');
    }
}
