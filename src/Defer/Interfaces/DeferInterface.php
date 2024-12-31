<?php

declare(strict_types=1);

namespace Maginium\Framework\Defer\Interfaces;

use Maginium\Framework\Defer\DeferredCallback;
use Maginium\Framework\Defer\DeferredCallbackCollection;

/**
 * Interface DeferInterface.
 *
 * Defines the contract for managing deferred callback execution. Any class
 * implementing this interface must provide methods for deferring, invoking,
 * canceling, and checking deferred callbacks.
 */
interface DeferInterface
{
    /**
     * Defer execution of the given callback.
     *
     * @param  callable|null  $callback  The callback to defer.
     * @param  string|null  $name  An optional name for the deferred callback.
     * @param  bool  $always  Indicates if the callback should always be executed.
     */
    public function execute(?callable $callback = null, ?string $name = null, bool $always = false): DeferredCallback|DeferredCallbackCollection;

    /**
     * Defer multiple callbacks at once.
     *
     * @param  array  $callbacks  An array of callbacks to defer.
     * @param  string|null  $name  An optional name for each deferred callback.
     * @param  bool  $always  Indicates if the callbacks should always be executed.
     */
    public function executeMultiple(array $callbacks, ?string $name = null, bool $always = false): void;

    /**
     * Cancel a deferred callback by its name.
     *
     * @param  string  $name  The name of the deferred callback to cancel.
     */
    public function cancel(string $name): void;

    /**
     * Invoke all deferred callbacks.
     */
    public function invokeAll(): void;

    /**
     * Check if a callback is already deferred.
     *
     * @param  string  $name  The name of the deferred callback to check.
     *
     * @return bool Returns true if the callback is deferred; otherwise, false.
     */
    public function hasDeferred(string $name): bool;
}
