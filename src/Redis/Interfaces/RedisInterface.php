<?php

declare(strict_types=1);

namespace Maginium\Framework\Redis\Interfaces;

use Illuminate\Contracts\Cache\Lock;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Predis\Client;

/**
 * Interface RedisInterface.
 *
 * Defines the contract for a Redis client.
 */
interface RedisInterface
{
    /**
     * Initializes the Redis client.
     *
     * @throws Exception If an error occurs during initialization.
     *
     * @return Client The initialized Redis client instance.
     */
    public function init(): Client;

    /**
     * Retrieves the Redis client instance.
     *
     * @return Client|null The Redis client instance.
     */
    public function getClient(): ?Client;

    /**
     * Checks if the Redis service is healthy.
     *
     * @return bool True if the Redis service is healthy, false otherwise.
     */
    public function isHealthy(): bool;

    /**
     * Creates and returns a lock instance to manage concurrency.
     *
     * This method generates a lock with the specified parameters: a unique name, a duration (in seconds),
     * and an optional owner. The lock is useful for preventing race conditions when multiple processes
     * need to access shared resources.
     *
     * @param  string $name The unique identifier for the lock. This helps distinguish different locks.
     * @param  int $seconds The lock duration in seconds. If set to 0, the lock will not expire.
     * @param  string|null $owner The owner of the lock (optional). Used to identify the model that owns the lock.
     *
     * @throws InvalidArgumentException Throws if invalid parameters are provided, such as a negative duration.
     *
     * @return Lock Returns the created lock instance.
     */
    public function lock(string $name, int $seconds = 0, ?string $owner = null): Lock;
}
