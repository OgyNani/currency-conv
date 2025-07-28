<?php

namespace App\Tests\Unit\Service;

use App\Entity\CurrencyData;
use App\Repository\CurrencyDataRepository;
use App\Http\CurrencyApiClient;
use App\Service\Command\FetchCurrenciesService;
use PHPUnit\Framework\TestCase;

class FetchCurrenciesServiceTest extends TestCase
{
    private CurrencyApiClient $apiClient;
    private CurrencyDataRepository $currencyDataRepository;
    private FetchCurrenciesService $service;
    private string $apiKey = 'test_api_key';

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(CurrencyApiClient::class);
        $this->currencyDataRepository = $this->createMock(CurrencyDataRepository::class);
        $this->service = new FetchCurrenciesService(
            $this->apiClient,
            $this->currencyDataRepository,
            $this->apiKey
        );
    }

    public function testExecuteWithoutArguments(): void
    {
        $currencyData = [
            'USD' => [
                'symbol' => '$',
                'name' => 'US Dollar',
                'symbol_native' => '$',
                'decimal_digits' => 2,
                'rounding' => 0,
                'code' => 'USD',
                'name_plural' => 'US dollars'
            ]
        ];
        
        $this->apiClient->expects($this->once())
            ->method('getCurrencies')
            ->with([])
            ->willReturn($currencyData);
        
        $this->currencyDataRepository->expects($this->once())
            ->method('findByCode')
            ->with('USD')
            ->willReturn(null);
        
        $this->currencyDataRepository->expects($this->once())
            ->method('createCurrency')
            ->with('USD', $currencyData['USD'])
            ->willReturn(new CurrencyData(
                'USD',
                '$',
                'US Dollar',
                '$',
                2,
                0,
                'US dollars'
            ));
        
        $this->currencyDataRepository->expects($this->once())
            ->method('saveChanges');
            
        $result = $this->service->execute([]);
        
        $this->assertIsArray($result);
        $this->assertEquals($currencyData, $result['currencies']);
        $this->assertEquals(1, $result['stats']['added']);
        $this->assertEquals(0, $result['stats']['updated']);
        $this->assertEquals(['USD'], $result['stats']['new_currencies']);
        $this->assertEquals([], $result['stats']['updated_currencies']);
        
        $displayData = $this->service->prepareDisplayData($result);
        
        $this->assertIsArray($displayData);
        $this->assertEquals('Currency Data', $displayData['title']);
        $this->assertStringContainsString('Successfully fetched 1 currencies: 1 new, 0 updated', $displayData['summary']);
        $this->assertCount(1, $displayData['rows']);
        $this->assertEquals('USD', $displayData['rows'][0][0]);
        $this->assertEquals('US Dollar', $displayData['rows'][0][1]);
        $this->assertEquals('$', $displayData['rows'][0][2]);
        $this->assertEquals('NEW', $displayData['rows'][0][4]);
    }

    public function testExecuteWithSpecificCurrencies(): void
    {
        $currencyData = [
            'EUR' => [
                'symbol' => '€',
                'name' => 'Euro',
                'symbol_native' => '€',
                'decimal_digits' => 2,
                'rounding' => 0,
                'code' => 'EUR',
                'name_plural' => 'Euros'
            ],
            'USD' => [
                'symbol' => '$',
                'name' => 'US Dollar',
                'symbol_native' => '$',
                'decimal_digits' => 2,
                'rounding' => 0,
                'code' => 'USD',
                'name_plural' => 'US dollars'
            ]
        ];
        
        $this->apiClient->expects($this->once())
            ->method('getCurrencies')
            ->with(['EUR', 'USD'])
            ->willReturn($currencyData);
        
        $this->currencyDataRepository->expects($this->exactly(2))
            ->method('findByCode')
            ->willReturnMap([
                ['EUR', null],
                ['USD', null]
            ]);
        
        $this->currencyDataRepository->expects($this->exactly(2))
            ->method('createCurrency')
            ->willReturnMap([
                ['EUR', $currencyData['EUR'], new CurrencyData(
                    'EUR',
                    '€',
                    'Euro',
                    '€',
                    2,
                    0,
                    'Euros'
                )],
                ['USD', $currencyData['USD'], new CurrencyData(
                    'USD',
                    '$',
                    'US Dollar',
                    '$',
                    2,
                    0,
                    'US dollars'
                )]
            ]);
        
        $this->currencyDataRepository->expects($this->once())
            ->method('saveChanges');
            
        $result = $this->service->execute(['EUR', 'USD']);
        
        $this->assertIsArray($result);
        $this->assertEquals($currencyData, $result['currencies']);
        $this->assertEquals(2, $result['stats']['added']);
        $this->assertEquals(0, $result['stats']['updated']);
        $this->assertContains('EUR', $result['stats']['new_currencies']);
        $this->assertContains('USD', $result['stats']['new_currencies']);
        
        $displayData = $this->service->prepareDisplayData($result);
        
        $this->assertIsArray($displayData);
        $this->assertEquals('Currency Data', $displayData['title']);
        $this->assertStringContainsString('Successfully fetched 2 currencies', $displayData['summary']);
        $this->assertCount(2, $displayData['rows']);
    }

    public function testExecuteWithError(): void
    {
        $this->apiClient->expects($this->once())
            ->method('getCurrencies')
            ->willThrowException(new \Exception('API Error'));
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API Error');
        
        $this->service->execute([]);
    }
    
    public function testExecuteWithUpdatedCurrency(): void
    {
        $currencyData = [
            'USD' => [
                'symbol' => '$',
                'name' => 'US Dollar (Updated)',
                'symbol_native' => '$',
                'decimal_digits' => 2,
                'rounding' => 0,
                'code' => 'USD',
                'name_plural' => 'US dollars'
            ]
        ];
        
        $existingCurrency = new CurrencyData(
            'USD',
            '$',
            'US Dollar',
            '$',
            2,
            0,
            'US dollars'
        );
        
        $this->apiClient->expects($this->once())
            ->method('getCurrencies')
            ->willReturn($currencyData);
        
        $this->currencyDataRepository->expects($this->once())
            ->method('findByCode')
            ->with('USD')
            ->willReturn($existingCurrency);
        
        $this->currencyDataRepository->expects($this->once())
            ->method('updateCurrency')
            ->with($existingCurrency, $currencyData['USD']);
        
        $this->currencyDataRepository->expects($this->once())
            ->method('saveChanges');
            
        $result = $this->service->execute([]);
        
        $this->assertIsArray($result);
        $this->assertEquals($currencyData, $result['currencies']);
        $this->assertEquals(0, $result['stats']['added']);
        $this->assertEquals(1, $result['stats']['updated']);
        $this->assertEquals([], $result['stats']['new_currencies']);
        $this->assertEquals(['USD'], $result['stats']['updated_currencies']);
    }
}
