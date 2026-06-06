<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260425040211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE acciones ADD sector VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE acciones ADD industry VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER INDEX uniq_portafolio_acciones_uuid RENAME TO UNIQ_69E58854D17F50A6');
        $this->addSql('ALTER INDEX uniq_portafolios_uuid RENAME TO UNIQ_B6976CABD17F50A6');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE acciones DROP sector');
        $this->addSql('ALTER TABLE acciones DROP industry');
        $this->addSql('ALTER INDEX uniq_69e58854d17f50a6 RENAME TO uniq_portafolio_acciones_uuid');
        $this->addSql('ALTER INDEX uniq_b6976cabd17f50a6 RENAME TO uniq_portafolios_uuid');
    }
}
