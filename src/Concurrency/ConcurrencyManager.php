<?php

declare(strict_types=1);

namespace Maginium\Framework\Concurrency;

use Illuminate\Process\Factory as ProcessFactory;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Concurrency\Interfaces\DriverInterface;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\MultipleInstanceManager;
use Maginium\Framework\Support\Php;
use Spatie\Fork\Fork;

/**
 * Class ConcurrencyManager.
 *
 * This class is responsible for managing concurrency across multiple instances.
 * It provides mechanisms to handle concurrency control and limits the execution
 * of specific operations to prevent race conditions.
 *
 * It extends `MultipleInstanceManager` to manage different concurrency drivers
 * and utilizes a `Driver` mixin to provide access to various concurrency driver methods.
 *
 * @mixin DriverInterface This mixin adds methods to handle concurrency drivers.
 */
class ConcurrencyManager extends MultipleInstanceManager
{
    /**
     * @var ProcessDriverFactory
     *
     * Factory for creating process concurrency drivers. This is used to generate drivers
     * that handle concurrency specifically through process-based mechanisms.
     */
    protected ProcessDriverFactory $processDriverFactory;

    /**
     * @var ForkDriverFactory
     *
     * Factory for creating fork-based concurrency drivers. This factory creates drivers
     * that utilize forking to manage concurrency and parallel execution.
     */
    protected ForkDriverFactory $forkDriverFactory;

    /**
     * @var SyncDriverFactory
     *
     * Factory for creating synchronous concurrency drivers. This factory generates drivers
     * that run tasks sequentially without any concurrency, ensuring tasks are processed in order.
     */
    protected SyncDriverFactory $syncDriverFactory;

    /**
     * Constructor for the ConcurrencyManager class.
     *
     * The constructor accepts dependencies for various concurrency driver factories
     * and initializes them. These factories will be used to create instances of the
     * corresponding concurrency drivers (process, fork, or sync).
     *
     * @param  ForkDriverFactory  $forkDriverFactory  Factory responsible for creating fork-based concurrency drivers.
     * @param  SyncDriverFactory  $syncDriverFactory  Factory responsible for creating synchronous concurrency drivers.
     * @param  ProcessDriverFactory  $processDriverFactory  Factory responsible for creating process concurrency drivers.
     */
    public function __construct(
        ForkDriverFactory $forkDriverFactory,
        SyncDriverFactory $syncDriverFactory,
        ProcessDriverFactory $processDriverFactory,
    ) {
        $this->forkDriverFactory = $forkDriverFactory;
        $this->syncDriverFactory = $syncDriverFactory;
        $this->processDriverFactory = $processDriverFactory;
    }

    /**
     * Get a driver instance by name.
     *
     * This method retrieves a driver instance based on the provided driver name.
     * It will return the instance of the specified driver, or the default driver if no name is provided.
     *
     * @param  string|null  $name  The name of the concurrency driver.
     *
     * @return mixed The driver instance corresponding to the provided name.
     */
    public function driver(?string $name = null): mixed
    {
        // Fetch the driver instance from the instance manager.
        return $this->instance($name);
    }

    /**
     * Create an instance of the process concurrency driver.
     *
     * This method creates and returns an instance of the process concurrency driver.
     * The process driver is used to run tasks concurrently using processes.
     *
     * @return ProcessDriver An instance of the ProcessDriver.
     */
    public function createProcessDriver(): ProcessDriver
    {
        // Use the injected ProcessFactory to create the ProcessDriver
        return $this->processDriverFactory->create();
    }

    /**
     * Create an instance of the fork concurrency driver.
     *
     * This method creates and returns an instance of the fork concurrency driver.
     * The fork driver is used to fork processes for concurrency within the system.
     * Before creating the driver, it checks if the necessary class for forking is available.
     *
     * @param  array  $config  Configuration options for the fork driver.
     *
     * @throws RuntimeException If the method is called outside of a console environment or
     *                          if the required "spatie/fork" package is not installed.
     *
     * @return ForkDriver An instance of the ForkDriver.
     */
    public function createForkDriver(array $config): ForkDriver
    {
        // Verify that the Fork class exists, indicating that the required package is installed.
        if (! Php::isClassExists(Fork::class)) {
            // If the class does not exist, throw an exception prompting the user to install the package.
            throw RuntimeException::make('Please install the "spatie/fork" Composer package in order to utilize the "fork" driver.');
        }

        // Use the injected ForkDriverFactory to create the ForkDriver
        return $this->forkDriverFactory->create();
    }

    /**
     * Create an instance of the sync concurrency driver.
     *
     * This method creates and returns an instance of the sync concurrency driver.
     * The sync driver is used to run tasks sequentially without any concurrency.
     * It is useful for tasks that need to be executed in order and cannot be parallelized.
     *
     * @param  array  $config  Configuration options for the sync driver.
     *
     * @return SyncDriver An instance of the SyncDriver.
     */
    public function createSyncDriver(array $config): SyncDriver
    {
        // Use the injected SyncDriverFactory to create the SyncDriver
        return $this->syncDriverFactory->create();
    }

    /**
     * Get the default instance name.
     *
     * This method retrieves the default driver instance name. The default instance is used
     * when no specific driver name is provided. It fetches this information from the configuration.
     *
     * @return string The default instance name, which defaults to "process" if not configured.
     */
    public function getDefaultInstance(): ?string
    {
        // Fetch the default driver name from configuration, fallback to 'process'
        return Config::getString('concurrency.default', 'process');
    }

    /**
     * Get the instance-specific configuration.
     *
     * This method retrieves configuration settings specific to the given concurrency driver instance.
     * It fetches the driver configuration from the global configuration settings.
     *
     * @param  string  $name  The name of the concurrency driver instance.
     *
     * @return array An array of configuration settings for the given driver instance.
     */
    public function getInstanceConfig($name): array
    {
        // Fetch the driver configuration from the global configuration
        return ['driver' => $name ?: Config::getString('concurrency.driver', $this->getDefaultInstance())];
    }
}
