<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fase and grupo columns to futbol_partidos for Mundial 2026';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE futbol_partidos ADD COLUMN IF NOT EXISTS fase VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE futbol_partidos ADD COLUMN IF NOT EXISTS grupo VARCHAR(5) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE futbol_partidos DROP COLUMN IF EXISTS fase');
        $this->addSql('ALTER TABLE futbol_partidos DROP COLUMN IF EXISTS grupo');
    }
}
