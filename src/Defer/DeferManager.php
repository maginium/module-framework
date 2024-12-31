<?php

declare(strict_types=1);

namespace Maginium\Framework\Defer;

use Maginium\Framework\Defer\Interfaces\DeferInterface;

/**
 * Class DeferManager.
 *
 * Provides methods to defer execution of callbacks. This class serves as an interface
 * for deferring, invoking, and managing callback functions that can be executed
 * at a later time or under specific conditions.
 *
 * @method static DeferredCallback|DeferredCallbackCollection defer(?callable $callback = null, ?string $name = null, bool $always = false)
 * @method static void deferMultiple(array $callbacks, ?string $name = null, bool $always = false)
 * @method static void cancel(string $name)
 * @method static void invokeAll()
 * @method static bool hasDeferred(string $name)
 */
class DeferManager implements DeferInterface
{
    /**
     * The collection of deferred callbacks.
     */
    protected DeferredCallbackFactory $deferredCallbackFactory;

    /**
     * The collection of deferred callbacks.
     */
    protected DeferredCallbackCollection $deferredCallbackCollection;

    /**
     * DeferManager constructor.
     */
    public function __construct(DeferredCallbackFactory $deferredCallbackFactory, DeferredCallbackCollection $deferredCallbackCollection)
    {
        $this->deferredCallbackFactory = $deferredCallbackFactory;
        $this->deferredCallbackCollection = $deferredCallbackCollection;
    }

    /**
     * Defer execution of the given callback.
     *
     * This method allows you to specify a callback that will be executed later.
     * If no callback is provided, it returns the current DeferredCallbackCollection instance.
     *
     * The method also supports providing an optional name for the deferred callback and
     * a flag to indicate whether the callback should always be executed (even in certain conditions).
     *
     * @param  callable|null  $callback  The callback to defer. If null, the current collection is returned.
     * @param  string|null  $name  An optional name for the deferred callback.
     * @param  bool  $always  Indicates if the callback should always be executed.
     *
     * @return DeferredCallback|DeferredCallbackCollection A DeferredCallback object if a callback is provided,
     *                                                     or the DeferredCallbackCollection if no callback is given.
     */
    public function execute(?callable $callback = null, ?string $name = null, bool $always = false): DeferredCallback|DeferredCallbackCollection
    {
        // Return the current DeferredCallbackCollection if no callback is provided.
        if ($callback === null) {
            return $this->deferredCallbackCollection;
        }

        // Create a new DeferredCallback instance.
        $deferredCallback = $this->deferredCallbackFactory->create([
            'name' => $name,
            'always' => $always,
            'callback' => $callback,
        ]);

        // Add the DeferredCallback to the collection.
        $this->deferredCallbackCollection[] = $deferredCallback;

        // TODO: CHECK HERE AS WE MANUALLY REVOKING THE CALLBACK WHICH IT WASN'T IN THE ORIGINAL CODE
        // Optionally execute the callback.
        // Ensure the `invoke` method exists and properly executes the callback.
        $deferredCallback->invoke();

        // Return the DeferredCallback instance.
        return $deferredCallback;
    }

    /**
     * Defer multiple callbacks at once.
     *
     * This method allows you to specify an array of callbacks to be deferred.
     * Each callback will be processed with the same optional name and always flag.
     *
     * @param  array  $callbacks  An array of callbacks to defer.
     * @param  string|null  $name  An optional name for each deferred callback.
     * @param  bool  $always  Indicates if the callbacks should always be executed.
     */
    public function executeMultiple(array $callbacks, ?string $name = null, bool $always = false): void
    {
        // Iterate over each callback in the array and defer it.
        foreach ($callbacks as $callback) {
            $this->defer($callback, $name, $always);
        }
    }

    /**
     * Cancel a deferred callback by its name.
     *
     * This method removes a deferred callback from the collection based on its name.
     * It is useful for preventing the execution of specific callbacks.
     *
     * @param  string  $name  The name of the deferred callback to cancel.
     */
    public function cancel(string $name): void
    {
        // Remove the specified deferred callback by name from the collection.
        $this->deferredCallbackCollection->forget($name);
    }

    /**
     * Invoke all deferred callbacks.
     *
     * This method executes all callbacks that have been deferred, ensuring that
     * they are processed when needed.
     */
    public function invokeAll(): void
    {
        // Invoke all the deferred callbacks.
        $this->deferredCallbackCollection->invoke();
    }

    /**
     * Check if a callback is already deferred.
     *
     * This method checks if a specific callback, identified by its name, has been
     * deferred in the collection. It helps prevent duplicate deferrals.
     *
     * @param  string  $name  The name of the deferred callback to check.
     *
     * @return bool Returns true if the callback is deferred; otherwise, false.
     */
    public function hasDeferred(string $name): bool
    {
        // Check if the callback with the specified name exists in the collection.
        return $this->deferredCallbackCollection->offsetExists($name);
    }
}
