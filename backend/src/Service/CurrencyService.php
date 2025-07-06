<?php

namespace App\Service;

use App\Entity\CurrencyData;
use Doctrine\ORM\EntityManagerInterface;

class CurrencyService
{
    private string $apiKey;
    private string $apiBaseUrl = 'https://api.freecurrencyapi.com/v1';

    public function __construct(
        private EntityManagerInterface $entityManager,
        string $apiKey
    ) {
        $this->apiKey = $apiKey;
    }

    public function fetchCurrencies(array $currencyCodes = []): array
    {
        $url = "{$this->apiBaseUrl}/currencies?apikey={$this->apiKey}";
        
        if (!empty($currencyCodes)) {
            $currencyParam = implode('%2C', array_map('strtoupper', $currencyCodes));
            $url .= "&currencies={$currencyParam}";
        }
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($err) {
            throw new \RuntimeException('cURL Error: ' . $err);
        }
        
        if ($statusCode !== 200) {
            throw new \RuntimeException('API Error: Received status code ' . $statusCode);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['data']) || !is_array($data['data'])) {
            throw new \RuntimeException('Invalid response from currency API');
        }

        $stats = $this->saveCurrencies($data['data']);
        
        return [
            'currencies' => $data['data'],
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
