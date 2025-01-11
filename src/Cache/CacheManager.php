<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

use Magento\Framework\App\Filesystem\DirectoryList as DirsList;
use Maginium\Framework\Cache\Enums\CacheDrivers;
use Maginium\Framework\Cache\Interfaces\FactoryInterface;
use Maginium\Framework\Cache\Interfaces\StoreInterface;
use Maginium\Framework\Cache\Stores\ArrayStoreFactory;
use Maginium\Framework\Cache\Stores\FileStoreFactory;
use Maginium\Framework\Cache\Stores\MemcachedStoreFactory;
use Maginium\Framework\Cache\Stores\MongoDbStoreFactory;
use Maginium\Framework\Cache\Stores\RedisStore;
use Maginium\Framework\Cache\Stores\RedisStoreFactory;
use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\MultipleInstanceManager;
use Maginium\Framework\Support\Path;

/**
 * Class ConfigManager.
 *
 * This class is responsible for managing config across multiple instances.
 * It provides mechanisms to handle config control and limits the execution
 * of specific operations to prevent race conditions.
 */
class CacheManager extends MultipleInstanceManager implements FactoryInterface
{
    /**
     * Redis client instance for cache operations.
     *
     * @var RedisStoreFactory
     */
    protected RedisStoreFactory $redisStoreFactory;

    /**
     * Factory for creating environment-based configuration instances.
     *
     * @var RepositoryFactory
     */
    private RepositoryFactory $repositoryFactory;

    /**
     * Factory for creating array store instances.
     *
     * @var ArrayStoreFactory
     */
    private ArrayStoreFactory $arrayStoreFactory;

    /**
     * Factory for creating file store instances.
     *
     * @var FileStoreFactory
     */
    private FileStoreFactory $fileStoreFactory;

    /**
     * Factory for creating mongo DB store instances.
     *
     * @var MongoDbStoreFactory
     */
    private MongoDbStoreFactory $mongoStoreFactory;

    /**
     * Factory for creating memecached store instances.
     *
     * @var MemcachedStoreFactory
     */
    private MemcachedStoreFactory $memecachedStoreFactory;

    /**
     * Instance of memecached connector.
     *
     * @var MemcachedConnector
     */
    private MemcachedConnector $memcachedConnector;

    /**
     * CacheManager constructor.
     *
     * Initializes the cache manager with necessary dependencies. It also sets up logging
     * for the cache operations, enhancing debug information.
     *
     * @param  MemcachedConnector $memcachedConnector Instance of memecached connector.
     * @param  RepositoryFactory $repositoryFactory Factory for creating repository instances.
     * @param  RedisStoreFactory $redisStoreFactory Factory for creating array Redis instances.
     * @param  ArrayStoreFactory $arrayStoreFactory Factory for creating array store instances.
     * @param  FileStoreFactory $fileStoreFactory Factory for creating file store instances.
     * @param  MongoDbStoreFactory $mongoStoreFactory Factory for creating mongo DB store instances.
     * @param  MemcachedStoreFactory $memecachedStoreFactory Factory for creating memecached store instances.
     */
    public function __construct(
        FileStoreFactory $fileStoreFactory,
        RepositoryFactory $repositoryFactory,
        RedisStoreFactory $redisStoreFactory,
        ArrayStoreFactory $arrayStoreFactory,
        MongoDbStoreFactory $mongoStoreFactory,
        MemcachedConnector $memcachedConnector,
        MemcachedStoreFactory $memecachedStoreFactory,
    ) {
        $this->fileStoreFactory = $fileStoreFactory;
        $this->repositoryFactory = $repositoryFactory;
        $this->redisStoreFactory = $redisStoreFactory;
        $this->mongoStoreFactory = $mongoStoreFactory;
        $this->arrayStoreFactory = $arrayStoreFactory;
        $this->memcachedConnector = $memcachedConnector;
        $this->memecachedStoreFactory = $memecachedStoreFactory;

        // Set the class name in the log for better traceability
        Log::setClassName(static::class);
    }

    /**
     * Get a cache store instance by name.
     *
     * @param string|null $name
     *
     * @return Repository
     */
    public function store($name = null): Repository
    {
        // Return the driver instance for the given channel.
        return $this->driver($name);
    }

    /**
     * Retrieve a driver instance by its name.
     *
     * This method fetches an instance of the specified cache driver or defaults to
     * the default driver if no name is provided.
     *
     * @param  string|null  $name  The name of the cache driver.
     *
     * @return mixed The instance of the cache driver.
     */
    public function driver(?string $name = null): mixed
    {
        // Fetch the appropriate driver instance
        return $this->instance($name);
    }

    /**
     * Create and return a Redis cache driver instance.
     *
     * This method uses the provided configuration to instantiate the Redis cache driver.
     *
     * @param array $config  The configuration settings for the Redis driver.
     *
     * @return Repository The cache repository instance for Redis.
     */
    public function createRedisDriver(array $config): Repository
    {
        // Get connection name
        $connection = Config::driver(ConfigDrivers::DEPLOYMENT)->getString('cache.default', 'default');
        $lockConnection = $this->getConfig('backend_options.lock_connection', $connection);

        // Create Redis instance
        /** @var RedisStore $instance */
        $instance = $this->redisStoreFactory->create(['prefix' => $this->getPrefix()]);

        // Set Lock connection
        $instance->setLockConnection($lockConnection ?? $connection);

        // Create and return the repository for the Redis driver
        return $this->repository($instance, $config);
    }

    /**
     * Create an instance of the Array Cache driver.
     *
     * This method initializes an `ArrayStore` instance based on the given configuration
     * and returns a repository wrapping the `ArrayStore` driver.
     *
     * @param array $config Configuration options for the ArrayStore driver.
     *                      - 'serialize' (bool): Whether to serialize stored values. Default: false.
     *
     * @return Repository The repository instance for interacting with the ArrayStore driver.
     */
    public function createArrayDriver(array $config): Repository
    {
        // Create an ArrayStore instance with the 'serialize' configuration option.
        $instance = $this->arrayStoreFactory->create([
            'config' => $config['serialize'] ?? false,
        ]);

        // Wrap the ArrayStore instance in a repository and return it.
        return $this->repository($instance, $config);
    }

    /**
     * Create an instance of the MongoDB Cache driver.
     *
     * This method initializes a MongoDB-based cache driver and returns a repository
     * for managing cached data using MongoDB as the backend.
     *
     * @param array $config Configuration options for the MongoDB driver.
     *                      - 'serialize' (bool): Whether to serialize stored values. Default: false.
     *
     * @return Repository The repository instance for interacting with the MongoDB driver.
     */
    public function createMongoDriver(array $config): Repository
    {
        // Create a MongoDB-based store instance using the provided configuration.
        $instance = $this->mongoStoreFactory->create([
            'prefix' => $this->getPrefix(),
        ]);

        // Wrap the MongoDB store instance in a repository and return it.
        return $this->repository($instance, $config);
    }

    /**
     * Create an instance of the File Cache driver.
     *
     * This method initializes a `FileStore` instance with the provided configuration
     * and returns a repository for managing file-based caching.
     *
     * @param array $config Configuration options for the FileStore driver.
     *                      - 'path' (string): Directory path for storing cache files.
     *                      - 'lock_path' (string|null): Optional path for storing lock files.
     *
     * @return Repository The repository instance for interacting with the FileStore driver.
     */
    public function createFileDriver(array $config): Repository
    {
        // Create a FileStore instance with the specified directory and lock path.
        $instance = $this->fileStoreFactory->create([
            'directory' => $config['path'] ?? Path::join(BP, DirsList::VAR_DIR, 'file_cache'),
        ]);

        // Set the optional lock directory if provided.
        $instance->setLockDirectory($config['lock_path'] ?? null);

        // Wrap the FileStore instance in a repository and return it.
        return $this->repository($instance, $config);
    }

    /**
     * Create an instance of the Memcached Cache driver.
     *
     * This method connects to a Memcached instance using the provided configuration,
     * initializes a Memcached-based cache driver, and returns a repository for managing
     * cache data with Memcached as the backend.
     *
     * @param array $config Configuration options for the Memcached driver.
     *                      - 'servers' (array): List of Memcached servers to connect to.
     *                      - 'options' (array): Optional Memcached configuration options.
     *                      - 'persistent_id' (string|null): Optional persistent connection ID.
     *                      - 'sasl' (array): Optional SASL credentials with 'username' and 'password'.
     *
     * @return Repository The repository instance for interacting with the Memcached driver.
     */
    public function createMemcachedDriver(array $config): Repository
    {
        // Establish a connection to Memcached using the connector.
        $memcached = $this->memcachedConnector->connect(
            $config['servers'], // List of Memcached servers.
            $config['options'] ?? [], // Optional Memcached options.
            $config['persistent_id'] ?? null, // Persistent connection ID (optional).
            array_filter($config['sasl'] ?? []), // SASL credentials (optional).
        );

        // Create a MemcachedStore instance with the connected Memcached client and prefix.
        $instance = $this->memecachedStoreFactory->create([
            'memcached' => $memcached,
            'prefix' => $this->getPrefix(),
        ]);

        // Wrap the MemcachedStore instance in a repository and return it.
        return $this->repository($instance, $config);
    }

    /**
     * Create a new cache repository with the given implementation.
     *
     * @param StoreInterface $store
     * @param array $config
     *
     * @return Repository
     */
    public function repository(StoreInterface $store, array $config = [])
    {
        return $this->repositoryFactory->create([
            'store' => $store,
        ]);
    }

    /**
     * Get the default cache driver instance name.
     *
     * This method returns the default cache driver name, which is configured to be Redis
     * by default in this case.
     *
     * @return string|null The name of the default cache driver (Redis).
     */
    public function getDefaultInstance(): ?string
    {
        // Return the default cache driver (Redis)
        return CacheDrivers::REDIS;
    }

    /**
     * Get the configuration for a specific cache driver instance.
     *
     * This method retrieves the configuration for the specified cache driver,
     * using the provided driver name or the default instance.
     *
     * @param  string  $name  The name of the cache driver instance.
     *
     * @return array  The configuration array for the specified cache driver.
     */
    public function getInstanceConfig(string $name): array
    {
        // Return the configuration for the given driver or default to Redis
        return ['driver' => $name ?: $this->getDefaultInstance()];
    }

    /**
     * Get the cache key prefix.
     *
     * @return string|null
     */
    private function getPrefix(): ?string
    {
        return $this->getConfig('backend_options.id_prefix', null);
    }

    /**
     * Retrieve a specific configuration value for the cache backend.
     *
     * This method first determines the default cache connection by retrieving
     * the `cache.default` configuration value. It then fetches the specific
     * configuration value for the backend options of the determined connection.
     *
     * @param string $key     The configuration key to retrieve from the backend options.
     * @param string|null $default The default value to return if the key is not found (optional).
     *
     * @return string|null    The configuration value for the given key, or the default value if not found.
     */
    private function getConfig(string $key, ?string $default = null): ?string
    {
        // Retrieve the default cache connection from the deployment configuration.
        $connection = Config::driver(ConfigDrivers::DEPLOYMENT)
            ->getString('cache.default', 'default');

        // Retrieve the backend configuration value for the specific key and connection.
        return Config::driver(ConfigDrivers::DEPLOYMENT)
            ->getString("cache.frontend.{$connection}.{$key}", $default);
    }
}
