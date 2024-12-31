<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

use Maginium\Framework\Support\Carbon;

/**
 * Class ArrayLock.
 *
 * This class represents a lock that is stored in an in-memory array.
 * It extends the Lock class and provides the implementation for acquiring,
 * releasing, and managing locks in an array cache store.
 */
class ArrayLock extends Lock
{
    /**
     * The parent array cache store where the locks are stored.
     */
    protected ArrayStore $store;

    /**
     * ArrayLock constructor.
     *
     * This constructor initializes the lock instance, accepts the cache store,
     * lock name, expiration time in seconds, and an optional owner identifier.
     *
     * @param  ArrayStore  $store  The cache store that holds the locks.
     * @param  string  $name  The unique name for the lock.
     * @param  int  $seconds  The expiration time for the lock in seconds.
     * @param  string|null  $owner  The identifier for the lock owner, optional.
     */
    public function __construct($store, $name, $seconds, $owner = null)
    {
        // Call the parent constructor to initialize the lock's basic properties.
        parent::__construct($name, $seconds, $owner);

        // Store the reference to the ArrayStore where the locks are saved.
        $this->store = $store;
    }

    /**
     * Attempt to acquire the lock.
     *
     * This method attempts to acquire the lock by checking if the lock already exists
     * and if the expiration time is in the future. If the lock exists and is still valid,
     * the lock acquisition fails. Otherwise, it sets the lock with the expiration time.
     *
     * @return bool True if the lock was successfully acquired, false otherwise.
     */
    public function acquire(): bool
    {
        // Get the expiration time of the current lock or default to the next second.
        $expiration = $this->store->locks[$this->name]['expiresAt'] ?? Carbon::now()->addSecond();

        // If the lock exists and has not expired, acquisition fails.
        if ($this->exists() && $expiration->isFuture()) {
            return false;
        }

        // Set the lock with the owner and expiration time.
        $this->store->locks[$this->name] = [
            'owner' => $this->owner,
            'expiresAt' => $this->seconds === 0 ? null : Carbon::now()->addSeconds($this->seconds),
        ];

        // Successfully acquired the lock.
        return true;
    }

    /**
     * Release the lock.
     *
     * This method releases the lock if the lock exists and is owned by the current process.
     * If the lock cannot be released, it returns false.
     *
     * @return bool True if the lock was released, false otherwise.
     */
    public function release(): bool
    {
        // If the lock doesn't exist, it cannot be released.
        if (! $this->exists()) {
            return false;
        }

        // If the lock is not owned by the current process, release fails.
        if (! $this->isOwnedByCurrentProcess()) {
            return false;
        }

        // Force release of the lock regardless of ownership.
        $this->forceRelease();

        return true;
    }

    /**
     * Force release the lock, disregarding ownership.
     *
     * This method directly removes the lock from the store without checking the ownership.
     *
     * @return void
     */
    public function forceRelease()
    {
        // Unset the lock entry from the store.
        unset($this->store->locks[$this->name]);
    }

    /**
     * Determine if the current lock exists.
     *
     * This method checks if the lock exists in the store.
     * It returns true if the lock exists, false otherwise.
     *
     * @return bool True if the lock exists, false otherwise.
     */
    protected function exists(): bool
    {
        return isset($this->store->locks[$this->name]);
    }

    /**
     * Retrieve the current owner of the lock.
     *
     * This method returns the identifier of the current owner of the lock.
     * If the lock does not exist, it returns an empty string.
     *
     * @return string The owner identifier of the lock, or an empty string if the lock does not exist.
     */
    protected function getCurrentOwner(): string
    {
        // If the lock doesn't exist, return an empty string.
        if (! $this->exists()) {
            return '';
        }

        // Return the current owner of the lock from the store.
        return $this->store->locks[$this->name]['owner'];
    }
}
