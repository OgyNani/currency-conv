<?php

namespace App\Service;

use App\Entity\CurrencyData;
use App\Entity\CurrencyPair;
use Doctrine\ORM\EntityManagerInterface;

class CurrencyPairService
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
    public function findCurrencyByCode(string $code): ?CurrencyData
    {
        return $this->entityManager->getRepository(CurrencyData::class)->findOneBy(['code' => $code]);
    }
    
    /**
     * Find a currency pair by ID
     * 
     * @param int $id Currency pair ID
     * @return CurrencyPair|null
     */
    public function findPairById(int $id): ?CurrencyPair
    {
        return $this->entityManager->getRepository(CurrencyPair::class)->find($id);
    }
    
    /**
     * Check if a currency pair already exists
     * 
     * @param CurrencyData $fromCurrency
     * @param CurrencyData $toCurrency
     * @return CurrencyPair|null
     */
    public function findExistingPair(CurrencyData $fromCurrency, CurrencyData $toCurrency): ?CurrencyPair
    {
        return $this->entityManager->getRepository(CurrencyPair::class)->findOneBy([
            'currencyFrom' => $fromCurrency,
            'currencyTo' => $toCurrency
        ]);
    }
    
    /**
     * Create a new currency pair
     * 
     * @param CurrencyData $fromCurrency From currency
     * @param CurrencyData $toCurrency To currency
     * @param bool $observe Whether to observe this pair for rate updates
     * @return CurrencyPair
     */
    public function createPair(CurrencyData $fromCurrency, CurrencyData $toCurrency, bool $observe = true): CurrencyPair
    {
        $currencyPair = new CurrencyPair();
        $currencyPair->setCurrencyFrom($fromCurrency);
        $currencyPair->setCurrencyTo($toCurrency);
        $currencyPair->setObserve($observe);
        
        $this->entityManager->persist($currencyPair);
        $this->entityManager->flush();
        
        return $currencyPair;
    }
    
    /**
     * List currency pairs with optional filtering by currency code
     * 
     * @param string|null $filterCode Optional currency code filter (from or to)
     * @return array Result with pairs and context information
     */
    public function listCurrencyPairs(?string $filterCode = null): array
    {
        $repository = $this->entityManager->getRepository(CurrencyPair::class);
        
        $qb = $repository->createQueryBuilder('p')
            ->select('p', 'fromCurr', 'toCurr')
            ->leftJoin('p.currencyFrom', 'fromCurr')
            ->leftJoin('p.currencyTo', 'toCurr');
            
        if ($filterCode) {
            $filterCode = strtoupper($filterCode);
            $qb->where('fromCurr.code = :code OR toCurr.code = :code')
               ->setParameter('code', $filterCode);
        }
        
        $pairs = $qb->getQuery()->getResult();
        
        $title = 'All currency pairs';
        if ($filterCode) {
            $title = "Currency pairs involving {$filterCode}";
        }
        
        return [
            'pairs' => $pairs,
            'title' => $title,
            'filterCode' => $filterCode,
            'count' => count($pairs)
        ];
    }
    
    /**
     * Prepare currency pair list data for display
     * 
     * @param array $result Result from listCurrencyPairs
     * @return array Display data with title, table rows, and summary
     */
    public function prepareCurrencyPairDisplayData(array $result): array
    {
        $pairs = $result['pairs'];
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
                $displayData['summary'] = "No currency pairs found involving {$filterCode}";
            } else {
                $displayData['summary'] = 'No currency pairs found in the database';
            }
            return $displayData;
        }
        
        foreach ($pairs as $pair) {
            $displayData['rows'][] = [
                $pair->getId(),
                $pair->getCurrencyFrom()->getCode(),
                $pair->getCurrencyFrom()->getName(),
                $pair->getCurrencyTo()->getCode(),
                $pair->getCurrencyTo()->getName(),
                $pair->isObserve() ? 'true' : 'false'
            ];
        }
        
        $displayData['summary'] = sprintf('Found %d currency pair(s)', $count);
        
        return $displayData;
    }
    
    /**
     * Create a currency pair with validation
     * 
     * @param string $fromCode From currency code
     * @param string $toCode To currency code
     * @param bool $observe Whether to observe this pair for rate updates
     * @return array Result with status, message, and pair if created
     */
    public function createPairWithValidation(string $fromCode, string $toCode, bool $observe = true): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'pair' => null
        ];
        
        if ($fromCode === $toCode) {
            $result['message'] = 'From and To currencies cannot be the same.';
            return $result;
        }
        
        $fromCurrency = $this->findCurrencyByCode($fromCode);
        if (!$fromCurrency) {
            $result['message'] = "Currency '{$fromCode}' not found. Please fetch currencies first with app:fetch-currencies command.";
            return $result;
        }
        
        $toCurrency = $this->findCurrencyByCode($toCode);
        if (!$toCurrency) {
            $result['message'] = "Currency '{$toCode}' not found. Please fetch currencies first with app:fetch-currencies command.";
            return $result;
        }
        
        $existingPair = $this->findExistingPair($fromCurrency, $toCurrency);
        if ($existingPair) {
            $result['message'] = "Currency pair {$fromCode} → {$toCode} already exists.";
            $result['pair'] = $existingPair;
            return $result;
        }
        
        $pair = $this->createPair($fromCurrency, $toCurrency, $observe);
        
        $result['success'] = true;
        $result['message'] = "Currency pair {$fromCode} → {$toCode} created successfully!";
        $result['pair'] = $pair;
        
        return $result;
    }
    
    /**
     * Parse status argument from string to boolean
     * 
     * @param string $statusArg Status argument as string ('true' or 'false')
     * @return array Result with parsed status and validation result
     */
    public function parseStatusArgument(string $statusArg): array
    {
        $statusArg = strtolower($statusArg);
        
        if ($statusArg === 'true') {
            return [
                'success' => true,
                'status' => true,
                'message' => ''
            ];
        } elseif ($statusArg === 'false') {
            return [
                'success' => true,
                'status' => false,
                'message' => ''
            ];
        } else {
            return [
                'success' => false,
                'status' => null,
                'message' => 'Status must be either "true" or "false".' 
            ];
        }
    }
    
    /**
     * Change the observe status of a currency pair
     * 
     * @param int $id Currency pair ID
     * @param bool $status New observe status
     * @return array Result with status, message, and pair information
     */
    public function changeObserveStatus(int $id, bool $status): array
    {
        $currencyPair = $this->findPairById($id);
        
        if (!$currencyPair) {
            return [
                'success' => false,
                'message' => "Currency pair with ID {$id} not found."
            ];
        }
        
        $fromCode = $currencyPair->getCurrencyFrom()->getCode();
        $toCode = $currencyPair->getCurrencyTo()->getCode();
        $oldStatus = $currencyPair->isObserve();
        
        if ($oldStatus !== $status) {
            $currencyPair->setObserve($status);
            $this->entityManager->flush();
        }
        
        $statusText = $status ? 'observed' : 'not observed';
        $oldStatusText = $oldStatus ? 'observed' : 'not observed';
        
        return [
            'success' => true,
            'message' => "Currency pair {$fromCode} → {$toCode} (ID: {$id}) status changed from {$oldStatusText} to {$statusText}.",
            'pair' => $currencyPair,
            'oldStatus' => $oldStatus,
            'newStatus' => $status,
            'fromCode' => $fromCode,
            'toCode' => $toCode
        ];
    }
}
