<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to change CurrencyExchangeRate from JSON to decimal field
 */
final class Version20250715204800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change exchange_rate from JSON to decimal field';
    }

    public function up(Schema $schema): void
    {
        // Add new rate column
        $this->addSql('ALTER TABLE currency_exchange_rate ADD rate DECIMAL(20, 10) DEFAULT NULL');
        
        // Migrate data from exchange_rate JSON to rate column
        $this->addSql('UPDATE currency_exchange_rate SET rate = CAST(exchange_rate->>\'rate\' AS DECIMAL(20, 10))');
        
        // Make rate column NOT NULL after data migration
        $this->addSql('ALTER TABLE currency_exchange_rate ALTER COLUMN rate SET NOT NULL');
        
        // Drop old exchange_rate column
        $this->addSql('ALTER TABLE currency_exchange_rate DROP exchange_rate');
    }

    public function down(Schema $schema): void
    {
        // Add back exchange_rate column
        $this->addSql('ALTER TABLE currency_exchange_rate ADD exchange_rate JSON NOT NULL');
        
        // Migrate data back to JSON format
        $this->addSql('UPDATE currency_exchange_rate SET exchange_rate = json_build_object(\'rate\', rate)');
        
        // Drop new rate column
        $this->addSql('ALTER TABLE currency_exchange_rate DROP rate');
    }
}
