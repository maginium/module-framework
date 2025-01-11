<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Locks;

use Maginium\Framework\Cache\Lock;
use Maginium\Framework\Cache\LuaScripts;
use Maginium\Framework\Redis\Interfaces\RedisInterface;
use Predis\Client;

/**
 * Class RedisLock.
 *
 * This class represents a lock that is stored in redis.
 * It extends the Lock class and provides the implementation for acquiring,
 * releasing, and managing locks in an array cache store.
 */
class RedisLock extends Lock
{
    /**
     * Redis client instance for cache operations.
     *
     * @var Client
     */
    protected RedisInterface $redis;

    /**
     * ArrayLock constructor.
     *
     * This constructor initializes the lock instance, accepts the cache store,
     * lock name, expiration time in seconds, and an optional owner identifier.
     *
     * @param RedisInterface $redis  The cache redis that holds the locks.
     * @param string $name  The unique name for the lock.
     * @param int $seconds  The expiration time for the lock in seconds.
     * @param string|null $owner  The identifier for the lock owner, optional.
     */
    public function __construct(RedisInterface $redis, string $name, int $seconds, ?string $owner = null)
    {
        // Call the parent constructor to initialize the lock's basic properties.
        parent::__construct($name, $seconds, $owner);

        // Store the reference to the ArrayStore where the locks are saved.
        $this->redis = $redis->getClient();
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
        if ($this->seconds > 0) {
            return $this->redis->set($this->name, $this->owner, 'EX', $this->seconds, 'NX') === true;
        }

        return $this->redis->setnx($this->name, $this->owner) === 1;
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
        return (bool)$this->redis->eval(LuaScripts::releaseLock(), 1, $this->name, $this->owner);
    }

    /**
     * Force release the lock, disregarding ownership.
     *
     * This method directly removes the lock from the store without checking the ownership.
     *
     * @return void
     */
    public function forceRelease(): void
    {
        $this->redis->del($this->name);
    }

    /**
     * Get the name of the Redis connection being used to manage the lock.
     *
     * @return string
     */
    public function getConnectionName(): string
    {
        return $this->redis->getName();
    }

    /**
     * Returns the owner value written into the driver for this lock.
     *
     * @return string
     */
    protected function getCurrentOwner(): string
    {
        return $this->redis->get($this->name);
    }
}
