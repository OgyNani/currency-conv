<?php

namespace App\Command\Currency;

use App\Entity\CurrencyData;
use App\Entity\CurrencyPair;
use App\Service\Command\CreateCurrencyPairService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-pair',
    description: 'Create a currency pair',
)]
class CreateCurrencyPairCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CreateCurrencyPairService $createCurrencyPairService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('from', InputArgument::REQUIRED, 'Currency code to convert from (e.g. EUR)')
            ->addArgument('to', InputArgument::REQUIRED, 'Currency code to convert to (e.g. USD)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fromCode = strtoupper($input->getArgument('from'));
        $toCode = strtoupper($input->getArgument('to'));

        $io->title("Creating currency pair: {$fromCode} → {$toCode}");

        $result = $this->createCurrencyPairService->execute($fromCode, $toCode);
        
        if (!$result['success']) {
            $io->error($result['message']);
            return Command::FAILURE;
        }
        
        $io->success($result['message']);
        return Command::SUCCESS;
    }
}
