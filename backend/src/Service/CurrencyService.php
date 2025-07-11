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
    
    /**
     * Prepare currency data for display with formatted information
     * 
     * @param array $result Result from fetchCurrencies
     * @return array Display data with title, summary, and table rows
     */
    public function prepareCurrencyDisplayData(array $result): array
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
    
    public function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        $rates = $this->apiClient->getLatestRates($fromCurrency, [$toCurrency]);
        
        if (!isset($rates[$toCurrency])) {
            throw new \RuntimeException("Could not fetch exchange rate for {$fromCurrency} → {$toCurrency}");
        }
        
        return $rates[$toCurrency];
    }
    
    /**
     * List currencies with optional filtering by code
     * 
     * @param string|null $filterCode Optional currency code filter
     * @return array Result with currencies and context information
     */
    public function listCurrencies(?string $filterCode = null): array
    {
        $repository = $this->entityManager->getRepository(CurrencyData::class);
        
        $qb = $repository->createQueryBuilder('c')
            ->orderBy('c.code', 'ASC');
            
        if ($filterCode) {
            $filterCode = strtoupper($filterCode);
            $qb->andWhere('c.code LIKE :code')
               ->setParameter('code', $filterCode . '%');
        }
        
        $currencies = $qb->getQuery()->getResult();
        
        $title = 'All currencies';
        if ($filterCode) {
            $title = "Currencies matching code: {$filterCode}";
        }
        
        return [
            'currencies' => $currencies,
            'title' => $title,
            'filterCode' => $filterCode,
            'count' => count($currencies)
        ];
    }
    
    /**
     * Prepare currency list data for display
     * 
     * @param array $result Result from listCurrencies
     * @return array Display data with title, table rows, and summary
     */
    public function prepareCurrencyListDisplayData(array $result): array
    {
        $currencies = $result['currencies'];
        $title = $result['title'];
        $filterCode = $result['filterCode'];
        $count = $result['count'];
        
        $displayData = [
            'title' => $title,
            'rows' => [],
            'summary' => '',
            'count' => $count,
            'isEmpty' => ($count === 0)
        ];
        
        if ($displayData['isEmpty']) {
            if ($filterCode) {
                $displayData['summary'] = "No currencies found matching code {$filterCode}";
            } else {
                $displayData['summary'] = 'No currencies found in the database';
            }
            return $displayData;
        }
        
        foreach ($currencies as $currency) {
            $displayData['rows'][] = [
                $currency->getCode(),
                $currency->getName(),
                $currency->getSymbol(),
                $currency->getSymbolNative(),
                $currency->getDecimalDigits(),
            ];
        }
        
        $displayData['summary'] = sprintf('Found %d currency/currencies', $count);
        
        return $displayData;
    }
}
