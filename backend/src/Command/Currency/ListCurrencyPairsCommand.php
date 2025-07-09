<?php

namespace App\Command\Currency;

use App\Entity\CurrencyPair;
use Doctrine\ORM\EntityManagerInterface;
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
        private EntityManagerInterface $entityManager
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
        
        if ($filterCode) {
            $filterCode = strtoupper($filterCode);
            $io->title("Currency pairs involving {$filterCode}");
        } else {
            $io->title('All currency pairs');
        }

        $repository = $this->entityManager->getRepository(CurrencyPair::class);
        
        $qb = $repository->createQueryBuilder('p')
            ->select('p', 'fromCurr', 'toCurr')
            ->leftJoin('p.currencyFrom', 'fromCurr')
            ->leftJoin('p.currencyTo', 'toCurr');
            
        if ($filterCode) {
            $qb->where('fromCurr.code = :code OR toCurr.code = :code')
               ->setParameter('code', $filterCode);
        }
        
        $pairs = $qb->getQuery()->getResult();
        
        if (empty($pairs)) {
            if ($filterCode) {
                $io->warning("No currency pairs found involving {$filterCode}");
            } else {
                $io->warning('No currency pairs found in the database');
            }
            
            return Command::SUCCESS;
        }
        
        $table = new Table($output);
        $table->setHeaders(['ID', 'From', 'From Name', 'To', 'To Name']);
        
        foreach ($pairs as $pair) {
            $table->addRow([
                $pair->getId(),
                $pair->getCurrencyFrom()->getCode(),
                $pair->getCurrencyFrom()->getName(),
                $pair->getCurrencyTo()->getCode(),
                $pair->getCurrencyTo()->getName(),
            ]);
        }
        
        $table->render();
        
        $io->success(sprintf('Found %d currency pair(s)', count($pairs)));
        
        return Command::SUCCESS;
    }
}
