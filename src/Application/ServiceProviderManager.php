<?php

declare(strict_types=1);

namespace Maginium\Framework\Application;

use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\ServiceProvider;

/**
 * ServiceProviderManager.
 *
 * This class manages multiple service providers, allowing dynamic access to providers.
 */
class ServiceProviderManager
{
    /**
     * A collection of service providers.
     *
     * @var Collection
     */
    private Collection $providers;

    /**
     * Constructor to initialize the service providers.
     *
     * @param ServiceProvider[] $providers
     */
    public function __construct(array $providers = [])
    {
        $this->providers = Collection::make($providers);
    }

    /**
     * Dynamically pass methods.
     *
     * @param  string  $method
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters): mixed
    {
        return $this->providers->{$method}(...$parameters);
    }
}
