<?php

namespace App\Service\Command;

use App\Entity\CurrencyPair;
use App\Repository\CurrencyPairRepository;

class PairObserveStatusService
{
    public function __construct(
        private CurrencyPairRepository $currencyPairRepository
    ) {
    }
    
    /**
     * Find a currency pair by ID
     * 
     * @param int $id Currency pair ID
     * @return CurrencyPair|null
     */
    private function findPairById(int $id): ?CurrencyPair
    {
        return $this->currencyPairRepository->findById($id);
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
    public function execute(int $id, bool $status): array
    {
        $currencyPair = $this->findPairById($id);
        
        if (!$currencyPair) {
            return $this->createErrorResponse($id);
        }
        
        $oldStatus = $currencyPair->isObserve();
        
        if ($oldStatus !== $status) {
            $this->updatePairStatus($currencyPair, $status);
        }
        
        return $this->createSuccessResponse($currencyPair, $oldStatus, $status);
    }
    
    /**
     * Update the observe status of a currency pair
     * 
     * @param CurrencyPair $currencyPair Currency pair to update
     * @param bool $status New observe status
     * @return void
     */
    private function updatePairStatus(CurrencyPair $currencyPair, bool $status): void
    {
        $this->currencyPairRepository->updateObserveStatus($currencyPair, $status);
    }
    
    /**
     * Create error response for currency pair not found
     * 
     * @param int $id Currency pair ID that was not found
     * @return array Error response
     */
    private function createErrorResponse(int $id): array
    {
        return [
            'success' => false,
            'message' => "Currency pair with ID {$id} not found."
        ];
    }
    
    /**
     * Create success response for status change
     * 
     * @param CurrencyPair $currencyPair Currency pair that was updated
     * @param bool $oldStatus Previous observe status
     * @param bool $newStatus New observe status
     * @return array Success response
     */
    private function createSuccessResponse(CurrencyPair $currencyPair, bool $oldStatus, bool $newStatus): array
    {
        $fromCode = $currencyPair->getCurrencyFrom()->getCode();
        $toCode = $currencyPair->getCurrencyTo()->getCode();
        $id = $currencyPair->getId();
        
        $statusText = $newStatus ? 'observed' : 'not observed';
        $oldStatusText = $oldStatus ? 'observed' : 'not observed';
        
        return [
            'success' => true,
            'message' => "Currency pair {$fromCode} → {$toCode} (ID: {$id}) status changed from {$oldStatusText} to {$statusText}.",
            'pair' => $currencyPair,
            'oldStatus' => $oldStatus,
            'newStatus' => $newStatus,
            'fromCode' => $fromCode,
            'toCode' => $toCode
        ];
    }
}
