<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
<<<<<<<< HEAD:migrations/Version20250325203231.php
final class Version20250325203231 extends AbstractMigration
========
final class Version20250326031350 extends AbstractMigration
>>>>>>>> origin/Gestion_Event_Hassen:migrations/Version20250326031350.php
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
<<<<<<<< HEAD:migrations/Version20250325203231.php
        $this->addSql('ALTER TABLE reclamation DROP resolved_at, CHANGE state status VARCHAR(20) DEFAULT \'pending\' NOT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE rating rating NUMERIC(3, 2) DEFAULT \'5\' NOT NULL');
========

>>>>>>>> origin/Gestion_Event_Hassen:migrations/Version20250326031350.php
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
<<<<<<<< HEAD:migrations/Version20250325203231.php
        $this->addSql('ALTER TABLE reclamation ADD resolved_at DATETIME DEFAULT NULL, CHANGE status state VARCHAR(20) DEFAULT \'pending\' NOT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE rating rating NUMERIC(3, 2) DEFAULT \'5.00\' NOT NULL');
========

>>>>>>>> origin/Gestion_Event_Hassen:migrations/Version20250326031350.php
    }
}
