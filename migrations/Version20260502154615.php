<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502154615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add appreciation column to classroom result';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE classroom_result ADD appreciation LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE classroom_result DROP appreciation');
    }
}
