<?php

namespace App\Service\Worker;

/**
 * Interface for worker services that can be started and stopped
 */
interface WorkerInterface
{
    /**
     * Start the worker process
     * 
     * @param int|null $iterations Number of iterations to run (null for infinite)
     * @return void
     */
    public function start(?int $iterations = null): void;
    
    /**
     * Stop the worker process
     * 
     * @return void
     */
    public function stop(): void;
    
    /**
     * Check if the worker is running
     * 
     * @return bool
     */
    public function isRunning(): bool;
    
    /**
     * Get the worker name
     * 
     * @return string
     */
    public function getName(): string;
}
