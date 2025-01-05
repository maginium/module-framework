<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Connectors;

use Illuminate\Database\Connectors\MySqlConnector as BaseMySqlConnector;
use Override;
use PDO;

/**
 * Class MySqlConnector.
 *
 * Extends the base MySQL connector to provide custom database connection logic
 * while maintaining compatibility with Laravel's database components.
 */
class MySqlConnector extends BaseMySqlConnector
{
    /**
     * Establish a database connection.
     *
     * This method creates a PDO instance using the configuration provided, applies
     * any additional options, and sets the default database schema if specified.
     *
     * @param  array  $config  Database configuration, which includes keys such as:
     *                         - 'host': The database host.
     *                         - 'port': (Optional) The database port.
     *                         - 'dbname': The default database schema name.
     *                         - 'unix_socket': (Optional) Path to the Unix socket.
     *                         - 'options': (Optional) Additional PDO options.
     *
     * @return PDO  The established PDO connection.
     */
    #[Override]
    public function connect(array $config): PDO
    {
        // Build the Data Source Name (DSN) based on the configuration.
        $dsn = $this->getDsn($config);

        // Retrieve any PDO options specified in the configuration.
        $options = $this->getOptions($config);

        // Create the PDO connection instance using the DSN, credentials, and options.
        $connection = $this->createConnection($dsn, $config, $options);

        // If a database name is specified in the configuration, set it as the default schema.
        if (! empty($config['dbname'])) {
            $connection->exec("USE `{$config['dbname']}`;");
        }

        // Apply additional configuration settings to the connection (e.g., collation).
        $this->configureConnection($connection, $config);

        return $connection;
    }

    /**
     * Get the DSN string for a Unix socket configuration.
     *
     * This method is used when the database server is accessed via a Unix socket
     * instead of a network connection. The DSN includes the Unix socket path and the
     * default database name.
     *
     * @param  array  $config  Configuration including 'unix_socket' and 'dbname'.
     *
     * @return string  The DSN formatted for Unix socket connections.
     */
    #[Override]
    protected function getSocketDsn(array $config): string
    {
        // Use sprintf to format the DSN string with the Unix socket and database name.
        return sprintf(
            'mysql:unix_socket=%s;dbname=%s',
            $config['unix_socket'], // The Unix socket path.
            $config['dbname'],      // The database name.
        );
    }

    /**
     * Get the DSN string for a host and port configuration.
     *
     * This method constructs a DSN for network-based connections using the host and
     * port specified in the configuration. If the port is not provided, it defaults
     * to MySQL's standard port.
     *
     * @param  array  $config  Configuration including 'host', 'port', and 'dbname'.
     *
     * @return string  The DSN formatted for host and port connections.
     */
    #[Override]
    protected function getHostDsn(array $config): string
    {
        // Check if the port is provided in the configuration.
        return isset($config['port'])
            // If port is provided, include it in the DSN.
            ? sprintf('mysql:host=%s;port=%d;dbname=%s', $config['host'], $config['port'], $config['dbname'])
            // If port is not provided, exclude it from the DSN.
            : sprintf('mysql:host=%s;dbname=%s', $config['host'], $config['dbname']);
    }

    /**
     * Configure the given PDO connection.
     *
     * Applies specific settings like isolation level, charset, collation,
     * timezone, and SQL mode based on the configuration array.
     *
     * @param  PDO  $connection  The PDO connection instance.
     * @param  array  $config  Configuration including optional settings like
     *                          'isolation_level', 'charset', 'collation', 'timezone', and 'modes'.
     *
     * @return void
     */
    protected function configureConnection(PDO $connection, array $config): void
    {
        // Set the transaction isolation level if specified in the configuration.
        if (isset($config['isolation_level'])) {
            $connection->exec(sprintf('SET SESSION TRANSACTION ISOLATION LEVEL %s;', $config['isolation_level']));
        }

        // Initialize an array to store SQL statements for setting connection attributes.
        $statements = [];

        // Configure the character set and collation if provided.
        if (isset($config['charset'])) {
            if (isset($config['collation'])) {
                // Include both charset and collation in the statement.
                $statements[] = sprintf("NAMES '%s' COLLATE '%s'", $config['charset'], $config['collation']);
            } else {
                // Include only the charset in the statement.
                $statements[] = sprintf("NAMES '%s'", $config['charset']);
            }
        }

        // Configure the timezone if specified.
        if (isset($config['timezone'])) {
            $statements[] = sprintf("time_zone='%s'", $config['timezone']);
        }

        // Retrieve the SQL mode based on the configuration and connection.
        $sqlMode = $this->getSqlMode($connection, $config);

        // If a SQL mode is defined, add it to the statements.
        if ($sqlMode !== null) {
            $statements[] = sprintf("SESSION sql_mode='%s'", $sqlMode);
        }

        // Execute all collected SQL statements as a single `SET` command if any exist.
        if ($statements !== []) {
            $connection->exec(sprintf('SET %s;', implode(', ', $statements)));
        }
    }

    /**
     * Get the SQL mode value.
     *
     * Determines the appropriate SQL mode for the connection based on the configuration
     * and server version. Defaults to strict modes for better data consistency.
     *
     * @param  PDO  $connection  The PDO connection instance.
     * @param  array  $config  Configuration including 'modes', 'strict', and 'version'.
     *
     * @return string|null  The SQL mode string or null if no mode should be set.
     */
    protected function getSqlMode(PDO $connection, array $config): ?string
    {
        // If specific SQL modes are defined in the configuration, return them as a comma-separated string.
        if (isset($config['modes'])) {
            return implode(',', $config['modes']);
        }

        // If 'strict' mode is not specified in the configuration, return null.
        if (! isset($config['strict'])) {
            return null;
        }

        // If 'strict' mode is explicitly disabled, return a minimal SQL mode.
        if (! $config['strict']) {
            return 'NO_ENGINE_SUBSTITUTION';
        }

        // Retrieve the server version from the configuration or the connection.
        $version = $config['version'] ?? $connection->getAttribute(PDO::ATTR_SERVER_VERSION);

        // Use a stricter SQL mode for MySQL 8.0.11 or later.
        if (version_compare($version, '8.0.11') >= 0) {
            return 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
        }

        // Use an alternative strict SQL mode for older versions of MySQL.
        return 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
    }
}
