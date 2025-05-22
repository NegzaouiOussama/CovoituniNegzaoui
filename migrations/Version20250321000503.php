<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250321000503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur DROP verificationcode, DROP is_verified, DROP roles, CHANGE rating rating NUMERIC(3, 2) DEFAULT \'5\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur ADD verificationcode VARCHAR(100) DEFAULT NULL, ADD is_verified TINYINT(1) DEFAULT 0 NOT NULL, ADD roles JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE rating rating NUMERIC(3, 2) DEFAULT \'5.00\' NOT NULL');
    }
}
