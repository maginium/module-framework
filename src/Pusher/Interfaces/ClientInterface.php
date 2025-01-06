<?php

declare(strict_types=1);

namespace Maginium\Framework\Pusher\Interfaces;

use Maginium\Foundation\Exceptions\Exception;
use Pusher\Pusher;

/**
 * Interface ClientInterface.
 *
 * This interface defines the contract for a Pusher client service.
 * Any class implementing this interface should provide functionality to
 * initialize a Pusher client, retrieve the client, and check its health.
 */
interface ClientInterface
{
    /**
     * Initializes and returns a Pusher client instance.
     *
     * This method initializes the Pusher client with configuration values such as app ID, app key, secret,
     * and other settings, and returns the client instance. It should throw an exception if initialization fails.
     *
     * @throws Exception If the Pusher client cannot be initialized.
     *
     * @return Pusher The initialized Pusher client instance.
     */
    public function init(): Pusher;

    /**
     * Retrieves the Pusher client instance.
     *
     * If the client is not already initialized, this method should initialize the client
     * and then return it. If the client cannot be retrieved or initialized, it should throw an exception.
     *
     * @throws Exception If the Pusher client cannot be retrieved or initialized.
     *
     * @return Pusher The Pusher client instance.
     */
    public function getClient(): Pusher;

    /**
     * Checks if the Pusher service is healthy.
     *
     * This method queries the Pusher service to ensure it's healthy and operational.
     * It returns true if the service is healthy, or false if it's not.
     *
     * @return bool True if the Pusher service is healthy, false otherwise.
     */
    public function isHealthy(): bool;
}
