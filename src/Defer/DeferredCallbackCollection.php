<?php

declare(strict_types=1);

namespace Maginium\Framework\Defer;

use ArrayAccess;
use Closure;
use Countable;
use Maginium\Framework\Support\Arr;
use Throwable;

/**
 * Class DeferredCallbackCollection.
 *
 * This class manages a collection of deferred callbacks, allowing for the storage,
 * retrieval, invocation, and management of these callbacks. The class implements
 * the ArrayAccess and Countable interfaces, enabling it to be used like an array
 * and providing a count of the callbacks.
 *
 * The collection can handle duplicate callbacks, and it allows for callbacks to
 * be invoked conditionally based on provided closure logic.
 */
class DeferredCallbackCollection implements ArrayAccess, Countable
{
    /**
     * All of the deferred callbacks.
     */
    protected array $callbacks = [];

    /**
     * Determine how many callbacks are in the collection.
     *
     * This method provides the count of callbacks currently stored in the collection,
     * accounting for any duplicates that may have been removed.
     *
     * @return int The number of callbacks in the collection.
     */
    public function count(): int
    {
        // Ensure duplicates are removed before counting the callbacks.
        $this->forgetDuplicates();

        return count($this->callbacks);
    }

    /**
     * Get the first callback in the collection.
     *
     * This method retrieves the first callback stored in the collection,
     * allowing for quick access to it. If the collection is empty,
     * this may cause an undefined offset notice.
     *
     * @return callable The first callback in the collection.
     */
    public function first()
    {
        // Return the first callback using array values.
        return Arr::values($this->callbacks)[0];
    }

    /**
     * Invoke all the deferred callbacks in the collection.
     *
     * This method calls the `invokeWhen` method with a truth test that
     * always returns true, effectively executing all registered callbacks.
     */
    public function invoke(): void
    {
        $this->invokeWhen(fn() => true);
    }

    /**
     * Invoke the deferred callbacks if the given truth test evaluates to true.
     *
     * This method allows for conditional execution of callbacks based on
     * the provided closure. If the closure returns true for a callback,
     * that callback will be executed and then removed from the collection.
     *
     * @param  Closure|null  $when  An optional closure that determines if
     *                              the callback should be invoked.
     */
    public function invokeWhen(?Closure $when = null): void
    {
        // Set a default truth test if none is provided.
        $when ??= fn() => true;

        // Remove duplicates from the callback collection.
        $this->forgetDuplicates();

        // Iterate over each callback and invoke it if the truth test passes.
        foreach ($this->callbacks as $index => $callback) {
            if ($when($callback)) {
                // Safely execute the callback, handling any exceptions.
                $this->rescue($callback);
            }

            // Remove the invoked callback from the collection.
            unset($this->callbacks[$index]);
        }
    }

    /**
     * Remove any deferred callbacks with the given name.
     *
     * This method filters out callbacks from the collection based on the
     * provided name, allowing for easy management and removal of specific
     * callbacks.
     *
     * @param  string  $name  The name of the callback to remove.
     */
    public function forget(string $name): void
    {
        // Filter the callbacks collection to remove callbacks with the given name.
        $this->callbacks = collect($this->callbacks)
            ->reject(fn($callback) => $callback->name === $name)
            ->values()
            ->all();
    }

    /**
     * Determine if the collection has a callback with the given key.
     *
     * This method checks if a callback exists at the specified key in the collection.
     *
     * @param  mixed  $offset  The key to check in the collection.
     *
     * @return bool True if the callback exists, false otherwise.
     */
    public function offsetExists(mixed $offset): bool
    {
        // Ensure duplicates are removed before checking existence.
        $this->forgetDuplicates();

        return isset($this->callbacks[$offset]);
    }

    /**
     * Get the callback with the given key.
     *
     * This method retrieves the callback stored at the specified key,
     * allowing for access to individual callbacks in the collection.
     *
     * @param  mixed  $offset  The key of the callback to retrieve.
     *
     * @return mixed The callback associated with the specified key.
     */
    public function offsetGet(mixed $offset): mixed
    {
        // Ensure duplicates are removed before getting the callback.
        $this->forgetDuplicates();

        return $this->callbacks[$offset];
    }

    /**
     * Set the callback with the given key.
     *
     * This method allows adding or updating a callback in the collection
     * at the specified key. If the key is null, the callback is appended
     * to the end of the collection.
     *
     * @param  mixed  $offset  The key under which to store the callback.
     * @param  mixed  $value  The callback to store in the collection.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Append the callback if the offset is null; otherwise, set it at the specified key.
        if ($offset === null) {
            $this->callbacks[] = $value;
        } else {
            $this->callbacks[$offset] = $value;
        }
    }

    /**
     * Remove the callback with the given key from the collection.
     *
     * This method allows for the removal of a specific callback based on its key,
     * updating the collection accordingly.
     *
     * @param  mixed  $offset  The key of the callback to remove.
     */
    public function offsetUnset(mixed $offset): void
    {
        // Ensure duplicates are removed before unsetting the callback.
        $this->forgetDuplicates();

        unset($this->callbacks[$offset]);
    }

    /**
     * Remove any duplicate callbacks.
     *
     * This method ensures that only unique callbacks remain in the collection,
     * keeping the last occurrence of each callback by name and removing earlier duplicates.
     *
     * @return $this
     */
    protected function forgetDuplicates(): self
    {
        // Reverse the collection, filter duplicates, and then restore the original order.
        $this->callbacks = collect($this->callbacks)
            ->reverse()
            ->unique(fn($c) => $c->name)
            ->reverse()
            ->values()
            ->all();

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Catch a potential exception and return a default value.
     *
     * @template TValue
     * @template TFallback
     *
     * @param  callable(): TValue  $callback
     * @param  (callable(Throwable): TFallback)|TFallback  $rescue
     * @param  bool|callable(Throwable): bool  $report
     *
     * @return TValue|TFallback
     */
    protected function rescue(callable $callback, $rescue = null, $report = true)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            if (value($report, $e)) {
                throw $e;
            }

            return value($rescue, $e);
        }
    }
}
