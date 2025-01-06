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
class ServiceProviderManager extends Collection
{
    /**
     * Constructor to initialize the service providers.
     *
     * @param ServiceProvider[] $providers
     */
    public function __construct(array $providers = [])
    {
        $this->items = $providers;
    }
}
