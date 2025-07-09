<?php

namespace App\Http;

/**
 * Client for interacting with the currency API
 */
class CurrencyApiClient extends ApiClient
{
    /**
     * Constructor
     * 
     * @param string $apiKey The API key for authentication
     * @param string $baseUrl The base URL for the currency API
     */
    public function __construct(
        private string $apiKey,
        string $baseUrl = 'https://api.freecurrencyapi.com/v1'
    ) {
        parent::__construct($baseUrl);
    }

    /**
     * Get available currencies
     * 
     * @param array $currencyCodes Optional list of currency codes to filter by
     * @return array The currencies data
     */
    public function getCurrencies(array $currencyCodes = []): array
    {
        $queryParams = ['apikey' => $this->apiKey];
        
        if (!empty($currencyCodes)) {
            $queryParams['currencies'] = implode(',', array_map('strtoupper', $currencyCodes));
        }
        
        $response = $this->get('currencies', $queryParams);
        
        if (!isset($response['data']) || !is_array($response['data'])) {
            throw new \RuntimeException('Invalid response from currency API');
        }
        
        return $response['data'];
    }

    /**
     * Get latest exchange rates
     * 
     * @param string $baseCurrency Base currency code
     * @param array $targetCurrencies Optional list of target currency codes
     * @return array The exchange rates data
     */
    public function getLatestRates(string $baseCurrency = 'USD', array $targetCurrencies = []): array
    {
        $queryParams = [
            'apikey' => $this->apiKey,
            'base_currency' => strtoupper($baseCurrency)
        ];
        
        if (!empty($targetCurrencies)) {
            $queryParams['currencies'] = implode(',', array_map('strtoupper', $targetCurrencies));
        }
        
        $response = $this->get('latest', $queryParams);
        
        if (!isset($response['data']) || !is_array($response['data'])) {
            throw new \RuntimeException('Invalid response from currency API');
        }
        
        return $response['data'];
    }

    /**
     * Get historical exchange rates for a specific date
     * 
     * @param string $date Date in YYYY-MM-DD format
     * @param string $baseCurrency Base currency code
     * @param array $targetCurrencies Optional list of target currency codes
     * @return array The historical exchange rates data
     */
    public function getHistoricalRates(string $date, string $baseCurrency = 'USD', array $targetCurrencies = []): array
    {
        $queryParams = [
            'apikey' => $this->apiKey,
            'base_currency' => strtoupper($baseCurrency),
            'date' => $date
        ];
        
        if (!empty($targetCurrencies)) {
            $queryParams['currencies'] = implode(',', array_map('strtoupper', $targetCurrencies));
        }
        
        $response = $this->get('historical', $queryParams);
        
        if (!isset($response['data']) || !is_array($response['data'])) {
            throw new \RuntimeException('Invalid response from currency API');
        }
        
        return $response['data'];
    }
}
