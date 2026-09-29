<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set the legacy default application test duration to 14 days.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE application SET duree_jours_defaut = 14 WHERE duree_jours_defaut = 12');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE application SET duree_jours_defaut = 12 WHERE duree_jours_defaut = 14');
    }
}