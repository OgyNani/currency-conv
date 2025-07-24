<?php

namespace App\Tests\Unit\Command\Currency;

use App\Command\Currency\FetchExchangeRateCommand;
use App\Entity\CurrencyData;
use App\Entity\CurrencyPair;
use App\Repository\CurrencyPairRepository;
use App\Service\Command\FetchExchangeRateService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class FetchExchangeRateCommandTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private FetchExchangeRateService $fetchExchangeRateService;
    private CommandTester $commandTester;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->fetchExchangeRateService = $this->createMock(FetchExchangeRateService::class);
        $this->repository = $this->createMock(EntityRepository::class);
        
        $command = new FetchExchangeRateCommand($this->entityManager, $this->fetchExchangeRateService);
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithValidPairId(): void
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
        
        $this->fetchExchangeRateService->expects($this->once())
            ->method('execute')
            ->with($currencyPair)
            ->willReturn([
                'title' => 'Exchange Rate Fetched',
                'message' => 'Successfully fetched exchange rate for USD/EUR',
                'details' => 'Rate: 0.85'
            ]);
        
        $this->commandTester->execute(['id' => '1']);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Exchange Rate Fetched', $output);
        $this->assertStringContainsString('Successfully fetched', $output);
        $this->assertStringContainsString('exchange rate for USD/EUR', $output);
        $this->assertStringContainsString('Rate: 0.85', $output);
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
        
        $this->fetchExchangeRateService->expects($this->never())
            ->method('execute');
        
        $this->commandTester->execute(['id' => '999']);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Currency pair with ID', $output);
        $this->assertStringContainsString('999 not found', $output);
    }

    public function testExecuteWithApiError(): void
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
        
        $this->fetchExchangeRateService->expects($this->once())
            ->method('execute')
            ->with($currencyPair)
            ->willThrowException(new \Exception('API Error'));
        
        $this->commandTester->execute(['id' => '1']);
        
        $this->assertEquals(1, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Error fetching exchange', $output);
        $this->assertStringContainsString('API Error', $output);
    }
}
