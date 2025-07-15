<?php

namespace App\Command\Currency;

use App\Service\Command\ListCurrenciesService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:list-currencies',
    description: 'List all available currencies',
)]
class ListCurrenciesCommand extends Command
{
    public function __construct(
        private ListCurrenciesService $listCurrenciesService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('code', null, InputOption::VALUE_OPTIONAL, 'Filter by currency code (e.g. EUR, USD)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filterCode = $input->getOption('code');
        $result = $this->listCurrenciesService->execute($filterCode);
        $displayData = $this->listCurrenciesService->prepareDisplayData($result);
        
        $io->title($displayData['title']);
        
        if ($displayData['isEmpty']) {
            $io->warning($displayData['summary']);
            return Command::SUCCESS;
        }
        
        $table = new Table($output);
        $table->setHeaders(['Code', 'Name', 'Symbol', 'Native Symbol', 'Decimals']);
        $table->setRows($displayData['rows']);
        $table->render();
        
        $io->success($displayData['summary']);
        
        return Command::SUCCESS;
    }
}
