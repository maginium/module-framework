<?php

declare(strict_types=1);

namespace Maginium\Framework\Firestore\Interfaces;

use Google\Cloud\Firestore\FirestoreClient;
use Kreait\Firebase\Contract\Firestore;
use Throwable;

/**
 * Interface ClientInterface.
 *
 * Defines the contract for a Firestore client.
 */
interface ClientInterface
{
    /**
     * Initializes the Firestore client.
     *
     * @throws Throwable If an error occurs during initialization.
     *
     * @return Firestore The initialized Firestore client instance.
     */
    public function init(): Firestore;

    /**
     * Retrieves the Firestore client instance.
     *
     * @return FirestoreClient|null The Firestore client instance.
     */
    public function getClient(): ?FirestoreClient;

    /**
     * Checks if the Firestore service is healthy.
     *
     * @return bool True if the Firestore service is healthy, false otherwise.
     */
    public function isHealthy(): bool;
}
