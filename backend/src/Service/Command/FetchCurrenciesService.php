<?php

namespace App\Service\Command;

use App\Entity\CurrencyData;
use App\Repository\CurrencyDataRepository;
use App\Http\CurrencyApiClient;

class FetchCurrenciesService
{
    public function __construct(
        private CurrencyApiClient $apiClient,
        private CurrencyDataRepository $currencyDataRepository,
        private string $apiKey
    ) {
    }

    /**
     * Fetch currencies from the API
     * 
     * @param array $currencyCodes Optional list of currency codes to fetch
     * @return array Result with currencies and stats
     */
    public function execute(array $currencyCodes = []): array
    {
        $currenciesData = $this->fetchCurrenciesFromApi($currencyCodes);
        
        $stats = $this->saveCurrencies($currenciesData);
        
        return [
            'currencies' => $currenciesData,
            'stats' => $stats
        ];
    }
    
    /**
     * Fetch currencies data from the API
     * 
     * @param array $currencyCodes Optional list of currency codes to fetch
     * @return array Currency data from API
     */
    private function fetchCurrenciesFromApi(array $currencyCodes = []): array
    {
        return $this->apiClient->getCurrencies($currencyCodes);
    }
    
    /**
     * Save currencies to the database
     * 
     * @param array $currenciesData Currency data from API
     * @return array Stats about the operation
     */
    private function saveCurrencies(array $currenciesData): array
    {
        $stats = $this->initializeStats();
        
        foreach ($currenciesData as $code => $currencyData) {
            $currency = $this->currencyDataRepository->findByCode($code);
            
            if (!$currency) {
                $currency = $this->createNewCurrency($code, $currencyData);
                $stats['added']++;
                $stats['new_currencies'][] = $code;
            } else {
                $this->updateExistingCurrency($currency, $currencyData);
                $stats['updated']++;
                $stats['updated_currencies'][] = $code;
            }
        }
        
        $this->currencyDataRepository->saveChanges();
        return $stats;
    }
    
    /**
     * Initialize statistics array for currency operations
     * 
     * @return array Empty stats array
     */
    private function initializeStats(): array
    {
        return [
            'added' => 0,
            'updated' => 0,
            'new_currencies' => [],
            'updated_currencies' => []
        ];
    }
    
    /**
     * Create a new currency entity
     * 
     * @param string $code Currency code
     * @param array $currencyData Currency data from API
     * @return CurrencyData New currency entity
     */
    private function createNewCurrency(string $code, array $currencyData): CurrencyData
    {
        return $this->currencyDataRepository->createCurrency($code, $currencyData);
    }
    
    /**
     * Update an existing currency entity
     * 
     * @param CurrencyData $currency Existing currency entity
     * @param array $currencyData Currency data from API
     * @return void
     */
    private function updateExistingCurrency(CurrencyData $currency, array $currencyData): void
    {
        $this->currencyDataRepository->updateCurrency($currency, $currencyData);
    }
    
    /**
     * Prepare currency data for display with formatted information
     * 
     * @param array $result Result from execute method
     * @return array Display data with title, summary, and table rows
     */
    public function prepareDisplayData(array $result): array
    {
        $currencies = $result['currencies'];
        $stats = $result['stats'];
        $count = count($currencies);
        
        $displayData = [
            'title' => '',
            'summary' => '',
            'rows' => []
        ];
        
        $displayData['title'] = 'Currency Data';
        
        $displayData['summary'] = sprintf(
            "Successfully fetched %d currencies: %d new, %d updated",
            $count,
            $stats['added'],
            $stats['updated']
        );
        
        foreach ($currencies as $code => $data) {
            $status = in_array($code, $stats['new_currencies']) ? 'NEW' : 'UPDATED';
            $displayData['rows'][] = [
                $code,
                $data['name'],
                $data['symbol'],
                $data['type'] ?? '',
                $status
            ];
        }
        
        return $displayData;
    }
}
