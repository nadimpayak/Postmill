<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170508085242 extends AbstractMigration {
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE submissions ADD sticky BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE submissions ALTER sticky DROP DEFAULT');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE submissions DROP sticky');
    }
}
