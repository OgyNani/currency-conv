<?php

namespace App\Tests\Unit\Service;

use App\Entity\CurrencyData;
use App\Entity\CurrencyPair;
use App\Repository\CurrencyPairRepository;
use App\Service\Command\PairObserveStatusService;
use PHPUnit\Framework\TestCase;

class PairObserveStatusServiceTest extends TestCase
{
    private CurrencyPairRepository $currencyPairRepository;
    private PairObserveStatusService $service;

    protected function setUp(): void
    {
        $this->currencyPairRepository = $this->createMock(CurrencyPairRepository::class);
        $this->service = new PairObserveStatusService($this->currencyPairRepository);
    }

    public function testParseStatusArgument(): void
    {
        $resultTrue = $this->service->parseStatusArgument('true');
        $this->assertTrue($resultTrue['success']);
        $this->assertTrue($resultTrue['status']);
        $this->assertEmpty($resultTrue['message']);
        
        $resultFalse = $this->service->parseStatusArgument('false');
        $this->assertTrue($resultFalse['success']);
        $this->assertFalse($resultFalse['status']);
        $this->assertEmpty($resultFalse['message']);
        
        $resultInvalid = $this->service->parseStatusArgument('invalid');
        $this->assertFalse($resultInvalid['success']);
        $this->assertNull($resultInvalid['status']);
        $this->assertStringContainsString('Status must be', $resultInvalid['message']);
    }
    
    public function testExecuteWithValidPair(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        
        $currencyPair = $this->getMockBuilder(CurrencyPair::class)
            ->setConstructorArgs([$usd, $eur, false])
            ->onlyMethods(['getId', 'isObserve'])
            ->getMock();
        $currencyPair->method('getId')
            ->willReturn(1);
        $currencyPair->method('isObserve')
            ->willReturn(false);
        
        $this->currencyPairRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($currencyPair);
        
        $this->currencyPairRepository->expects($this->once())
            ->method('updateObserveStatus')
            ->with($currencyPair, true);
        
        $result = $this->service->execute(1, true);
        
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('status changed from not observed to observed', $result['message']);
        $this->assertSame($currencyPair, $result['pair']);
        $this->assertFalse($result['oldStatus']);
        $this->assertTrue($result['newStatus']);
        $this->assertEquals('USD', $result['fromCode']);
        $this->assertEquals('EUR', $result['toCode']);
    }

    public function testExecuteWithNoStatusChange(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        
        $currencyPair = $this->getMockBuilder(CurrencyPair::class)
            ->setConstructorArgs([$usd, $eur, true])
            ->onlyMethods(['getId', 'isObserve'])
            ->getMock();
        $currencyPair->method('getId')
            ->willReturn(1);
        $currencyPair->method('isObserve')
            ->willReturn(true);
        
        $this->currencyPairRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($currencyPair);
        
        $this->currencyPairRepository->expects($this->never())
            ->method('updateObserveStatus');
        
        $result = $this->service->execute(1, true);
        
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('status changed from observed to observed', $result['message']);
        $this->assertSame($currencyPair, $result['pair']);
        $this->assertTrue($result['oldStatus']);
        $this->assertTrue($result['newStatus']);
    }

    public function testExecuteWithInvalidPairId(): void
    {
        $this->currencyPairRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);
        
        $result = $this->service->execute(999, false);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Currency pair with ID 999 not found', $result['message']);
        $this->assertArrayNotHasKey('pair', $result);
    }
}
