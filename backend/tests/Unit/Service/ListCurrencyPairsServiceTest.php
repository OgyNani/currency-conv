<?php

namespace App\Tests\Unit\Service;

use App\Entity\CurrencyData;
use App\Entity\CurrencyPair;
use App\Repository\CurrencyPairRepository;
use App\Service\Command\ListCurrencyPairsService;
use PHPUnit\Framework\TestCase;

class ListCurrencyPairsServiceTest extends TestCase
{
    private CurrencyPairRepository $currencyPairRepository;
    private ListCurrencyPairsService $service;

    protected function setUp(): void
    {
        $this->currencyPairRepository = $this->createMock(CurrencyPairRepository::class);
        $this->service = new ListCurrencyPairsService($this->currencyPairRepository);
    }

    public function testExecuteWithoutFilter(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $gbp = new CurrencyData('GBP', '£', 'British Pound', '£', 2, 0, 'British pounds');
        
        $pair1 = $this->getMockBuilder(CurrencyPair::class)
            ->setConstructorArgs([$usd, $eur, true])
            ->onlyMethods(['getId'])
            ->getMock();
        $pair1->method('getId')
            ->willReturn(1);
        
        $pair2 = $this->getMockBuilder(CurrencyPair::class)
            ->setConstructorArgs([$eur, $gbp, false])
            ->onlyMethods(['getId'])
            ->getMock();
        $pair2->method('getId')
            ->willReturn(2);
        
        $pairs = [$pair1, $pair2];
        
        $this->currencyPairRepository->expects($this->once())
            ->method('findCurrencyPairs')
            ->with(null)
            ->willReturn($pairs);
        
        $result = $this->service->execute();
        
        $this->assertCount(2, $result['pairs']);
        $this->assertEquals('All currency pairs', $result['title']);
        $this->assertEquals(null, $result['filterCode']);
        $this->assertEquals(2, $result['count']);
        
        $displayData = $this->service->prepareDisplayData($result);
        
        $this->assertEquals('All currency pairs', $displayData['title']);
        $this->assertFalse($displayData['isEmpty']);
        $this->assertEquals('Found 2 currency pair(s)', $displayData['summary']);
        $this->assertCount(2, $displayData['rows']);
        
        $this->assertEquals(1, $displayData['rows'][0][0]);
        $this->assertEquals('USD', $displayData['rows'][0][1]);
        $this->assertEquals('US Dollar', $displayData['rows'][0][2]);
        $this->assertEquals('EUR', $displayData['rows'][0][3]);
        $this->assertEquals('Euro', $displayData['rows'][0][4]);
        $this->assertEquals('Yes', $displayData['rows'][0][5]);
    }

    public function testExecuteWithFilter(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        
        $pair = $this->getMockBuilder(CurrencyPair::class)
            ->setConstructorArgs([$usd, $eur, true])
            ->onlyMethods(['getId'])
            ->getMock();
        $pair->method('getId')
            ->willReturn(1);
        
        $pairs = [$pair];
        
        $this->currencyPairRepository->expects($this->once())
            ->method('findCurrencyPairs')
            ->with('USD')
            ->willReturn($pairs);
        
        $result = $this->service->execute('USD');
        
        $this->assertCount(1, $result['pairs']);
        $this->assertEquals('Currency pairs involving USD', $result['title']);
        $this->assertEquals('USD', $result['filterCode']);
        $this->assertEquals(1, $result['count']);
        
        $displayData = $this->service->prepareDisplayData($result);
        
        $this->assertEquals('Currency pairs involving USD', $displayData['title']);
        $this->assertFalse($displayData['isEmpty']);
        $this->assertEquals('Found 1 currency pair(s)', $displayData['summary']);
        $this->assertCount(1, $displayData['rows']);
        
        $this->assertEquals(1, $displayData['rows'][0][0]);
        $this->assertEquals('USD', $displayData['rows'][0][1]);
        $this->assertEquals('EUR', $displayData['rows'][0][3]);
    }

    public function testExecuteWithNoResults(): void
    {
        $this->currencyPairRepository->expects($this->once())
            ->method('findCurrencyPairs')
            ->with('XYZ')
            ->willReturn([]);
        
        $result = $this->service->execute('XYZ');
        
        $this->assertEmpty($result['pairs']);
        $this->assertEquals('Currency pairs involving XYZ', $result['title']);
        $this->assertEquals('XYZ', $result['filterCode']);
        $this->assertEquals(0, $result['count']);
        
        $displayData = $this->service->prepareDisplayData($result);
        
        $this->assertEquals('Currency pairs involving XYZ', $displayData['title']);
        $this->assertTrue($displayData['isEmpty']);
        $this->assertStringContainsString('No currency pairs found', $displayData['summary']);
        $this->assertStringContainsString('XYZ', $displayData['summary']);
        $this->assertEmpty($displayData['rows']);
    }
}
