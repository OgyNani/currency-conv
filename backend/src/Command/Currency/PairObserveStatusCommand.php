<?php

namespace App\Command\Currency;

use App\Service\CurrencyPairService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:pair-observe-status',
    description: 'Change the observe status of a currency pair',
)]
class PairObserveStatusCommand extends Command
{
    public function __construct(
        private CurrencyPairService $currencyPairService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'ID of the currency pair')
            ->addArgument('status', InputArgument::REQUIRED, 'New observe status (true or false)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = (int)$input->getArgument('id');
        $statusArg = $input->getArgument('status');
        $parseResult = $this->currencyPairService->parseStatusArgument($statusArg);
        
        if (!$parseResult['success']) {
            $io->error($parseResult['message']);
            return Command::FAILURE;
        }
        
        $result = $this->currencyPairService->changeObserveStatus($id, $parseResult['status']);
        
        if (!$result['success']) {
            $io->error($result['message']);
            return Command::FAILURE;
        }
        
        $io->success($result['message']);
        
        return Command::SUCCESS;
    }
}
