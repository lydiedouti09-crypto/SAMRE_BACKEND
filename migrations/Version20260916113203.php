<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260916113203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, plateforme VARCHAR(100) NOT NULL, version VARCHAR(50) NOT NULL, lien_telechargement VARCHAR(500) DEFAULT NULL, developpeur_nom VARCHAR(255) DEFAULT NULL, developpeur_email VARCHAR(255) DEFAULT NULL, api_key VARCHAR(120) NOT NULL, token_integration VARCHAR(120) NOT NULL, duree_jours_defaut INT NOT NULL, nb_max_panelistes INT NOT NULL, statut VARCHAR(50) NOT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME NOT NULL, UNIQUE INDEX UNIQ_A45BDDC1C912ED9D (api_key), UNIQUE INDEX UNIQ_A45BDDC1D1D83F01 (token_integration), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE mission ADD application_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23C3E030ACD FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9067F23C3E030ACD ON mission (application_id)');
        $this->addSql('ALTER TABLE participation ADD paneliste_uid VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23C3E030ACD');
        $this->addSql('DROP INDEX IDX_9067F23C3E030ACD ON mission');
        $this->addSql('ALTER TABLE mission DROP application_id');
        $this->addSql('ALTER TABLE participation DROP paneliste_uid');
        $this->addSql('DROP TABLE application');
    }
}
