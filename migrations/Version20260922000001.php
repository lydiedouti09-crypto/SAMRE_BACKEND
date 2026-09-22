<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260922000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add daily-code secret key and participation validation tracking for secure HMAC validation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE application ADD secret_key VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE application ADD sdk_token VARCHAR(120) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A45BDDC1F3F4A6A7 ON application (sdk_token)');
        $this->addSql('ALTER TABLE participation ADD jours_valides JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE participation ADD validation_history JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE application DROP secret_key');
        $this->addSql('ALTER TABLE application DROP sdk_token');
        $this->addSql('ALTER TABLE participation DROP jours_valides');
        $this->addSql('ALTER TABLE participation DROP validation_history');
    }
}
