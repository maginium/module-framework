<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Locks;

use Maginium\Framework\Cache\Lock;
use Memcached;

/**
 * Class RedisLock.
 *
 * This class represents a lock that is stored in memcached.
 * It extends the Lock class and provides the implementation for acquiring,
 * releasing, and managing locks in an array cache store.
 */
class MemcachedLock extends Lock
{
    /**
     * The parent array cache memcached where the locks are memcachedd.
     */
    protected Memcached $memcached;

    /**
     * ArrayLock constructor.
     *
     * This constructor initializes the lock instance, accepts the cache memcached,
     * lock name, expiration time in seconds, and an optional owner identifier.
     *
     * @param Memcached $memcached  The cache memcached that holds the locks.
     * @param string $name  The unique name for the lock.
     * @param int $seconds  The expiration time for the lock in seconds.
     * @param string|null $owner  The identifier for the lock owner, optional.
     */
    public function __construct($memcached, string $name, int $seconds, ?string $owner = null)
    {
        // Call the parent constructor to initialize the lock's basic properties.
        parent::__construct($name, $seconds, $owner);

        // Store the reference to the Memcached where the locks are saved.
        $this->memcached = $memcached;
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
        return $this->memcached->add(
            $this->name,
            $this->owner,
            $this->seconds,
        );
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
        if ($this->isOwnedByCurrentProcess()) {
            return $this->memcached->delete($this->name);
        }

        return false;
    }

    /**
     * Force release the lock, disregarding ownership.
     *
     * This method directly removes the lock from the memcached without checking the ownership.
     *
     * @return void
     */
    public function forceRelease(): void
    {
        $this->memcached->delete($this->name);
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
        return $this->memcached->get($this->name);
    }
}
