<?php

namespace App\Tests\Unit\Command\Currency;

use App\Command\Currency\ListCurrencyPairsCommand;
use App\Service\Command\ListCurrencyPairsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListCurrencyPairsCommandTest extends TestCase
{
    private $listCurrencyPairsService;
    private $commandTester;

    protected function setUp(): void
    {
        $this->listCurrencyPairsService = $this->createMock(ListCurrencyPairsService::class);
        
        $command = new ListCurrencyPairsCommand($this->listCurrencyPairsService);
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithoutFilter(): void
    {
        $this->listCurrencyPairsService->expects($this->once())
            ->method('execute')
            ->with(null)
            ->willReturn(['pairs' => [
                ['id' => 1, 'from' => 'USD', 'fromName' => 'US Dollar', 'to' => 'EUR', 'toName' => 'Euro', 'observe' => true],
                ['id' => 2, 'from' => 'EUR', 'fromName' => 'Euro', 'to' => 'GBP', 'toName' => 'British Pound', 'observe' => false]
            ]]);
        
        $this->listCurrencyPairsService->expects($this->once())
            ->method('prepareDisplayData')
            ->willReturn([
                'title' => 'All Currency Pairs',
                'isEmpty' => false,
                'summary' => 'Found 2 currency pairs',
                'rows' => [
                    [1, 'USD', 'US Dollar', 'EUR', 'Euro', 'Yes'],
                    [2, 'EUR', 'Euro', 'GBP', 'British Pound', 'No']
                ]
            ]);
        
        $this->commandTester->execute([]);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('All Currency Pairs', $output);
        $this->assertStringContainsString('USD', $output);
        $this->assertStringContainsString('EUR', $output);
        $this->assertStringContainsString('Found 2 currency pairs', $output);
    }

    public function testExecuteWithFilter(): void
    {
        $this->listCurrencyPairsService->expects($this->once())
            ->method('execute')
            ->with('USD')
            ->willReturn(['pairs' => [
                ['id' => 1, 'from' => 'USD', 'fromName' => 'US Dollar', 'to' => 'EUR', 'toName' => 'Euro', 'observe' => true]
            ]]);
        
        $this->listCurrencyPairsService->expects($this->once())
            ->method('prepareDisplayData')
            ->willReturn([
                'title' => 'Currency Pairs With USD',
                'isEmpty' => false,
                'summary' => 'Found 1 currency pair',
                'rows' => [
                    [1, 'USD', 'US Dollar', 'EUR', 'Euro', 'Yes']
                ]
            ]);
        
        $this->commandTester->execute(['--code' => 'USD']);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Currency Pairs With USD', $output);
        $this->assertStringContainsString('USD', $output);
        $this->assertStringContainsString('Found 1 currency pair', $output);
    }

    public function testExecuteWithNoResults(): void
    {
        $this->listCurrencyPairsService->expects($this->once())
            ->method('execute')
            ->with('XYZ')
            ->willReturn(['pairs' => []]);
        
        $this->listCurrencyPairsService->expects($this->once())
            ->method('prepareDisplayData')
            ->willReturn([
                'title' => 'Currency Pairs With XYZ',
                'isEmpty' => true,
                'summary' => 'No currency pairs found',
                'rows' => []
            ]);
        
        $this->commandTester->execute(['--code' => 'XYZ']);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }
}
