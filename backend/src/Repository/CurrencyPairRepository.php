<?php

namespace App\Repository;

use App\Entity\CurrencyData;
use App\Entity\CurrencyPair;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository for CurrencyPair entity
 */
class CurrencyPairRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }
    
    /**
     * Find a currency pair by from and to currencies
     * 
     * @param CurrencyData $fromCurrency
     * @param CurrencyData $toCurrency
     * @return CurrencyPair|null
     */
    public function findByFromAndToCurrencies(CurrencyData $fromCurrency, CurrencyData $toCurrency): ?CurrencyPair
    {
        return $this->entityManager->getRepository(CurrencyPair::class)->findOneBy([
            'currencyFrom' => $fromCurrency,
            'currencyTo' => $toCurrency
        ]);
    }
    
    /**
     * Create a new currency pair
     * 
     * @param CurrencyData $fromCurrency From currency
     * @param CurrencyData $toCurrency To currency
     * @param bool $observe Whether to observe this pair for rate updates
     * @return CurrencyPair
     */
    public function createPair(CurrencyData $fromCurrency, CurrencyData $toCurrency, bool $observe = true): CurrencyPair
    {
        $currencyPair = new CurrencyPair($fromCurrency, $toCurrency, $observe);
        
        $this->entityManager->persist($currencyPair);
        $this->entityManager->flush();
        
        return $currencyPair;
    }
    
    /**
     * Find all currency pairs with optional code filtering
     * 
     * @param string|null $filterCode Optional currency code filter
     * @return array List of currency pair entities
     */
    public function findCurrencyPairs(?string $filterCode = null): array
    {
        $qb = $this->entityManager->getRepository(CurrencyPair::class)
            ->createQueryBuilder('p')
            ->select('p', 'fromCurr', 'toCurr')
            ->leftJoin('p.currencyFrom', 'fromCurr')
            ->leftJoin('p.currencyTo', 'toCurr');
            
        if ($filterCode) {
            $this->applyCodeFilter($qb, $filterCode);
        }
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Find a currency pair by ID
     * 
     * @param int $id Currency pair ID
     * @return CurrencyPair|null
     */
    public function findById(int $id): ?CurrencyPair
    {
        return $this->entityManager->getRepository(CurrencyPair::class)->find($id);
    }
    
    /**
     * Update the observe status of a currency pair
     * 
     * @param CurrencyPair $currencyPair Currency pair to update
     * @param bool $status New observe status
     * @return void
     */
    public function updateObserveStatus(CurrencyPair $currencyPair, bool $status): void
    {
        $currencyPair->setObserve($status);
        $this->entityManager->flush();
    }
    
    /**
     * Apply currency code filter to query builder
     * 
     * @param QueryBuilder $qb Query builder
     * @param string $filterCode Currency code to filter by
     * @return void
     */
    private function applyCodeFilter(QueryBuilder $qb, string $filterCode): void
    {
        $filterCode = strtoupper($filterCode);
        $qb->where('fromCurr.code = :code OR toCurr.code = :code')
           ->setParameter('code', $filterCode);
    }
}
