<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260922140526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application ADD secret_key VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC6ACE3B73 FOREIGN KEY (participation_id) REFERENCES participation (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE etape ADD CONSTRAINT FK_285F75DDBE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id)');
        $this->addSql('ALTER TABLE mission CHANGE lien_application lien_application VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23C53C59D72 FOREIGN KEY (responsable_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23C3E030ACD FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE mission_tag ADD CONSTRAINT FK_98EC20FABE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mission_tag ADD CONSTRAINT FK_98EC20FABAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE participation ADD jours_valides JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FBE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id)');
        $this->addSql('ALTER TABLE reference ADD CONSTRAINT FK_AEA34913BE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id)');
        $this->addSql('ALTER TABLE reference ADD CONSTRAINT FK_AEA349136ACE3B73 FOREIGN KEY (participation_id) REFERENCES participation (id)');
        $this->addSql('ALTER TABLE reference ADD CONSTRAINT FK_AEA349134A8CA2AD FOREIGN KEY (etape_id) REFERENCES etape (id)');
        $this->addSql('ALTER TABLE resultat ADD CONSTRAINT FK_E7DB5DE26ACE3B73 FOREIGN KEY (participation_id) REFERENCES participation (id)');
        $this->addSql('ALTER TABLE resultat ADD CONSTRAINT FK_E7DB5DE24A8CA2AD FOREIGN KEY (etape_id) REFERENCES etape (id)');
        $this->addSql('ALTER TABLE user_profile ADD CONSTRAINT FK_D95AB405A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application DROP secret_key');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC6ACE3B73');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCFB88E14F');
        $this->addSql('ALTER TABLE etape DROP FOREIGN KEY FK_285F75DDBE6CAE90');
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23C53C59D72');
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23C3E030ACD');
        $this->addSql('ALTER TABLE mission CHANGE lien_application lien_application VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE mission_tag DROP FOREIGN KEY FK_98EC20FABE6CAE90');
        $this->addSql('ALTER TABLE mission_tag DROP FOREIGN KEY FK_98EC20FABAD26311');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAFB88E14F');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FA76ED395');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FBE6CAE90');
        $this->addSql('ALTER TABLE participation DROP jours_valides');
        $this->addSql('ALTER TABLE reference DROP FOREIGN KEY FK_AEA34913BE6CAE90');
        $this->addSql('ALTER TABLE reference DROP FOREIGN KEY FK_AEA349136ACE3B73');
        $this->addSql('ALTER TABLE reference DROP FOREIGN KEY FK_AEA349134A8CA2AD');
        $this->addSql('ALTER TABLE resultat DROP FOREIGN KEY FK_E7DB5DE26ACE3B73');
        $this->addSql('ALTER TABLE resultat DROP FOREIGN KEY FK_E7DB5DE24A8CA2AD');
        $this->addSql('ALTER TABLE user_profile DROP FOREIGN KEY FK_D95AB405A76ED395');
    }
}
