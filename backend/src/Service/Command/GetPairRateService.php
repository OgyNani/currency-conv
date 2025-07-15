<?php

namespace App\Service\Command;

use App\Entity\CurrencyExchangeRate;
use App\Entity\CurrencyPair;
use App\Repository\CurrencyExchangeRateRepository;

class GetPairRateService
{
    public function __construct(
        private CurrencyExchangeRateRepository $exchangeRateRepository
    ) {
    }
    
    /**
     * Execute the service
     * 
     * @param CurrencyPair $currencyPair Currency pair to get rates for
     * @param string|null $dateStr Optional date string for filtering
     * @param string|null $toDateStr Optional end date string for range filtering
     * @return array Result with rates and context information
     */
    public function execute(CurrencyPair $currencyPair, ?string $dateStr = null, ?string $toDateStr = null): array
    {
        $fromCode = $currencyPair->getCurrencyFrom()->getCode();
        $toCode = $currencyPair->getCurrencyTo()->getCode();
        
        $latestOnly = ($dateStr === null && $toDateStr === null);
        list($rates, $dateFilter) = $this->fetchExchangeRates($currencyPair, $dateStr, $toDateStr, $latestOnly);
        
        $title = $this->generateTitle($fromCode, $toCode, $dateFilter);
        
        return [
            'rates' => $rates,
            'title' => $title,
            'dateFilter' => $dateFilter,
            'count' => count($rates),
            'fromCode' => $fromCode,
            'toCode' => $toCode,
            'currencyPair' => $currencyPair
        ];
    }
    
    /**
     * Fetch exchange rates for a currency pair with optional date filtering
     * 
     * @param CurrencyPair $currencyPair Currency pair to get rates for
     * @param string|null $dateStr Optional date string for filtering
     * @param string|null $toDateStr Optional end date string for range filtering
     * @param bool $latestOnly Whether to return only the latest rate
     * @return array Array with rates and date filter description
     */
    private function fetchExchangeRates(CurrencyPair $currencyPair, ?string $dateStr = null, ?string $toDateStr = null, bool $latestOnly = false): array
    {
        return $this->exchangeRateRepository->findExchangeRatesWithDateFilter($currencyPair, $dateStr, $toDateStr, $latestOnly);
    }
    
    /**
     * Generate title for display
     * 
     * @param string $fromCode From currency code
     * @param string $toCode To currency code
     * @param string|null $dateFilter Date filter description
     * @return string Title
     */
    private function generateTitle(string $fromCode, string $toCode, ?string $dateFilter): string
    {
        $title = "Exchange rates for {$fromCode} → {$toCode}";
        
        if ($dateFilter) {
            if ($dateFilter === 'latest') {
                $title = "Latest exchange rate for {$fromCode} → {$toCode}";
            } else {
                $title .= " ({$dateFilter})";
            }
        }
        
        return $title;
    }
    
    /**
     * Prepare exchange rate data for display
     * 
     * @param array $result Result from execute method
     * @return array Display data with title, table rows, and summary
     */
    public function prepareDisplayData(array $result): array
    {
        $rates = $result['rates'];
        $title = $result['title'];
        $dateFilter = $result['dateFilter'];
        $count = $result['count'];
        $fromCode = $result['fromCode'];
        $toCode = $result['toCode'];
        
        $displayData = $this->initializeDisplayData($title, $count);
        
        if ($displayData['isEmpty']) {
            $displayData['summary'] = $this->generateEmptySummary($fromCode, $toCode, $dateFilter);
            return $displayData;
        }
        
        $displayData['rows'] = $this->buildTableRows($rates, $fromCode, $toCode);
        
        $displayData['summary'] = $this->generateSummary($count, $fromCode, $toCode, $dateFilter);
        
        return $displayData;
    }
    
    /**
     * Initialize display data structure
     * 
     * @param string $title Title for display
     * @param int $count Number of items
     * @return array Initial display data structure
     */
    private function initializeDisplayData(string $title, int $count): array
    {
        return [
            'title' => $title,
            'rows' => [],
            'summary' => '',
            'count' => $count,
            'isEmpty' => ($count === 0)
        ];
    }
    
    /**
     * Generate summary text for empty results
     * 
     * @param string $fromCode From currency code
     * @param string $toCode To currency code
     * @param string|null $dateFilter Optional date filter description
     * @return string Summary text
     */
    private function generateEmptySummary(string $fromCode, string $toCode, ?string $dateFilter = null): string
    {
        if ($dateFilter) {
            return "No exchange rates found for {$fromCode} → {$toCode} {$dateFilter}";
        }
        
        return "No exchange rates found for {$fromCode} → {$toCode}";
    }
    
    /**
     * Build table rows from exchange rates
     * 
     * @param array $rates List of exchange rate entities
     * @param string $fromCode From currency code
     * @param string $toCode To currency code
     * @return array Table rows for display
     */
    private function buildTableRows(array $rates, string $fromCode, string $toCode): array
    {
        $rows = [];
        
        foreach ($rates as $rate) {
            $rows[] = [
                $rate->getId(),
                $rate->getDate()->format('Y-m-d H:i:s'),
                $rate->getRate(),
                "{$fromCode} → {$toCode}"
            ];
        }
        
        return $rows;
    }
    
    /**
     * Generate summary text for results
     * 
     * @param int $count Number of items
     * @param string $fromCode From currency code
     * @param string $toCode To currency code
     * @param string|null $dateFilter Optional date filter description
     * @return string Summary text
     */
    private function generateSummary(int $count, string $fromCode, string $toCode, ?string $dateFilter = null): string
    {
        if ($dateFilter) {
            return sprintf('Found %d exchange rate(s) for %s → %s %s', 
                $count, $fromCode, $toCode, $dateFilter);
        }
        
        return sprintf('Found %d exchange rate(s) for %s → %s', 
            $count, $fromCode, $toCode);
    }
}
