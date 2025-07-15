<?php

namespace App\Repository;

use App\Entity\CurrencyExchangeRate;
use App\Entity\CurrencyPair;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository for CurrencyExchangeRate entity
 */
class CurrencyExchangeRateRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }
    
    /**
     * Create and save a new exchange rate
     * 
     * @param CurrencyPair $currencyPair Currency pair
     * @param array $exchangeRateData Exchange rate data
     * @return CurrencyExchangeRate Created and saved entity
     */
    public function createExchangeRate(CurrencyPair $currencyPair, array $exchangeRateData): CurrencyExchangeRate
    {
        $exchangeRate = new CurrencyExchangeRate(
            $currencyPair, 
            $exchangeRateData['rate'], 
            $exchangeRateData['date']
        );
        
        $this->entityManager->persist($exchangeRate);
        $this->entityManager->flush();
        
        return $exchangeRate;
    }
    
    /**
     * Find the latest exchange rate for a currency pair
     * 
     * @param CurrencyPair $currencyPair Currency pair
     * @return CurrencyExchangeRate|null Latest exchange rate or null if none exists
     */
    public function findLatestExchangeRate(CurrencyPair $currencyPair): ?CurrencyExchangeRate
    {
        return $this->entityManager->getRepository(CurrencyExchangeRate::class)
            ->createQueryBuilder('er')
            ->where('er.pair = :pair')
            ->setParameter('pair', $currencyPair)
            ->orderBy('er.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    /**
     * Find exchange rates for a currency pair with optional date filtering
     * 
     * @param CurrencyPair $currencyPair Currency pair
     * @param string|null $dateStr Optional date string for filtering
     * @param string|null $toDateStr Optional end date string for range filtering
     * @param bool $latestOnly Whether to return only the latest rate
     * @return array Array with rates and date filter description
     */
    public function findExchangeRatesWithDateFilter(CurrencyPair $currencyPair, ?string $dateStr = null, ?string $toDateStr = null, bool $latestOnly = false): array
    {
        $qb = $this->entityManager->getRepository(CurrencyExchangeRate::class)
            ->createQueryBuilder('er')
            ->where('er.pair = :pair')
            ->setParameter('pair', $currencyPair)
            ->orderBy('er.date', 'DESC');
        
        if ($latestOnly && !$dateStr && !$toDateStr) {
            $qb->setMaxResults(1);
            $rates = $qb->getQuery()->getResult();
            return [$rates, 'latest'];
        }
        
        $dateFilter = $this->buildDateFilter($qb, $dateStr, $toDateStr);
        
        $rates = $qb->getQuery()->getResult();
        
        return [$rates, $dateFilter];
    }
    
    /**
     * Build date filter for query
     * 
     * @param QueryBuilder $qb Query builder
     * @param string|null $dateStr Date string
     * @param string|null $toDateStr End date string for range
     * @return string|null Date filter description
     */
    private function buildDateFilter(QueryBuilder $qb, ?string $dateStr, ?string $toDateStr): ?string
    {
        if (!$dateStr) {
            return null;
        }
        
        if (strtolower($dateStr) === 'all') {
            return 'all available dates';
        }
        
        try {
            $dateStr = str_replace('_', ' ', $dateStr);
            $date = new \DateTimeImmutable($dateStr);
            
            if ($toDateStr) {
                $toDateStr = str_replace('_', ' ', $toDateStr);
                $toDate = new \DateTimeImmutable($toDateStr);
                
                if ($toDate < $date) {
                    throw new \InvalidArgumentException('End date must be after start date');
                }
                
                $qb->andWhere('er.date BETWEEN :startDate AND :endDate')
                   ->setParameter('startDate', $date)
                   ->setParameter('endDate', $toDate);
                
                return "from {$date->format('Y-m-d H:i')} to {$toDate->format('Y-m-d H:i')}";
            } else {
                $nextDay = $date->modify('+1 day');
                
                $qb->andWhere('er.date >= :startDate AND er.date < :endDate')
                   ->setParameter('startDate', $date)
                   ->setParameter('endDate', $nextDay);
                
                return "on {$date->format('Y-m-d')}";
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid date format: ' . $e->getMessage());
        }
    }
}
