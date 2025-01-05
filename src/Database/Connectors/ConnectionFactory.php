<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Connectors;

use Illuminate\Database\Connectors\ConnectionFactory as BaseConnectionFactory;
use Illuminate\Database\Connectors\ConnectorInterface;
use InvalidArgumentException;
use Maginium\Framework\Database\Interfaces\ConnectionInterface;
use Maginium\Framework\Database\MySqlConnectionFactory;

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
     * Constructor to initialize the ConnectionFactory with the required connector factories.
     *
     * @param MySqlConnectionFactory $mySqlConnectionFactory  Factory to create MySQL connections.
     * @param MySqlConnectorFactory $mySqlConnectorFactory  Factory to create MySQL connectors.
     */
    public function __construct(MySqlConnectionFactory $mySqlConnectionFactory, MySqlConnectorFactory $mySqlConnectorFactory)
    {
        // Store the MySQL connection factory instance.
        $this->mySqlConnectionFactory = $mySqlConnectionFactory;

        // Store the MySQL connector factory instance.
        $this->mySqlConnectorFactory = $mySqlConnectorFactory;
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
     * @param string $driver  The database driver.
     * @param Closure $pdo  The PDO resolver.
     * @param string $database  The database name.
     * @param string $prefix  The table prefix.
     * @param array $config  The database configuration.
     *
     * @return ConnectionInterface  The created connection instance.
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = []): ConnectionInterface
    {
        /** @var ConnectionInterface */
        $connection = $this->mySqlConnectionFactory->create([
            'config' => $config,
            'pdo' => $connection,
            'database' => $database,
            'tablePrefix' => $prefix,
        ]);

        // Fall back to the default connector factory.
        return $connection;
    }
}
