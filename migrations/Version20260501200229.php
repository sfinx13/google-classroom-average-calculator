<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260501200229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create database';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE classroom_result (id INT AUTO_INCREMENT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, average DOUBLE PRECISION NOT NULL, result JSON DEFAULT NULL, student_id INT NOT NULL, INDEX IDX_87620508CB944F1A (student_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE student (id INT AUTO_INCREMENT NOT NULL, google_student_id VARCHAR(255) NOT NULL, google_classroom_id VARCHAR(128) NOT NULL, email VARCHAR(255) NOT NULL, fullname VARCHAR(128) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE classroom_result ADD CONSTRAINT FK_87620508CB944F1A FOREIGN KEY (student_id) REFERENCES student (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE classroom_result DROP FOREIGN KEY FK_87620508CB944F1A');
        $this->addSql('DROP TABLE classroom_result');
        $this->addSql('DROP TABLE student');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
