<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Traits;

/**
 * Singleton trait provides a simple interface for treating a class as a singleton.
 * It ensures that only one instance of a class exists and provides global access to that instance.
 *
 * Usage:
 * - Access the singleton instance via `YourClass::instance()`.
 * - Reset the instance and its state via `YourClass::reset()`.
 */
trait Singleton
{
    /**
     * @var ?static The single instance of the class.
     */
    protected static $instance;

    /**
     * Constructs the singleton instance.
     *
     * The constructor is protected to prevent direct instantiation from outside the class.
     * It calls the `init()` method, which can be overridden to perform additional initialization.
     */
    final protected function __construct()
    {
        $this->init();
    }

    /**
     * Retrieves the singleton instance of the class.
     *
     * If the instance does not exist, it creates a new one.
     *
     * @return static The singleton instance of the class.
     */
    final public static function instance()
    {
        // If there is no instance, create a new one and assign it to the static property
        if (static::$instance === null) {
            static::$instance = new static;
        }

        // Return the existing or newly created instance
        return static::$instance;
    }

    /**
     * Clears the singleton instance.
     *
     * This method sets the static instance property to null, effectively removing the reference
     * to the current instance. This is useful for resetting or reinitializing the singleton.
     *
     * @return void
     */
    final public static function forgetInstance(): void
    {
        static::$instance = null;
    }

    /**
     * Resets the singleton instance to its default state.
     *
     * This method clears the current singleton instance and reinitializes it.
     * Override this method in a subclass to reset specific properties or perform
     * additional reset logic if needed.
     *
     * @return void
     */
    public static function reset(): void
    {
        // Clear the current singleton instance
        static::forgetInstance();

        // Reinitialize the singleton instance
        static::instance();
    }

    /**
     * Initializes the singleton instance.
     *
     * This method is called by the constructor and can be overridden in subclasses
     * to perform additional initialization tasks. By default, it does nothing.
     *
     * @return void
     */
    protected function init(): void
    {
    }

    /**
     * Prevents cloning of the singleton instance.
     *
     * This method is triggered if an attempt is made to clone the singleton instance.
     * It throws an error to prevent the creation of multiple instances.
     *
     * @ignore
     *
     * @return void
     */
    public function __clone()
    {
        trigger_error('Cloning ' . __CLASS__ . ' is not allowed.', E_USER_ERROR);
    }

    /**
     * Prevents unserializing of the singleton instance.
     *
     * This method is triggered if an attempt is made to unserialize the singleton instance.
     * It throws an error to prevent the creation of multiple instances.
     *
     * @ignore
     *
     * @return void
     */
    public function __wakeup(): void
    {
        trigger_error('Unserializing ' . __CLASS__ . ' is not allowed.', E_USER_ERROR);
    }
}
