<?php

namespace App\Command\Currency;

use App\Entity\CurrencyData;
use App\Entity\CurrencyPair;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-pair',
    description: 'Create a currency pair from two existing currencies',
)]
class CreateCurrencyPairCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
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

        if ($fromCode === $toCode) {
            $io->error('From and To currencies cannot be the same.');
            return Command::FAILURE;
        }

        $fromCurrency = $this->entityManager->getRepository(CurrencyData::class)->findOneBy(['code' => $fromCode]);
        if (!$fromCurrency) {
            $io->error("Currency '{$fromCode}' not found. Please fetch currencies first with app:fetch-currencies command.");
            return Command::FAILURE;
        }

        $toCurrency = $this->entityManager->getRepository(CurrencyData::class)->findOneBy(['code' => $toCode]);
        if (!$toCurrency) {
            $io->error("Currency '{$toCode}' not found. Please fetch currencies first with app:fetch-currencies command.");
            return Command::FAILURE;
        }

        $existingPair = $this->entityManager->getRepository(CurrencyPair::class)->findOneBy([
            'currencyFrom' => $fromCurrency,
            'currencyTo' => $toCurrency
        ]);

        if ($existingPair) {
            $io->warning("Currency pair {$fromCode} → {$toCode} already exists.");
            return Command::SUCCESS;
        }

        $currencyPair = new CurrencyPair();
        $currencyPair->setCurrencyFrom($fromCurrency);
        $currencyPair->setCurrencyTo($toCurrency);

        $this->entityManager->persist($currencyPair);
        $this->entityManager->flush();

        $io->success("Currency pair {$fromCode} → {$toCode} created successfully!");
        
        return Command::SUCCESS;
    }
}
