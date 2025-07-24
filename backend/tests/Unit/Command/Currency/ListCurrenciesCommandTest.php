<?php

namespace App\Tests\Unit\Command\Currency;

use App\Command\Currency\ListCurrenciesCommand;
use App\Service\Command\ListCurrenciesService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListCurrenciesCommandTest extends TestCase
{
    private $listCurrenciesService;
    private $commandTester;

    protected function setUp(): void
    {
        $this->listCurrenciesService = $this->createMock(ListCurrenciesService::class);
        
        $command = new ListCurrenciesCommand($this->listCurrenciesService);
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithoutFilter(): void
    {
        $this->listCurrenciesService->expects($this->once())
            ->method('execute')
            ->with(null)
            ->willReturn(['currencies' => [
                'USD' => ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'symbol_native' => '$', 'decimal_digits' => 2],
                'EUR' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'symbol_native' => '€', 'decimal_digits' => 2]
            ]]);
        
        $this->listCurrenciesService->expects($this->once())
            ->method('prepareDisplayData')
            ->willReturn([
                'title' => 'All Available Currencies',
                'isEmpty' => false,
                'summary' => 'Found 2 currencies',
                'rows' => [
                    ['USD', 'US Dollar', '$', '$', '2'],
                    ['EUR', 'Euro', '€', '€', '2']
                ]
            ]);
        
        $this->commandTester->execute([]);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('All Available Currencies', $output);
        $this->assertStringContainsString('USD', $output);
        $this->assertStringContainsString('EUR', $output);
        $this->assertStringContainsString('Found 2 currencies', $output);
    }

    public function testExecuteWithFilter(): void
    {
        $this->listCurrenciesService->expects($this->once())
            ->method('execute')
            ->with('USD')
            ->willReturn(['currencies' => [
                'USD' => ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'symbol_native' => '$', 'decimal_digits' => 2]
            ]]);
        
        $this->listCurrenciesService->expects($this->once())
            ->method('prepareDisplayData')
            ->willReturn([
                'title' => 'Currencies Matching: USD',
                'isEmpty' => false,
                'summary' => 'Found 1 currency',
                'rows' => [
                    ['USD', 'US Dollar', '$', '$', '2']
                ]
            ]);
        
        $this->commandTester->execute(['--code' => 'USD']);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Currencies Matching: USD', $output);
        $this->assertStringContainsString('USD', $output);
        $this->assertStringNotContainsString('EUR', $output);
        $this->assertStringContainsString('Found 1 currency', $output);
    }

    public function testExecuteWithNoResults(): void
    {
        $this->listCurrenciesService->expects($this->once())
            ->method('execute')
            ->with('XYZ')
            ->willReturn(['currencies' => []]);
        
        $this->listCurrenciesService->expects($this->once())
            ->method('prepareDisplayData')
            ->willReturn([
                'title' => 'Currencies Matching: XYZ',
                'isEmpty' => true,
                'summary' => 'No currencies found',
                'rows' => []
            ]);
        
        $this->commandTester->execute(['--code' => 'XYZ']);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Currencies Matching: XYZ', $output);
        $this->assertStringContainsString('No currencies found', $output);
    }
}
