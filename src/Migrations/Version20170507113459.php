<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170507113459 extends AbstractMigration {
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE moderators ADD timestamp TIMESTAMP(0) WITH TIME ZONE DEFAULT NOW() NOT NULL');
        $this->addSql('ALTER TABLE moderators ALTER timestamp DROP DEFAULT');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE moderators DROP timestamp');
    }
}
