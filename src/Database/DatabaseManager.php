<?php

declare(strict_types=1);

namespace Maginium\Framework\Database;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Magento\Framework\Event\ManagerInterface;
use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Database\Connectors\ConnectionFactory;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Str;
use PDO;
use RuntimeException;

/**
 * @mixin Connection
 */
class DatabaseManager implements ConnectionResolverInterface
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The database connection factory instance.
     *
     * @var ConnectionFactory
     */
    protected $factory;

    /**
     * The database connection factory instance.
     *
     * @var DatabaseTransactionsManager
     */
    protected $databaseTransactionsManager;

    /**
     * The database connection factory instance.
     *
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * The active connection instances.
     *
     * @var array<string, Connection>
     */
    protected $connections = [];

    /**
     * The custom connection resolvers.
     *
     * @var array<string, callable>
     */
    protected $extensions = [];

    /**
     * The callback to be executed to reconnect to a database.
     *
     * @var callable
     */
    protected $reconnector;

    /**
     * The custom Doctrine column types.
     *
     * @var array<string, array>
     */
    protected $doctrineTypes = [];

    /**
     * Create a new database manager instance.
     *
     * @param  ConnectionFactory  $factory
     *
     * @return void
     */
    public function __construct(ConnectionFactory $factory, DatabaseTransactionsManager $databaseTransactionsManager, ManagerInterface $eventManager)
    {
        $this->factory = $factory;
        $this->eventManager = $eventManager;
        $this->databaseTransactionsManager = $databaseTransactionsManager;

        // The reconnector callback is responsible for reconnecting a database connection
        $this->reconnector = function($connection) {
            // If the connection is in read / write mode, we will reconnect the read connection
            $this->reconnect($connection->getNameWithReadWriteType());
        };
    }

    /**
     * Get a database connection instance.
     *
     * @param  string|null  $name
     *
     * @return ConnectionInterface
     */
    public function connection($name = null): ConnectionInterface
    {
        [$database, $type] = $this->parseConnectionName($name);

        $name = $name ?: $database;

        // If we haven't created this connection, we'll create it based on the config
        // provided in the application. Once we've created the connections we will
        // set the "fetch mode" for PDO which determines the query return types.
        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->configure(
                $this->makeConnection($database),
                $type,
            );

            $this->dispatchConnectionEstablishedEvent($this->connections[$name]);
        }

        return $this->connections[$name];
    }

    /**
     * Get a database connection instance from the given configuration.
     *
     * @param  string  $name
     * @param  array  $config
     * @param  bool  $force
     *
     * @return ConnectionInterface
     */
    public function connectUsing(string $name, array $config, bool $force = false)
    {
        if ($force) {
            $this->purge($name);
        }

        if (isset($this->connections[$name])) {
            throw new RuntimeException("Cannot establish connection [{$name}] because another connection with that name already exists.");
        }

        $connection = $this->configure(
            $this->factory->make($config, $name),
            null,
        );

        $this->dispatchConnectionEstablishedEvent($connection);

        return tap($connection, fn($connection) => $this->connections[$name] = $connection);
    }

    /**
     * Register a custom Doctrine type.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  string  $type
     *
     * @throws Exception
     * @throws RuntimeException
     *
     * @return void
     */
    public function registerDoctrineType(string $class, string $name, string $type): void
    {
        if (! class_exists('Doctrine\DBAL\Connection')) {
            throw new RuntimeException(
                'Registering a custom Doctrine type requires Doctrine DBAL (doctrine/dbal).',
            );
        }

        if (! Type::hasType($name)) {
            Type::addType($name, $class);
        }

        $this->doctrineTypes[$name] = [$type, $class];
    }

    /**
     * Disconnect from the given database and remove from local cache.
     *
     * @param  string|null  $name
     *
     * @return void
     */
    public function purge($name = null)
    {
        $name = $name ?: $this->getDefaultConnection();

        $this->disconnect($name);

        unset($this->connections[$name]);
    }

    /**
     * Disconnect from the given database.
     *
     * @param  string|null  $name
     *
     * @return void
     */
    public function disconnect($name = null)
    {
        if (isset($this->connections[$name = $name ?: $this->getDefaultConnection()])) {
            $this->connections[$name]->disconnect();
        }
    }

    /**
     * Reconnect to the given database.
     *
     * @param  string|null  $name
     *
     * @return ConnectionInterface
     */
    public function reconnect($name = null): ConnectionInterface
    {
        $this->disconnect($name = $name ?: $this->getDefaultConnection());

        if (! isset($this->connections[$name])) {
            return $this->connection($name);
        }

        return $this->refreshPdoConnections($name);
    }

    /**
     * Set the default database connection for the callback execution.
     *
     * @param  string  $name
     * @param  callable  $callback
     *
     * @return mixed
     */
    public function usingConnection($name, callable $callback)
    {
        $previousName = $this->getDefaultConnection();

        $this->setDefaultConnection($name);

        return tap($callback(), function() use ($previousName) {
            $this->setDefaultConnection($previousName);
        });
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection(): ?string
    {
        // Retrieve the default connection name from the deployment configuration
        return Config::driver(ConfigDrivers::DEPLOYMENT)->getString('db/default');
    }

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     *
     * @return void
     */
    public function setDefaultConnection($name): void
    {
        // Set the default connection name in the deployment configuration (config change is TODO)
        Config::driver(ConfigDrivers::DEPLOYMENT)->getString('db/connection/default', $name);
    }

    /**
     * Get all of the support drivers.
     *
     * @return string[]
     */
    public function supportedDrivers()
    {
        return ['mysql', 'pgsql', 'sqlite', 'sqlsrv'];
    }

    /**
     * Get all of the drivers that are actually available.
     *
     * @return string[]
     */
    public function availableDrivers()
    {
        return array_intersect(
            $this->supportedDrivers(),
            str_replace('dblib', 'sqlsrv', PDO::getAvailableDrivers()),
        );
    }

    /**
     * Register an extension connection resolver.
     *
     * @param  string  $name
     * @param  callable  $resolver
     *
     * @return void
     */
    public function extend($name, callable $resolver): void
    {
        $this->extensions[$name] = $resolver;
    }

    /**
     * Remove an extension connection resolver.
     *
     * @param  string  $name
     *
     * @return void
     */
    public function forgetExtension($name): void
    {
        unset($this->extensions[$name]);
    }

    /**
     * Return all of the created connections.
     *
     * @return array<string, ConnectionInterface>
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Set the database reconnector callback.
     *
     * @param  callable  $reconnector
     *
     * @return void
     */
    public function setReconnector(callable $reconnector): void
    {
        $this->reconnector = $reconnector;
    }

    /**
     * Parse the connection into an array of the name and read / write type.
     *
     * @param  string  $name
     *
     * @return array
     */
    protected function parseConnectionName($name): array
    {
        $name = $name ?: $this->getDefaultConnection();

        return Str::endsWith($name, ['::read', '::write'])
                            ? explode('::', $name, 2) : [$name, null];
    }

    /**
     * Make the database connection instance.
     *
     * @param  string  $name
     *
     * @return ConnectionInterface
     */
    protected function makeConnection($name): ConnectionInterface
    {
        $config = $this->configuration($name);

        // First we will check by the connection name to see if an extension has been
        // registered specifically for that connection. If it has we will call the
        // Closure and pass it the config allowing it to resolve the connection.
        if (isset($this->extensions[$name])) {
            return call_user_func($this->extensions[$name], $config, $name);
        }

        // Next we will check to see if an extension has been registered for a driver
        // and will call the Closure if so, which allows us to have a more generic
        // resolver for the drivers themselves which applies to all connections.
        if (isset($this->extensions[$driver = $config['driver']])) {
            return call_user_func($this->extensions[$driver], $config, $name);
        }

        return $this->factory->make($config, $name);
    }

    /**
     * Get the configuration for a connection.
     *
     * @param  string  $name
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    protected function configuration($name): array
    {
        // Use the default connection name if none is provided
        $name = $name ?: $this->getDefaultConnection();

        // Retrieve the connection configuration for the given connection name
        $connections = Config::driver(ConfigDrivers::DEPLOYMENT)->get('db/connection');

        // Throw an exception if the configuration for the connection doesn't exist
        if (null === ($config = Arr::get($connections, $name))) {
            throw new InvalidArgumentException("Database connection [{$name}] not configured.");
        }

        // Parse and return the configuration for the connection
        return (new ConfigurationUrlParser)
            ->parseConfiguration($config);
    }

    /**
     * Prepare the database connection instance.
     *
     * @param  ConnectionInterface  $connection
     * @param  string  $type
     *
     * @return ConnectionInterface
     */
    protected function configure(mixed $connection, $type): ConnectionInterface
    {
        $connection = $this->setPdoForType($connection, $type);

        /** @var Connection $connection */
        $connection->setReadWriteType($type);

        // First we'll set the fetch mode and a few other dependencies of the database
        // connection. This method basically just configures and prepares it to get
        // used by the application. Once we're finished we'll return it back out.
        // $connection->setEventDispatcher($this->eventManager);

        $connection->setTransactionManager($this->databaseTransactionsManager);

        // Here we'll set a reconnector callback. This reconnector can be any callable
        // so we will set a Closure to reconnect from this manager with the name of
        // the connection, which will allow us to reconnect from the connections.
        $connection->setReconnector($this->reconnector);

        $this->registerConfiguredDoctrineTypes($connection);

        return $connection;
    }

    /**
     * Dispatch the ConnectionEstablished event if the event dispatcher is available.
     *
     * @param  ConnectionInterface  $connection
     *
     * @return void
     */
    protected function dispatchConnectionEstablishedEvent(ConnectionInterface $connection): void
    {
        // Dispatch the "ConnectionEstablished" event with connection details
        /** @var Connection $connection */
        $this->eventManager->dispatch(
            $connection->getName(),
            ['data' => new ConnectionEstablished($connection)],
        );
    }

    /**
     * Prepare the read / write mode for database connection instance.
     *
     * @param  ConnectionInterface  $connection
     * @param  string|null  $type
     *
     * @return ConnectionInterface
     */
    protected function setPdoForType(mixed $connection, $type = null): ConnectionInterface
    {
        /** @var Connection $connection */
        if ($type === 'read') {
            $connection->setPdo($connection->getReadPdo());
        } elseif ($type === 'write') {
            $connection->setReadPdo($connection->getPdo());
        }

        return $connection;
    }

    /**
     * Register custom Doctrine types with the connection.
     *
     * @param  ConnectionInterface  $connection
     *
     * @return void
     */
    protected function registerConfiguredDoctrineTypes(ConnectionInterface $connection): void
    {
        // Get Dal type
        $dalTypes = Config::driver(ConfigDrivers::DEPLOYMENT)->get('db/dbal/types', []);

        // Register Doctrine types that are configured in the deployment configuration
        foreach ($dalTypes as $name => $class) {
            $this->registerDoctrineType($class, $name, $name);
        }

        // Register any custom Doctrine types already stored in the connection
        foreach ($this->doctrineTypes as $name => [$type, $class]) {
            /** @var Connection $connection */
            $connection->registerDoctrineType($class, $name, $type);
        }
    }

    /**
     * Refresh the PDO connections on a given connection.
     *
     * @param  string  $name
     *
     * @return ConnectionInterface
     */
    protected function refreshPdoConnections($name): ConnectionInterface
    {
        [$database, $type] = $this->parseConnectionName($name);

        /** @var Connection $fresh */
        $fresh = $this->configure(
            $this->makeConnection($database),
            $type,
        );

        return $this->connections[$name]
            ->setPdo($fresh->getRawPdo())
            ->setReadPdo($fresh->getRawReadPdo());
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string  $method
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->connection()->{$method}(...$parameters);
    }
}
