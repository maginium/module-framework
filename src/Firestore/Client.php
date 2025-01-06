<?php

declare(strict_types=1);

namespace Maginium\Framework\Firestore;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Cloud\Firestore\FirestoreClient;
use Kreait\Firebase\Contract\Firestore;
use Kreait\Firebase\Factory as KreaitClient;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Firestore\Helpers\Data as FirestoreHelper;
use Maginium\Framework\Firestore\Interfaces\ClientInterface;
use Maginium\Framework\Support\Facades\Log;

/**
 * Class Client.
 */
class Client implements ClientInterface
{
    /**
     * @var KreaitClient|null The Firestore client instance.
     */
    protected KreaitClient $client;

    /**
     * Client constructor.
     *
     * Initializes the Firestore client instance and sets the logger class name.
     */
    public function __construct()
    {
        // Set Logger class name for logging purposes
        Log::setClassName(static::class);
    }

    /**
     * Initializes the Firestore client.
     *
     * @throws Exception If an error occurs during initialization.
     *
     * @return Firestore The initialized Firestore client instance.
     */
    public function init(): Firestore
    {
        try {
            // Extract configuration values using list()
            $config = FirestoreHelper::getConfig();

            // Initialize Firebase
            $this->client = (new KreaitClient)
                ->withServiceAccount($config);

            // Check if the Firestore service is healthy before returning the client
            if ($this->isHealthy()) {
                // Initialize Firestore component
                return $this->client->createFirestore();
            }

            // Throw the exception
            throw RuntimeException::make('Firestore service is not healthy.');
        } catch (Exception $e) {
            // Log any exceptions that occur during the retrieval process
            Log::error(__('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));

            throw $e;
        }
    }

    /**
     * Retrieves the Firestore client instance.
     *
     * @return FirestoreClient|null The Firestore client instance.
     */
    public function getClient(): ?FirestoreClient
    {
        try {
            // Check if the client is already initialized
            if (! $this->client) {
                // Initialize the Firestore client
                $this->init();
            }

            // Initialize Firestore component
            $firestore = $this->client;

            /** @var Firestore $firestore */
            return $firestore->database();
        } catch (Exception $e) {
            // Log any exceptions that occur during the retrieval process
            Log::error(__('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));

            throw $e;
        }
    }

    /**
     * Checks if the Firestore service is healthy.
     *
     * @return bool True if the Firestore service is healthy, false otherwise.
     */
    public function isHealthy(): bool
    {
        try {
            // Attempt to ping the Redis server
            $statusResponse = $this->client->getDebugInfo();

            if (isset($statusResponse['credentialsType'])) {
                // If $statusResponse is an instance of ClientStatus, access the payload
                return $statusResponse['credentialsType'] === ServiceAccountCredentials::class;
            }

            // Handle other cases or return false if the type is unexpected
            return false;
        } catch (Exception $e) {
            // Log any exceptions that occur during the retrieval process
            Log::error(__('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));

            return false;
        }
    }
}
