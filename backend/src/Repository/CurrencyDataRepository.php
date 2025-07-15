<?php

namespace App\Repository;

use App\Entity\CurrencyData;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository for CurrencyData entity
 */
class CurrencyDataRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }
    
    /**
     * Find a currency by its code
     * 
     * @param string $code Currency code
     * @return CurrencyData|null
     */
    public function findByCode(string $code): ?CurrencyData
    {
        return $this->entityManager->getRepository(CurrencyData::class)->findOneBy(['code' => $code]);
    }
    
    /**
     * Create a new currency entity
     * 
     * @param string $code Currency code
     * @param array $currencyData Currency data from API
     * @return CurrencyData New currency entity
     */
    public function createCurrency(string $code, array $currencyData): CurrencyData
    {
        $currency = new CurrencyData(
            $code,
            $currencyData['symbol'],
            $currencyData['name'],
            $currencyData['symbol_native'],
            $currencyData['decimal_digits'],
            $currencyData['rounding'],
            $currencyData['name_plural'],
            $currencyData['type'] ?? null
        );
        
        $this->entityManager->persist($currency);
        
        return $currency;
    }
    
    /**
     * Update an existing currency entity
     * 
     * @param CurrencyData $currency Existing currency entity
     * @param array $currencyData Currency data from API
     * @return void
     */
    public function updateCurrency(CurrencyData $currency, array $currencyData): void
    {
        $currency->setSymbol($currencyData['symbol']);
        $currency->setName($currencyData['name']);
        $currency->setSymbolNative($currencyData['symbol_native']);
        $currency->setDecimalDigits($currencyData['decimal_digits']);
        $currency->setRounding($currencyData['rounding']);
        $currency->setNamePlural($currencyData['name_plural']);
        $currency->setType($currencyData['type'] ?? null);
        
        $this->entityManager->persist($currency);
    }
    
    /**
     * Save changes to the database
     * 
     * @return void
     */
    public function saveChanges(): void
    {
        $this->entityManager->flush();
    }
    
    /**
     * Find all currencies with optional code filtering
     * 
     * @param string|null $filterCode Optional currency code filter
     * @return array List of currency entities
     */
    public function findCurrencies(?string $filterCode = null): array
    {
        $qb = $this->entityManager->getRepository(CurrencyData::class)
            ->createQueryBuilder('c')
            ->orderBy('c.code', 'ASC');
            
        if ($filterCode) {
            $this->applyCodeFilter($qb, $filterCode);
        }
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Apply currency code filter to query builder
     * 
     * @param QueryBuilder $qb Query builder
     * @param string $filterCode Currency code to filter by
     * @return void
     */
    private function applyCodeFilter(QueryBuilder $qb, string $filterCode): void
    {
        $filterCode = strtoupper($filterCode);
        $qb->andWhere('c.code LIKE :code')
           ->setParameter('code', $filterCode . '%');
    }
}
