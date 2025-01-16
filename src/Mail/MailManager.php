<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail;

use Magento\Email\Model\Transport;
use Maginium\Framework\Mail\Interfaces\FactoryInterface;
use Maginium\Framework\Mail\Interfaces\MailerInterface;
use Maginium\Framework\Mail\Interfaces\MailerInterfaceFactory;
use Maginium\Framework\Mail\Interfaces\TransportBuilderInterface;
use Maginium\Framework\Mail\Interfaces\Transporters\LaminasInterface;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\MultipleInstanceManager;
use Override;
use Throwable;

/**
 * Class MailManager.
 *
 * Manages multiple mail driver instances and provides mechanisms for handling mail operations.
 * This class ensures efficient email sending while preventing race conditions in concurrent scenarios.
 *
 * It extends `MultipleInstanceManager` to facilitate the management of different mail drivers.
 *
 * @mixin MailerInterface Adds methods for mail driver handling.
 */
class MailManager extends MultipleInstanceManager implements FactoryInterface
{
    /**
     * Constructs the email transport.
     *
     * @var LaminasInterface
     */
    protected LaminasInterface $laminasTransport;

    /**
     * Factory for creating mailer instances.
     *
     * The factory is used to create mail drivers that process tasks in a synchronous manner,
     * ensuring sequential execution without overlap.
     *
     * @var MailerInterfaceFactory
     */
    protected MailerInterfaceFactory $mailerFactory;

    /**
     * MailManager Constructor.
     *
     * Initializes the mail manager with the provided dependencies for creating and managing
     * mail driver instances.
     *
     * @param MailerInterfaceFactory $mailerFactory Factory for creating mailer instances.
     * @param LaminasInterface $laminasTransport Constructs and manages email transport.
     */
    public function __construct(
        LaminasInterface $laminasTransport,
        MailerInterfaceFactory $mailerFactory,
    ) {
        $this->mailerFactory = $mailerFactory;
        $this->laminasTransport = $laminasTransport;
    }

    /**
     * Retrieves a mailer instance by name.
     *
     * If the mailer instance exists in the local cache, it will be returned.
     * Otherwise, it will be resolved and created using the provided mailer factory.
     *
     * @param  string  $name  The unique identifier for the mailer channel.
     * @param  array|null  $config  Optional array of configuration parameters.
     *
     * @throws Throwable If an error occurs during resolution or instantiation.
     *
     * @return mixed The resolved mailer instance.
     */
    #[Override]
    protected function get($name, $config = null): mixed
    {
        try {
            // Check if the requested mailer instance already exists in the cache.
            return $this->instances[$name] ?? with(
                $this->resolve($name),
                function(TransportBuilderInterface $resolvedMailer) use ($name): MailerInterface {
                    // Create a new mailer instance using the mailer factory.
                    /** @var Transport $resolvedMailer */
                    $mailerInstance = $this->mailerFactory->create(['transport' => $resolvedMailer]);

                    // Store the instance in the local cache and return it.
                    return $this->instances[$name] = $mailerInstance;
                },
            );
        } catch (Throwable $e) {
            // If an exception occurs, use the default mailer and log the error as emergency
            Log::emergency('Unable to create configured mailer. Using emergency mailer.', [
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Get a mail implementation by name.
     *
     * This method retrieves a mail instance based on the provided mailer name.
     * The mailer could represent a specific storage solution (e.g., resend, SES, or a custom configuration).
     *
     * @param  string|null  $name Optional name of the mail mailer to retrieve.
     *
     * @return MailerInterface The mail instance corresponding to the provided name or default.
     */
    public function mailer($name = null): MailerInterface
    {
        // Retrieve the driver instance for the given disk name.
        return $this->driver($name);
    }

    /**
     * Get a driver instance by name.
     *
     * This method retrieves a driver instance based on the provided driver name.
     * It will return the instance of the specified driver, or the default driver if no name is provided.
     *
     * @param  string|null  $name  The name of the mail driver.
     *
     * @return mixed The driver instance corresponding to the provided name.
     */
    public function driver(?string $name = null): mixed
    {
        // Fetch the driver instance from the instance manager.
        return $this->instance($name);
    }

    /**
     * Create an instance of the sync mail driver.
     *
     * This method creates and returns an instance of the sync mail driver.
     * The sync driver is used to run tasks sequentially without any mail.
     * It is useful for tasks that need to be executed in order and cannot be parallelized.
     *
     * @param  array  $config  Configuration options for the sync driver.
     *
     * @return LaminasInterface An instance of the SyncDriver.
     */
    public function createLaminasDriver(array $config): LaminasInterface
    {
        // Use the injected SyncDriverFactory to create the SyncDriver
        return $this->laminasTransport;
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
        return 'laminas';
    }

    /**
     * Get the instance-specific configuration.
     *
     * This method retrieves configuration settings specific to the given mail driver instance.
     * It fetches the driver configuration from the global configuration settings.
     *
     * @param  string  $name  The name of the mail driver instance.
     *
     * @return array An array of configuration settings for the given driver instance.
     */
    public function getInstanceConfig($name): array
    {
        // Fetch the driver configuration from the global configuration
        return ['driver' => $name ?: $this->getDefaultInstance()];
    }

    /**
     * Dynamically call the default instance.
     *
     * This magic method allows for method calls on the default instance directly.
     * If a method is not found in the MultipleInstanceManager, it will be passed
     * to the resolved instance.
     *
     * @param  string  $method The name of the method to call.
     * @param  array  $parameters The parameters to pass to the method.
     *
     * @return mixed The result of the method call.
     */
    public function __call($method, $parameters)
    {
        // Delegate the method call to the default instance.
        return $this->mailer()->{$method}(...$parameters);
    }
}
