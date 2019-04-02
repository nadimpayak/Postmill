<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170418114308 extends AbstractMigration {
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE comments DROP body');
        $this->addSql('ALTER TABLE comments RENAME COLUMN raw_body TO body');
        $this->addSql('ALTER TABLE submissions DROP body');
        $this->addSql('ALTER TABLE submissions RENAME COLUMN raw_body TO body');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void {
        $this->throwIrreversibleMigrationException();
    }
}
