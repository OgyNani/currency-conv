<?php

namespace App\Command\Currency;

use App\Service\CurrencyService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fetch-currencies',
    description: 'Fetch currencies from the API',
)]
class FetchCurrenciesCommand extends Command
{
    public function __construct(
        private CurrencyService $currencyService
    ) {
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this
            ->setDescription('Fetch currencies from the API')
            ->addArgument(
                'currencies',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'List of currency codes to fetch (e.g., EUR USD JPY). If empty, fetches all currencies.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $currencyCodes = $input->getArgument('currencies');
        
        if (!empty($currencyCodes)) {
            $io->title(sprintf('Fetching specific currencies: %s', implode(', ', $currencyCodes)));
        } else {
            $io->title('Fetching all available currencies');
        }

        try {
            $result = $this->currencyService->fetchCurrencies($currencyCodes);
            $currencies = $result['currencies'];
            $stats = $result['stats'];
            $count = count($currencies);
            
            $io->success(sprintf(
                "Successfully fetched %d currencies: %d new, %d updated",
                $count,
                $stats['added'],
                $stats['updated']
            ));
            
            $rows = [];

            foreach ($currencies as $code => $data) {
                $status = in_array($code, $stats['new_currencies']) ? 'NEW' : 'UPDATED';
                $rows[] = [
                    $code,
                    $data['name'],
                    $data['symbol'],
                    $data['type'],
                    $status
                ];
            }
            
            $io->table(
                ['Code', 'Name', 'Symbol', 'Type', 'Status'],
                $rows
            );
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error fetching currencies: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
