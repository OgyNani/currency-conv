<?php

namespace App\Service\Command;

use App\Entity\CurrencyExchangeRate;
use App\Entity\CurrencyPair;
use App\Http\CurrencyApiClient;
use App\Repository\CurrencyExchangeRateRepository;

class FetchExchangeRateService
{
    public function __construct(
        private CurrencyApiClient $apiClient,
        private CurrencyExchangeRateRepository $exchangeRateRepository,
        private string $apiKey
    ) {
    }

    /**
     * Fetch and store the exchange rate for a currency pair
     * 
     * @param CurrencyPair $currencyPair Currency pair to fetch rate for
     * @return array Result with status, message, and details
     */
    public function execute(CurrencyPair $currencyPair): array
    {
        $fromCode = $currencyPair->getCurrencyFrom()->getCode();
        $toCode = $currencyPair->getCurrencyTo()->getCode();
        
        $exchangeRateData = $this->fetchExchangeRateData($fromCode, $toCode);
        
        $exchangeRate = $this->createAndSaveExchangeRate($currencyPair, $exchangeRateData);
        
        return $this->prepareResponse($fromCode, $toCode, $exchangeRateData['rate'], $exchangeRate->getDate());
    }
    
    /**
     * Fetch exchange rate data from the API
     * 
     * @param string $fromCode Base currency code
     * @param string $toCode Target currency code
     * @return array Exchange rate data
     * @throws \RuntimeException If exchange rate cannot be fetched
     */
    private function fetchExchangeRateData(string $fromCode, string $toCode): array
    {
        $rates = $this->apiClient->getLatestRates($fromCode, [$toCode]);
        
        if (!isset($rates[$toCode])) {
            throw new \RuntimeException("Could not fetch exchange rate for {$fromCode} → {$toCode}");
        }
        
        $rate = $rates[$toCode];
        $timestamp = new \DateTime();
        
        return [
            'from' => $fromCode,
            'to' => $toCode,
            'rate' => $rate,
            'timestamp' => $timestamp->format('c'),
            'date' => $timestamp
        ];
    }
    
    /**
     * Create and save exchange rate entity
     * 
     * @param CurrencyPair $currencyPair Currency pair
     * @param array $exchangeRateData Exchange rate data
     * @return CurrencyExchangeRate Created and saved entity
     */
    private function createAndSaveExchangeRate(CurrencyPair $currencyPair, array $exchangeRateData): CurrencyExchangeRate
    {
        return $this->exchangeRateRepository->createExchangeRate($currencyPair, $exchangeRateData);
    }
    
    /**
     * Prepare response data
     * 
     * @param string $fromCode Base currency code
     * @param string $toCode Target currency code
     * @param float $rate Exchange rate value
     * @param \DateTime $timestamp Timestamp of the exchange rate
     * @return array Response data
     */
    private function prepareResponse(string $fromCode, string $toCode, float $rate, \DateTime $timestamp): array
    {
        return [
            'title' => "Exchange Rate: {$fromCode} → {$toCode}",
            'message' => "Successfully fetched and stored exchange rate.",
            'details' => "1 {$fromCode} = {$rate} {$toCode} (as of {$timestamp->format('Y-m-d H:i:s')})" 
        ];
    }
}
