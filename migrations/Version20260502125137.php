<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502125137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store google doc bulletin link';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE classroom_result ADD google_doc_bulletin_link VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE classroom_result DROP google_doc_bulletin_link');
    }
}
