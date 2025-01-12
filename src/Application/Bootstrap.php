<?php

declare(strict_types=1);

namespace Magento\Framework\App;

use Maginium\Framework\Application\BaseBootstrap;
use Maginium\Framework\Application\ServiceProvider\Registry as ServiceProviderRegistry;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\ServiceProvider;
use Maginium\Framework\Support\Validator;
use Override;

/**
 * A bootstrap of Magento application.
 *
 * Performs basic initialization root function: injects init parameters and creates object manager
 * Can create/run applications
 */
class Bootstrap extends BaseBootstrap
{
    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $serviceProviders = [];

    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = [];

    /**
     * All of the registered booting callbacks.
     *
     * @var array
     */
    protected $bootingCallbacks = [];

    /**
     * All of the registered booted callbacks.
     *
     * @var array
     */
    protected $bootedCallbacks = [];

    /**
     * ServiceProviderRegistry.
     *
     * @var ServiceProviderRegistry
     */
    protected ServiceProviderRegistry $serviceProviderRegistry;

    /**
     * Constructor.
     *
     * Sets up the application with the object manager factory, root directory,
     * and initialization parameters. It also resolves the service provider registry,
     * registers service providers, and boots the application.
     *
     * @param ObjectManagerFactory $factory The factory for creating the object manager.
     * @param string $rootDir The application's root directory.
     * @param array $initParams Parameters for initialization.
     */
    public function __construct(ObjectManagerFactory $factory, $rootDir, array $initParams)
    {
        // Initialize the core application components.
        parent::__construct($factory, $rootDir, $initParams);

        // Resolve and store the service provider registry.
        $this->serviceProviderRegistry = Container::resolve(ServiceProviderRegistry::class);

        // Get all service providers from registry
        $this->serviceProviders = $this->getServiceProviders();

        // Register all required service providers.
        $this->registerServiceProviders();

        // Boot the application to prepare it for use.
        $this->boot();
    }

    /**
     * Static method to create a new Bootstrap instance.
     *
     * This method ensures that the client code doesn't need to instantiate the Object Manager Factory every time
     * Bootstrap is invoked.
     *
     * @param string $rootDir The root directory of the application.
     * @param array $initParams Initialization parameters for the application.
     * @param ObjectManagerFactory|null $factory Optional factory instance for creating the object manager.
     *
     * @return Bootstrap Returns an instance of the Bootstrap class.
     */
    #[Override]
    public static function create($rootDir, array $initParams, ?ObjectManagerFactory $factory = null): static
    {
        // Configure the autoloader using the provided root directory and initialization parameters.
        self::populateAutoloader($rootDir, $initParams);

        // If no factory is provided, create one using the root directory and initialization parameters.
        if ($factory === null) {
            $factory = self::createObjectManagerFactory($rootDir, $initParams);
        }

        // Return a new Bootstrap instance initialized with the factory, root directory, and parameters.
        return new self($factory, $rootDir, $initParams);
    }

    /**
     * Retrieve the service providers from the registry.
     *
     * This method retrieves all registered service providers from the service provider registry,
     * processes them to extract their class names, and returns an array of these class names.
     *
     * @return array An array containing the class names of all registered service providers.
     */
    public function getServiceProviders(): array
    {
        // Retrieve all service providers registered in the application.
        $serviceProviders = $this->serviceProviderRegistry->all();

        // Map through each service provider and extract the `class` property.
        $serviceProviders = Arr::map($serviceProviders, function($provider) {
            // Ensure $provider is an instance of DataObject and return its `class`.
            return $provider->getClass();
        });

        // Return the array of service provider classes.
        return $serviceProviders;
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        // Check if the application has already been booted to prevent redundant operations.
        if ($this->isBooted()) {
            return;
        }

        // Trigger "booting" callbacks to execute preliminary tasks before the application boots.
        $this->fireAppCallbacks($this->bootingCallbacks);

        // Boot each service provider by calling their respective boot methods.
        Arr::walk($this->serviceProviders, function($p) {
            $this->bootProvider($p);
        });

        // Mark the application as booted.
        $this->booted = true;

        // Trigger "booted" callbacks for any logic that depends on the application being fully booted.
        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /**
     * Register a new boot listener.
     *
     * Allows client code to add callbacks to execute during the boot process.
     *
     * @param callable $callback The callback function to register.
     *
     * @return void
     */
    public function booting($callback)
    {
        // Append the provided callback to the list of "booting" callbacks.
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     *
     * Allows client code to add callbacks to execute after the application has booted.
     *
     * @param callable $callback The callback function to register.
     *
     * @return void
     */
    public function booted($callback)
    {
        // Append the provided callback to the list of "booted" callbacks.
        $this->bootedCallbacks[] = $callback;

        // If the application has already booted, invoke the callback immediately.
        if ($this->isBooted()) {
            $callback($this);
        }
    }

    /**
     * Register a service provider with the application.
     *
     * @param ServiceProvider|string $provider The service provider instance or class name to register.
     * @param bool $force Whether to force re-registration even if the provider is already registered.
     *
     * @return ServiceProvider The registered service provider instance.
     */
    public function register(ServiceProvider|string $provider, bool $force = false)
    {
        // Check if the provider is already registered and return it unless forced to re-register.
        if (($registered = $this->getProvider($provider)) && ! $force) {
            return $registered;
        }

        // Resolve the provider instance if a class name is given.
        if (Validator::isString($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        // Call the provider's register method to initialize its services.
        $provider->register();

        // Mark the provider as registered in the registry.
        $this->markAsRegistered(provider: $provider);

        // If the application is already booted, boot the provider immediately.
        if ($this->isBooted()) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  ServiceProvider|string  $provider The service provider or its class name.
     *
     * @return ServiceProvider|null The service provider instance or null if not found.
     */
    public function getProvider($provider)
    {
        // Determine the provider name (class name if a provider object is passed).
        $name = Validator::isString($provider) ? $provider : get_class($provider);

        // Return the provider if registered, otherwise null.
        return $this->serviceProviders[$name] ?? null;
    }

    /**
     * Get the registered service provider instances if any exist.
     *
     * @param  ServiceProvider|string  $provider The service provider or its class name.
     *
     * @return array List of service provider instances.
     */
    public function getProviders($provider)
    {
        // Determine the provider name (class name if a provider object is passed).
        $name = Validator::isString($provider) ? $provider : get_class($provider);

        // Filter and return all providers that match the given name.
        return Arr::where($this->serviceProviderRegistry->all(), fn($value) => $value instanceof $name);
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string  $provider The service provider class name.
     *
     * @return ServiceProvider The resolved service provider instance.
     */
    public function resolveProvider($provider)
    {
        // Instantiate and return the provider class.
        return new $provider($this);
    }

    /**
     * Determine if the application has booted.
     *
     * @return bool True if the application has booted, false otherwise.
     */
    public function isBooted()
    {
        // Return the booted status.
        return $this->booted;
    }

    /**
     * Call the registered booting callbacks.
     *
     * @return void
     */
    public function callBootingCallbacks()
    {
        // Loop through and call each booting callback.
        $index = 0;

        while ($index < count($this->bootingCallbacks)) {
            Container::call($this->bootingCallbacks[$index]);
            $index++;
        }
    }

    /**
     * Register service providers for the application.
     */
    protected function registerServiceProviders()
    {
        // Register each service provider if not already registered.
        Arr::walk($this->serviceProviders, function(ServiceProvider $provider) {
            $this->register($provider);
        });
    }

    /**
     * Boot the given service provider.
     *
     * @param ServiceProvider  $provider The service provider to boot.
     *
     * @return void
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        // Call the booting callbacks for the provider.
        $provider->callBootingCallbacks();

        // Call the 'boot' method if it exists.
        if (method_exists($provider, 'boot')) {
            Container::call([$provider, 'boot']);
        }

        // Call the booted callbacks for the provider.
        $provider->callBootedCallbacks();
    }

    /**
     * Call the booting callbacks for the application.
     *
     * @param  callable[]  $callbacks List of booting callbacks.
     *
     * @return void
     */
    protected function fireAppCallbacks(array &$callbacks)
    {
        // Loop through and call each callback.
        $index = 0;

        while ($index < count($callbacks)) {
            $callbacks[$index]($this);
            $index++;
        }
    }

    /**
     * Mark the given provider as registered.
     *
     * @param  ServiceProvider  $provider The service provider to mark.
     *
     * @return void
     */
    protected function markAsRegistered($provider)
    {
        // Get the class name of the provider.
        $class = get_class($provider);

        // Set the service class.
        $this->serviceProviders[$class] = $provider;

        // Mark the provider as registered.
        $this->loadedProviders[$class] = true;
    }
}
