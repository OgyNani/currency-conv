<?php

namespace App\Service;

use App\Entity\CurrencyData;
use App\Http\CurrencyApiClient;
use Doctrine\ORM\EntityManagerInterface;

class CurrencyService
{
    private CurrencyApiClient $apiClient;

    public function __construct(
        private EntityManagerInterface $entityManager,
        string $apiKey
    ) {
        $this->apiClient = new CurrencyApiClient($apiKey);
    }

    public function fetchCurrencies(array $currencyCodes = []): array
    {
        $currenciesData = $this->apiClient->getCurrencies($currencyCodes);
        $stats = $this->saveCurrencies($currenciesData);
        
        return [
            'currencies' => $currenciesData,
            'stats' => $stats
        ];
    }

    public function saveCurrencies(array $currenciesData): array
    {
        $repository = $this->entityManager->getRepository(CurrencyData::class);
        $stats = [
            'added' => 0,
            'updated' => 0,
            'new_currencies' => [],
            'updated_currencies' => []
        ];
        
        foreach ($currenciesData as $code => $currencyData) {
            $currency = $repository->findOneBy(['code' => $code]);
            $isNew = false;
            
            if (!$currency) {
                $currency = new CurrencyData();
                $currency->setCode($code);
                $isNew = true;
                $stats['added']++;
                $stats['new_currencies'][] = $code;
            } else {
                $stats['updated']++;
                $stats['updated_currencies'][] = $code;
            }
            
            $currency->setSymbol($currencyData['symbol']);
            $currency->setName($currencyData['name']);
            $currency->setSymbolNative($currencyData['symbol_native']);
            $currency->setDecimalDigits($currencyData['decimal_digits']);
            $currency->setRounding($currencyData['rounding']);
            $currency->setNamePlural($currencyData['name_plural']);
            $currency->setType($currencyData['type'] ?? null);
            
            $this->entityManager->persist($currency);
        }
        
        $this->entityManager->flush();
        return $stats;
    }
}
