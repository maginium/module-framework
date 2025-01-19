<?php

declare(strict_types=1);

namespace Maginium\Framework\Log;

use Magento\Framework\Logger\MonologFactory;
use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Log\Enums\LogDrivers;
use Maginium\Framework\Log\Handlers\Channel\CloudwatchFactory;
use Maginium\Framework\Log\Handlers\Channel\SlackFactory;
use Maginium\Framework\Log\Interfaces\FactoryInterface;
use Maginium\Framework\Log\Interfaces\LoggerInterface;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\MultipleInstanceManager;
use Override;
use Psr\Log\LoggerInterface as PsrLogInterface;
use Throwable;

/**
 * Class LogManager.
 *
 * Manages the log drivers across different instances and ensures proper log handling.
 * This class is responsible for creating and returning instances of specific log drivers
 * such as default, Slack, and CloudWatch, and ensures that logs are handled across the system.
 * It extends `MultipleInstanceManager` to handle different log drivers and prevent race conditions.
 *
 * @mixin LoggerInterface This mixin adds methods for handling log drivers.
 */
class LogManager extends MultipleInstanceManager implements FactoryInterface
{
    /**
     * Handles the creation of Monolog instances for various log drivers.
     */
    private MonologFactory $monologFactory;

    /**
     * Handles the creation of the default log driver.
     */
    private LoggerFactory $loggerFactory;

    /**
     * Handles the creation of Slack log driver.
     */
    private SlackFactory $slackFactory;

    /**
     * Handles the creation of CloudWatch log driver.
     */
    private CloudwatchFactory $cloudwatchFactory;

    /**
     * LogManager constructor.
     *
     * Initializes the LogManager with necessary service factories for creating log drivers.
     *
     * @param  SlackFactory  $slackFactory  Factory for creating the Slack log driver.
     * @param  LoggerFactory  $loggerFactory  Factory for creating the default log driver.
     * @param  MonologFactory  $monologFactory  Factory for creating Monolog log instances.
     * @param  CloudwatchFactory  $cloudwatchFactory  Factory for creating the CloudWatch log driver.
     */
    public function __construct(
        SlackFactory $slackFactory,
        LoggerFactory $loggerFactory,
        MonologFactory $monologFactory,
        CloudwatchFactory $cloudwatchFactory,
    ) {
        $this->slackFactory = $slackFactory;
        $this->loggerFactory = $loggerFactory;
        $this->monologFactory = $monologFactory;
        $this->cloudwatchFactory = $cloudwatchFactory;
    }

    /**
     * Retrieves a logger instance by name.
     *
     * If the logger instance exists in the local cache, it will be returned.
     * Otherwise, it will be resolved and created using the provided logger factory.
     *
     * @param  string  $name  The unique identifier for the logger channel.
     * @param  array|null  $config  Optional array of configuration parameters.
     *
     * @throws Throwable If an error occurs during resolution or instantiation.
     *
     * @return mixed The resolved logger instance.
     */
    #[Override]
    protected function get($name, $config = null): mixed
    {
        try {
            // Check if the requested logger instance already exists in the cache.
            return $this->instances[$name] ?? with(
                $this->resolve($name),
                function($resolvedLogger) use ($name) {
                    // Create a new logger instance using the logger factory.
                    $loggerInstance = $this->loggerFactory->create(['logger' => $resolvedLogger]);

                    // Store the instance in the local cache and return it.
                    return $this->instances[$name] = $loggerInstance;
                },
            );
        } catch (Throwable $e) {
            // If an exception occurs, use the default logger and log the error as emergency
            return tap($this->createDefaultDriver(), function($logger) use ($e) {
                $logger->emergency('Unable to create configured logger. Using emergency logger.', [
                    'exception' => $e,
                ]);
            });
        }
    }

    /**
     * Get a log channel instance.
     *
     * This method retrieves a log channel instance by name.
     * A channel can be a specific log handler, such as Slack or CloudWatch.
     *
     * @param  string|null  $name  Optional name of the log channel.
     *
     * @return PsrLogInterface The log channel instance for the given channel name.
     */
    public function channel(?string $name = null): PsrLogInterface
    {
        // Return the driver instance for the given channel.
        return $this->driver($name);
    }

    /**
     * Get a driver instance by name.
     *
     * This method retrieves a driver instance based on the provided driver name.
     * It will return the instance of the specified driver, or the default driver if no name is provided.
     *
     * @param  string|null  $name  The name of the logger driver.
     *
     * @return mixed The driver instance corresponding to the provided name.
     */
    public function driver(?string $name = null): mixed
    {
        // Fetch the driver instance from the instance manager.
        return $this->instance($name);
    }

    /**
     * Creates and returns an instance of the default log driver.
     *
     * This driver handles the default logging behavior for the system.
     *
     * @return LoggerInterface An instance of the default log driver.
     */
    public function createDefaultDriver(): LoggerInterface
    {
        // Create and return the default log driver using the logger factory
        return $this->loggerFactory->create();
    }

    /**
     * Creates and returns an instance of the Slack log driver.
     *
     * This driver sends logs to a Slack channel via a loggerured webhook.
     *
     * @return PsrLogInterface An instance of the Slack log driver.
     */
    public function createSlackDriver(): PsrLogInterface
    {
        // Create the Slack log handler using the Slack factory
        $slack = $this->slackFactory->create();

        // Create and return a Monolog logger instance loggerured with the Slack handler
        return $this->monologFactory->create(['name' => LogDrivers::SLACK, 'handlers' => [$slack]]);
    }

    /**
     * Creates and returns an instance of the CloudWatch log driver.
     *
     * This driver sends logs to AWS CloudWatch for cloud-based log storage and management.
     *
     * @return PsrLogInterface An instance of the CloudWatch log driver.
     */
    public function createCloudWatchDriver(): PsrLogInterface
    {
        // Create the CloudWatch log handler using the CloudWatch factory
        $cloudwatch = $this->cloudwatchFactory->create();

        // Create and return a Monolog logger instance loggerured with the CloudWatch handler
        return $this->monologFactory->create(['name' => LogDrivers::CLOUDWATCH, 'handlers' => [$cloudwatch]]);
    }

    /**
     * Get all of the resolved log instances.
     *
     * @return array The list of all resolved log instances.
     */
    public function getChannels()
    {
        // Return all the resolved instances from the cache
        return parent::getInstances();
    }

    /**
     * Unset the given channel instance from the cache.
     *
     * @param  string|null  $driver  The name of the driver to remove.
     *
     * @return void
     */
    public function forgetChannel($driver = null)
    {
        // Parse and resolve the driver name
        $driver = $this->parseDriver($driver);

        parent::forgetInstance($driver);
    }

    /**
     * Get the default instance name.
     *
     * This method retrieves the default driver instance name.
     * It is used when no specific driver name is provided by the caller.
     *
     * @return string The default instance name, which is "process" if not loggerured.
     */
    public function getDefaultInstance(): ?string
    {
        // Return the default log driver instance name from the LogDrivers enum
        return Config::driver(ConfigDrivers::ENV)->getString('logger.default', LogDrivers::DEFAULT);
    }

    /**
     * Get the instance-specific log logger.
     *
     * This method retrieves the logger for the specified log driver.
     *
     * @param  string  $name  The name of the log driver.
     *
     * @return array An array containing the logger for the given driver.
     */
    public function getInstanceConfig($name): array
    {
        // Return the logger for the given driver, using the default if none is provided
        return ['driver' => $name ?: $this->getDefaultInstance()];
    }

    /**
     * Parse the driver name.
     *
     * If no driver name is provided, it will return the default driver.
     *
     * @param  string|null  $driver  The driver name (optional).
     *
     * @return string|null The resolved driver name.
     */
    protected function parseDriver(?string $driver): string
    {
        // Use the provided driver name, or resolve the default driver if none is provided
        $driver ??= $this->getDefaultDriver();

        return $driver;
    }
}
