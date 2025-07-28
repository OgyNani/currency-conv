<?php

namespace App\Tests\Unit\Service\Command;

use App\Entity\CurrencyData;
use App\Entity\CurrencyExchangeRate;
use App\Entity\CurrencyPair;
use App\Http\CurrencyApiClient;
use App\Repository\CurrencyExchangeRateRepository;
use App\Service\Command\FetchExchangeRateService;
use PHPUnit\Framework\TestCase;

class FetchExchangeRateServiceTest extends TestCase
{
    private CurrencyApiClient $apiClient;
    private CurrencyExchangeRateRepository $exchangeRateRepository;
    private FetchExchangeRateService $service;
    private string $apiKey = 'test_api_key';
    
    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(CurrencyApiClient::class);
        $this->exchangeRateRepository = $this->createMock(CurrencyExchangeRateRepository::class);
        $this->service = new FetchExchangeRateService(
            $this->apiClient,
            $this->exchangeRateRepository,
            $this->apiKey
        );
    }

    public function testExecuteWithValidCurrencyPair(): void
    {
        $fromCurrency = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $toCurrency = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencyPair = new CurrencyPair($fromCurrency, $toCurrency, true);
        
        $expectedRate = 0.85;
        $this->apiClient->expects($this->once())
            ->method('getLatestRates')
            ->with('USD', ['EUR'])
            ->willReturn(['EUR' => $expectedRate]);
        
        $timestamp = new \DateTime();
        $exchangeRate = new CurrencyExchangeRate($currencyPair, $expectedRate, $timestamp);
        
        $this->exchangeRateRepository->expects($this->once())
            ->method('createExchangeRate')
            ->willReturnCallback(function ($pair, $data) use ($exchangeRate) {
                $this->assertEquals('USD', $data['from']);
                $this->assertEquals('EUR', $data['to']);
                $this->assertEquals(0.85, $data['rate']);
                $this->assertArrayHasKey('timestamp', $data);
                $this->assertArrayHasKey('date', $data);
                return $exchangeRate;
            });
        
        $result = $this->service->execute($currencyPair);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('details', $result);
        
        $this->assertStringContainsString('USD → EUR', $result['title']);
        $this->assertStringContainsString('Successfully fetched', $result['message']);
        $this->assertIsString($result['details']);
        $this->assertStringContainsString('1 USD = 0.85 EUR', $result['details']);
        $this->assertStringContainsString('as of', $result['details']);
    }

    public function testExecuteWithMissingCurrencyRate(): void
    {
        $fromCurrency = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $toCurrency = new CurrencyData('XYZ', 'X', 'Unknown Currency', 'X', 2, 0, 'Unknown');
        $currencyPair = new CurrencyPair($fromCurrency, $toCurrency, true);
        
        $this->apiClient->expects($this->once())
            ->method('getLatestRates')
            ->with('USD', ['XYZ'])
            ->willReturn([]); 
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not fetch exchange rate for USD → XYZ');
        
        $this->service->execute($currencyPair);
    }

    public function testExecuteWithApiError(): void
    {
        $fromCurrency = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $toCurrency = new CurrencyData('JPY', '¥', 'Japanese Yen', '¥', 0, 0, 'Japanese yen');
        $currencyPair = new CurrencyPair($fromCurrency, $toCurrency, true);
        
        $this->apiClient->expects($this->once())
            ->method('getLatestRates')
            ->with('USD', ['JPY'])
            ->willThrowException(new \RuntimeException('API connection error'));
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API connection error');
        
        $this->service->execute($currencyPair);
    }
}
