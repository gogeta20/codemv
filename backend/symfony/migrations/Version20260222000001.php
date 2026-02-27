<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema: categories, tags, studies, study_tags';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE categories (
            id SERIAL PRIMARY KEY,
            slug VARCHAR(100) NOT NULL UNIQUE,
            name VARCHAR(150) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW()
        )');

        $this->addSql('CREATE TABLE tags (
            id SERIAL PRIMARY KEY,
            slug VARCHAR(100) NOT NULL UNIQUE,
            name VARCHAR(150) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW()
        )');

        $this->addSql('CREATE TABLE studies (
            id SERIAL PRIMARY KEY,
            uuid VARCHAR(36) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            summary TEXT DEFAULT NULL,
            category_id INTEGER NOT NULL REFERENCES categories(id),
            is_favorite BOOLEAN NOT NULL DEFAULT FALSE,
            status VARCHAR(20) NOT NULL DEFAULT \'draft\',
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW()
        )');

        $this->addSql('CREATE TABLE study_tags (
            study_id INTEGER NOT NULL REFERENCES studies(id) ON DELETE CASCADE,
            tag_id INTEGER NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
            PRIMARY KEY (study_id, tag_id)
        )');

        // Indexes
        $this->addSql('CREATE INDEX idx_studies_category ON studies(category_id)');
        $this->addSql('CREATE INDEX idx_studies_favorite ON studies(is_favorite)');
        $this->addSql('CREATE INDEX idx_studies_status ON studies(status)');
        $this->addSql('CREATE INDEX idx_study_tags_tag ON study_tags(tag_id)');

        // Full-text search index
        $this->addSql("CREATE INDEX idx_studies_fts ON studies USING gin(to_tsvector('spanish', title || ' ' || content))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS study_tags');
        $this->addSql('DROP TABLE IF EXISTS studies');
        $this->addSql('DROP TABLE IF EXISTS tags');
        $this->addSql('DROP TABLE IF EXISTS categories');
    }
}
