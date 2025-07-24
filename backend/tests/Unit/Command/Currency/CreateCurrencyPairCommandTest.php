<?php

namespace App\Tests\Unit\Command\Currency;

use App\Command\Currency\CreateCurrencyPairCommand;
use App\Entity\CurrencyData;
use App\Service\Command\CreateCurrencyPairService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateCurrencyPairCommandTest extends TestCase
{
    private $entityManager;
    private $createCurrencyPairService;
    private $repository;
    private $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->createCurrencyPairService = $this->createMock(CreateCurrencyPairService::class);
        $this->repository = $this->createMock(EntityRepository::class);
        
        $command = new CreateCurrencyPairCommand(
            $this->entityManager,
            $this->createCurrencyPairService
        );
        
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithValidCurrencies(): void
    {
        $this->createCurrencyPairService->expects($this->once())
            ->method('execute')
            ->with('USD', 'EUR')
            ->willReturn([
                'success' => true,
                'message' => 'Currency pair created successfully'
            ]);
        
        $this->commandTester->execute([
            'from' => 'USD',
            'to' => 'EUR'
        ]);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithError(): void
    {
        $this->createCurrencyPairService->expects($this->once())
            ->method('execute')
            ->with('USD', 'INVALID')
            ->willReturn([
                'success' => false,
                'message' => 'Currency INVALID not found'
            ]);
        
        $this->commandTester->execute([
            'from' => 'USD',
            'to' => 'INVALID'
        ]);
        
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }
}
