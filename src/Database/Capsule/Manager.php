<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Capsule;

use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\FileSystemException;
use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Database\Connections\Connection;
use Maginium\Framework\Database\DatabaseManager;
use Maginium\Framework\Database\DatabaseManagerFactory;
use Maginium\Framework\Database\Eloquent\Model;
use Maginium\Framework\Database\Facades\AdminConfig;
use Maginium\Framework\Database\Interfaces\BuilderInterface;
use Maginium\Framework\Database\Interfaces\ConnectionInterface;
use Maginium\Framework\Database\Query\Builder as QueryBuilder;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Log;

class Manager
{
    /**
     * The current globally used instance.
     *
     * @var Manager
     */
    protected static $instance;

    /**
     * The database manager instance.
     *
     * @var DatabaseManager
     */
    protected $manager;

    /**
     * Admin configuration handler.
     *
     * @var AdminConfig
     */
    protected AdminConfig $adminConfig;

    /**
     * Magento event manager instance for handling events.
     *
     * @var ManagerInterface
     */
    protected ManagerInterface $eventManager;

    /**
     * Manager constructor.
     *
     * Initializes required dependencies and creates a database manager instance.
     *
     * @param AdminConfig $adminConfig
     * @param ManagerInterface $eventManager
     * @param DatabaseManagerFactory $databaseManager
     */
    public function __construct(
        AdminConfig $adminConfig,
        ManagerInterface $eventManager,
        DatabaseManagerFactory $databaseManager,
    ) {
        $this->adminConfig = $adminConfig;
        $this->eventManager = $eventManager;

        // Create the database manager instance.
        $this->manager = $databaseManager->create();
    }

    /**
     * Get a connection instance from the global manager.
     *
     * @param  string|null  $connection
     *
     * @return ConnectionInterface
     */
    public static function connection($connection = null): ConnectionInterface
    {
        return static::$instance->getConnection($connection);
    }

    /**
     * Get a fluent query builder instance.
     *
     * @param  Closure|QueryBuilder|string  $table
     * @param  string|null  $as
     * @param  string|null  $connection
     *
     * @return QueryBuilder
     */
    public static function table($table, $as = null, $connection = null): QueryBuilder
    {
        /** @var Connection $connection */
        $connection = static::$instance->connection($connection);

        return $connection->table($table, $as);
    }

    /**
     * Get a schema builder instance.
     *
     * @param  string|null  $connection
     *
     * @return BuilderInterface
     */
    public static function schema($connection = null): BuilderInterface
    {
        /** @var Connection $connection */
        $connection = static::$instance->connection($connection);

        return $connection->getSchemaBuilder();
    }

    /**
     * Make this capsule instance available globally.
     *
     * @return void
     */
    public function setAsGlobal(): void
    {
        static::$instance = $this;
    }

    /**
     * Get a registered connection instance.
     *
     * @param  string|null  $name
     *
     * @return ConnectionInterface
     */
    public function getConnection($name = null): ConnectionInterface
    {
        return $this->manager->connection($name);
    }

    /**
     * Register a connection with the manager.
     *
     * @param  array  $config
     * @param  string  $name
     *
     * @return void
     */
    public function addConnection(array $config, $name = 'default'): void
    {
        // Fetch current connections and add the new one.
        $connections = Config::driver(ConfigDrivers::DEPLOYMENT)->get('db/connection', []);
        $connections[$name] = $config;

        // Save the updated configuration.
        $this->saveDatabaseConfig(['connection' => $connections]);
    }

    /**
     * Bootstrap Eloquent so it is ready for usage.
     *
     * @return void
     */
    public function bootEloquent(): void
    {
        // Set the connection resolver for Eloquent.
        Model::setConnectionResolver($this->manager);

        // Optionally, set the event dispatcher for Eloquent models.
        // Model::setEventDispatcher($this->eventManager);
    }

    /**
     * Set the fetch mode for the database connections.
     *
     * @param int $fetchMode Fetch mode to set (e.g., PDO::FETCH_ASSOC).
     *
     * @return static
     */
    public function setFetchMode(int $fetchMode): static
    {
        $this->saveDatabaseConfig(['fetch_mode' => $fetchMode]);

        return $this;
    }

    /**
     * Get the database manager instance.
     *
     * @return DatabaseManager
     */
    public function getDatabaseManager(): DatabaseManager
    {
        return $this->manager;
    }

    /**
     * Get the current event dispatcher instance.
     *
     * @return ManagerInterface
     */
    public function getEventDispatcher(): ManagerInterface
    {
        return $this->eventManager;
    }

    /**
     * Save database-related configuration settings.
     *
     * @param array $data Configuration data to save.
     *
     * @throws FileSystemException If saving the configuration fails.
     *
     * @return void
     */
    private function saveDatabaseConfig(array $data): void
    {
        try {
            // Save the provided configuration into the appropriate file pool.
            $this->adminConfig->save(
                [
                    ConfigFilePool::APP_ENV => ['db' => $data],
                ],
            );
        } catch (FileSystemException $e) {
            // Log the error and re-throw the exception for handling by the caller.
            Log::error('Failed to save database config: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string  $method
     * @param  array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters): mixed
    {
        return static::connection()->{$method}(...$parameters);
    }
}
