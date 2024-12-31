<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

use Maginium\Framework\Redis\Interfaces\RedisInterface;
use Predis\ClientInterface as RedisClientInterface;

/**
 * Class RedisLock.
 *
 * This class implements a locking mechanism using Redis to manage concurrency
 * in a distributed environment. It extends the base Lock class and provides
 * methods for acquiring, releasing, and managing locks with Redis.
 *
 * The RedisLock class allows for time-limited locks and supports
 * ownership checks to ensure that only the owner of a lock can release it.
 *
 * Usage:
 * - Create an instance of RedisLock with a Redis connection, lock name,
 *   expiration time, and optional owner identifier.
 * - Call the `acquire()` method to attempt to acquire the lock.
 * - Use the `release()` method to release the lock.
 * - Call `forceRelease()` to forcibly release the lock without ownership checks.
 */
class RedisLock extends Lock
{
    /**
     * The Redis factory implementation.
     *
     * This property holds the Redis connection instance used for lock operations.
     */
    protected RedisClientInterface $redis;

    /**
     * Create a new lock instance.
     *
     * Initializes the RedisLock with the provided Redis connection, lock name,
     * expiration time in seconds, and optional owner identifier.
     *
     * @param  RedisInterface  $redis  The Redis connection instance.
     * @param  string  $name  The name of the lock.
     * @param  int  $seconds  The duration in seconds for which the lock should be held.
     * @param  string|null  $owner  Optional owner identifier for the lock.
     *
     * @return void
     */
    public function __construct(RedisInterface $redis, string $name, int $seconds, ?string $owner = null)
    {
        // Call the parent constructor to initialize the lock with name, duration, and owner.
        parent::__construct($name, $seconds, $owner);

        // Set the Redis connection instance.
        $this->redis = $redis->getClient();
    }

    /**
     * Attempt to acquire the lock.
     *
     * This method attempts to set a lock in Redis with the specified name and owner.
     * It uses the Redis SET command with the NX (set if not exists) option to ensure
     * that the lock can only be acquired if it is not already held.
     *
     * If the lock has a defined expiration time, it uses the EX option to set
     * the expiration; otherwise, it falls back to using setnx for acquiring the lock.
     *
     * @return bool True if the lock was successfully acquired, false otherwise.
     */
    public function acquire(): bool
    {
        // If a duration is specified, attempt to acquire the lock with an expiration.
        if ($this->seconds > 0) {
            return $this->redis->set($this->name, $this->owner, 'EX', $this->seconds, 'NX') === true;
        }

        // If no expiration is specified, use setnx to acquire the lock.
        return $this->redis->setnx($this->name, $this->owner) === 1;
    }

    /**
     * Release the lock.
     *
     * This method attempts to release the lock by executing a Lua script that checks
     * if the current owner of the lock matches the owner identifier. It only releases
     * the lock if the owner matches, ensuring that locks cannot be released by other
     * processes.
     *
     * @return bool True if the lock was released, false otherwise.
     */
    public function release(): bool
    {
        // Execute the Lua script to release the lock atomically.
        return (bool)$this->redis->eval(LuaScripts::releaseLock(), 1, $this->name, $this->owner);
    }

    /**
     * Releases this lock in disregard of ownership.
     *
     * This method forcibly releases the lock without checking the owner,
     * effectively removing the lock from Redis.
     */
    public function forceRelease(): void
    {
        // Delete the lock from Redis, ignoring the ownership.
        $this->redis->del($this->name);
    }

    /**
     * Get the name of the Redis connection being used to manage the lock.
     *
     * This method returns the name of the Redis connection instance
     * associated with this lock, allowing for easy identification of the connection.
     *
     * @return string The name of the Redis connection.
     */
    public function getConnectionName(): string
    {
        return $this->redis->getName();
    }

    /**
     * Retrieve the current owner value stored in the lock driver.
     *
     * This method retrieves the current owner identifier of the lock from Redis.
     * It is intended to be used by subclasses to define how to check the owner.
     *
     * @return string The owner identifier of the lock.
     */
    protected function getCurrentOwner(): string
    {
        // Retrieve the owner identifier associated with the lock name from Redis.
        return $this->redis->get($this->name);
    }
}
