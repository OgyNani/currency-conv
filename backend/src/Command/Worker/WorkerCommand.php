<?php

namespace App\Command\Worker;

use App\Service\Worker\WorkerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

#[AsCommand(
    name: 'app:worker',
    description: 'Control worker processes',
)]
class WorkerCommand extends Command
{
    /**
     * @var WorkerInterface[]
     */
    private array $workers = [];

    public function __construct(
        #[TaggedIterator('app.worker')] iterable $workers = []
    ) {
        parent::__construct();
        
        foreach ($workers as $worker) {
            if ($worker instanceof WorkerInterface) {
                $this->workers[$worker->getName()] = $worker;
            }
        }
    }

    protected function configure(): void
    {
        $this
            ->addArgument('worker', InputArgument::REQUIRED, 'Worker name to control')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform (on/off)')
            ->addOption('iterations', 'i', InputOption::VALUE_OPTIONAL, 'Number of iterations to run (for "on" action)', null)
            ->addOption('interval', null, InputOption::VALUE_OPTIONAL, 'Interval between iterations in seconds (for "on" action)', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $workerName = $input->getArgument('worker');
        $action = $input->getArgument('action');
        
        if (!isset($this->workers[$workerName])) {
            $availableWorkers = array_keys($this->workers);
            $io->error(sprintf(
                'Worker "%s" not found. Available workers: %s',
                $workerName,
                empty($availableWorkers) ? 'none' : implode(', ', $availableWorkers)
            ));
            return Command::FAILURE;
        }
        
        $worker = $this->workers[$workerName];
        
        switch ($action) {
            case 'on':
                $iterations = $input->getOption('iterations');
                if ($iterations !== null) {
                    $iterations = (int) $iterations;
                }
                
                $interval = $input->getOption('interval');
                if ($interval !== null && method_exists($worker, 'setSleepInterval')) {
                    $worker->setSleepInterval((int) $interval);
                }
                
                $worker->setOutput($output);
                $worker->start($iterations);
                return Command::SUCCESS;
                
            case 'off':
                if (!$worker->isRunning()) {
                    $io->warning(sprintf('Worker "%s" is not running', $workerName));
                    return Command::SUCCESS;
                }
                
                $worker->stop();
                $io->success(sprintf('Worker "%s" has been stopped', $workerName));
                return Command::SUCCESS;
                
            default:
                $io->error(sprintf('Invalid action "%s". Valid actions are: on, off', $action));
                return Command::FAILURE;
        }
    }
}
