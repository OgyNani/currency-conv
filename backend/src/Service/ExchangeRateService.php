<?php

namespace App\Service;

use App\Entity\CurrencyExchangeRate;
use App\Entity\CurrencyPair;
use App\Repository\CurrencyExchangeRateRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for managing exchange rates
 */
class ExchangeRateService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CurrencyExchangeRateRepository $rateRepository
    ) {
    }

    /**
     * Get the latest exchange rate for a currency pair
     *
     * @param CurrencyPair $pair
     * @return CurrencyExchangeRate|null
     */
    public function getLatestRate(CurrencyPair $pair): ?CurrencyExchangeRate
    {
        return $this->rateRepository->findLatestRateForPair($pair);
    }

    /**
     * Get exchange rates for a currency pair within a date range
     *
     * @param CurrencyPair $pair
     * @param \DateTimeInterface $fromDate
     * @param \DateTimeInterface $toDate
     * @return array
     */
    public function getRatesByDateRange(CurrencyPair $pair, \DateTimeInterface $fromDate, \DateTimeInterface $toDate): array
    {
        return $this->rateRepository->findRatesByDateRange($pair, $fromDate, $toDate);
    }

    /**
     * Parse date string to DateTime object
     *
     * @param string $dateString
     * @return \DateTime
     * @throws \Exception
     */
    public function parseDate(string $dateString): \DateTime
    {
        try {
            return new \DateTime($dateString);
        } catch (\Exception $e) {
            throw new \Exception("Invalid date format: $dateString");
        }
    }
}
