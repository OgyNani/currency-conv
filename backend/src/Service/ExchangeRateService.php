<?php

namespace App\Service;

use App\Entity\CurrencyPair;
use App\Entity\CurrencyExchangeRate;
use Doctrine\ORM\EntityManagerInterface;

class ExchangeRateService
{
    public const RESULT_TYPE_LATEST = 'latest';
    public const RESULT_TYPE_ALL = 'all';
    public const RESULT_TYPE_DATE = 'date';
    public const RESULT_TYPE_TIMESTAMP = 'timestamp';
    public const RESULT_TYPE_DATE_RANGE = 'date_range';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CurrencyService $currencyService
    ) {
    }
    
    /**
     * Fetch and store exchange rate for a currency pair
     * 
     * @param CurrencyPair $currencyPair The currency pair to fetch rate for
     * @return array Result with rate data and display information
     */
    public function fetchAndStoreExchangeRate(CurrencyPair $currencyPair): array
    {
        $fromCode = $currencyPair->getCurrencyFrom()->getCode();
        $toCode = $currencyPair->getCurrencyTo()->getCode();
        
        $rate = $this->currencyService->getExchangeRate($fromCode, $toCode);
        
        $exchangeRate = new CurrencyExchangeRate();
        $exchangeRate->setPair($currencyPair);
        $exchangeRate->setExchangeRate([
            'rate' => $rate
        ]);
        $exchangeRate->setDate(new \DateTime());
        
        $this->entityManager->persist($exchangeRate);
        $this->entityManager->flush();
        
        return [
            'success' => true,
            'rate' => $rate,
            'exchangeRate' => $exchangeRate,
            'title' => "Fetching exchange rate: {$fromCode} → {$toCode}",
            'message' => "Exchange rate for {$fromCode} → {$toCode}: {$rate}",
            'details' => "Stored in database with ID: " . $exchangeRate->getId()
        ];
    }
    
    /**
     * Get exchange rates with context information based on input parameters
     * 
     * @param CurrencyPair $currencyPair The currency pair to get rates for
     * @param string|null $dateStr Optional date string or 'all'
     * @param string|null $toDateStr Optional end date string for range
     * @return array [type, title, rates, fromDate, toDate]
     */
    public function getRatesWithContext(CurrencyPair $currencyPair, ?string $dateStr = null, ?string $toDateStr = null): array
    {
        $fromCode = $currencyPair->getCurrencyFrom()->getCode();
        $toCode = $currencyPair->getCurrencyTo()->getCode();
        
        $title = "Exchange rates for {$fromCode} → {$toCode}";
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('er')
            ->from(CurrencyExchangeRate::class, 'er')
            ->where('er.pair = :pair')
            ->setParameter('pair', $currencyPair)
            ->orderBy('er.date', 'DESC');
        
        $resultType = self::RESULT_TYPE_LATEST;
        
        if ($dateStr !== null) {
            if ($dateStr === 'all') {
                $resultType = self::RESULT_TYPE_ALL;
                $title .= " (all records)";
            } else {
                $date = $this->parseDate($dateStr);
                
                if ($toDateStr !== null) {
                    $toDate = $this->parseDate($toDateStr);
                    $queryBuilder
                        ->andWhere('er.date BETWEEN :fromDate AND :toDate')
                        ->setParameter('fromDate', $date)
                        ->setParameter('toDate', $toDate);
                    
                    $resultType = self::RESULT_TYPE_DATE_RANGE;
                    $title .= sprintf(
                        " (from %s to %s)",
                        $date->format('Y-m-d H:i:s'),
                        $toDate->format('Y-m-d H:i:s')
                    );
                } else {
                    $format = 'Y-m-d';
                    $resultType = self::RESULT_TYPE_DATE;
                    
                    if ($date->format('H:i:s') !== '00:00:00') {
                        $resultType = self::RESULT_TYPE_TIMESTAMP;
                        $format = 'Y-m-d H:i:s';
                        $queryBuilder
                            ->andWhere('er.date = :date')
                            ->setParameter('date', $date);
                    } else {
                        $nextDay = clone $date;
                        $nextDay->modify('+1 day');
                        
                        $queryBuilder
                            ->andWhere('er.date >= :date AND er.date < :nextDay')
                            ->setParameter('date', $date)
                            ->setParameter('nextDay', $nextDay);
                    }
                    
                    $title .= " (on {$date->format($format)})";
                }
            }
        } else {
            $queryBuilder->setMaxResults(1);
            $title .= " (latest)";
        }
        
        $rates = $queryBuilder->getQuery()->getResult();
        
        return [
            'rates' => $rates,
            'title' => $title,
            'resultType' => $resultType
        ];
    }
    
    /**
     * Prepare exchange rate data for display
     * 
     * @param array $result Result from getRatesWithContext
     * @return array Display data with title, table rows, and summary
     */
    public function prepareExchangeRateDisplayData(array $result): array
    {
        $rates = $result['rates'];
        $title = $result['title'];
        
        $displayData = [
            'title' => $title,
            'rows' => [],
            'summary' => '',
            'count' => count($rates)
        ];
        
        if (empty($rates)) {
            $displayData['summary'] = 'No exchange rates found for the specified criteria.';
            return $displayData;
        }
        
        $firstRate = reset($rates);
        $currencyPair = $firstRate->getPair();
        $fromCode = $currencyPair->getCurrencyFrom()->getCode();
        $toCode = $currencyPair->getCurrencyTo()->getCode();
        
        foreach ($rates as $rate) {
            $displayData['rows'][] = [
                $rate->getId(),
                $rate->getDate()->format('Y-m-d H:i:s'),
                $rate->getExchangeRate()['rate'],
                "{$fromCode} → {$toCode}"
            ];
        }
        
        $displayData['summary'] = sprintf('Found %d exchange rate(s)', count($rates));
        
        return $displayData;
    }
    
    /**
     * Get the latest exchange rate for a currency pair
     * 
     * @param CurrencyPair $currencyPair
     * @return CurrencyExchangeRate|null
     */
    public function getLatestRate(CurrencyPair $currencyPair): ?CurrencyExchangeRate
    {
        return $this->entityManager->getRepository(CurrencyExchangeRate::class)
            ->findOneBy(
                ['pair' => $currencyPair],
                ['date' => 'DESC']
            );
    }
    
    /**
     * Get all exchange rates for a currency pair
     * 
     * @param CurrencyPair $currencyPair
     * @return array
     */
    public function getAllRates(CurrencyPair $currencyPair): array
    {
        return $this->entityManager->getRepository(CurrencyExchangeRate::class)
            ->findBy(
                ['pair' => $currencyPair],
                ['date' => 'DESC']
            );
    }
    
    /**
     * Get exchange rates for a currency pair on a specific date
     * 
     * @param CurrencyPair $currencyPair
     * @param \DateTime $date
     * @return array
     */
    public function getRatesForDate(CurrencyPair $currencyPair, \DateTime $date): array
    {
        $startDate = clone $date;
        $startDate->setTime(0, 0, 0);
        
        $endDate = clone $date;
        $endDate->setTime(23, 59, 59);
        
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('er')
           ->from(CurrencyExchangeRate::class, 'er')
           ->where('er.pair = :pair')
           ->andWhere('er.date >= :startDate AND er.date <= :endDate')
           ->setParameter('pair', $currencyPair)
           ->setParameter('startDate', $startDate)
           ->setParameter('endDate', $endDate)
           ->orderBy('er.date', 'DESC');
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Get exchange rates for a currency pair at a specific timestamp
     * 
     * @param CurrencyPair $currencyPair
     * @param \DateTime $timestamp
     * @return array
     */
    public function getRatesForTimestamp(CurrencyPair $currencyPair, \DateTime $timestamp): array
    {
        $startTime = clone $timestamp;
        $startTime->modify('-1 minute');
        
        $endTime = clone $timestamp;
        $endTime->modify('+1 minute');
        
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('er')
           ->from(CurrencyExchangeRate::class, 'er')
           ->where('er.pair = :pair')
           ->andWhere('er.date BETWEEN :startTime AND :endTime')
           ->setParameter('pair', $currencyPair)
           ->setParameter('startTime', $startTime)
           ->setParameter('endTime', $endTime)
           ->orderBy('er.date', 'DESC');
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Get exchange rates for a currency pair within a date range
     * 
     * @param CurrencyPair $currencyPair
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     * @return array
     */
    public function getRatesForDateRange(CurrencyPair $currencyPair, \DateTime $fromDate, \DateTime $toDate): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('er')
           ->from(CurrencyExchangeRate::class, 'er')
           ->where('er.pair = :pair')
           ->andWhere('er.date BETWEEN :fromDate AND :toDate')
           ->setParameter('pair', $currencyPair)
           ->setParameter('fromDate', $fromDate)
           ->setParameter('toDate', $toDate)
           ->orderBy('er.date', 'DESC');
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Parse a date string into a DateTime object
     * 
     * @param string $dateStr Date string in format YYYY-MM-DD [HH:MM[:SS]]
     * @return \DateTime
     * @throws \Exception If the date format is invalid
     */
    public function parseDate(string $dateStr): \DateTime
    {
        $dateStr = str_replace('_', ' ', $dateStr);
        
        $formats = [
            'Y-m-d',
            'Y-m-d H:i',
            'Y-m-d H:i:s',
        ];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateStr);
            if ($date && $date->format($format) === $dateStr) {
                return $date;
            }
        }
        
        throw new \Exception("Date must be in format YYYY-MM-DD [HH:MM[:SS]]");
    }
}
