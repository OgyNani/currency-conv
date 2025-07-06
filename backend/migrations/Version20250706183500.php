<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add type column to currency_data table
 */
final class Version20250706183500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add type column to currency_data table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE currency_data ADD type VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE currency_data DROP type');
    }
}
