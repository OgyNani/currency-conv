<?php

namespace App\Command\Currency;

use App\Entity\CurrencyData;
use Doctrine\ORM\EntityManagerInterface;
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
        private EntityManagerInterface $entityManager
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
        
        $title = 'All currencies';
        if ($filterCode) {
            $filterCode = strtoupper($filterCode);
            $title = "Currencies matching code: {$filterCode}";
        }
        
        $io->title($title);

        $repository = $this->entityManager->getRepository(CurrencyData::class);
        
        $qb = $repository->createQueryBuilder('c')
            ->orderBy('c.code', 'ASC');
            
        if ($filterCode) {
            $qb->andWhere('c.code LIKE :code')
               ->setParameter('code', $filterCode . '%');
        }
        
        $currencies = $qb->getQuery()->getResult();
        
        if (empty($currencies)) {
            if ($filterCode) {
                $io->warning("No currencies found matching code {$filterCode}");
            } else {
                $io->warning('No currencies found in the database');
            }
            
            return Command::SUCCESS;
        }
        
        $table = new Table($output);
        $table->setHeaders(['Code', 'Name', 'Symbol', 'Native Symbol', 'Decimals']);
        
        foreach ($currencies as $currency) {
            $table->addRow([
                $currency->getCode(),
                $currency->getName(),
                $currency->getSymbol(),
                $currency->getSymbolNative(),
                $currency->getDecimalDigits(),
            ]);
        }
        
        $table->render();
        
        $io->success(sprintf('Found %d currency/currencies', count($currencies)));
        
        return Command::SUCCESS;
    }
}
