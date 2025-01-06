<?php

declare(strict_types=1);

namespace Magento\Framework\App;

use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Application\BaseBootstrap;
use Maginium\Framework\Application\ServiceProviderManager;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\ServiceProvider;
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
     * ServiceProviderManager.
     *
     * @var Collection
     */
    protected ServiceProviderManager $serviceProviderManager;

    /**
     * Constructor.
     *
     * @param ObjectManagerFactory $factory
     * @param string $rootDir
     * @param array $initParams
     */
    public function __construct(ObjectManagerFactory $factory, $rootDir, array $initParams)
    {
        parent::__construct($factory, $rootDir, $initParams);

        $this->serviceProviderManager = Container::resolve(ServiceProviderManager::class);

        // Register necessary service providers
        $this->registerServiceProviders();

        // Boot the application
        $this->boot();
    }

    /**
     * Static method so that client code does not have to create Object Manager Factory every time Bootstrap is called.
     *
     * @param string $rootDir
     * @param array $initParams
     * @param ObjectManagerFactory $factory
     *
     * @return Bootstrap
     */
    #[Override]
    public static function create($rootDir, array $initParams, ?ObjectManagerFactory $factory = null): static
    {
        self::populateAutoloader($rootDir, $initParams);

        if ($factory === null) {
            $factory = self::createObjectManagerFactory($rootDir, $initParams);
        }

        return new self($factory, $rootDir, $initParams);
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isBooted()) {
            return;
        }

        // Once the application has booted we will also fire some "booted" callbacks
        // for any listeners that need to do work after this initial booting gets
        // finished. This is useful when ordering the boot-up processes we run.
        $this->fireAppCallbacks($this->bootingCallbacks);

        $serviceProviders = $this->serviceProviderManager->all();

        array_walk($serviceProviders, function($p) {
            $this->bootProvider($p);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /**
     * Register a new boot listener.
     *
     * @param  callable  $callback
     *
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     *
     * @param  callable  $callback
     *
     * @return void
     */
    public function booted($callback)
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $callback($this);
        }
    }

    /**
     * Register a service provider with the application.
     *
     * @param  ServiceProvider|string  $provider
     * @param  bool  $force
     *
     * @return ServiceProvider
     */
    public function register(ServiceProvider|string $provider, bool $force = false)
    {
        if (($registered = $this->getProvider($provider)) && ! $force) {
            return $registered;
        }

        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        $provider->register();

        $this->markAsRegistered($provider);

        // If the application has already booted, we will call this boot method on
        // the provider class so it has an opportunity to do its boot logic and
        // will be ready for any usage by this developer's application logic.
        if ($this->isBooted()) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  ServiceProvider|string  $provider
     *
     * @return ServiceProvider|null
     */
    public function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        $serviceProviders = $this->serviceProviderManager->all();

        return $serviceProviders[$name] ?? null;
    }

    /**
     * Get the registered service provider instances if any exist.
     *
     * @param  ServiceProvider|string  $provider
     *
     * @return array
     */
    public function getProviders($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::where($this->serviceProviderManager->all(), fn($value) => $value instanceof $name);
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string  $provider
     *
     * @return ServiceProvider
     */
    public function resolveProvider($provider)
    {
        return new $provider($this);
    }

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Call the registered booting callbacks.
     *
     * @return void
     */
    public function callBootingCallbacks()
    {
        $index = 0;

        while ($index < count($this->bootingCallbacks)) {
            $this->call($this->bootingCallbacks[$index]);

            $index++;
        }
    }

    /**
     * Call a method or callback dynamically.
     *
     * This method allows the execution of any callable provided to it, which could be
     * a simple function, method in a class, or even a closure.
     *
     * @param callable $callback
     * @param array $parameters Parameters to be passed to the callable
     *
     * @return mixed The result of the callable execution
     */
    public function call($callback, array $parameters = [])
    {
        if (is_callable($callback)) {
            // Call the callback with the provided parameters
            return call_user_func_array($callback, $parameters);
        }

        throw InvalidArgumentException::make('Provided callback is not callable');
    }

    /**
     * Register service providers for the application.
     */
    protected function registerServiceProviders()
    {
        // Optionally, call specific providers to register after boot if needed
        $serviceProviders = $this->serviceProviderManager->all();

        array_walk($serviceProviders, function($provider) {
            // Register the provider if it's not already registered
            $this->register($provider);
        });
    }

    /**
     * Boot the given service provider.
     *
     * @param ServiceProvider  $provider
     *
     * @return void
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        $provider->callBootingCallbacks();

        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }

    /**
     * Call the booting callbacks for the application.
     *
     * @param  callable[]  $callbacks
     *
     * @return void
     */
    protected function fireAppCallbacks(array &$callbacks)
    {
        $index = 0;

        while ($index < count($callbacks)) {
            $callbacks[$index]($this);

            $index++;
        }
    }

    /**
     * Mark the given provider as registered.
     *
     * @param  ServiceProvider  $provider
     *
     * @return void
     */
    protected function markAsRegistered($provider)
    {
        $class = get_class($provider);

        $serviceProviders = $this->serviceProviderManager->all();

        $serviceProviders[$class] = $provider;

        $this->loadedProviders[$class] = true;
    }
}
