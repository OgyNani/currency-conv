<?php

namespace App\Tests\Unit\Command\Currency;

use App\Command\Currency\PairObserveStatusCommand;
use App\Service\Command\PairObserveStatusService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PairObserveStatusCommandTest extends TestCase
{
    private $pairObserveStatusService;
    private $commandTester;

    protected function setUp(): void
    {
        $this->pairObserveStatusService = $this->createMock(PairObserveStatusService::class);
        
        $command = new PairObserveStatusCommand($this->pairObserveStatusService);
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithValidArguments(): void
    {
        $this->pairObserveStatusService->expects($this->once())
            ->method('parseStatusArgument')
            ->with('true')
            ->willReturn([
                'success' => true,
                'status' => true,
                'message' => 'Status parsed successfully'
            ]);
        
        $this->pairObserveStatusService->expects($this->once())
            ->method('execute')
            ->with(1, true)
            ->willReturn([
                'success' => true,
                'message' => 'Observe status changed to true for pair ID 1'
            ]);
        
        $this->commandTester->execute([
            'id' => '1',
            'status' => 'true'
        ]);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithInvalidStatus(): void
    {
        $this->pairObserveStatusService->expects($this->once())
            ->method('parseStatusArgument')
            ->with('invalid')
            ->willReturn([
                'success' => false,
                'message' => 'Invalid status value: invalid. Use true or false.'
            ]);
        
        $this->pairObserveStatusService->expects($this->never())
            ->method('execute');
        
        $this->commandTester->execute([
            'id' => '1',
            'status' => 'invalid'
        ]);
        
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithInvalidPairId(): void
    {
        $this->pairObserveStatusService->expects($this->once())
            ->method('parseStatusArgument')
            ->with('false')
            ->willReturn([
                'success' => true,
                'status' => false,
                'message' => 'Status parsed successfully'
            ]);
        
        $this->pairObserveStatusService->expects($this->once())
            ->method('execute')
            ->with(999, false)
            ->willReturn([
                'success' => false,
                'message' => 'Currency pair with ID 999 not found'
            ]);
        
        $this->commandTester->execute([
            'id' => '999',
            'status' => 'false'
        ]);
        
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }
}
