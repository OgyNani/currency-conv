<?php

namespace App\Service\Command;

use App\Entity\CurrencyData;
use App\Repository\CurrencyDataRepository;

class ListCurrenciesService
{
    public function __construct(
        private CurrencyDataRepository $currencyDataRepository
    ) {
    }
    
    /**
     * List currencies with optional filtering by code
     * 
     * @param string|null $filterCode Optional currency code filter
     * @return array Result with currencies and context information
     */
    public function execute(?string $filterCode = null): array
    {
        $currencies = $this->fetchCurrencies($filterCode);
        
        $title = $this->generateTitle($filterCode);
        
        return [
            'currencies' => $currencies,
            'title' => $title,
            'filterCode' => $filterCode,
            'count' => count($currencies)
        ];
    }
    
    /**
     * Fetch currencies from database with optional filtering
     * 
     * @param string|null $filterCode Optional currency code filter
     * @return array List of currency entities
     */
    private function fetchCurrencies(?string $filterCode = null): array
    {
        return $this->currencyDataRepository->findCurrencies($filterCode);
    }
    
    // Code filter logic moved to CurrencyDataRepository
    
    /**
     * Generate title based on filter
     * 
     * @param string|null $filterCode Optional currency code filter
     * @return string Title for display
     */
    private function generateTitle(?string $filterCode = null): string
    {
        if ($filterCode) {
            return "Currencies matching code: {$filterCode}";
        }
        
        return 'All currencies';
    }
    
    /**
     * Prepare currency list data for display
     * 
     * @param array $result Result from execute method
     * @return array Display data with title, table rows, and summary
     */
    public function prepareDisplayData(array $result): array
    {
        $currencies = $result['currencies'];
        $title = $result['title'];
        $filterCode = $result['filterCode'];
        $count = $result['count'];
        
        $displayData = $this->initializeDisplayData($title, $count);
        
        if ($displayData['isEmpty']) {
            $displayData['summary'] = $this->generateEmptySummary($filterCode);
            return $displayData;
        }
        
        $displayData['rows'] = $this->buildTableRows($currencies);
        
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
            return "No currencies found matching code {$filterCode}";
        }
        
        return 'No currencies found in the database';
    }
    
    /**
     * Build table rows from currencies
     * 
     * @param array $currencies List of currency entities
     * @return array Table rows for display
     */
    private function buildTableRows(array $currencies): array
    {
        $rows = [];
        
        foreach ($currencies as $currency) {
            $rows[] = [
                $currency->getCode(),
                $currency->getName(),
                $currency->getSymbol(),
                $currency->getSymbolNative(),
                $currency->getDecimalDigits(),
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
        return sprintf('Found %d currency/currencies', $count);
    }
}
