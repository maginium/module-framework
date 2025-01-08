<?php

declare(strict_types=1);

namespace Maginium\Framework\Redis;

use Illuminate\Contracts\Cache\Lock;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Cache\RedisLockFactory;
use Maginium\Framework\Redis\Helpers\Data as RedisHelper;
use Maginium\Framework\Redis\Interfaces\RedisInterface;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use Predis\Client;
use Predis\ClientFactory;
use Predis\Response\Status as ClientStatus;

/**
 * Class RedisManager.
 *
 * Manages Redis client instances, providing methods to interact with the Redis database,
 * handle Redis locks, and integrate logging for debugging and performance monitoring.
 */
class RedisManager implements RedisInterface
{
    /**
     * @var Client|null The Redis client instance.
     */
    protected ?Client $client = null;

    /**
     * @var RedisLockFactory The factory for creating Redis locks.
     */
    protected RedisLockFactory $redisLockFactory;

    /**
     * @var ClientFactory The factory for creating Redis client instances.
     */
    protected ClientFactory $clientFactory;

    /**
     * RedisManager constructor.
     *
     * Initializes the Redis manager with factories for creating Redis client and lock instances.
     *
     * @param ClientFactory $clientFactory The ClientFactory instance.
     * @param RedisLockFactory $redisLockFactory The RedisLockFactory instance.
     */
    public function __construct(ClientFactory $clientFactory, RedisLockFactory $redisLockFactory)
    {
        $this->clientFactory = $clientFactory;
        $this->redisLockFactory = $redisLockFactory;

        // Set logger class name for contextual logging
        Log::setClassName(static::class);
    }

    /**
     * Initializes the Redis client, checks its health, and returns the client instance.
     *
     * This method performs the following:
     * 1. Initializes the Redis client with the Guzzle client.
     * 2. Checks if the Redis service is healthy by querying its settings.
     *
     * @throws Exception If the Redis client cannot be initialized or is unhealthy.
     *
     * @return Client The initialized Redis client instance.
     */
    public function init(): Client
    {
        try {
            // Step 1: Initialize the Redis client using the Guzzle client.
            $this->client = $this->build();

            // Step 3: Ensure the Redis service is healthy before returning the client.
            if (! $this->isHealthy()) {
                throw RuntimeException::make('Redis service is not healthy.');
            }

            return $this->client;
        } catch (Exception $e) {
            // Log the exception with detailed information and rethrow it.
            Log::error(Str::format('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));

            throw $e;
        }
    }

    /**
     * Retrieves the Redis client instance.
     *
     * Initializes the client if not already connected, and returns the instance.
     *
     * @throws Exception If an error occurs during client or initialization.
     *
     * @return Client|null The Redis client instance.
     */
    public function getClient(): ?Client
    {
        try {
            // Initialize the client if not already connected
            if (! $this->client || ! $this->client->isConnected()) {
                $this->init();
            }

            return $this->client;
        } catch (Exception $e) {
            // Log the error and rethrow
            Log::error(__('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));

            throw $e;
        }
    }

    /**
     * Checks if the Redis service is healthy.
     *
     * @return bool True if the Redis service is healthy, false otherwise.
     */
    public function isHealthy(): bool
    {
        try {
            // Attempt to ping the Redis server
            $statusResponse = $this->client->ping();

            // Check the type of $statusResponse and handle accordingly
            if (Validator::isString($statusResponse)) {
                // If $statusResponse is a string, convert to uppercase
                return Str::upper($statusResponse) === 'PONG';
            }

            if ($statusResponse instanceof ClientStatus) {
                // If $statusResponse is an instance of ClientStatus, access the payload
                return Str::upper($statusResponse->getPayload()) === 'PONG';
            }

            // Handle other cases or return false if the type is unexpected
            return false;
        } catch (Exception $e) {
            // Log any exceptions that occur during the retrieval process
            Log::error(__('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));

            return false;
        }
    }

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
    public function lock(string $name, int $seconds = 0, ?string $owner = null): Lock
    {
        // Validate the lock duration to ensure it's non-negative.
        if ($seconds < 0) {
            throw InvalidArgumentException::make(__('Lock duration must be a non-negative integer.'));
        }

        // Build the parameters array for lock creation.
        $parameters = [
            'name' => $name,
            'owner' => $owner,
            'seconds' => $seconds,
        ];

        // Create and return the lock instance using the redis lock factory.
        return $this->redisLockFactory->create($parameters);
    }

    /**
     * Initializes the Redis client with required configuration values.
     *
     * This method creates a Redis client instance using configuration values
     * retrieved from the RedisHelper class, which manages the Redis app credentials.
     *
     * @throws Exception If an error occurs while initializing the Redis client.
     *
     * @return Client The initialized Redis client.
     */
    private function build(): Client
    {
        // Fetch configuration values required to initialize the Redis client.
        $config = RedisHelper::getConfig();

        // Create a new Redis client using the provided configuration.
        $redisClient = $this->clientFactory->create($config);

        // Return the initialized Redis client.
        return $redisClient;
    }

    /**
     * Dynamically handles method calls to the Redis client.
     *
     * Delegates method calls to the Redis client if the method is not defined in the manager.
     *
     * @param  string $method The name of the method being called.
     * @param  array $parameters The parameters passed to the method.
     *
     * @return mixed The result of the method call on the Redis client.
     */
    public function __call(string $method, array $parameters)
    {
        // Call the method on the Redis client instance
        return $this->getClient()->{$method}(...$parameters);
    }
}
