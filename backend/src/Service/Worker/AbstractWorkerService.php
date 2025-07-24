<?php

namespace App\Service\Worker;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Abstract base class for worker services
 */
abstract class AbstractWorkerService implements WorkerInterface
{
    protected bool $running = false;
    protected ?OutputInterface $output = null;
    protected string $lockDir;
    
    public function __construct(KernelInterface $kernel)
    {
        $this->lockDir = $kernel->getProjectDir() . '/var/worker_locks';
        
        // Ensure lock directory exists
        $filesystem = new Filesystem();
        if (!$filesystem->exists($this->lockDir)) {
            $filesystem->mkdir($this->lockDir);
        }
    }
    
    /**
     * Set the output interface for logging
     * 
     * @param OutputInterface $output
     * @return self
     */
    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function start(?int $iterations = null): void
    {
        $lockFile = $this->getLockFilePath();
        
        if ($this->isRunning()) {
            $this->log(sprintf('%s worker is already running', $this->getName()));
            return;
        }
        
        file_put_contents($lockFile, getmypid());
        $this->running = true;
        
        $this->log(sprintf('Starting %s worker (PID: %s)', $this->getName(), getmypid()));
        
        $count = 0;
        while ($this->shouldContinueRunning() && ($iterations === null || $count < $iterations)) {
            $this->process();
            
            if ($iterations !== null) {
                $count++;
                $this->log(sprintf('Completed iteration %d/%d', $count, $iterations));
            }
            
            if ($this->shouldContinueRunning() && ($iterations === null || $count < $iterations)) {
                sleep($this->getSleepInterval());
            }
        }
        
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
        
        $this->running = false;
        $this->log(sprintf('%s worker stopped', $this->getName()));
    }
    
    /**
     * {@inheritdoc}
     */
    public function stop(): void
    {
        $lockFile = $this->getLockFilePath();
        
        if (!file_exists($lockFile)) {
            $this->log(sprintf('%s worker is not running', $this->getName()));
            return;
        }
        
        $stopFile = $this->getStopFilePath();
        file_put_contents($stopFile, time());
        
        $this->log(sprintf('Stop signal sent to %s worker', $this->getName()));
        
        $maxWait = 5;
        $waited = 0;
        while (file_exists($lockFile) && $waited < $maxWait) {
            sleep(1);
            $waited++;
        }
        
        if (file_exists($lockFile)) {
            $this->log(sprintf('Warning: %s worker did not stop after %d seconds', $this->getName(), $maxWait));
        } else {
            $this->log(sprintf('%s worker has been stopped', $this->getName()));
        }
        
        if (file_exists($stopFile)) {
            unlink($stopFile);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function isRunning(): bool
    {
        return file_exists($this->getLockFilePath());
    }
    
    /**
     * Process a single worker iteration
     * 
     * @return void
     */
    abstract protected function process(): void;
    
    /**
     * Get the sleep interval between iterations in seconds
     * 
     * @return int
     */
    abstract protected function getSleepInterval(): int;
    
    /**
     * Log a message to the output if available
     * 
     * @param string $message
     * @return void
     */
    protected function log(string $message): void
    {
        if ($this->output) {
            $timestamp = (new \DateTime())->format('Y-m-d H:i:s');
            $this->output->writeln(sprintf('[%s] %s', $timestamp, $message));
        }
    }
    
    /**
     * Check if the worker should continue running
     * 
     * @return bool
     */
    protected function shouldContinueRunning(): bool
    {
        $stopFile = $this->getStopFilePath();
        if (file_exists($stopFile)) {
            $this->log(sprintf('Stop signal detected for %s worker', $this->getName()));
            return false;
        }
        
        return true;
    }
    
    /**
     * Get the path to the lock file for this worker
     * 
     * @return string
     */
    protected function getLockFilePath(): string
    {
        return sprintf('%s/%s.lock', $this->lockDir, $this->getName());
    }
    
    /**
     * Get the path to the stop file for this worker
     * 
     * @return string
     */
    protected function getStopFilePath(): string
    {
        return sprintf('%s/%s.stop', $this->lockDir, $this->getName());
    }
}
