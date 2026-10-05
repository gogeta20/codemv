<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create futbol_copa_partidos table for persisted UEFA cups feed';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE futbol_copa_partidos (
                id SERIAL NOT NULL,
                uuid VARCHAR(36) NOT NULL,
                competition_code VARCHAR(40) NOT NULL,
                event_id VARCHAR(160) NOT NULL,
                fecha DATE NOT NULL,
                hora_utc TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                equipo_local VARCHAR(120) NOT NULL,
                equipo_visitante VARCHAR(120) NOT NULL,
                payload JSON NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_COPA_PARTIDO_UUID ON futbol_copa_partidos (uuid)');
        $this->addSql('CREATE UNIQUE INDEX uq_copa_competition_event ON futbol_copa_partidos (competition_code, event_id)');
        $this->addSql('CREATE INDEX idx_copa_partido_fecha ON futbol_copa_partidos (fecha)');
        $this->addSql('CREATE INDEX idx_copa_partido_competition ON futbol_copa_partidos (competition_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE futbol_copa_partidos');
    }
}
