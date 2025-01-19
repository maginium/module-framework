<?php

declare(strict_types=1);

namespace Maginium\Framework\Filesystem;

use Aws\S3\S3ClientFactory;
use Closure;
use Illuminate\Contracts\Filesystem\Cloud as CloudInterface;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemInterface;
use Illuminate\Support\Arr;
use League\Flysystem\AwsS3V3\AwsS3V3AdapterFactory as S3AdapterFactory;
use League\Flysystem\AwsS3V3\PortableVisibilityConverterFactory as AwsS3PortableVisibilityConverterFactory;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemFactory as FlysystemFactory;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\Local\LocalFilesystemAdapterFactory as LocalAdapterFactory;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Filesystem\Drivers\AwsS3V3;
use Maginium\Framework\Filesystem\Drivers\AwsS3V3Factory as AwsS3DriverFactory;
use Maginium\Framework\Filesystem\Drivers\DriverFilesystem;
use Maginium\Framework\Filesystem\Drivers\Filesystem;
use Maginium\Framework\Filesystem\Drivers\LocalFilesystem;
use Maginium\Framework\Filesystem\Drivers\LocalFilesystemFactory as LocalDriverFactory;
use Maginium\Framework\Filesystem\Enums\StorageDrivers;
use Maginium\Framework\Filesystem\Interfaces\FactoryInterface;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\MultipleInstanceManager;
use Override;
use RuntimeException;

/**
 * FilesystemManager is responsible for managing the creation of various filesystem storage drivers.
 * This class uses multiple factory classes to generate instances of local storage drivers, S3 drivers,
 * and Flysystem instances, allowing for flexible configuration and integration with different storage backends.
 *
 * @mixin DriverFilesystem
 * @mixin FilesystemInterface
 */
class FilesystemManager extends MultipleInstanceManager implements FactoryInterface
{
    /**
     * Factory responsible for creating Flysystem instances.
     *
     * @var FlysystemFactory
     */
    private FlysystemFactory $flysystemFactory;

    /**
     * Factory responsible for creating LocalAdapter instances.
     *
     * @var LocalDriverFactory
     */
    private LocalDriverFactory $localDriverFactory;

    /**
     * Factory responsible for creating S3Client instances.
     *
     * @var S3ClientFactory
     */
    private S3ClientFactory $s3ClientFactory;

    /**
     * Factory responsible for creating S3Adapter instances.
     *
     * @var AwsS3DriverFactory
     */
    private AwsS3DriverFactory $awsS3DriverFactory;

    /**
     * Factory responsible for creating S3 Adapter instances.
     *
     * @var S3AdapterFactory
     */
    private S3AdapterFactory $s3AdapterFactory;

    /**
     * Factory responsible for creating Local Adapter instances.
     *
     * @var LocalAdapterFactory
     */
    private LocalAdapterFactory $localAdapterFactory;

    /**
     * Factory responsible for creating S3 visibility converter instances.
     *
     * @var AwsS3PortableVisibilityConverterFactory
     */
    private AwsS3PortableVisibilityConverterFactory $awsS3PortableVisibilityConverterFactory;

    /**
     * Constructor method to initialize the necessary factory classes.
     *
     * The constructor accepts several factory classes, each responsible for creating
     * specific components required for the filesystem drivers, such as adapters,
     * clients, and visibility converters.
     *
     * @param LocalAdapterFactory $localAdapterFactory Factory responsible for creating local adapters.
     * @param S3ClientFactory $s3ClientFactory Factory responsible for creating S3 clients.
     * @param S3AdapterFactory $s3AdapterFactory Factory responsible for creating S3 adapters.
     * @param FlysystemFactory $flysystemFactory Factory responsible for creating Flysystem instances.
     * @param AwsS3DriverFactory $awsS3DriverFactory Factory responsible for creating AWS S3 drivers.
     * @param LocalDriverFactory $localDriverFactory Factory responsible for creating local drivers.
     * @param AwsS3PortableVisibilityConverterFactory $awsS3PortableVisibilityConverterFactory Factory responsible for creating AWS S3 visibility converters.
     */
    public function __construct(
        S3ClientFactory $s3ClientFactory,
        S3AdapterFactory $s3AdapterFactory,
        FlysystemFactory $flysystemFactory,
        AwsS3DriverFactory $awsS3DriverFactory,
        LocalDriverFactory $localDriverFactory,
        LocalAdapterFactory $localAdapterFactory,
        AwsS3PortableVisibilityConverterFactory $awsS3PortableVisibilityConverterFactory,
    ) {
        $this->s3ClientFactory = $s3ClientFactory;
        $this->s3AdapterFactory = $s3AdapterFactory;
        $this->flysystemFactory = $flysystemFactory;
        $this->awsS3DriverFactory = $awsS3DriverFactory;
        $this->localDriverFactory = $localDriverFactory;
        $this->localAdapterFactory = $localAdapterFactory;
        $this->awsS3PortableVisibilityConverterFactory = $awsS3PortableVisibilityConverterFactory;
    }

    /**
     * Attempt to get the disk from the local cache or resolve it if not present.
     *
     * This method first checks the local cache (`$this->disks`) for the specified disk by name.
     * If the disk is not already cached, it will resolve the disk using the `resolve` method.
     *
     * @param  string  $name  The name of the disk to retrieve.
     *
     * @return FilesystemInterface The resolved filesystem instance.
     */
    #[Override]
    protected function get(string $name): FilesystemInterface
    {
        return $this->disks[$name] ?? $this->resolve($name);
    }

    /**
     * Retrieve a filesystem implementation by its disk name.
     *
     * This method returns a filesystem instance based on the provided disk name.
     * If no name is provided, it will retrieve the default disk instance.
     *
     * @param  string|null  $name  The name of the filesystem disk (optional).
     *
     * @return FilesystemInterface The filesystem instance corresponding to the specified disk name.
     */
    public function disk($name = null): FilesystemInterface
    {
        // Retrieve the driver instance for the given disk name.
        return $this->driver($name);
    }

    /**
     * Retrieve the default cloud filesystem instance.
     *
     * This method is used to fetch the instance of the default cloud-based filesystem,
     * as defined in the application's configuration.
     *
     * @return CloudInterface The default cloud filesystem instance.
     */
    public function cloud(): CloudInterface
    {
        // Retrieve the name of the default cloud driver from the configuration.
        $name = $this->getDefaultCloudDriver();

        return $this->instances[$name] = $this->get($name);
    }

    /**
     * Retrieve a driver instance by its name.
     *
     * This method fetches a specific filesystem driver instance, which could represent
     * different storage backends (e.g., local disk, S3, custom implementations).
     * If no name is provided, the default driver is retrieved.
     *
     * @param  string|null  $name  The name of the filesystem driver (optional).
     *
     * @return FilesystemInterface The driver instance corresponding to the specified name.
     */
    public function driver(?string $name = null): FilesystemInterface
    {
        // Retrieve the driver instance using the base instance manager.
        return $this->instance($name);
    }

    /**
     * Create an instance of the local driver.
     *
     * This method configures a local storage driver, taking into account permissions,
     * visibility, and other configurations provided in the $config array. It uses a
     * factory method to create the local driver and then configures its disk name and
     * URL serving behavior (for signed URLs).
     *
     * @param  array  $config Configuration array for the local driver.
     * @param  string $name   Name for the local driver (default is 'local').
     *
     * @return Filesystem Returns the fully configured local filesystem instance.
     */
    public function createLocalDriver(array $config, string $name = 'local'): LocalFilesystem
    {
        // Set the visibility and permissions for the local filesystem.
        $visibility = PortableVisibilityConverter::fromArray(
            $config['permissions'] ?? [],
            $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE,
        );

        // Determine how to handle symbolic links: skip or disallow.
        $links = ($config['links'] ?? null) === 'skip'
            ? LocalAdapter::SKIP_LINKS
            : LocalAdapter::DISALLOW_LINKS;

        // Create the local adapter with the provided configuration.
        $adapter = $this->localAdapterFactory->create([
            'location' => $config['root'], // Root directory for the local filesystem.
            'visibility' => $visibility, // Visibility settings for the filesystem.
            'writeFlags' => $config['lock'] ?? LOCK_EX, // Locking mechanism for file operations.
            'linkHandling' => $links, // Link handling option (skip or disallow links).
        ]);

        // Create the local filesystem driver using a factory.
        /** @var LocalFilesystem $localDriver */
        $localDriver = $this->localDriverFactory->create([
            'config' => $config, // Configuration array for the local driver.
            'adapter' => $adapter, // The local adapter instance.
            'driver' => $this->createFlysystem($adapter, $config), // Flysystem instance for local storage.
        ]);

        // Set the disk name for the local driver.
        $localDriver->diskName($name);

        // Configure whether the local driver should serve signed URLs.
        $localDriver->shouldServeSignedUrls(
            $config['serve'] ?? false,    // Default to false if not specified.
            // TODO: FIX HERE BY IMPLEMETING URL facade
            // fn() => $this->app['url'],     // URL generator for signed URLs.
        );

        return $localDriver;
    }

    /**
     * Create an instance of the Amazon S3 driver.
     *
     * This method configures the Amazon S3 driver by first formatting the S3 configuration,
     * then creating the necessary S3 client, adapter, and finally the AWS S3 driver.
     * It applies any custom visibility and stream read settings based on the provided configuration.
     *
     * @param  array $config Configuration array for the Amazon S3 driver.
     *
     * @return Cloud Returns the fully configured AWS S3 driver instance.
     */
    public function createS3Driver(array $config): AwsS3V3
    {
        // Format the S3 configuration to ensure correct parameters.
        $s3Config = $this->formatS3Config($config);

        // Get the root directory for the S3 filesystem, if provided.
        $root = (string)($s3Config['root'] ?? '');

        // Set up visibility for the S3 adapter (default to public visibility).
        $visibility = $this->awsS3PortableVisibilityConverterFactory->create([
            'defaultForDirectories' => $config['visibility'] ?? Visibility::PUBLIC,
        ]);

        // Determine if streaming reads are enabled for the S3 driver.
        $streamReads = $s3Config['stream_reads'] ?? false;

        // Create the S3 client using the formatted configuration.
        $client = $this->s3ClientFactory->create([
            'args' => $s3Config,  // Pass the S3 configuration to the client factory.
        ]);

        // Create the S3 adapter using the factory and configuration.
        $adapter = $this->s3AdapterFactory->create([
            'prefix' => $root, // Root directory for the S3 bucket.
            'client' => $client, // The created S3 client.
            'mimeTypeDetector' => null, // Optional MIME type detection.
            'visibility' => $visibility, // Visibility for the S3 adapter.
            'streamReads' => $streamReads, // Whether to allow streaming reads.
            'bucket' => $s3Config['bucket'], // S3 bucket name.
            'options' => $config['options'] ?? [], // Additional options for the adapter.
        ]);

        // Create the AWS S3 driver instance using the S3 client and adapter.
        $awsS3Driver = $this->awsS3DriverFactory->create([
            'client' => $client, // S3 client.
            'adapter' => $adapter, // S3 adapter.
            'config' => $s3Config, // S3 configuration.
            'driver' => $this->createFlysystem($adapter, $config), // Flysystem instance for S3.
        ]);

        // Return the configured AWS S3 driver.
        return $awsS3Driver;
    }

    /**
     * Set the given disk instance.
     *
     * This method is used to store a disk instance in the `instances` property, where it can be accessed by the name of the disk.
     * It is typically used for storing a reference to a filesystem (disk) so that it can be retrieved later by its name.
     *
     * @param  string  $name The name to associate with the disk instance.
     * @param  mixed  $disk The disk instance (can be any object or value representing a filesystem).
     *
     * @return $this The current instance, allowing for method chaining.
     */
    public function set($name, $disk)
    {
        // Store the disk instance in the `instances` array using the provided name.
        $this->instances[$name] = $disk;

        // Return $this to allow for method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the default driver name.
     *
     * This method retrieves the default filesystem driver name, typically used for defining which disk is the default for operations like storage.
     * It retrieves this value from the configuration, and defaults to `StorageDrivers::LOCAL` if not set.
     *
     * @return string The name of the default driver.
     */
    public function getDefaultInstance()
    {
        // Fetch the 'filesystems.default' configuration setting from the deployment driver config.
        // If the setting is not present, 'StorageDrivers::LOCAL' will be returned as the default.
        return Config::driver(ConfigDrivers::DEPLOYMENT)->getString('filesystems.default', StorageDrivers::LOCAL);
    }

    /**
     * Get the default cloud driver name.
     *
     * This method retrieves the name of the default cloud storage driver.
     * It checks the configuration for 'filesystems.cloud', and if it is not set, it defaults to `StorageDrivers::S3`.
     *
     * @return string The name of the default cloud driver (typically S3).
     */
    public function getDefaultCloudDriver()
    {
        // Fetch the 'filesystems.cloud' configuration setting from the deployment driver config.
        // If the setting is not present, it will default to 'StorageDrivers::S3'.
        return Config::driver(ConfigDrivers::DEPLOYMENT)->getString('filesystems.cloud', StorageDrivers::S3);
    }

    /**
     * Get all of the resolved disk instances.
     *
     * This method retrieves all of the disk instances that have been resolved and cached in the system.
     * These instances are typically stored in the `instances` property for easy access.
     *
     * @return array The list of all resolved disk instances.
     */
    public function getDisks()
    {
        // Return all resolved instances stored in the parent class's `instances` array.
        return parent::getInstances();
    }

    /**
     * Unset the given disk instance from the cache.
     *
     * This method allows for removing a disk instance from the cache. This is useful when you no longer need to reference a particular disk.
     * If no driver name is provided, the method will attempt to remove the default disk instance.
     *
     * @param  string|null  $driver The name of the driver to remove. If null, it will remove the default driver.
     *
     * @return void
     */
    public function forgetDisk($driver = null)
    {
        // Resolve the driver name to be used for removing the disk instance.
        $driver = $this->parseDriver($driver);

        // Remove the instance for the resolved driver from the cache.
        parent::forgetInstance($driver);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * This method allows you to register a custom creator function (closure) that will be used to create a disk instance for a specific driver.
     * This is useful for extending the system with custom filesystem drivers.
     *
     * @param  string  $driver The name of the driver to create.
     * @param  Closure  $callback The callback function responsible for creating the disk instance.
     *
     * @return $this The current instance, allowing for method chaining.
     */
    public function extend($driver, Closure $callback)
    {
        // Store the provided closure in the `customCreators` array, indexed by the driver name.
        $this->customCreators[$driver] = $callback;

        // Return $this to allow for method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the instance-specific configuration.
     *
     * This method retrieves the configuration settings specific to a given driver instance.
     * The configuration is fetched from the global settings under the 'filesystems.disks' key.
     *
     * @param string $name The name of the config driver instance.
     *
     * @return array An array of configuration settings for the specified driver.
     */
    public function getInstanceConfig($name): array
    {
        // Fetch and return the configuration settings specific to the given disk driver instance.
        return Config::driver(ConfigDrivers::DEPLOYMENT)->get("filesystems.{$name}");
    }

    /**
     * Creates a Flysystem instance with the specified adapter and configuration.
     *
     * This method allows for dynamic configuration of the Flysystem instance by wrapping the given adapter
     * with additional features such as read-only access or path prefixing based on the provided config options.
     *
     * @param  FilesystemAdapter  $adapter The adapter to be used by the Flysystem instance.
     * @param  array  $config Configuration options for the Flysystem instance.
     *
     * @return FilesystemOperator The created Flysystem instance.
     */
    private function createFlysystem(FlysystemAdapter $adapter, array $config): FilesystemOperator
    {
        // Check if the 'read-only' flag is set in the config and wrap the adapter in a read-only adapter if necessary.
        if ($config['read-only'] ?? false === true) {
            $class = "\League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter";

            // Check if ReadOnlyFilesystemAdapter class exists before using it.
            if (class_exists($class)) {
                $adapter = new $class($adapter);
            } else {
                // Handle the case where the class does not exist, maybe log or throw an exception
                throw new RuntimeException('ReadOnlyFilesystemAdapter class not found.');
            }
        }

        // Check if a path prefix is provided in the config and apply it to the adapter.
        if (! empty($config['prefix'])) {
            $class = "\League\Flysystem\PathPrefixing\PathPrefixedAdapter";

            // Check if PathPrefixedAdapter class exists before using it.
            if (class_exists($class)) {
                $adapter = new $class($adapter, $config['prefix']);
            } else {
                // Handle the case where the class does not exist, maybe log or throw an exception
                throw new RuntimeException('PathPrefixedAdapter class not found.');
            }
        }

        // Create the Flysystem instance with the configured adapter and additional settings from the config.
        $flysystem = $this->flysystemFactory->create([
            'adapter' => $adapter,
            // Filter the config array to only include relevant settings.
            'config' => Arr::only($config, [
                'directory_visibility',
                'disable_asserts',
                'retain_visibility',
                'temporary_url',
                'url',
                'visibility',
            ]),
        ]);

        return $flysystem;
    }

    /**
     * Format the given S3 configuration with the default options.
     *
     * This method applies default configuration settings to the provided S3 configuration array.
     * It adds a 'version' key if not present, and ensures that the credentials are formatted correctly.
     * It also removes the 'token' key from the config array, as it's handled separately in the credentials array.
     *
     * @param  array  $config The provided S3 configuration array.
     *
     * @return array The formatted configuration array, ready for use with the S3 client.
     */
    private function formatS3Config(array $config)
    {
        // Add default 'version' setting if it's not already set.
        $config += ['version' => 'latest'];

        // If both 'key' and 'secret' are provided, create a 'credentials' array with them.
        if (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
        }

        // If a 'token' is provided, add it to the 'credentials' array.
        if (! empty($config['token'])) {
            $config['credentials']['token'] = $config['token'];
        }

        // Remove the 'token' key from the config array, as it's now part of the 'credentials' array.
        return Arr::except($config, ['token']);
    }
}
