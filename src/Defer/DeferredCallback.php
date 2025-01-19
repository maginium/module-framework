<?php

declare(strict_types=1);

namespace Maginium\Framework\Defer;

use Maginium\Framework\Support\Facades\Uuid;

/**
 * Class DeferredCallback.
 *
 * This class represents a deferred callback that can be invoked at a later time.
 * It allows for the registration of callbacks that can be named for easy cancellation
 * and can be configured to always run, even on unsuccessful requests or jobs.
 *
 * The DeferredCallback can be useful in scenarios where certain actions need to be
 * delayed until a specific point in time, such as after a job completes or during
 * a response cycle.
 */
class DeferredCallback
{
    /**
     * @var callable The callback function to be executed later.
     */
    public $callback;

    /**
     * @var string|null An optional name for the callback, used for identification or cancellation.
     */
    public ?string $name = null;

    /**
     * @var bool Indicates whether the callback should always be executed.
     */
    public bool $always = false;

    /**
     * Create a new deferred callback instance.
     *
     * The constructor initializes the deferred callback with the provided callable.
     * If a name is not provided, a unique UUID will be generated to identify the callback.
     *
     * @param  callable  $callback  The callback function to be executed later.
     * @param  string|null  $name  An optional name for the callback to allow for later identification or cancellation.
     * @param  bool  $always  Indicates if the callback should always be invoked.
     *
     * @return void
     */
    public function __construct(
        callable $callback,
        ?string $name = null,
        bool $always = false,
    ) {
        // Store the callback function to be executed later.
        $this->callback = $callback;

        // Assign a unique name using a UUID if not provided by the user.
        $this->name = $name ?? Uuid::generate();

        // Set whether this callback should always be executed.
        $this->always = $always;
    }

    /**
     * Invoke the deferred callback.
     *
     * This magic method allows the instance to be called as a function,
     * executing the registered callback. This method does not return any value.
     */
    public function __invoke(): void
    {
        // Execute the callback using call_user_func.
        call_user_func($this->callback);
    }

    /**
     * Invoke the deferred callback explicitly.
     *
     * This method executes the registered callback function. It is useful when
     * you want to invoke the callback manually instead of relying on magic methods.
     */
    public function invoke(): void
    {
        call_user_func($this->callback);
    }

    /**
     * Specify the name of the deferred callback so it can be cancelled later.
     *
     * This method allows setting or updating the name of the callback,
     * which can be used to reference and cancel the callback if needed.
     *
     * @param  string  $name  The name to assign to the deferred callback.
     *
     * @return $this
     */
    public function name(string $name): self
    {
        // Set the name for the deferred callback.
        $this->name = $name;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Indicate that the deferred callback should run even on unsuccessful requests and jobs.
     *
     * This method configures the callback to be executed regardless of the outcome
     * of the surrounding request or job, allowing for cleanup or logging actions.
     *
     * @param  bool  $always  Set to true to ensure the callback is always invoked.
     *
     * @return $this
     */
    public function always(bool $always = true): self
    {
        // Set the always property to determine invocation behavior.
        $this->always = $always;

        // Return the current instance to allow method chaining
        return $this;
    }
}
