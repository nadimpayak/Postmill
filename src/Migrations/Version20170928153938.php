<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170928153938 extends AbstractMigration {
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE INDEX submissions_ranking_id_idx ON submissions (ranking, id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX submissions_ranking_id_idx');
    }
}
