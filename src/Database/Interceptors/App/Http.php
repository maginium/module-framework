<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interceptors\App;

use Closure;
use Magento\Framework\App\Http as AppHttp;
use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Database\Capsule\Manager;
use Maginium\Framework\Database\Capsule\ManagerFactory as CapsuleFactory;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facades\Config;

/**
 * Class Http.
 *
 * Intercepts the application launch process to configure and initialize database connections.
 */
class Http
{
    /**
     * Factory to create Capsule ORM Manager instances.
     *
     * @var CapsuleFactory
     */
    private CapsuleFactory $capsuleFactory;

    /**
     * Constructor.
     *
     * Initializes the CapsuleFactory dependency required for database management.
     *
     * @param CapsuleFactory $capsuleFactory Factory instance for creating Capsule ORM managers.
     */
    public function __construct(CapsuleFactory $capsuleFactory)
    {
        $this->capsuleFactory = $capsuleFactory;
    }

    /**
     * Intercepts the application launch process to configure database connections.
     *
     * This method wraps around the `launch` method in the `AppHttp` class, executing
     * custom logic before invoking the original method.
     *
     * @param AppHttp $subject The application HTTP entry point being intercepted.
     * @param Closure $proceed The original method to be executed.
     *
     * @return mixed The result of the original `launch` method.
     */
    public function aroundLaunch(AppHttp $subject, Closure $proceed): mixed
    {
        // Prepares database connections before launching the application.
        $this->initializeDatabaseConnections();

        // Executes the original `launch` method and returns its result.
        return $proceed();
    }

    /**
     * Initializes and configures Capsule ORM with database connections.
     *
     * - Fetches database configurations from the deployment settings.
     * - Adds each connection to the Capsule ORM manager.
     * - Sets Capsule as a global instance for use across the application.
     * - Boots Eloquent ORM functionality to enable database operations.
     */
    private function initializeDatabaseConnections(): void
    {
        // Retrieves the database connection configurations.
        $connections = $this->fetchDatabaseConnections();

        // Creates a new instance of the Capsule ORM manager.
        /** @var Manager $capsule */
        $capsule = $this->capsuleFactory->create();

        // Loops through each database connection and registers it with the Capsule manager.
        // foreach ($connections as $name => $config) {
        //     $capsule->addConnection($config, $name);
        // }

        // Makes the Capsule manager globally accessible.
        $capsule->setAsGlobal();

        // Enables Eloquent ORM functionality for database interactions.
        $capsule->bootEloquent();
    }

    /**
     * Fetches and formats database connection configurations.
     *
     * Retrieves connection details from the deployment configuration, filters out inactive connections,
     * and formats them to match the configuration schema required by Capsule ORM.
     *
     * @return array<string, array<string, mixed>> Associative array of connection names and configurations.
     */
    private function fetchDatabaseConnections(): array
    {
        // Retrieves the deployment configuration driver.
        $deploymentDriver = Config::driver(ConfigDrivers::DEPLOYMENT);

        // Retrieves all database connection details as an array.
        $connections = $deploymentDriver->get('db/connection', []);

        // Retrieves default database settings for charset, collation, and table prefix.
        $prefix = $deploymentDriver->getString('db/table_prefix', '');
        $defaultCharset = $deploymentDriver->getString('db/charset', 'utf8mb4');
        $defaultCollation = $deploymentDriver->getString('db/collation', 'utf8mb4_unicode_ci');

        // Initializes an empty array to hold formatted connections.
        $dbConfig = [];

        // Iterates through each connection configuration.
        foreach ($connections as $name => $connection) {
            // Convert the connection array to a DataObject for better access to methods.
            $connection = DataObject::make($connection);

            // Checks if the connection is active. Skips inactive connections.
            if ($connection->getActive() !== '1') {
                continue;
            }

            // Formats the connection details into the schema required by Capsule ORM.
            $dbConfig[$name] = [
                'driver' => 'mysql', // Specifies the database driver as MySQL.
                'prefix' => $prefix, // Table prefix, if any.
                'port' => $connection->getPort() ?? '3306', // Database port number, default to '3306' if not set.
                'password' => $connection->getPassword() ?? '', // Database password, default to empty if not set.
                'database' => $connection->getDbname() ?? 'magento', // Database name, default to 'magento' if not set.
                'username' => $connection->getUsername() ?? 'root', // Database username, default to 'root' if not set.
                'host' => $connection->getHost() ?? '127.0.0.1', // Database host address, default to '127.0.0.1' if not set.
                'charset' => $connection->getCharset() ?? $defaultCharset, // Character set for the database, default to $defaultCharset if not set.
                'collation' => $connection->getCollation() ?? $defaultCollation, // Collation for the database, default to $defaultCollation if not set.
                'options' => $connection->getDriverOptions() ?? [], // Additional database options, default to an empty array if not set.
            ];
        }

        // Returns the formatted connection configurations.
        return $dbConfig;
    }
}