<?php

namespace App\Tests\Integration\Api;

use App\Http\CurrencyApiClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

class CurrencyApiClientTest extends TestCase
{
    private CurrencyApiClient $apiClient;
    private string $apiKey;

    protected function setUp(): void
    {
        if (file_exists(__DIR__ . '/../../../.env.test')) {
            $dotenv = new \Symfony\Component\Dotenv\Dotenv();
            $dotenv->load(__DIR__ . '/../../../.env.test');
        }
        
        $this->apiKey = $_ENV['CURRENCY_API_KEY'] ?? getenv('CURRENCY_API_KEY');
        
        if (empty($this->apiKey)) {
            $this->markTestSkipped('CURRENCY_API_KEY not set in environment');
        }
        
        $this->apiClient = new CurrencyApiClient($this->apiKey);
    }

    public function testGetCurrencies(): void
    {
        $currencies = $this->apiClient->getCurrencies();
        
        $this->assertNotEmpty($currencies);
        
        $this->assertArrayHasKey('USD', $currencies);
        $this->assertArrayHasKey('EUR', $currencies);
        
        $usd = $currencies['USD'];
        $this->assertArrayHasKey('symbol', $usd);
        $this->assertArrayHasKey('name', $usd);
        $this->assertArrayHasKey('code', $usd);
        $this->assertEquals('USD', $usd['code']);
    }

    public function testGetCurrenciesWithFilter(): void
    {
        $currencies = $this->apiClient->getCurrencies(['USD', 'EUR']);
        
        $this->assertCount(2, $currencies);
        $this->assertArrayHasKey('USD', $currencies);
        $this->assertArrayHasKey('EUR', $currencies);
        $this->assertArrayNotHasKey('JPY', $currencies);
    }

    public function testGetLatestRates(): void
    {
        $rates = $this->apiClient->getLatestRates('USD');
        
        $this->assertNotEmpty($rates);
        
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertArrayHasKey('GBP', $rates);
        
        $this->assertIsFloat($rates['EUR']);
        $this->assertGreaterThan(0, $rates['EUR']);
    }

    public function testGetLatestRatesWithFilter(): void
    {
        $rates = $this->apiClient->getLatestRates('USD', ['EUR', 'GBP']);
        
        $this->assertCount(2, $rates);
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertArrayHasKey('GBP', $rates);
        $this->assertArrayNotHasKey('JPY', $rates);
    }
}
