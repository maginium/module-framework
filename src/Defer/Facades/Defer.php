<?php

declare(strict_types=1);

namespace Maginium\Framework\Defer\Facades;

use Maginium\Framework\Defer\Interfaces\DeferInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for the DeferManager class.
 *
 * This class provides a static interface to the DeferManager, allowing
 * easier access to defer, invoke, and manage callbacks.
 *
 * @method static \Maginium\Framework\Defer\DeferredCallback|\Maginium\Framework\Defer\DeferredCallbackCollection execute(?callable $callback = null, ?string $name = null, bool $always = false) Defer execution of the given callback.
 * @method static void executeMultiple(array $callbacks, ?string $name = null, bool $always = false) Defer multiple callbacks at once.
 * @method static void cancel(string $name) Cancel a deferred callback by its name.
 * @method static void invokeAll() Invoke all deferred callbacks.
 * @method static bool hasDeferred(string $name) Check if a callback is already deferred.
 *
 * @see DeferInterface
 */
class Defer extends Facade
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
        return DeferInterface::class;
    }
}
