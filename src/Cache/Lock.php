<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Sleep;
use Maginium\Framework\Cache\Interfaces\LockInterface;
use Maginium\Framework\Support\Facades\Date;
use Maginium\Framework\Support\Str;

/**
 * Abstract class representing a lock mechanism.
 *
 * This class provides basic functionality for locking resources in a cache.
 * Subclasses must implement the specific locking mechanism.
 */
abstract class Lock implements LockInterface
{
    // Trait that provides helper methods to interact with time-related operations.
    use InteractsWithTime;

    /**
     * The name of the lock.
     */
    protected string $name;

    /**
     * The number of seconds the lock should be maintained.
     */
    protected int $seconds;

    /**
     * The scope identifier of this lock.
     */
    protected string $owner;

    /**
     * The number of milliseconds to wait before re-attempting to acquire a lock while blocking.
     */
    protected int $sleepMilliseconds = 250;

    /**
     * Create a new lock instance.
     *
     * @param  string  $name  The name of the lock.
     * @param  int  $seconds  The duration in seconds for which the lock should be held.
     * @param  string|null  $owner  An optional identifier for the owner of the lock.
     */
    public function __construct(string $name, int $seconds, ?string $owner = null)
    {
        // If no owner is provided, generate a random one
        $this->name = $name;
        $this->seconds = $seconds;
        $this->owner = $owner ?? Str::random();
    }

    /**
     * Attempt to acquire the lock.
     *
     * @param  callable|null  $callback
     *
     * @return mixed
     */
    public function get($callback = null)
    {
        $result = $this->acquire();

        if ($result && is_callable($callback)) {
            try {
                return $callback();
            } finally {
                // Ensure the lock is released after the callback execution
                $this->release();
            }
        }

        // Return true if the lock was acquired but no callback was provided
        return $result;
    }

    /**
     * Attempt to acquire the lock.
     *
     * This method should be implemented by subclasses to define
     * how to acquire the lock.
     *
     * @return bool True if the lock was successfully acquired, false otherwise.
     */
    abstract public function acquire(): bool;

    /**
     * Release the lock.
     *
     * This method should be implemented by subclasses to define
     * how to release the lock.
     *
     * @return bool True if the lock was released, false otherwise.
     */
    abstract public function release(): bool;

    /**
     * Attempt to acquire the lock for the given number of seconds.
     *
     * @param  int  $seconds
     * @param  callable|null  $callback
     *
     * @return mixed
     */
    public function block($seconds, $callback = null)
    {
        // Record the starting time for the block operation
        $starting = ((int)Date::now()->format('Uu')) / 1000;

        // Convert seconds to milliseconds
        $milliseconds = $seconds * 1000;

        // Keep attempting to acquire the lock until successful or timeout occurs
        while (! $this->acquire()) {
            $now = ((int)Date::now()->format('Uu')) / 1000;

            // Check if the blocking time has exceeded the specified limit
            if (($now + $this->sleepMilliseconds - $milliseconds) >= $starting) {
                // Throw exception if timeout is reached
                throw new LockTimeoutException;
            }

            // Sleep before retrying
            Sleep::usleep($this->sleepMilliseconds * 1000);
        }

        // If a callback is provided, execute it and release the lock after
        if (is_callable($callback)) {
            try {
                return $callback();
            } finally {
                $this->release();
            }
        }

        // Return true if the lock was acquired without a callback
        return true;
    }

    /**
     * Get the current owner of the lock.
     *
     * @return string The identifier of the current lock owner.
     */
    public function owner(): string
    {
        return $this->owner;
    }

    /**
     * Determine if the current process owns the lock.
     *
     * @return bool True if the lock is owned by the current process, false otherwise.
     */
    public function isOwnedByCurrentProcess(): bool
    {
        return $this->isOwnedBy($this->owner);
    }

    /**
     * Determine if the lock is owned by the specified identifier.
     *
     * @param  string|null  $owner  The owner identifier to check against.
     *
     * @return bool True if the lock is owned by the specified identifier, false otherwise.
     */
    public function isOwnedBy(?string $owner): bool
    {
        // Compare the current owner with the given owner
        return $this->getCurrentOwner() === $owner;
    }

    /**
     * Set the number of milliseconds to sleep between blocked lock acquisition attempts.
     *
     * @param  int  $milliseconds  The sleep duration in milliseconds.
     *
     * @return $this Fluent interface for method chaining.
     */
    public function betweenBlockedAttemptsSleepFor(int $milliseconds): self
    {
        // Set the sleep duration
        $this->sleepMilliseconds = $milliseconds;

        // Return the current instance for chaining
        return $this;
    }

    /**
     * Retrieve the current owner value stored in the lock driver.
     *
     * This method should be implemented by subclasses to define
     * how to retrieve the current owner of the lock.
     *
     * @return string The owner identifier of the lock.
     */
    abstract protected function getCurrentOwner(): string;
}
