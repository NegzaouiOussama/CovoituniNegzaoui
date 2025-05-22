<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250326224026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur ADD is_banned TINYINT(1) DEFAULT 0 NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE rating rating NUMERIC(3, 2) DEFAULT \'5\' NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3C9AA420C FOREIGN KEY (role_code) REFERENCES role (code)');
        $this->addSql('ALTER TABLE messenger_messages CHANGE id id BIGINT AUTO_INCREMENT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messenger_messages CHANGE id id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3C9AA420C');
        $this->addSql('ALTER TABLE utilisateur DROP is_banned, CHANGE id id INT NOT NULL, CHANGE rating rating NUMERIC(3, 2) DEFAULT \'5.00\' NOT NULL');
    }
}
