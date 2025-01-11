<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Connectors;

use Illuminate\Database\Connectors\ConnectionFactory as BaseConnectionFactory;
use Illuminate\Database\Connectors\ConnectorInterface;
use InvalidArgumentException;
use Maginium\Framework\Database\Connections\Connection;
use Maginium\Framework\Database\Connections\MySqlConnectionFactory;
use Maginium\Framework\Database\Connections\PostgresConnectionFactory;
use Maginium\Framework\Database\Interfaces\ConnectionInterface;

/**
 * Class ConnectionFactory.
 *
 * Factory class responsible for creating and managing database connections.
 * It supports both single database connections and read/write split connections.
 * This class handles the connection to MySQL databases, including connection pooling
 * with multiple hosts for high availability, and provides a flexible interface
 * for handling database connection configurations.
 */
class ConnectionFactory extends BaseConnectionFactory
{
    /**
     * Factory for creating MySQL database connections.
     *
     * @var MySqlConnectionFactory
     */
    private $mySqlConnectionFactory;

    /**
     * Factory for creating MySQL connector instances.
     *
     * @var MySqlConnectorFactory
     */
    private $mySqlConnectorFactory;

    /**
     * Factory to create Postgres connections.
     *
     * @var PostgresConnectionFactory
     */
    private $postgresConnectionFactory;

    /**
     * Constructor to initialize the ConnectionFactory with the required connector factories.
     *
     * @param MySqlConnectorFactory $mySqlConnectorFactory Factory to create MySQL connectors.
     * @param MySqlConnectionFactory $mySqlConnectionFactory Factory to create MySQL connections.
     * @param PostgresConnectionFactory $postgresConnectionFactory Factory to create Postgres connections.
     */
    public function __construct(MySqlConnectorFactory $mySqlConnectorFactory, MySqlConnectionFactory $mySqlConnectionFactory, PostgresConnectionFactory $postgresConnectionFactory)
    {
        $this->mySqlConnectorFactory = $mySqlConnectorFactory;
        $this->mySqlConnectionFactory = $mySqlConnectionFactory;
        $this->postgresConnectionFactory = $postgresConnectionFactory;
    }

    /**
     * Establish a PDO connection based on the configuration.
     *
     * @param  array  $config
     * @param  string|null  $name
     *
     * @return ConnectionInterface
     */
    public function make(array $config, $name = null): ConnectionInterface
    {
        $config = $this->parseConfig($config, $name);

        if (isset($config['read'])) {
            return $this->createReadWriteConnection($config);
        }

        return $this->createSingleConnection($config);
    }

    /**
     * Creates a connector instance for the database driver.
     *
     * @param array $config  Database configuration.
     *
     * @throws InvalidArgumentException  If the provided driver is unsupported.
     *
     * @return ConnectorInterface  The created connector instance.
     */
    public function createConnector(array $config): ConnectorInterface
    {
        // Always use MySQL connector in this factory.
        return $this->mySqlConnectorFactory->create();
    }

    /**
     * Creates a single database connection instance.
     *
     * @param array $config  Database connection configuration.
     *
     * @return ConnectionInterface  The created single connection.
     */
    protected function createSingleConnection(array $config): ConnectionInterface
    {
        // Create a PDO resolver (closure) for the database connection.
        $pdo = $this->createPdoResolver($config);

        // Create and return the actual connection using the PDO resolver.
        return $this->createConnection(
            $config['driver'], // Database driver (e.g., 'mysql').
            $pdo, // The PDO resolver for the connection.
            $config['dbname'], // Database name.
            $config['prefix'], // Table prefix (optional).
            $config, // Full database configuration.
        );
    }

    /**
     * Creates a new database connection instance.
     *
     * This method creates a connection instance based on the provided database driver.
     * It supports custom resolvers and specific factories for MySQL and PostgreSQL.
     * If no resolver or factory is found, it falls back to the default connection instance.
     *
     * @param string $driver The database driver (e.g., 'mysql', 'pgsql').
     * @param mixed $connection The PDO connection resolver or connection instance.
     * @param string $database The name of the database.
     * @param string $prefix The table prefix to use (optional).
     * @param array $config Additional configuration options (optional).
     *
     * @throws InvalidArgumentException If the driver is unsupported.
     *
     * @return ConnectionInterface The created connection instance.
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = []): ConnectionInterface
    {
        // Check if a custom resolver is registered for the driver
        $resolver = Connection::getResolver($driver);

        if ($resolver) {
            return $resolver($connection, $database, $prefix, $config);
        }

        // Factory method mapping for supported drivers
        $factories = [
            'mysql' => fn() => $this->mySqlConnectionFactory->create([
                'config' => $config,
                'pdo' => $connection,
                'database' => $database,
                'tablePrefix' => $prefix,
            ]),
            'pgsql' => fn() => $this->postgresConnectionFactory->create([
                'config' => $config,
                'pdo' => $connection,
                'database' => $database,
                'tablePrefix' => $prefix,
            ]),
        ];

        // Check if a factory exists for the given driver and use it
        if (array_key_exists($driver, $factories)) {
            return $factories[$driver]();
        }

        // Fallback to the default connection instance
        if ($connection instanceof ConnectionInterface) {
            return $connection;
        }

        // Throw an exception for unsupported drivers
        throw new InvalidArgumentException("Unsupported database driver: {$driver}");
    }
}
