<?php

namespace App\Command\Currency;

use App\Entity\CurrencyPair;
use App\Service\Command\FetchExchangeRateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fetch-exchange-rate',
    description: 'Get and store the exchange rate for a currency pair',
)]
class FetchExchangeRateCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FetchExchangeRateService $fetchExchangeRateService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'ID of the currency pair')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = (int)$input->getArgument('id');

        $currencyPair = $this->entityManager->getRepository(CurrencyPair::class)->find($id);
        
        if (!$currencyPair) {
            $io->error("Currency pair with ID {$id} not found.");
            return Command::FAILURE;
        }
        
        try {
            $result = $this->fetchExchangeRateService->execute($currencyPair);
            
            $io->title($result['title']);
            $io->success($result['message']);
            $io->text($result['details']);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Error fetching exchange rate: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
