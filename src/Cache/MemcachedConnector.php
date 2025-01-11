<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

use InvalidArgumentException;
use Memcached;
use MemcachedFactory;

/**
 * Class MemcachedConnector.
 *
 * This class is responsible for managing Memcached connections using configurable servers,
 * options, and SASL authentication. It adheres to Magento's factory pattern for creating
 * Memcached instances to ensure greater flexibility and testability.
 */
class MemcachedConnector
{
    /**
     * @var MemcachedFactory
     */
    private ?MemcachedFactory $memcachedFactory = null;

    /**
     * Constructor.
     *
     * @param MemcachedFactory $memcachedFactory Factory for creating Memcached instances.
     */
    public function __construct($memcachedFactory)
    {
        $this->memcachedFactory = $memcachedFactory;
    }

    /**
     * Establish a new Memcached connection with the given configuration.
     *
     * Initializes a Memcached instance, sets credentials, options, and adds server configurations.
     * If no servers are already configured, it populates the connection with the provided servers.
     *
     * @param array $servers List of servers with `host`, `port`, and optional `weight`.
     * @param string|null $connectionId  Optional connection ID for persistent Memcached connections.
     * @param array $options Array of Memcached options to configure the connection.
     * @param array $credentials Optional SASL credentials with `username` and `password`.

     *
     * @return Memcached  The configured Memcached instance.
     */
    public function connect(
        array $servers,
        ?string $connectionId = null,
        array $options = [],
        array $credentials = [],
    ): Memcached {
        $memcached = $this->getMemcached($connectionId, $credentials, $options);

        // Check if the connection already has servers configured.
        if (! $memcached->getServerList()) {
            // Add each server from the configuration to the Memcached instance.
            foreach ($servers as $server) {
                $this->addServerToMemcached($memcached, $server);
            }
        }

        return $memcached;
    }

    /**
     * Retrieve a configured Memcached instance.
     *
     * This method initializes a Memcached instance, sets SASL credentials, and applies options.
     *
     * @param  string|null $connectionId  Optional connection ID for persistent Memcached connections.
     * @param  array       $credentials   Optional SASL credentials with `username` and `password`.
     * @param  array       $options       Array of Memcached options to configure the connection.
     *
     * @return Memcached  The initialized Memcached instance.
     */
    protected function getMemcached(
        ?string $connectionId,
        array $credentials,
        array $options,
    ): Memcached {
        $memcached = $this->createMemcachedInstance($connectionId);

        // Set SASL credentials if provided.
        if (! empty($credentials)) {
            $this->setCredentials($memcached, $credentials);
        }

        // Apply options to the Memcached instance if any are provided.
        if (! empty($options)) {
            $memcached->setOptions($options);
        }

        return $memcached;
    }

    /**
     * Create a new Memcached instance using the factory pattern.
     *
     * If a connection ID is provided, the Memcached instance will persist across requests.
     * Otherwise, a new instance will be created.
     *
     * @param  string|null $connectionId  Optional connection ID for persistent connections.
     *
     * @return Memcached  A new or persistent Memcached instance.
     */
    protected function createMemcachedInstance(?string $connectionId): Memcached
    {
        return $this->memcachedFactory->create(['connectionId' => $connectionId]);
    }

    /**
     * Configure SASL authentication credentials for the Memcached connection.
     *
     * This method sets the binary protocol and applies the provided SASL username and password.
     *
     * @param  Memcached $memcached    The Memcached instance to configure.
     * @param  array     $credentials  An array containing the `username` and `password`.
     *
     * @return void
     */
    protected function setCredentials(Memcached $memcached, array $credentials): void
    {
        [$username, $password] = $credentials;

        // Enable binary protocol for SASL authentication.
        $memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);

        // Set SASL authentication data.
        $memcached->setSaslAuthData($username, $password);
    }

    /**
     * Add a server configuration to the Memcached instance.
     *
     * This method extracts `host`, `port`, and `weight` from the server configuration
     * and adds it to the Memcached connection.
     *
     * @param  Memcached $memcached  The Memcached instance to configure.
     * @param  array     $server     The server configuration with `host`, `port`, and `weight`.
     *
     * @throws InvalidArgumentException if server configuration is incomplete.
     *
     * @return void
     */
    protected function addServerToMemcached(Memcached $memcached, array $server): void
    {
        // Ensure the server configuration contains the required keys.
        if (! isset($server['host'], $server['port'], $server['weight'])) {
            throw new InvalidArgumentException(
                'Server configuration must include host, port, and weight.',
            );
        }

        // Add the server to the Memcached instance.
        $memcached->addServer(
            $server['host'],
            $server['port'],
            $server['weight'],
        );
    }
}
