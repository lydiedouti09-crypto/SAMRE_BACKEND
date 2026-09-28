<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260928143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store administrator-configured daily SDK screens for each application.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE application ADD daily_pages JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE application DROP daily_pages');
    }
}