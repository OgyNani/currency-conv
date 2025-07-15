<?php

namespace App\Service\Command;

use App\Entity\CurrencyPair;
use App\Repository\CurrencyPairRepository;

class ListCurrencyPairsService
{
    public function __construct(
        private CurrencyPairRepository $currencyPairRepository
    ) {
    }
    
    /**
     * List currency pairs with optional filtering by currency code
     * 
     * @param string|null $filterCode Optional currency code filter (from or to)
     * @return array Result with pairs and context information
     */
    public function execute(?string $filterCode = null): array
    {
        $pairs = $this->fetchCurrencyPairs($filterCode);
        
        $title = $this->generateTitle($filterCode);
        
        return [
            'pairs' => $pairs,
            'title' => $title,
            'filterCode' => $filterCode,
            'count' => count($pairs)
        ];
    }
    
    /**
     * Fetch currency pairs from database with optional filtering
     * 
     * @param string|null $filterCode Optional currency code filter
     * @return array List of currency pair entities
     */
    private function fetchCurrencyPairs(?string $filterCode = null): array
    {
        return $this->currencyPairRepository->findCurrencyPairs($filterCode);
    }
    
    // Code filter logic moved to CurrencyPairRepository
    
    /**
     * Generate title based on filter
     * 
     * @param string|null $filterCode Optional currency code filter
     * @return string Title for display
     */
    private function generateTitle(?string $filterCode = null): string
    {
        if ($filterCode) {
            return "Currency pairs involving {$filterCode}";
        }
        
        return 'All currency pairs';
    }
    
    /**
     * Prepare currency pair list data for display
     * 
     * @param array $result Result from execute method
     * @return array Display data with title, table rows, and summary
     */
    public function prepareDisplayData(array $result): array
    {
        $pairs = $result['pairs'];
        $title = $result['title'];
        $filterCode = $result['filterCode'];
        $count = $result['count'];
        
        $displayData = $this->initializeDisplayData($title, $count);
        
        if ($displayData['isEmpty']) {
            $displayData['summary'] = $this->generateEmptySummary($filterCode);
            return $displayData;
        }
        
        $displayData['rows'] = $this->buildTableRows($pairs);
        
        $displayData['summary'] = $this->generateSummary($count);
        
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
     * @param string|null $filterCode Optional currency code filter
     * @return string Summary text
     */
    private function generateEmptySummary(?string $filterCode = null): string
    {
        if ($filterCode) {
            return "No currency pairs found involving {$filterCode}";
        }
        
        return 'No currency pairs found in the database';
    }
    
    /**
     * Build table rows from currency pairs
     * 
     * @param array $pairs List of currency pair entities
     * @return array Table rows for display
     */
    private function buildTableRows(array $pairs): array
    {
        $rows = [];
        
        foreach ($pairs as $pair) {
            $fromCurrency = $pair->getCurrencyFrom();
            $toCurrency = $pair->getCurrencyTo();
            
            $rows[] = [
                $pair->getId(),
                $fromCurrency->getCode(),
                $fromCurrency->getName(),
                $toCurrency->getCode(),
                $toCurrency->getName(),
                $pair->isObserve() ? 'Yes' : 'No'
            ];
        }
        
        return $rows;
    }
    
    /**
     * Generate summary text for results
     * 
     * @param int $count Number of items
     * @return string Summary text
     */
    private function generateSummary(int $count): string
    {
        return sprintf('Found %d currency pair(s)', $count);
    }
}
