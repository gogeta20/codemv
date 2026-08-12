<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add computed fair value columns to acciones_analisis';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acciones_analisis ADD fair_value NUMERIC(15, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE acciones_analisis ADD fair_value_method VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE acciones_analisis ADD fair_value_detail JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acciones_analisis DROP fair_value');
        $this->addSql('ALTER TABLE acciones_analisis DROP fair_value_method');
        $this->addSql('ALTER TABLE acciones_analisis DROP fair_value_detail');
    }
}
