<?php

namespace App\Service\Command;

use App\Entity\CurrencyData;
use App\Entity\CurrencyPair;
use App\Repository\CurrencyDataRepository;
use App\Repository\CurrencyPairRepository;

class CreateCurrencyPairService
{
    public function __construct(
        private CurrencyDataRepository $currencyDataRepository,
        private CurrencyPairRepository $currencyPairRepository
    ) {
    }
    
    /**
     * Find a currency by its code
     * 
     * @param string $code Currency code
     * @return CurrencyData|null
     */
    private function findCurrencyByCode(string $code): ?CurrencyData
    {
        return $this->currencyDataRepository->findByCode($code);
    }
    
    /**
     * Check if a currency pair already exists
     * 
     * @param CurrencyData $fromCurrency
     * @param CurrencyData $toCurrency
     * @return CurrencyPair|null
     */
    private function findExistingPair(CurrencyData $fromCurrency, CurrencyData $toCurrency): ?CurrencyPair
    {
        return $this->currencyPairRepository->findByFromAndToCurrencies($fromCurrency, $toCurrency);
    }
    
    /**
     * Create a new currency pair
     * 
     * @param CurrencyData $fromCurrency From currency
     * @param CurrencyData $toCurrency To currency
     * @param bool $observe Whether to observe this pair for rate updates
     * @return CurrencyPair
     */
    private function createPair(CurrencyData $fromCurrency, CurrencyData $toCurrency, bool $observe = true): CurrencyPair
    {
        return $this->currencyPairRepository->createPair($fromCurrency, $toCurrency, $observe);
    }
    
    /**
     * Create a currency pair with validation
     * 
     * @param string $fromCode From currency code
     * @param string $toCode To currency code
     * @param bool $observe Whether to observe this pair for rate updates
     * @return array Result with status, message, and pair if created
     */
    public function execute(string $fromCode, string $toCode, bool $observe = true): array
    {
        $validationResult = $this->validateCurrencyCodes($fromCode, $toCode);
        if (!$validationResult['valid']) {
            return $this->createErrorResponse($validationResult['message']);
        }
        
        $currencyResult = $this->findCurrencyEntities($fromCode, $toCode);
        if (!$currencyResult['valid']) {
            return $this->createErrorResponse($currencyResult['message']);
        }
        
        $fromCurrency = $currencyResult['fromCurrency'];
        $toCurrency = $currencyResult['toCurrency'];
        
        $existingPair = $this->findExistingPair($fromCurrency, $toCurrency);
        if ($existingPair) {
            return $this->createExistingPairResponse($fromCode, $toCode, $existingPair);
        }
        
        $pair = $this->createPair($fromCurrency, $toCurrency, $observe);
        
        return $this->createSuccessResponse($fromCode, $toCode, $pair);
    }
    
    /**
     * Validate that the currency codes are valid and not the same
     * 
     * @param string $fromCode From currency code
     * @param string $toCode To currency code
     * @return array Validation result with valid flag and message
     */
    private function validateCurrencyCodes(string $fromCode, string $toCode): array
    {
        if ($fromCode === $toCode) {
            return [
                'valid' => false,
                'message' => 'From and To currencies cannot be the same.'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Find currency entities for both codes
     * 
     * @param string $fromCode From currency code
     * @param string $toCode To currency code
     * @return array Result with currencies and validation status
     */
    private function findCurrencyEntities(string $fromCode, string $toCode): array
    {
        $fromCurrency = $this->findCurrencyByCode($fromCode);
        if (!$fromCurrency) {
            return [
                'valid' => false,
                'message' => "Currency '{$fromCode}' not found. Please fetch currencies first with app:fetch-currencies command."
            ];
        }
        
        $toCurrency = $this->findCurrencyByCode($toCode);
        if (!$toCurrency) {
            return [
                'valid' => false,
                'message' => "Currency '{$toCode}' not found. Please fetch currencies first with app:fetch-currencies command."
            ];
        }
        
        return [
            'valid' => true,
            'fromCurrency' => $fromCurrency,
            'toCurrency' => $toCurrency
        ];
    }
    
    /**
     * Create an error response
     * 
     * @param string $message Error message
     * @return array Error response
     */
    private function createErrorResponse(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'pair' => null
        ];
    }
    
    /**
     * Create a response for existing pair
     * 
     * @param string $fromCode From currency code
     * @param string $toCode To currency code
     * @param CurrencyPair $pair Existing pair
     * @return array Response for existing pair
     */
    private function createExistingPairResponse(string $fromCode, string $toCode, CurrencyPair $pair): array
    {
        return [
            'success' => false,
            'message' => "Currency pair {$fromCode} → {$toCode} already exists.",
            'pair' => $pair
        ];
    }
    
    /**
     * Create a success response
     * 
     * @param string $fromCode From currency code
     * @param string $toCode To currency code
     * @param CurrencyPair $pair Created pair
     * @return array Success response
     */
    private function createSuccessResponse(string $fromCode, string $toCode, CurrencyPair $pair): array
    {
        return [
            'success' => true,
            'message' => "Currency pair {$fromCode} → {$toCode} created successfully!",
            'pair' => $pair
        ];
    }
}
