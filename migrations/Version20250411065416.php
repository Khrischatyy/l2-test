<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250411065416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add foreign key columns for Lead entity relationships';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE leads ADD created_by_request_id INT DEFAULT NULL, ADD last_modified_by_request_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE leads ADD CONSTRAINT FK_4C94A7324162D4A8 FOREIGN KEY (created_by_request_id) REFERENCES api_logs (id)');
        $this->addSql('ALTER TABLE leads ADD CONSTRAINT FK_4C94A732B6C0CF1 FOREIGN KEY (last_modified_by_request_id) REFERENCES api_logs (id)');
        $this->addSql('CREATE INDEX IDX_4C94A7324162D4A8 ON leads (created_by_request_id)');
        $this->addSql('CREATE INDEX IDX_4C94A732B6C0CF1 ON leads (last_modified_by_request_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE leads DROP FOREIGN KEY FK_4C94A7324162D4A8');
        $this->addSql('ALTER TABLE leads DROP FOREIGN KEY FK_4C94A732B6C0CF1');
        $this->addSql('DROP INDEX IDX_4C94A7324162D4A8 ON leads');
        $this->addSql('DROP INDEX IDX_4C94A732B6C0CF1 ON leads');
        $this->addSql('ALTER TABLE leads DROP created_by_request_id, DROP last_modified_by_request_id');
    }
} 