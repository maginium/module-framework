<?php

declare(strict_types=1);

namespace Maginium\Framework\Pusher\Services;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientFactory as GuzzleClientFactory;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Pusher\Helpers\Data as PusherHelper;
use Maginium\Framework\Pusher\Interfaces\ClientInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Pusher\Pusher as PusherClient;
use Pusher\PusherFactory as ClientFactory;

/**
 * Class Client.
 *
 * Handles the initialization, client retrieval, and health checks for the Pusher service.
 * Provides methods for configuring and connecting to Pusher, checking its health, and ensuring
 * the client is ready for use.
 */
class Client implements ClientInterface
{
    /**
     * @var PusherClient|null The Pusher client instance.
     */
    protected ?PusherClient $client = null;

    /**
     * @var LoggerInterface Logger instance for logging errors and debug information.
     */
    protected LoggerInterface $logger;

    /**
     * @var ClientFactory Factory for creating Pusher client instances.
     */
    protected ClientFactory $clientFactory;

    /**
     * @var GuzzleClientFactory Factory for creating Guzzle client instances.
     */
    protected GuzzleClientFactory $guzzleClientFactory;

    /**
     * Client constructor.
     *
     * Initializes the Pusher client with the provided logger, Pusher client factory,
     * and Guzzle client factory. Sets up the logger class name for easier debugging.
     *
     * @param LoggerInterface $logger Logger instance.
     * @param ClientFactory $clientFactory Factory to create Pusher client instances.
     * @param GuzzleClientFactory $guzzleClientFactory Factory to create Guzzle client instances.
     */
    public function __construct(
        LoggerInterface $logger,
        ClientFactory $clientFactory,
        GuzzleClientFactory $guzzleClientFactory,
    ) {
        $this->logger = $logger;
        $this->clientFactory = $clientFactory;
        $this->guzzleClientFactory = $guzzleClientFactory;

        // Set the class name for logging purposes, making it easier to track logs.
        Log::setClassName(static::class);
    }

    /**
     * Initializes the Pusher client, checks its health, and returns the client instance.
     *
     * This method performs the following:
     * 1. Initializes a Guzzle HTTP client for making requests to Pusher.
     * 2. Initializes the Pusher client with the Guzzle client.
     * 3. Checks if the Pusher service is healthy by querying its settings.
     *
     * @throws Exception If the Pusher client cannot be initialized or is unhealthy.
     *
     * @return PusherClient The initialized Pusher client instance.
     */
    public function init(): PusherClient
    {
        try {
            // Step 1: Initialize Guzzle client for HTTP requests to Pusher.
            $guzzleClient = $this->initializeGuzzleClient();

            // Step 2: Initialize the Pusher client using the Guzzle client.
            $this->client = $this->initializeClient($guzzleClient);

            // Step 3: Ensure the Pusher service is healthy before returning the client.
            if (! $this->isHealthy()) {
                throw RuntimeException::make('Pusher service is not healthy.');
            }

            return $this->client;
        } catch (Exception $e) {
            // Log the exception with detailed information and rethrow it.
            Log::error(sprintf('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));

            throw $e;
        }
    }

    /**
     * Retrieves the Pusher client instance, initializing it if necessary.
     *
     * This method checks if the client is already initialized. If not, it calls
     * the init() method to initialize the client and return it.
     *
     * @throws Exception If the client could not be initialized or the service is unhealthy.
     *
     * @return PusherClient The Pusher client instance.
     */
    public function getClient(): PusherClient
    {
        if ($this->client === null) {
            // Initialize the client if it hasn't been initialized yet.
            $this->client = $this->init();
        }

        return $this->client;
    }

    /**
     * Checks if the Pusher service is healthy by querying its settings.
     *
     * This method makes an attempt to retrieve the Pusher settings to confirm
     * if the service is available and functional.
     *
     * @return bool True if the service is healthy, false otherwise.
     */
    public function isHealthy(): bool
    {
        try {
            // Attempt to retrieve Pusher settings to verify the service's health.
            return (bool)$this->client->getSettings();
        } catch (Exception $e) {
            // If health check fails, log the error and return false.
            Log::error(sprintf('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));

            return false;
        }
    }

    /**
     * Initializes a Guzzle HTTP client with necessary configurations.
     *
     * This method creates a Guzzle client configured with timeout, secure connection settings,
     * and custom headers required for communication with Pusher.
     *
     * @return GuzzleClient The configured Guzzle client.
     */
    private function initializeGuzzleClient(): GuzzleClient
    {
        // Return a new Guzzle client with predefined configurations.
        return $this->guzzleClientFactory->create([
            'connect_timeout' => 10,  // Timeout for establishing the connection (10 seconds).
            'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,  // Enforce secure TLS 1.2 connection.
            'timeout' => 30,  // Timeout for the overall request (30 seconds).
            'headers' => [
                'User-Agent' => 'Client',  // Custom User-Agent header to identify the client.
            ],
        ]);
    }

    /**
     * Initializes the Pusher client with required configuration values.
     *
     * This method creates a Pusher client instance using configuration values
     * retrieved from the PusherHelper class, which manages the Pusher app credentials.
     *
     * @param GuzzleClient $guzzleClient The Guzzle client to use for the requests.
     *
     * @throws Exception If an error occurs while initializing the Pusher client.
     *
     * @return PusherClient The initialized Pusher client.
     */
    private function initializeClient(GuzzleClient $guzzleClient): PusherClient
    {
        // Fetch configuration values required to initialize the Pusher client.
        $config = PusherHelper::getConfig();

        // Create the Pusher client using the configuration and Guzzle client
        $pusherClient = $this->clientFactory->create(Arr::merge($config, ['client' => $guzzleClient]));

        // If debugging is enabled, set the logger for the Pusher client.
        if (PusherHelper::getDebug()) {
            $pusherClient->setLogger($this->logger);
        }

        // Return the initialized Pusher client.
        return $pusherClient;
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
