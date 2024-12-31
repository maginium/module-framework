<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Traits;

/**
 * Trait Dumpable.
 *
 * This trait provides utility methods for dumping the current object instance along with additional arguments.
 * It can be useful for debugging purposes by printing the values of variables and objects.
 * The `dd()` method terminates execution after dumping, while the `dump()` method continues execution.
 */
trait Dumpable
{
    /**
     * Dump the current object instance and any additional arguments, then terminate execution.
     *
     * This method allows you to inspect the current object and any additional passed variables.
     * It uses the Laravel `dd()` (dump and die) function to output the information and stop the script.
     *
     * @param mixed ...$args Any number of additional arguments to dump along with the current object.
     *
     * @return never This method terminates the script execution.
     */
    public function dd(...$args)
    {
        // Use Laravel's dd() function to dump the current object ($this) and any additional arguments, then stop execution.
        dd($this, ...$args);
    }

    /**
     * Dump the current object instance and any additional arguments without terminating execution.
     *
     * This method behaves similarly to `dd()`, but it does not terminate the script after dumping the information.
     * It is useful for debugging while allowing the program to continue running after the dump.
     *
     * @param mixed ...$args Any number of additional arguments to dump along with the current object.
     *
     * @return $this Returns the current object instance to allow for method chaining.
     */
    public function dump(...$args)
    {
        // Use Laravel's dump() function to output the current object ($this) and any additional arguments.
        dump($this, ...$args);

        // Return the current object to enable method chaining.
        return $this;
    }
}
