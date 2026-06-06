<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426193945 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uq_seleccion_partido');
        $this->addSql('ALTER TABLE futbol_seleccion_diaria ADD tipo VARCHAR(30) DEFAULT \'con_temporada\' NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uq_seleccion_partido ON futbol_seleccion_diaria (fecha, partido_id, tipo)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uq_seleccion_partido');
        $this->addSql('ALTER TABLE futbol_seleccion_diaria DROP tipo');
        $this->addSql('CREATE UNIQUE INDEX uq_seleccion_partido ON futbol_seleccion_diaria (fecha, partido_id)');
    }
}
