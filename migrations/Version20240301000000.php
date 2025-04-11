<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240301000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial migration for lead management system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE leads (
            id INT AUTO_INCREMENT NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(180) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            date_of_birth DATE NOT NULL,
            additional_data JSON NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_4C94A832E7927C74 (email),
            -- Covering index for name searches and sorting, includes created_at
            INDEX IDX_leads_search (first_name, last_name, created_at),
            -- Index for time-based queries and pagination
            INDEX IDX_leads_created (created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT=COMPRESSED');

        $this->addSql('CREATE TABLE api_logs (
            id INT AUTO_INCREMENT NOT NULL,
            method VARCHAR(7) NOT NULL COMMENT "GET,POST,PUT,etc",
            endpoint VARCHAR(255) NOT NULL,
            request_data JSON NOT NULL,
            response_data JSON NOT NULL,
            status_code INT NOT NULL,
            ip_address VARCHAR(45) DEFAULT NULL COMMENT "IPv4/IPv6",
            user_agent VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            processing_time DOUBLE PRECISION NOT NULL,
            PRIMARY KEY(id),
            -- Combined index for API monitoring and error tracking
            INDEX IDX_api_logs_monitor (status_code, created_at, method),
            -- Index for time-based queries and cleanup
            INDEX IDX_api_logs_created (created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` 
        ENGINE = InnoDB
        PARTITION BY RANGE (TO_DAYS(created_at)) (
            PARTITION p_old VALUES LESS THAN (TO_DAYS(NOW() - INTERVAL 30 DAY)),
            PARTITION p_current VALUES LESS THAN MAXVALUE
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE leads');
        $this->addSql('DROP TABLE api_logs');
    }
}
