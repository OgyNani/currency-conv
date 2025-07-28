<?php

namespace App\Tests\Unit\Service\Command;

use App\Entity\CurrencyData;
use App\Entity\CurrencyPair;
use App\Repository\CurrencyDataRepository;
use App\Repository\CurrencyPairRepository;
use App\Service\Command\CreateCurrencyPairService;
use PHPUnit\Framework\TestCase;

class CreateCurrencyPairServiceTest extends TestCase
{
    private CurrencyDataRepository $currencyDataRepository;
    private CurrencyPairRepository $currencyPairRepository;
    private CreateCurrencyPairService $service;

    protected function setUp(): void
    {
        $this->currencyDataRepository = $this->createMock(CurrencyDataRepository::class);
        $this->currencyPairRepository = $this->createMock(CurrencyPairRepository::class);
        
        $this->service = new CreateCurrencyPairService(
            $this->currencyDataRepository,
            $this->currencyPairRepository
        );
    }

    public function testExecuteWithValidCurrencies(): void
    {
        $usdCurrency = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eurCurrency = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencyPair = new CurrencyPair($usdCurrency, $eurCurrency, true);
        
        $this->currencyDataRepository->method('findByCode')
            ->willReturnMap([
                ['USD', $usdCurrency],
                ['EUR', $eurCurrency]
            ]);
        
        $this->currencyPairRepository->method('findByFromAndToCurrencies')
            ->with($usdCurrency, $eurCurrency)
            ->willReturn(null);
        
        $this->currencyPairRepository->method('createPair')
            ->with($usdCurrency, $eurCurrency, true)
            ->willReturn($currencyPair);
        
        $result = $this->service->execute('USD', 'EUR');
        
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('created successfully', $result['message']);
        $this->assertSame($currencyPair, $result['pair']);
    }

    public function testExecuteWithError(): void
    {
        $usdCurrency = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        
        $this->currencyDataRepository->method('findByCode')
            ->willReturnMap([
                ['USD', $usdCurrency],
                ['INVALID', null]
            ]);
        
        $result = $this->service->execute('USD', 'INVALID');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
        $this->assertStringContainsString('INVALID', $result['message']);
        $this->assertNull($result['pair']);
    }
    
    public function testExecuteWithExistingPair(): void
    {
        $usdCurrency = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eurCurrency = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $existingPair = new CurrencyPair($usdCurrency, $eurCurrency, true);
        
        $this->currencyDataRepository->method('findByCode')
            ->willReturnMap([
                ['USD', $usdCurrency],
                ['EUR', $eurCurrency]
            ]);
        
        $this->currencyPairRepository->method('findByFromAndToCurrencies')
            ->with($usdCurrency, $eurCurrency)
            ->willReturn($existingPair);
        
        $result = $this->service->execute('USD', 'EUR');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['message']);
        $this->assertSame($existingPair, $result['pair']);
    }
    
    public function testExecuteWithSameCurrency(): void
    {
        $result = $this->service->execute('USD', 'USD');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cannot be the same', $result['message']);
        $this->assertNull($result['pair']);
    }
}
