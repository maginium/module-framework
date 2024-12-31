<?php

declare(strict_types=1);

namespace Maginium\Framework\Actions\Concerns;

use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Fluent;

/**
 * Trait AsObject.
 *
 * Provides a mechanism to create a new instance of a class using a dependency injection container.
 * This trait simplifies instance creation while ensuring dependencies are automatically resolved.
 *
 * Usage:
 * - Include this trait in any class where you want to enable the `make()` method for easy instantiation.
 *
 * Example:
 * ```php
 * class ExampleClass {
 *     use AsObject;
 * }
 *
 * $instance = ExampleClass::make();
 * ```
 *
 * @method static mixed make() Create a new instance of the class using the container.
 * @method static mixed run(mixed ...$arguments) Run the handle method of the current class with the given arguments.
 * @method static mixed runIf(bool $boolean, mixed ...$arguments) Run the `run()` method if the given boolean condition is true.
 * @method static mixed runUnless(bool $boolean, mixed ...$arguments) Run the `run()` method unless the given boolean condition is true.
 */
trait AsObject
{
    /**
     * Create a new instance of the class using the container.
     *
     * This method utilizes the `Container::make()` method to resolve an instance of the class
     * that uses this trait. It is designed to simplify the process of creating new instances
     * while ensuring that dependencies are automatically injected via the container.
     *
     * @param  mixed  ...$arguments  The arguments to pass to the `handle()` method.
     *
     * @return static  The instance of the class using this trait.
     */
    public static function make(mixed ...$arguments)
    {
        // Resolve and return a new instance of the current class using the container
        return Container::make(static::class, ...$arguments);
    }

    /**
     * Run the handle method of the current class with the given arguments.
     *
     * This method creates an instance of the class using `make()` and then calls the
     * `handle()` method on that instance, passing the provided arguments. The method assumes
     * that the class using this trait has a `handle()` method that processes the given arguments.
     *
     * @param  mixed  ...$arguments  The arguments to pass to the `handle()` method.
     *
     * @return mixed  The result of calling the `handle()` method.
     *
     * @see static::handle()
     */
    public static function run(mixed ...$arguments): mixed
    {
        // Create an instance of the class and call the handle method with the given arguments
        return static::make()->handle(...$arguments);
    }

    /**
     * Run the `run()` method if the given boolean condition is true.
     *
     * This method evaluates the provided boolean value. If the value is `true`, it calls
     * the `run()` method with the provided arguments. If the value is `false`, it returns
     * an empty `Fluent` object. The `Fluent` class is used here as a placeholder when the
     * condition is not met.
     *
     * @param  bool  $boolean  The condition to evaluate.
     * @param  mixed  ...$arguments  The arguments to pass to the `run()` method if the condition is true.
     *
     * @return mixed  The result of `run()` if the condition is true, or an empty `Fluent` object otherwise.
     */
    public static function runIf(bool $boolean, mixed ...$arguments): mixed
    {
        // Return the result of run() if the condition is true, or an empty Fluent object if false
        return $boolean ? static::run(...$arguments) : new Fluent;
    }

    /**
     * Run the `run()` method unless the given boolean condition is true.
     *
     * This method inverts the provided boolean condition and calls the `runIf()` method.
     * It is essentially the opposite of `runIf()`. If the condition is `false`, it calls
     * the `run()` method; otherwise, it returns an empty `Fluent` object.
     *
     * @param  bool  $boolean  The condition to evaluate.
     * @param  mixed  ...$arguments  The arguments to pass to the `run()` method unless the condition is true.
     *
     * @return mixed  The result of `run()` unless the condition is true, or an empty `Fluent` object if the condition is true.
     */
    public static function runUnless(bool $boolean, mixed ...$arguments): mixed
    {
        // Invert the boolean condition and call runIf()
        return static::runIf(! $boolean, ...$arguments);
    }
}
