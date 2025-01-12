<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Closure;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\DefaultProviders;
use Maginium\Framework\Application\Interfaces\ApplicationInterface;
use Maginium\Framework\Support\Facades\Container;

/**
 * The ServiceProvider class provides a base implementation for service providers.
 *
 * Service providers are responsible for registering and bootstrapping services
 * within the application. This abstract class defines methods for registering
 * services, calling booting/booted callbacks, and checking if the provider is deferred.
 * It also allows the registration of callbacks to be executed at specific
 * stages of the provider lifecycle.
 */
abstract class ServiceProvider
{
    /**
     * The application instance.
     *
     * @var ApplicationInterface
     */
    protected $app;

    /**
     * All of the registered booting callbacks.
     *
     * This array holds the callbacks that will be called during the booting phase,
     * before the "boot" method is invoked.
     *
     * @var array
     */
    protected $bootingCallbacks = [];

    /**
     * All of the registered booted callbacks.
     *
     * This array holds the callbacks that will be called during the booted phase,
     * after the "boot" method is invoked.
     *
     * @var array
     */
    protected $bootedCallbacks = [];

    /**
     * Create a new service provider instance.
     *
     * @param  ApplicationInterface  $app
     *
     * @return void
     */
    public function __construct(ApplicationInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Get the default providers for a Laravel application.
     *
     * This static method returns an instance of DefaultProviders, which contains
     * the default service providers for the application.
     *
     * @return DefaultProviders
     */
    public static function defaultProviders()
    {
        return new DefaultProviders;
    }

    /**
     * Register any application services.
     *
     * This method is intended to be overridden in subclasses where specific
     * application services can be registered in the container.
     *
     * @return void
     */
    public function register()
    {
        // This method can be left empty, as subclasses can register their services here.
    }

    /**
     * Register a booting callback to be run before the "boot" method is called.
     *
     * This method allows registering callbacks to be executed before the provider
     * bootstraps its services, useful for performing tasks like early setup.
     *
     * @param  Closure  $callback The callback to be executed.
     *
     * @return void
     */
    public function booting(Closure $callback)
    {
        // Store the booting callback in the $bootingCallbacks array
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a booted callback to be run after the "boot" method is called.
     *
     * This method allows registering callbacks to be executed after the provider
     * has finished bootstrapping its services, useful for post-boot tasks.
     *
     * @param  Closure  $callback The callback to be executed.
     *
     * @return void
     */
    public function booted(Closure $callback)
    {
        // Store the booted callback in the $bootedCallbacks array
        $this->bootedCallbacks[] = $callback;
    }

    /**
     * Call the registered booting callbacks.
     *
     * This method iterates over all the registered booting callbacks and calls them
     * using the Container's call method.
     *
     * @return void
     */
    public function callBootingCallbacks()
    {
        $index = 0;

        // Iterate over each registered booting callback
        while ($index < count($this->bootingCallbacks)) {
            // Call each booting callback using the Container's call method
            Container::call($this->bootingCallbacks[$index]);

            $index++;
        }
    }

    /**
     * Call the registered booted callbacks.
     *
     * This method iterates over all the registered booted callbacks and calls them
     * using the Container's call method.
     *
     * @return void
     */
    public function callBootedCallbacks()
    {
        $index = 0;

        // Iterate over each registered booted callback
        while ($index < count($this->bootedCallbacks)) {
            // Call each booted callback using the Container's call method
            Container::call($this->bootedCallbacks[$index]);

            $index++;
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * This method returns an empty array by default, but subclasses can override
     * it to specify the services they provide.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /**
     * Get the events that trigger this service provider to register.
     *
     * This method returns an empty array by default, but subclasses can override
     * it to specify the events that should trigger the registration of the service provider.
     *
     * @return array
     */
    public function when()
    {
        return [];
    }

    /**
     * Determine if the provider is deferred.
     *
     * This method checks if the service provider is deferred by looking for the
     * DeferrableProvider interface. If the provider implements this interface,
     * it is considered deferred.
     *
     * @return bool
     */
    public function isDeferred()
    {
        // Check if the provider implements the DeferrableProvider interface
        return $this instanceof DeferrableProvider;
    }
}
