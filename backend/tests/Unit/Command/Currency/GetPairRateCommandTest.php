<?php

namespace App\Tests\Unit\Command\Currency;

use App\Command\Currency\GetPairRateCommand;
use App\Entity\CurrencyData;
use App\Entity\CurrencyPair;
use App\Entity\CurrencyExchangeRate;
use App\Repository\CurrencyPairRepository;
use App\Service\Command\GetPairRateService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class GetPairRateCommandTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private GetPairRateService $getPairRateService;
    private CommandTester $commandTester;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->getPairRateService = $this->createMock(GetPairRateService::class);
        $this->repository = $this->createMock(EntityRepository::class);
        
        $command = new GetPairRateCommand($this->entityManager, $this->getPairRateService);
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithValidPairId(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencyPair = new CurrencyPair($usd, $eur, true);
        
        $date = new \DateTime();
        $rate = 0.85;
        $exchangeRate = new CurrencyExchangeRate($currencyPair, $rate, $date);
        
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository);
            
        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($currencyPair);
        
        $this->getPairRateService->expects($this->once())
            ->method('execute')
            ->with($currencyPair, null, null)
            ->willReturn([
                'rates' => [$exchangeRate],
                'pair' => $currencyPair
            ]);
        
        $this->getPairRateService->expects($this->once())
            ->method('prepareDisplayData')
            ->willReturn([
                'title' => 'Exchange Rates for USD/EUR',
                'summary' => 'Found 1 exchange rate(s)',
                'count' => 1,
                'rows' => [
                    [1, $date->format('Y-m-d H:i:s'), '0.85', 'USD/EUR']
                ]
            ]);
        
        $this->commandTester->execute(['id' => '1']);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Exchange Rates for USD/EUR', $output);
        $this->assertStringContainsString('Found 1 exchange rate(s)', $output);
        $this->assertStringContainsString('0.85', $output);
        $this->assertStringContainsString('USD/EUR', $output);
    }

    public function testExecuteWithDateFilter(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencyPair = new CurrencyPair($usd, $eur, true);
        
        $date = new \DateTime('2023-01-01');
        $rate = 0.85;
        $exchangeRate = new CurrencyExchangeRate($currencyPair, $rate, $date);
        
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository);
            
        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($currencyPair);
        
        $this->getPairRateService->expects($this->once())
            ->method('execute')
            ->with($currencyPair, '2023-01-01', null)
            ->willReturn([
                'rates' => [$exchangeRate],
                'pair' => $currencyPair
            ]);
        
        $this->getPairRateService->expects($this->once())
            ->method('prepareDisplayData')
            ->willReturn([
                'title' => 'Exchange Rates for USD/EUR on 2023-01-01',
                'summary' => 'Found 1 exchange rate(s)',
                'count' => 1,
                'rows' => [
                    [1, '2023-01-01 00:00:00', '0.85', 'USD/EUR']
                ]
            ]);
        
        $this->commandTester->execute([
            'id' => '1',
            'date' => '2023-01-01'
        ]);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Exchange Rates for USD/EUR on 2023-01-01', $output);
        $this->assertStringContainsString('Found 1 exchange rate(s)', $output);
        $this->assertStringContainsString('2023-01-01', $output);
    }

    public function testExecuteWithDateRange(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencyPair = new CurrencyPair($usd, $eur, true);
        
        $date1 = new \DateTime('2023-01-01');
        $date2 = new \DateTime('2023-01-02');
        $exchangeRate1 = new CurrencyExchangeRate($currencyPair, 0.85, $date1);
        $exchangeRate2 = new CurrencyExchangeRate($currencyPair, 0.86, $date2);
        
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository);
            
        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($currencyPair);
        
        $this->getPairRateService->expects($this->once())
            ->method('execute')
            ->with($currencyPair, '2023-01-01', '2023-01-02')
            ->willReturn([
                'rates' => [$exchangeRate1, $exchangeRate2],
                'pair' => $currencyPair
            ]);
        
        $this->getPairRateService->expects($this->once())
            ->method('prepareDisplayData')
            ->willReturn([
                'title' => 'Exchange Rates for USD/EUR from 2023-01-01 to 2023-01-02',
                'summary' => 'Found 2 exchange rate(s)',
                'count' => 2,
                'rows' => [
                    [1, '2023-01-01 00:00:00', '0.85', 'USD/EUR'],
                    [2, '2023-01-02 00:00:00', '0.86', 'USD/EUR']
                ]
            ]);
        
        $this->commandTester->execute([
            'id' => '1',
            'date' => '2023-01-01',
            'to_date' => '2023-01-02'
        ]);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Exchange Rates for USD/EUR from 2023-01-01 to 2023-01-02', $output);
        $this->assertStringContainsString('Found 2 exchange rate(s)', $output);
        $this->assertStringContainsString('2023-01-01', $output);
        $this->assertStringContainsString('2023-01-02', $output);
        $this->assertStringContainsString('0.85', $output);
        $this->assertStringContainsString('0.86', $output);
    }

    public function testExecuteWithInvalidPairId(): void
    {
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository);
            
        $this->repository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);
        
        $this->getPairRateService->expects($this->never())
            ->method('execute');
        
        $this->commandTester->execute(['id' => '999']);
        
        $this->assertEquals(1, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Currency pair with ID', $output);
        $this->assertStringContainsString('999 not found', $output);
    }

    public function testExecuteWithNoRates(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencyPair = new CurrencyPair($usd, $eur, true);
        
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository);
            
        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($currencyPair);
        
        $this->getPairRateService->expects($this->once())
            ->method('execute')
            ->with($currencyPair, null, null)
            ->willReturn([
                'rates' => [],
                'pair' => $currencyPair
            ]);
        
        $this->getPairRateService->expects($this->once())
            ->method('prepareDisplayData')
            ->willReturn([
                'title' => 'Exchange Rates for USD/EUR',
                'summary' => 'No exchange rates found',
                'count' => 0,
                'rows' => []
            ]);
        
        $this->commandTester->execute(['id' => '1']);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('No exchange rates', $output);
        $this->assertStringContainsString('found', $output);
    }

    public function testExecuteWithError(): void
    {
        $usd = new CurrencyData('USD', '$', 'US Dollar', '$', 2, 0, 'US dollars');
        $eur = new CurrencyData('EUR', '€', 'Euro', '€', 2, 0, 'Euros');
        $currencyPair = new CurrencyPair($usd, $eur, true);
        
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository);
            
        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($currencyPair);
        
        $this->getPairRateService->expects($this->once())
            ->method('execute')
            ->with($currencyPair, null, null)
            ->willThrowException(new \Exception('Invalid date format'));
        
        $this->commandTester->execute(['id' => '1']);
        
        $this->assertEquals(1, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Error:', $output);
        $this->assertStringContainsString('Invalid date', $output);
    }
}
