-- Doctrine Migration File Generated on 2025-03-20 22:46:14

-- Version DoctrineMigrations\Version20250320224551
CREATE TABLE eventparticipation (id_participation INT AUTO_INCREMENT NOT NULL, id_event INT NOT NULL, id_utilisateur INT NOT NULL, date_inscription DATETIME NOT NULL, INDEX IDX_1C1D8A15D52B4B97 (id_event), INDEX IDX_1C1D8A1550EAE44 (id_utilisateur), PRIMARY KEY(id_participation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE password_reset_tokens (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, token VARCHAR(255) NOT NULL, expiration DATETIME NOT NULL, UNIQUE INDEX UNIQ_3967A2165F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE typeevent (id_type INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_B184BFB46C6E55B5 (nom), PRIMARY KEY(id_type)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE eventparticipation ADD CONSTRAINT FK_1C1D8A15D52B4B97 FOREIGN KEY (id_event) REFERENCES event (id_event);
ALTER TABLE eventparticipation ADD CONSTRAINT FK_1C1D8A1550EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id);
DROP TABLE event_participation;
DROP TABLE password_reset_token;
DROP TABLE type_event;
ALTER TABLE annonce CHANGE date_publication date_publication DATETIME NOT NULL, CHANGE date_termination date_termination DATETIME DEFAULT NULL;
DROP INDEX IDX_3BAE0AA7C54C8C93 ON event;
ALTER TABLE event CHANGE status status VARCHAR(20) DEFAULT 'ACTIVE' NOT NULL, CHANGE type_id id_type INT NOT NULL;
ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA77FE4B2B FOREIGN KEY (id_type) REFERENCES typeevent (id_type);
CREATE INDEX IDX_3BAE0AA77FE4B2B ON event (id_type);
ALTER TABLE reclamation CHANGE state state VARCHAR(50) DEFAULT 'pending' NOT NULL;
ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id);
ALTER TABLE reservation CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id BIGINT DEFAULT NULL, CHANGE date_reservation date_reservation DATETIME NOT NULL, CHANGE status status VARCHAR(20) DEFAULT 'PENDING' NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE type type VARCHAR(10) DEFAULT 'TRAJET' NOT NULL;
ALTER TABLE reservation ADD CONSTRAINT FK_42C849558805AB2F FOREIGN KEY (annonce_id) REFERENCES annonce (id);
ALTER TABLE role MODIFY id INT NOT NULL;
DROP INDEX UNIQ_57698A6A77153098 ON role;
DROP INDEX `primary` ON role;
ALTER TABLE role DROP id;
ALTER TABLE role ADD PRIMARY KEY (code);
ALTER TABLE trajet CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL;
DROP INDEX IDX_1D1C63B3D60322AC ON utilisateur;
ALTER TABLE utilisateur ADD role_code VARCHAR(50) NOT NULL, DROP role_id, CHANGE rating rating NUMERIC(3, 2) DEFAULT '5' NOT NULL, CHANGE trips_count trips_count INT DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME NOT NULL;
ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3C9AA420C FOREIGN KEY (role_code) REFERENCES role (code);
CREATE INDEX IDX_1D1C63B3C9AA420C ON utilisateur (role_code);
-- Version DoctrineMigrations\Version20250320224551 update table metadata;
INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES ('DoctrineMigrations\\Version20250320224551', '2025-03-20 22:46:14', 0);
