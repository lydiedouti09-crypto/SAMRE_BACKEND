<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260913033252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, note INT NOT NULL, facilite_utilisation INT NOT NULL, points_positifs LONGTEXT NOT NULL, problemes LONGTEXT NOT NULL, difficultes LONGTEXT NOT NULL, ameliorations LONGTEXT NOT NULL, commentaires LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, participation_id INT DEFAULT NULL, utilisateur_id INT DEFAULT NULL, INDEX IDX_67F068BC6ACE3B73 (participation_id), INDEX IDX_67F068BCFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE etape (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, instruction LONGTEXT NOT NULL, ordre INT NOT NULL, jour INT NOT NULL, resultat_attendu VARCHAR(255) NOT NULL, besoin_reference TINYINT NOT NULL, duree_estimee VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL, mission_id INT DEFAULT NULL, INDEX IDX_285F75DDBE6CAE90 (mission_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mission (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, objectif LONGTEXT NOT NULL, image VARCHAR(255) NOT NULL, application VARCHAR(255) NOT NULL, version_application VARCHAR(255) NOT NULL, platforme VARCHAR(255) NOT NULL, lien_application VARCHAR(255) NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, dure_estime VARCHAR(255) NOT NULL, remuneration NUMERIC(10, 2) NOT NULL, conditions_participation LONGTEXT NOT NULL, nombre_participants_souhaites INT NOT NULL, nombre_participants_actuels INT NOT NULL, statut VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL, responsable_id INT DEFAULT NULL, INDEX IDX_9067F23C53C59D72 (responsable_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mission_tag (mission_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_98EC20FABE6CAE90 (mission_id), INDEX IDX_98EC20FABAD26311 (tag_id), PRIMARY KEY (mission_id, tag_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, lu TINYINT NOT NULL, date_creation DATETIME NOT NULL, utilisateur_id INT DEFAULT NULL, INDEX IDX_BF5476CAFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, contrat_accepte TINYINT NOT NULL, date_acceptation DATETIME NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, progression INT NOT NULL, etapes_completees INT NOT NULL, etapes_total INT NOT NULL, date_creation DATETIME NOT NULL, user_id INT DEFAULT NULL, mission_id INT DEFAULT NULL, INDEX IDX_AB55E24FA76ED395 (user_id), INDEX IDX_AB55E24FBE6CAE90 (mission_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reference (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(255) NOT NULL, date_generation DATETIME NOT NULL, date_expiration DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, date_validation DATETIME NOT NULL, reference_saisie VARCHAR(255) NOT NULL, mission_id INT DEFAULT NULL, participation_id INT DEFAULT NULL, etape_id INT DEFAULT NULL, INDEX IDX_AEA34913BE6CAE90 (mission_id), INDEX IDX_AEA349136ACE3B73 (participation_id), INDEX IDX_AEA349134A8CA2AD (etape_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE resultat (id INT AUTO_INCREMENT NOT NULL, reponse LONGTEXT NOT NULL, resultat VARCHAR(255) NOT NULL, statut_validation VARCHAR(255) NOT NULL, date_soumission DATETIME NOT NULL, participation_id INT DEFAULT NULL, etape_id INT DEFAULT NULL, INDEX IDX_E7DB5DE26ACE3B73 (participation_id), INDEX IDX_E7DB5DE24A8CA2AD (etape_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_profile (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_D95AB405A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC6ACE3B73 FOREIGN KEY (participation_id) REFERENCES participation (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE etape ADD CONSTRAINT FK_285F75DDBE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id)');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23C53C59D72 FOREIGN KEY (responsable_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE mission_tag ADD CONSTRAINT FK_98EC20FABE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mission_tag ADD CONSTRAINT FK_98EC20FABAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FBE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id)');
        $this->addSql('ALTER TABLE reference ADD CONSTRAINT FK_AEA34913BE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id)');
        $this->addSql('ALTER TABLE reference ADD CONSTRAINT FK_AEA349136ACE3B73 FOREIGN KEY (participation_id) REFERENCES participation (id)');
        $this->addSql('ALTER TABLE reference ADD CONSTRAINT FK_AEA349134A8CA2AD FOREIGN KEY (etape_id) REFERENCES etape (id)');
        $this->addSql('ALTER TABLE resultat ADD CONSTRAINT FK_E7DB5DE26ACE3B73 FOREIGN KEY (participation_id) REFERENCES participation (id)');
        $this->addSql('ALTER TABLE resultat ADD CONSTRAINT FK_E7DB5DE24A8CA2AD FOREIGN KEY (etape_id) REFERENCES etape (id)');
        $this->addSql('ALTER TABLE user_profile ADD CONSTRAINT FK_D95AB405A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL ON user');
        $this->addSql('ALTER TABLE user ADD nom VARCHAR(255) NOT NULL, ADD prenom VARCHAR(255) NOT NULL, ADD telephone VARCHAR(20) NOT NULL, ADD photo VARCHAR(255) NOT NULL, ADD role VARCHAR(255) NOT NULL, ADD statut VARCHAR(255) NOT NULL, ADD date_creation DATETIME NOT NULL, ADD date_motification DATETIME NOT NULL, DROP roles, CHANGE email email VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC6ACE3B73');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCFB88E14F');
        $this->addSql('ALTER TABLE etape DROP FOREIGN KEY FK_285F75DDBE6CAE90');
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23C53C59D72');
        $this->addSql('ALTER TABLE mission_tag DROP FOREIGN KEY FK_98EC20FABE6CAE90');
        $this->addSql('ALTER TABLE mission_tag DROP FOREIGN KEY FK_98EC20FABAD26311');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAFB88E14F');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FA76ED395');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FBE6CAE90');
        $this->addSql('ALTER TABLE reference DROP FOREIGN KEY FK_AEA34913BE6CAE90');
        $this->addSql('ALTER TABLE reference DROP FOREIGN KEY FK_AEA349136ACE3B73');
        $this->addSql('ALTER TABLE reference DROP FOREIGN KEY FK_AEA349134A8CA2AD');
        $this->addSql('ALTER TABLE resultat DROP FOREIGN KEY FK_E7DB5DE26ACE3B73');
        $this->addSql('ALTER TABLE resultat DROP FOREIGN KEY FK_E7DB5DE24A8CA2AD');
        $this->addSql('ALTER TABLE user_profile DROP FOREIGN KEY FK_D95AB405A76ED395');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE etape');
        $this->addSql('DROP TABLE mission');
        $this->addSql('DROP TABLE mission_tag');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE participation');
        $this->addSql('DROP TABLE reference');
        $this->addSql('DROP TABLE resultat');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE user_profile');
        $this->addSql('ALTER TABLE user ADD roles JSON NOT NULL, DROP nom, DROP prenom, DROP telephone, DROP photo, DROP role, DROP statut, DROP date_creation, DROP date_motification, CHANGE email email VARCHAR(180) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }
}
