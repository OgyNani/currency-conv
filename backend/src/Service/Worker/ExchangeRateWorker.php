<?php

namespace App\Service\Worker;

use App\Entity\CurrencyPair;
use App\Repository\CurrencyPairRepository;
use App\Service\Command\FetchExchangeRateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Worker for automatically fetching exchange rates for observed currency pairs
 */
#[AutoconfigureTag('app.worker')]
class ExchangeRateWorker extends AbstractWorkerService
{
    private int $sleepInterval = 60; // Default 60 seconds
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CurrencyPairRepository $currencyPairRepository,
        private FetchExchangeRateService $fetchExchangeRateService,
        KernelInterface $kernel
    ) {
        parent::__construct($kernel);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'exchange_rate';
    }
    
    /**
     * Set the sleep interval between iterations
     * 
     * @param int $seconds
     * @return self
     */
    public function setSleepInterval(int $seconds): self
    {
        $this->sleepInterval = $seconds;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getSleepInterval(): int
    {
        return $this->sleepInterval;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function process(): void
    {
        $this->log('Fetching exchange rates for observed currency pairs');
        
        $observedPairs = $this->getObservedPairs();
        
        if (empty($observedPairs)) {
            $this->log('No observed currency pairs found');
            return;
        }
        
        $this->log(sprintf('Found %d observed currency pairs', count($observedPairs)));
        
        foreach ($observedPairs as $pair) {
            $this->processExchangeRate($pair);
        }
    }
    
    /**
     * Get all currency pairs with observe=true
     * 
     * @return CurrencyPair[]
     */
    private function getObservedPairs(): array
    {
        return $this->entityManager->getRepository(CurrencyPair::class)
            ->createQueryBuilder('p')
            ->select('p', 'fromCurr', 'toCurr')
            ->leftJoin('p.currencyFrom', 'fromCurr')
            ->leftJoin('p.currencyTo', 'toCurr')
            ->where('p.observe = :observe')
            ->setParameter('observe', true)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Process exchange rate for a single currency pair
     * 
     * @param CurrencyPair $pair
     * @return void
     */
    private function processExchangeRate(CurrencyPair $pair): void
    {
        $fromCode = $pair->getCurrencyFrom()->getCode();
        $toCode = $pair->getCurrencyTo()->getCode();
        
        try {
            $this->log(sprintf('Fetching exchange rate for %s → %s', $fromCode, $toCode));
            
            $result = $this->fetchExchangeRateService->execute($pair);
            
            $this->log(sprintf('Successfully fetched rate for %s → %s: %s', 
                $fromCode, 
                $toCode, 
                $result['details']
            ));
        } catch (\Exception $e) {
            $this->log(sprintf('Error fetching exchange rate for %s → %s: %s', 
                $fromCode, 
                $toCode, 
                $e->getMessage()
            ));
        }
    }
}
