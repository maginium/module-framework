<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Traits;

use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Reflection;

/**
 * Trait Traitable.
 *
 * This trait provides functionality to initialize and boot traits within a model. It supports
 * the lifecycle of traits through initialization and boot methods, ensuring that trait-specific
 * logic is executed when the model is instantiated or unserialized.
 */
trait Traitable
{
    /**
     * A list of trait initializers that are executed when a new model instance is created.
     *
     * This array holds the initializers for each trait used in the class. The initializers are
     * executed when a new instance of the model is created, allowing the traits to initialize
     * specific properties or behaviors.
     *
     * @var array
     */
    protected static $traitInitializers = [];

    /**
     * Bootstrap the model and its traits.
     *
     * This method is invoked to initialize the model's properties, traits, and events
     * before it becomes available for use.
     *
     * @return void
     */
    protected static function boot()
    {
        // Boot all traits associated with this class
        static::bootTraits();
    }

    /**
     * Boot all of the bootable traits on the model.
     *
     * This method is responsible for booting all the traits that have been applied to the model.
     * It ensures that any `boot` or `initialize` methods within the traits are called, as part
     * of the model's lifecycle.
     *
     * The `boot` method will be executed for each trait if it exists, ensuring proper initialization
     * of all traits used by the model.
     *
     * @return void
     */
    protected static function bootTraits(): void
    {
        // Get the current class name.
        $class = static::class;

        // Initialize an array to track which methods have already been booted.
        $booted = [];

        // Initialize trait-specific initializers for the current class.
        static::$traitInitializers[$class] = [];

        // Iterate over all traits used by this class and its parent classes.
        foreach (class_uses_recursive($class) as $trait) {
            // Construct the boot method name for the trait.
            $method = 'boot' . class_basename($trait);

            // Check if the trait has a boot method and it hasn't been executed yet.
            if (Reflection::methodExists($class, $method) && ! in_array($method, $booted)) {
                // Execute the boot method for the trait.
                forward_static_call([$class, $method]);

                // Mark the method as booted to prevent re-execution.
                $booted[] = $method;
            }

            // Look for an initializer method for the trait.
            if (Reflection::methodExists($class, $method = 'initialize' . class_basename($trait))) {
                // Add the initializer method to the trait initializers list.
                static::$traitInitializers[$class][] = $method;
                static::$traitInitializers[$class] = Arr::unique(static::$traitInitializers[$class]);
            }
        }
    }

    /**
     * Initialize the traits.
     *
     * This method is called during object construction to ensure the model is properly initialized.
     * It initializes traits, sets the resource model, defines the ID field name, and sets event-related properties.
     */
    public function _construct(): void
    {
        // Boot all traits associated with this class
        $this->bootTraits();

        // Boot all traits associated with this class
        $this->initializeTraits();
    }

    /**
     * Initialize any initializable traits on the model.
     *
     * This method runs all the initializer methods for traits that are defined. Traits in PHP can
     * contain their own initialization logic, which will be executed when this method is called.
     *
     * @return void
     */
    protected function initializeTraits(): void
    {
        // Loop through each trait initializer method defined for this class.
        foreach (static::$traitInitializers[static::class] as $method) {
            // Call the trait's initializer method.
            $this->{$method}();
        }
    }

    /**
     * When a model is being unserialized, ensure traits are properly initialized.
     *
     * This method is invoked when the model object is unserialized from storage, such as from
     * a session or cache. It ensures that traits are initialized upon restoration, ensuring the
     * object's behavior is consistent after unserialization.
     *
     * @return void
     */
    public function __wakeup()
    {
        // Initialize traits when the object is unserialized.
        $this->initializeTraits();
    }
}
