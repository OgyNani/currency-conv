<?php

namespace App\Tests\Unit\Service;

use App\Entity\CurrencyData;
use App\Repository\CurrencyDataRepository;
use App\Service\Command\ListCurrenciesService;
use PHPUnit\Framework\TestCase;

class ListCurrenciesServiceTest extends TestCase
{
    private $currencyDataRepository;
    private $service;

    protected function setUp(): void
    {
        $this->currencyDataRepository = $this->createMock(CurrencyDataRepository::class);
        $this->service = new ListCurrenciesService($this->currencyDataRepository);
    }

    public function testExecuteWithoutFilter(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencies = [$usd, $eur];
        
        $this->currencyDataRepository->expects($this->once())
            ->method('findCurrencies')
            ->with(null)
            ->willReturn($currencies);
        
        $result = $this->service->execute();
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result['currencies']);
        $this->assertEquals('All currencies', $result['title']);
        $this->assertEquals(null, $result['filterCode']);
        $this->assertEquals(2, $result['count']);
        
        $displayData = $this->service->prepareDisplayData($result);
        
        $this->assertEquals('All currencies', $displayData['title']);
        $this->assertFalse($displayData['isEmpty']);
        $this->assertStringContainsString('Found 2', $displayData['summary']);
        $this->assertCount(2, $displayData['rows']);
    }

    public function testExecuteWithFilter(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $currencies = [$usd];
        
        $this->currencyDataRepository->expects($this->once())
            ->method('findCurrencies')
            ->with('USD')
            ->willReturn($currencies);
        
        $result = $this->service->execute('USD');
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result['currencies']);
        $this->assertEquals('Currencies matching code: USD', $result['title']);
        $this->assertEquals('USD', $result['filterCode']);
        $this->assertEquals(1, $result['count']);
        
        $displayData = $this->service->prepareDisplayData($result);
        
        $this->assertEquals('Currencies matching code: USD', $displayData['title']);
        $this->assertFalse($displayData['isEmpty']);
        $this->assertStringContainsString('Found 1', $displayData['summary']);
        $this->assertCount(1, $displayData['rows']);
        $this->assertEquals('USD', $displayData['rows'][0][0]);
    }

    public function testExecuteWithNoResults(): void
    {
        $this->currencyDataRepository->expects($this->once())
            ->method('findCurrencies')
            ->with('XYZ')
            ->willReturn([]);
        
        $result = $this->service->execute('XYZ');
        
        $this->assertIsArray($result);
        $this->assertCount(0, $result['currencies']);
        $this->assertEquals('Currencies matching code: XYZ', $result['title']);
        $this->assertEquals('XYZ', $result['filterCode']);
        $this->assertEquals(0, $result['count']);
        
        $displayData = $this->service->prepareDisplayData($result);
        
        $this->assertEquals('Currencies matching code: XYZ', $displayData['title']);
        $this->assertTrue($displayData['isEmpty']);
        $this->assertStringContainsString('No currencies found', $displayData['summary']);
        $this->assertCount(0, $displayData['rows']);
    }
}
