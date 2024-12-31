<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Traits;

use Illuminate\Support\HigherOrderTapProxy;

/**
 * Trait Tappable.
 *
 * This trait provides a convenient method to "tap" into the instance.
 * The `tap()` method allows you to call a closure with the current object instance, and return the object instance afterward.
 * It is useful for performing side effects without breaking the method chain or modifying the object.
 */
trait Tappable
{
    /**
     * Call the given Closure with this instance, then return the instance.
     *
     * This method allows you to "tap" into the instance to perform any side effects using the provided callback,
     * without affecting the instance itself. If no callback is provided, a `HigherOrderTapProxy` is returned, allowing
     * you to call methods directly on the tapped instance within a chain.
     *
     * @param  (callable($this): mixed)|null  $callback A callback to execute with the current object instance.
     *
     * @return ($callback is null ? HigherOrderTapProxy : $this) Returns the current instance or a proxy when no callback is provided.
     */
    public function tap($callback = null)
    {
        // Use Laravel's tap() helper to call the callback with $this, then return $this.
        return tap($this, $callback);
    }
}
