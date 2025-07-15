<?php

namespace App\Command\Currency;

use App\Service\Command\ListCurrencyPairsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:list-pairs',
    description: 'List all available currency pairs',
)]
class ListCurrencyPairsCommand extends Command
{
    public function __construct(
        private ListCurrencyPairsService $listCurrencyPairsService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('code', 'c', InputOption::VALUE_OPTIONAL, 'Filter by currency code (from or to)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filterCode = $input->getOption('code');
        $result = $this->listCurrencyPairsService->execute($filterCode);
        $displayData = $this->listCurrencyPairsService->prepareDisplayData($result);
        
        $io->title($displayData['title']);
        
        if ($displayData['isEmpty']) {
            $io->warning($displayData['summary']);
            return Command::SUCCESS;
        }
        
        $table = new Table($output);
        $table->setHeaders(['ID', 'From', 'From Name', 'To', 'To Name', 'Observe']);
        $table->setRows($displayData['rows']);
        $table->render();
        
        $io->success($displayData['summary']);
        
        return Command::SUCCESS;
    }
}
