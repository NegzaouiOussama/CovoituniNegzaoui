<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250320234522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE role (code VARCHAR(50) NOT NULL, libelle VARCHAR(100) NOT NULL, PRIMARY KEY(code)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE utilisateur ADD is_verified TINYINT(1) DEFAULT 0 NOT NULL, ADD roles JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE rating rating NUMERIC(3, 2) DEFAULT \'5\' NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3C9AA420C FOREIGN KEY (role_code) REFERENCES role (code)');
        $this->addSql('CREATE INDEX IDX_1D1C63B3C9AA420C ON utilisateur (role_code)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3C9AA420C');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP INDEX IDX_1D1C63B3C9AA420C ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP is_verified, DROP roles, CHANGE rating rating NUMERIC(3, 2) DEFAULT \'5.00\' NOT NULL');
    }
}
