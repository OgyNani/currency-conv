<?php

namespace App\Tests\Unit\Command\Currency;

use App\Command\Currency\FetchCurrenciesCommand;
use App\Service\Command\FetchCurrenciesService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;

class FetchCurrenciesCommandTest extends TestCase
{
    private FetchCurrenciesService $fetchCurrenciesService;
    private FetchCurrenciesCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->fetchCurrenciesService = $this->createMock(FetchCurrenciesService::class);
        $this->command = new FetchCurrenciesCommand($this->fetchCurrenciesService);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithoutArguments(): void
    {
        $this->fetchCurrenciesService->expects($this->once())
            ->method('execute')
            ->with([])
            ->willReturn([
                'currencies' => [
                    'USD' => [
                        'symbol' => '$',
                        'name' => 'US Dollar',
                        'symbol_native' => '$',
                        'decimal_digits' => 2,
                        'rounding' => 0,
                        'code' => 'USD',
                        'name_plural' => 'US dollars'
                    ]
                ],
                'stats' => [
                    'added' => 1,
                    'updated' => 0,
                    'new_currencies' => ['USD'],
                    'updated_currencies' => []
                ]
            ]);

        $this->fetchCurrenciesService->expects($this->once())
            ->method('prepareDisplayData')
            ->willReturn([
                'title' => 'Currency Data',
                'summary' => 'Successfully fetched 1 currencies: 1 new, 0 updated',
                'rows' => [
                    ['USD', 'US Dollar', '$', '', 'NEW']
                ]
            ]);

        $this->commandTester->execute([]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Fetching all available currencies', $output);
        $this->assertStringContainsString('Successfully fetched 1', $output);
        $this->assertStringContainsString('USD', $output);
        $this->assertStringContainsString('US Dollar', $output);
    }

    public function testExecuteWithSpecificCurrencies(): void
    {
        $this->fetchCurrenciesService->expects($this->once())
            ->method('execute')
            ->with(['EUR', 'USD'])
            ->willReturn([
                'currencies' => [
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
                ],
                'stats' => [
                    'added' => 2,
                    'updated' => 0,
                    'new_currencies' => ['EUR', 'USD'],
                    'updated_currencies' => []
                ]
            ]);

        $this->fetchCurrenciesService->expects($this->once())
            ->method('prepareDisplayData')
            ->willReturn([
                'title' => 'Currency Data',
                'summary' => 'Successfully fetched 2 currencies: 2 new, 0 updated',
                'rows' => [
                    ['EUR', 'Euro', '€', '', 'NEW'],
                    ['USD', 'US Dollar', '$', '', 'NEW']
                ]
            ]);

        $this->commandTester->execute([
            'currencies' => ['EUR', 'USD']
        ]);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Fetching specific currencies: EUR, USD', $output);
        $this->assertStringContainsString('Successfully fetched 2', $output);
        $this->assertStringContainsString('EUR', $output);
        $this->assertStringContainsString('Euro', $output);
        $this->assertStringContainsString('USD', $output);
        $this->assertStringContainsString('US Dollar', $output);
    }

    public function testExecuteWithError(): void
    {
        $this->fetchCurrenciesService->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('API Error'));
        $this->commandTester->execute([]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Error fetching', $output);
        $this->assertStringContainsString('API Error', $output);
    }
}
