<?php

namespace App\Command\Currency;

use App\Entity\CurrencyPair;
use App\Entity\CurrencyExchangeRate;
use App\Service\ExchangeRateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(
    name: 'app:get-pair-rate',
    description: 'Get exchange rates for a currency pair with optional date filtering',
)]
class GetPairRateCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExchangeRateService $exchangeRateService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'ID of the currency pair')
            ->addArgument('date', InputArgument::OPTIONAL, 'Specific date to get rate for (YYYY-MM-DD [HH:MM[:SS]])')
            ->addArgument('to_date', InputArgument::OPTIONAL, 'End date for date range (YYYY-MM-DD [HH:MM[:SS]])')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = (int)$input->getArgument('id');
        $dateStr = $input->getArgument('date');
        $toDateStr = $input->getArgument('to_date');

        $currencyPair = $this->entityManager->getRepository(CurrencyPair::class)->find($id);
        
        if (!$currencyPair) {
            $io->error("Currency pair with ID {$id} not found.");
            return Command::FAILURE;
        }

        $fromCode = $currencyPair->getCurrencyFrom()->getCode();
        $toCode = $currencyPair->getCurrencyTo()->getCode();
        
        try {
            $result = $this->exchangeRateService->getRatesWithContext($currencyPair, $dateStr, $toDateStr);
            
            $displayData = $this->exchangeRateService->prepareExchangeRateDisplayData($result);
            
            if ($displayData['count'] === 0) {
                $io->warning($displayData['summary']);
                return Command::SUCCESS;
            }
            
            $io->title($displayData['title']);
            
            $table = new Table($output);
            $table->setHeaders(['ID', 'Date', 'Rate', 'Pair']);
            $table->setRows($displayData['rows']);
            $table->render();
            
            $io->success($displayData['summary']);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    

}
