<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Closure;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\DefaultProviders;
use Maginium\Foundation\Exceptions\InvalidArgumentException;

abstract class ServiceProvider
{
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
     * Get the default providers for a Laravel application.
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
     * @return void
     */
    public function register()
    {
    }

    /**
     * Register a booting callback to be run before the "boot" method is called.
     *
     * @param  Closure  $callback
     *
     * @return void
     */
    public function booting(Closure $callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a booted callback to be run after the "boot" method is called.
     *
     * @param  Closure  $callback
     *
     * @return void
     */
    public function booted(Closure $callback)
    {
        $this->bootedCallbacks[] = $callback;
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
     * Call the registered booted callbacks.
     *
     * @return void
     */
    public function callBootedCallbacks()
    {
        $index = 0;

        while ($index < count($this->bootedCallbacks)) {
            $this->call($this->bootedCallbacks[$index]);

            $index++;
        }
    }

    /**
     * Get the services provided by the provider.
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
     * @return array
     */
    public function when()
    {
        return [];
    }

    /**
     * Determine if the provider is deferred.
     *
     * @return bool
     */
    public function isDeferred()
    {
        return $this instanceof DeferrableProvider;
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
    private function call($callback, array $parameters = [])
    {
        if (is_callable($callback)) {
            // Call the callback with the provided parameters
            return call_user_func_array($callback, $parameters);
        }

        throw InvalidArgumentException::make('Provided callback is not callable');
    }
}
