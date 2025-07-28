<?php

namespace App\Tests\Unit\Service\Command;

use App\Entity\CurrencyData;
use App\Entity\CurrencyPair;
use App\Entity\CurrencyExchangeRate;
use App\Repository\CurrencyExchangeRateRepository;
use App\Service\Command\GetPairRateService;
use PHPUnit\Framework\TestCase;

class GetPairRateServiceTest extends TestCase
{
    private CurrencyExchangeRateRepository $exchangeRateRepository;
    private GetPairRateService $service;

    protected function setUp(): void
    {
        $this->exchangeRateRepository = $this->createMock(CurrencyExchangeRateRepository::class);
        $this->service = new GetPairRateService($this->exchangeRateRepository);
    }

    public function testExecuteWithValidPairId(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencyPair = new CurrencyPair($usd, $eur, true);
        
        $date = new \DateTime();
        $rate = 0.85;
        $exchangeRate = new CurrencyExchangeRate($currencyPair, $rate, $date);
        
        $this->exchangeRateRepository->expects($this->once())
            ->method('findExchangeRatesWithDateFilter')
            ->with($currencyPair, null, null, true)
            ->willReturn([[$exchangeRate], 'latest']);
        
        $result = $this->service->execute($currencyPair);
        
        $this->assertIsArray($result);
        $this->assertEquals([$exchangeRate], $result['rates']);
        $this->assertStringContainsString('Latest exchange rate for USD → EUR', $result['title']);
        $this->assertEquals('latest', $result['dateFilter']);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals('USD', $result['fromCode']);
        $this->assertEquals('EUR', $result['toCode']);
        
        $displayData = $this->service->prepareDisplayData($result);
        
        $this->assertIsArray($displayData);
        $this->assertEquals('Latest exchange rate for USD → EUR', $displayData['title']);
        $this->assertFalse($displayData['isEmpty']);
        $this->assertStringContainsString('Found 1 exchange rate', $displayData['summary']);
        $this->assertCount(1, $displayData['rows']);
        $this->assertEquals($exchangeRate->getRate(), $displayData['rows'][0][2]);
        $this->assertEquals('USD → EUR', $displayData['rows'][0][3]);
    }

    public function testExecuteWithDateFilter(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencyPair = new CurrencyPair($usd, $eur, true);
        
        $date = new \DateTime('2023-01-01');
        $rate = 0.85;
        $exchangeRate = new CurrencyExchangeRate($currencyPair, $rate, $date);
        
        $this->exchangeRateRepository->expects($this->once())
            ->method('findExchangeRatesWithDateFilter')
            ->with($currencyPair, '2023-01-01', null, false)
            ->willReturn([[$exchangeRate], 'on 2023-01-01']);
        
        $result = $this->service->execute($currencyPair, '2023-01-01');
        
        $this->assertIsArray($result);
        $this->assertEquals([$exchangeRate], $result['rates']);
        $this->assertStringContainsString('Exchange rates for USD → EUR', $result['title']);
        $this->assertStringContainsString('2023-01-01', $result['title']);
        $this->assertEquals('on 2023-01-01', $result['dateFilter']);
        $this->assertEquals(1, $result['count']);
        
        $displayData = $this->service->prepareDisplayData($result);
        
        $this->assertIsArray($displayData);
        $this->assertStringContainsString('Exchange rates for USD → EUR', $displayData['title']);
        $this->assertStringContainsString('2023-01-01', $displayData['title']);
        $this->assertFalse($displayData['isEmpty']);
        $this->assertStringContainsString('Found 1 exchange rate', $displayData['summary']);
        $this->assertCount(1, $displayData['rows']);
        $this->assertEquals($exchangeRate->getRate(), $displayData['rows'][0][2]);
        $this->assertEquals('USD → EUR', $displayData['rows'][0][3]);
    }

    public function testExecuteWithDateRange(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencyPair = new CurrencyPair($usd, $eur, true);
        
        $date1 = new \DateTime('2023-01-01');
        $rate1 = 0.85;
        $exchangeRate1 = new CurrencyExchangeRate($currencyPair, $rate1, $date1);
        
        $date2 = new \DateTime('2023-01-02');
        $rate2 = 0.86;
        $exchangeRate2 = new CurrencyExchangeRate($currencyPair, $rate2, $date2);
        
        $this->exchangeRateRepository->expects($this->once())
            ->method('findExchangeRatesWithDateFilter')
            ->with($currencyPair, '2023-01-01', '2023-01-02', false)
            ->willReturn([[$exchangeRate1, $exchangeRate2], 'from 2023-01-01 to 2023-01-02']);
        
        $result = $this->service->execute($currencyPair, '2023-01-01', '2023-01-02');
        
        $this->assertIsArray($result);
        $this->assertEquals([$exchangeRate1, $exchangeRate2], $result['rates']);
        $this->assertStringContainsString('Exchange rates for USD → EUR', $result['title']);
        $this->assertStringContainsString('2023-01-01 to 2023-01-02', $result['title']);
        $this->assertEquals('from 2023-01-01 to 2023-01-02', $result['dateFilter']);
        $this->assertEquals(2, $result['count']);
        
        $displayData = $this->service->prepareDisplayData($result);
        
        $this->assertIsArray($displayData);
        $this->assertStringContainsString('Exchange rates for USD → EUR', $displayData['title']);
        $this->assertStringContainsString('from 2023-01-01 to 2023-01-02', $displayData['title']);
        $this->assertFalse($displayData['isEmpty']);
        $this->assertStringContainsString('Found 2 exchange rate', $displayData['summary']);
        $this->assertCount(2, $displayData['rows']);
        $this->assertEquals($exchangeRate1->getRate(), $displayData['rows'][0][2]);
        $this->assertEquals($exchangeRate2->getRate(), $displayData['rows'][1][2]);
        $this->assertEquals('USD → EUR', $displayData['rows'][0][3]);
    }


    public function testExecuteWithNoRates(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencyPair = new CurrencyPair($usd, $eur, true);
        
        $this->exchangeRateRepository->expects($this->once())
            ->method('findExchangeRatesWithDateFilter')
            ->with($currencyPair, null, null, true)
            ->willReturn([[], 'latest']);
        
        $result = $this->service->execute($currencyPair);
        
        $this->assertIsArray($result);
        $this->assertEquals([], $result['rates']);
        $this->assertEquals(0, $result['count']);
        
        $displayData = $this->service->prepareDisplayData($result);
        
        $this->assertIsArray($displayData);
        $this->assertTrue($displayData['isEmpty']);
        $this->assertStringContainsString('No exchange rates found', $displayData['summary']);
        $this->assertEmpty($displayData['rows']);
    }

    public function testExecuteWithError(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencyPair = new CurrencyPair($usd, $eur, true);
        
        $this->exchangeRateRepository->expects($this->once())
            ->method('findExchangeRatesWithDateFilter')
            ->with($currencyPair, 'invalid-date', null, false)
            ->willThrowException(new \Exception('Invalid date format'));
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid date format');
        
        $this->service->execute($currencyPair, 'invalid-date');
    }
    
    public function testPrepareDisplayDataWithEmptyResult(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencyPair = new CurrencyPair($usd, $eur, true);
        
        $result = [
            'rates' => [],
            'title' => 'Exchange rates for USD → EUR',
            'dateFilter' => null,
            'count' => 0,
            'fromCode' => 'USD',
            'toCode' => 'EUR',
            'currencyPair' => $currencyPair
        ];
        
        $displayData = $this->service->prepareDisplayData($result);
        
        $this->assertIsArray($displayData);
        $this->assertEquals('Exchange rates for USD → EUR', $displayData['title']);
        $this->assertTrue($displayData['isEmpty']);
        $this->assertEquals('No exchange rates found for USD → EUR', $displayData['summary']);
    }
}
