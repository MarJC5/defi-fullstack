<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251119104356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE routes (id VARCHAR(36) NOT NULL, from_station_id VARCHAR(10) NOT NULL, to_station_id VARCHAR(10) NOT NULL, analytic_code VARCHAR(50) NOT NULL, distance_km DOUBLE PRECISION NOT NULL, path JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_routes_analytic_code ON routes (analytic_code)');
        $this->addSql('CREATE INDEX idx_routes_created_at ON routes (created_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE routes');
    }
}
