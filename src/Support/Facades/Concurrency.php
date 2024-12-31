<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Concurrency\ConcurrencyManager;
use Maginium\Framework\Concurrency\ForkDriver;
use Maginium\Framework\Concurrency\ProcessDriver;
use Maginium\Framework\Concurrency\SyncDriver;
use Maginium\Framework\Defer\DeferredCallback;
use Maginium\Framework\Support\Facade;

/**
 * @method static mixed driver(string|null $name = null)
 * @method static ProcessDriver createProcessDriver(array $config)
 * @method static ForkDriver createForkDriver(array $config)
 * @method static SyncDriver createSyncDriver(array $config)
 * @method static string getDefaultInstance()
 * @method static mixed instance(string|null $name = null)
 * @method static ConcurrencyManager forgetInstance(array|string|null $name = null)
 * @method static void purge(string|null $name = null)
 * @method static ConcurrencyManager extend(string $name, \Closure $callback)
 * @method static array run(\Closure|array $tasks)
 * @method static DeferredCallback defer(\Closure|array $tasks)
 *
 * @see ConcurrencyManager
 */
class Concurrency extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return ConcurrencyManager::class;
    }
}
