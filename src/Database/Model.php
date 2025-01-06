<?php

declare(strict_types=1);

namespace Maginium\Framework\Database;

use AllowDynamicProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Maginium\Foundation\Enums\DataType;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Container\Facades\Container;
use Maginium\Framework\Database\Concerns\HasTimestamps;
use Maginium\Framework\Database\Helpers\Model as ModelHelper;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Database\Traits\Identifiable;
use Maginium\Framework\Database\Traits\Searchable;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;
use Override;

/**
 * Abstract base class representing a custom model in the application.
 *
 * This class extends Magento's `AbstractModel` and incorporates additional traits and logic
 * to provide extra features such as global query scopes, timestamps, UUID handling, and event
 * dispatching. It acts as a foundational class for custom model models that require features
 * beyond the default Magento model.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @property string $slugKey The key used for model slugs, typically for URL slugs.
 */
#[AllowDynamicProperties] //@phpstan-ignore-line
class Model extends AbstractModel implements ModelInterface
{
    // Adds conditional logic support.
    use Conditionable;
    // Forwards dynamic calls to other methods.
    use ForwardsCalls;
    // Adds factory creation support.
    use HasFactory;
    // Handles timestamp fields (`created_at`, `updated_at`).
    use HasTimestamps;
    // For handling model identification.
    use Identifiable;
    // Allows dynamic method calls via registered macros.
    use Macroable {
        __call as macroCall;
        __callStatic as macroCallStatic;
    }

    // Adds search functionality to the model.
    use Searchable;

    /**
     * The name of the database table associated with the model.
     * This can be used by the ORM to determine the target table for queries.
     *
     * @var string|null
     */
    public static string $table;

    /**
     * The name of the primary key field for the model.
     *
     * This is typically 'id', but could be customized based on the model.
     *
     * @var string
     */
    public static string $primaryKey = 'id';

    /**
     * The event prefix used when firing model-related events.
     *
     * This is used to generate event names when the model is saved, deleted, etc.
     *
     * @var string
     */
    public static string $eventPrefix = 'abstract';

    /**
     * The event object type for the model's events.
     *
     * The event object represents the model instance when events are triggered.
     *
     * @var string
     */
    public static string $eventObject = 'object';

    /**
     * The "type" of the primary key ID.
     *
     * This defines the data type of the primary key, usually 'int', but could be 'string'
     * for UUIDs or other types.
     *
     * @var string
     */
    public static string $keyType = DataType::INT;

    /**
     * The array of booted models.
     *
     * Tracks the models that have been booted to avoid multiple booting calls.
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * The array of trait initializers that will be called on each new instance.
     *
     * @var array
     */
    protected static $traitInitializers = [];

    /**
     * The instance of the original Magento model.
     *
     * Holds the resolved Magento model (e.g., Customer model) to interact with.
     *
     * @var class-string<AbstractModel>
     */
    protected $baseModel;

    /**
     * The resolved model instance.
     *
     * This is the model that is dynamically resolved and set by the subclass.
     * It allows the parent class to interact with the resolved model methods.
     *
     * @var AbstractModel|null
     */
    protected $model;

    /**
     * Create a new instance of the model and optionally populate it with the provided data.
     *
     * This is a factory-style method that allows you to instantiate the model and populate it
     * with data. It returns the newly created instance, which can then be used as needed.
     *
     * @param array $attributes An optional array of attributes to initialize the model with.
     *                          These attributes can be set on the instance upon creation.
     *
     * @return ModelInterface The newly created instance of the model, populated with the provided attributes.
     */
    public static function make(array $attributes = []): ModelInterface
    {
        // Create a new instance of the model with the given attributes
        $instance = Container::make(self::class, ['data' => $attributes]);

        // Return the created instance
        return $instance;
    }

    /**
     * Perform any actions required before the model boots.
     *
     * This method is called just before the model is booted and should be used to
     * handle any initialization or pre-boot logic.
     *
     * @return void
     */
    protected static function booting()
    {
        // Placeholder for pre-boot actions.
    }

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
        static::bootTraits();
    }

    /**
     * Perform any actions required after the model boots.
     *
     * This method is triggered after the model is fully initialized.
     *
     * @return void
     */
    protected static function booted()
    {
        // Placeholder for post-boot actions.
    }

    /**
     * Boot all of the bootable traits on the model.
     *
     * This method is responsible for invoking trait-specific boot or initialize methods.
     * It ensures that traits used in the class are properly initialized by calling their
     * respective `boot` and `initialize` methods if they exist. This is an essential
     * part of the model's lifecycle.
     *
     * @return void
     */
    protected static function bootTraits(): void
    {
        // Get the current class name.
        $class = static::class;

        // Initialize an array to track already booted methods to avoid duplicates.
        $booted = [];

        // Initialize trait-specific initializers for the current class.
        static::$traitInitializers[$class] = [];

        // Iterate over all traits used by this class and its parent classes.
        foreach (class_uses_recursive($class) as $trait) {
            // Construct the boot method name for the trait.
            $method = 'boot' . class_basename($trait);

            // Check if the trait has a boot method and it hasn't been called yet.
            if (Reflection::methodExists($class, $method) && ! in_array($method, $booted)) {
                // Invoke the boot method.
                forward_static_call([$class, $method]);

                // Mark the method as booted to prevent re-execution.
                $booted[] = $method;
            }

            // Look for an initializer method for the trait and add it to the initializer list.
            if (Reflection::methodExists($class, $method = 'initialize' . class_basename($trait))) {
                static::$traitInitializers[$class][] = $method;
                static::$traitInitializers[$class] = Arr::unique(static::$traitInitializers[$class]);
            }
        }
    }

    /**
     * Save the model data.
     *
     * This method delegates the responsibility of saving the current model data
     * to the resource model. It encapsulates the logic to persist the model's state
     * to the underlying database or storage.
     *
     * @throws Exception If saving the data fails.
     *
     * @return $this The current instance of the model after save operation.
     */
    public function save(): self
    {
        // Delegate the saving process to the resource model.
        $this->getResource()->save($this);

        return $this;
    }

    /**
     * Load object data.
     *
     * @param int $modelId
     * @param null|string $field
     *
     * @return $this
     *
     * @deprecated 100.1.0 because models must not be responsible for their own loading.
     * Service contracts should persist models. Use resource model "load" or collections to implement
     * service contract model loading operations.
     * @see we don't recommend this approach anymore
     */
    public function load($modelId, $field = null)
    {
        $this->_getResource()->load($this, $modelId, $field);

        $this->model->setData($this->getData());

        return $this;
    }

    /**
     * Getter for the model instance.
     *
     * @return AbstractModel|null The resolved model instance.
     */
    public function getModel(): ?AbstractModel
    {
        return $this->model;
    }

    /**
     * Setter for the model instance.
     *
     * This method allows for manually setting the resolved model instance.
     * It will replace the current model with the provided instance.
     *
     * @param AbstractModel $model The resolved model instance to set.
     *
     * @return void
     */
    public function setModel(AbstractModel $model): void
    {
        $this->model = $model;
    }

    /**
     * Get the event object type for the model's events.
     *
     * This method returns the event object type associated with the model's events.
     * The event object is used when dispatching or handling model events.
     *
     * @return string|null The event object type.
     */
    public function getEventObject(): ?string
    {
        return static::$eventObject;
    }

    /**
     * Id field name getter.
     *
     * @return string
     */
    #[Override]
    public function getIdFieldName()
    {
        return $this->getKeyName();
    }

    /**
     * Id field name getter.
     *
     * @return string
     */
    public function getKeyName()
    {
        return static::$primaryKey ?? parent::getIdFieldName();
    }

    /**
     * Set the primary key for the model.
     *
     * This method allows setting a custom primary key field name for the model.
     * By default, it uses the standard "id" field, but this method provides
     * flexibility in case a custom key is needed.
     *
     * @param string $key The custom primary key name.
     *
     * @return ModelInterface The current instance with the updated primary key name.
     */
    public function setKeyName(string $key): ModelInterface
    {
        parent::setIdFieldName($key);

        return $this;
    }

    /**
     * Get the fully qualified key name for the model's primary key.
     *
     * This method returns the fully qualified name of the primary key field,
     * including the table name if necessary. This is useful for SQL queries
     * involving joins or when the primary key is referenced in other tables.
     *
     * @return string The fully qualified key name.
     */
    public function getQualifiedKeyName(): string
    {
        return $this->qualifyColumn($this->getKeyName());
    }

    /**
     * Get the auto-incrementing key type for the model.
     *
     * This method returns the data type of the primary key. The type is typically
     * set to "int" but could also be "string" if UUIDs are used as primary keys.
     *
     * @return string|null The data type of the primary key.
     */
    public function getKeyType(): ?string
    {
        return static::$keyType;
    }

    /**
     * Set the data type for the primary key.
     *
     * This method allows setting a custom data type for the primary key. It can
     * be useful when using non-integer primary keys, such as UUIDs.
     *
     * @param string $type The new data type for the primary key (e.g., 'string' or 'int').
     *
     * @return ModelInterface The current instance with the updated key type.
     */
    public function setKeyType(string $type): ModelInterface
    {
        static::$keyType = $type;

        return $this;
    }

    /**
     * Qualify the given column name by prefixing it with the model's table name.
     *
     * This method ensures that the column name is properly qualified with the
     * table name, which is essential for constructing SQL queries. If the column
     * is already qualified (contains a dot), the method returns it as is.
     *
     * @param string $column The column name to qualify.
     *
     * @return string The qualified column name, prefixed with the table name.
     */
    public function qualifyColumn(string $column): string
    {
        // If the column is already qualified, return it as is.
        if (str_contains($column, '.')) {
            return $column;
        }

        // Otherwise, qualify the column with the table name.
        return $this->getTableName() . '.' . $column;
    }

    /**
     * Qualify the given columns with the model's table.
     *
     * This method takes an array of column names and qualifies each column by appending the model's table name
     * to the column name. This is useful when working with queries that involve multiple tables or when column
     * names might clash with others. The qualified column is in the form of `table.column`.
     *
     * @param  array  $columns Array of column names to be qualified.
     *
     * @return array Qualified column names.
     */
    public function qualifyColumns($columns)
    {
        // Use the 'collect' method to wrap the columns array into a collection.
        // Then, map each column to its qualified name using the 'qualifyColumn' method.
        return collect($columns)->map(fn($column) => $this->qualifyColumn($column))->all();
    }

    /**
     * Get the main table name associated with the resource model.
     *
     * This method returns the table name that the model is associated with. It is used
     * for generating SQL queries and identifying which table the model refers to.
     * It defaults to the static `$table` property of the model class.
     *
     * @return string The table name associated with the model.
     */
    public function getTableName(): string
    {
        return static::$table ?? $this->getResource()->getMainTable();
    }

    /**
     * Check if the object has unsaved changes (is "dirty").
     *
     * This method checks whether the model has any changes that have not been saved to the database.
     * It can check specific keys (e.g., a single column or an array of columns) or check the entire model.
     *
     * @param string|array|null $keys The specific key(s) to check for changes. If null, checks the entire model.
     *
     * @return bool True if the model has unsaved changes, false otherwise.
     */
    public function isDirty($keys = null): bool
    {
        if ($keys !== null) {
            // If specific keys are provided, ensure they are in an array format.
            $keys = Validator::isString($keys) ? [$keys] : $keys;

            // Loop through each key to check if it has unsaved changes.
            foreach ((array)$keys as $key) {
                if (parent::dataHasChangedFor($key)) {
                    // Return true if any key has unsaved changes.
                    return true;
                }
            }

            // Return false if no changes were found for the specified keys.
            return false;
        }

        // If no keys are specified, check if there are any changes to the model as a whole.
        return $this->_hasDataChanges;
    }

    /**
     * Check if the object data is unchanged (clean).
     *
     * This method checks whether the model has any unsaved changes or if it remains in its original state.
     * It can check specific keys or the entire model for changes.
     *
     * @param string|array|null $keys The specific key(s) to check for changes. If null, checks the entire model.
     *
     * @return bool True if the model has no unsaved changes, false if there are changes.
     */
    public function isClean($keys = null): bool
    {
        if ($keys !== null) {
            // If specific keys are provided, convert them to an array if necessary.
            $keys = Validator::isString($keys) ? [$keys] : $keys;

            // Loop through each key and check for changes.
            foreach ((array)$keys as $key) {
                if (parent::dataHasChangedFor($key)) {
                    // Return false if any key has unsaved changes.
                    return false;
                }
            }

            // Return true if none of the keys have changes.
            return true;
        }

        // Return true if the model is entirely unchanged.
        return ! $this->_hasDataChanges;
    }

    /**
     * Convert the group instance to an associative array.
     *
     * This method transforms the group instance into an associative array representation,
     * allowing for optional filtering of specific keys.
     *
     * @param array $keys Optional array of keys to include in the resulting array.
     *                    Defaults to all keys ('*') if not specified.
     *
     * @return array Associative array representation of the group data.
     */
    #[Override]
    public function toArray(array $keys = ['*']): array
    {
        // Get the initial data source (either `dataArray` or model data)
        $data = collect($this->getData());

        // Clear the `dataArray` to avoid unintended reuse
        $this->dataArray = null;

        // Return the full data if no specific keys are provided or '*' is included
        if (empty($keys) || in_array('*', $keys, true)) {
            return $data->toArray();
        }

        // Filter and return only the specified keys
        return $data->only($keys)->toArray();
    }

    /**
     * Get the instance as an array.
     *
     * This method delegates to `toArray` to convert the group instance into an array,
     * optionally including only specific keys.
     *
     * @param array $keys Optional array of keys to include in the resulting array.
     *                    Defaults to all keys ('*') if not specified.
     *
     * @return array The model's data as an associative array.
     */
    public function toDataArray(array $keys = ['*']): array
    {
        // Delegate to the `toArray` method for conversion and key filtering
        return $this->toArray($keys);
    }

    /**
     * Populate the model with data from an array, setting properties as needed.
     *
     * This method assumes that the keys in the provided `$data` array correspond
     * to the property names of the model. It uses `setData` to assign values to
     * the model's properties.
     *
     * @param array $data Associative array of data to populate the model.
     *
     * @return ModelInterface Returns the current instance of the model for method chaining.
     */
    public function fill(array $data): ModelInterface
    {
        // Set the data for the model, mapping the array values to the model's properties.
        $this->setData($data);

        // Return the current instance for method chaining.
        return $this;
    }

    /**
     * Returns eventPrefix.
     *
     * This method simply returns the event prefix for this model, which is typically
     * used to differentiate events for different types of models or models.
     *
     * @return string|null The event prefix for the model.
     */
    public function getEventPrefix(): ?string
    {
        return static::$eventPrefix;
    }

    /**
     * Fire the specified event.
     *
     * This method allows you to trigger a custom event in the system. The event can
     * be handled by listeners who are subscribed to it.
     *
     * @param string $event The name of the event.
     * @param mixed $data Additional data to pass to the event listeners.
     *
     * @return void
     */
    public function fireEvent(string $event, $data): void
    {
        // Dispatch the event with the provided data to all listeners.
        $this->dispatch($event, $data);
    }

    /**
     * BaseModel constructor.
     *
     * Resolves and initializes the original Magento model and ensures the boot process is handled.
     */
    public function _construct()
    {
        // Call the parent constructor to ensure necessary initialization
        parent::_construct();

        // Ensure the model is booted if it hasn't already been booted
        $this->bootIfNotBooted();

        // Initialize any traits on the model that require initialization
        $this->initializeTraits();

        // Resolve and set the model specified by the subclass
        $this->resolveBaseModel();

        // Set up event prefix and object
        $this->setEventProperties();

        // Initialize resource model if available
        $this->initializeResourceModel();
    }

    /**
     * Fire a model event.
     *
     * This method is responsible for dispatching model events. It uses Magento's event
     * manager to trigger events that are part of the model lifecycle.
     *
     * @param string $event The name of the event to fire.
     * @param bool $halt Whether to halt the event dispatching (default: true).
     *
     * @return mixed The result of the event dispatch.
     */
    protected function fireModelEvent(string $event)
    {
        // Dispatch a model-specific event using the event prefix and event name.
        $this->dispatch($this->_eventPrefix . $event, $this->_getEventData());
    }

    /**
     * Get array of objects transferred to default events processing.
     *
     * This method prepares the data for event processing, returning an array
     * with the necessary data that will be dispatched when an event is fired.
     *
     * @return array The event data, typically containing the model instance and any relevant data.
     */
    protected function _getEventData(): array
    {
        return [
            'data_object' => $this, // Include the current model instance as part of the event data.
            $this->getEventObject() => $this, // Include the event object itself.
        ];
    }

    /**
     * Set the event prefix and object properties.
     *
     * @return void
     */
    private function setEventProperties()
    {
        // Set the event prefix by calling the getEventPrefix method
        $this->_eventPrefix = $this->getEventPrefix();

        // Set the event object by calling the getEventObject method
        $this->_eventObject = $this->getEventObject();
    }

    /**
     * Initialize the resource model based on the static class or the helper.
     *
     * @return void
     */
    private function initializeResourceModel()
    {
        // Check if a resource model is statically defined for the class, otherwise fallback to the helper
        $resourceModel = static::$resourceModel ?? ModelHelper::getResourceModel(static::class);

        // If a resource model is found, initialize it
        if ($resourceModel) {
            // Initialize the resource model for the current model instance
            $this->_init($resourceModel);
        }
    }

    /**
     * Fire a model event.
     *
     * This method dispatches a model event using Magento's event manager. It triggers
     * events that are part of the model lifecycle and passes the model instance along
     * with any additional data if provided.
     *
     * @param string $event The name of the event to fire.
     * @param array $data Additional data to pass with the event (optional).
     *
     * @return mixed The result of the event dispatch.
     */
    private function dispatch(string $event, array $data = [])
    {
        // If data is not empty, dispatch the event with the provided data
        if (! Validator::isEmpty($data)) {
            return $this->_eventManager->dispatch($event, $data);
        }

        // If no data is provided, dispatch the event with the model instance
        return $this->_eventManager->dispatch($event, ['model' => $this]);
    }

    /**
     * Boot the model if it hasn't been booted already.
     *
     * This method ensures the model goes through its boot process, including firing events
     * and invoking boot hooks if it hasn't been booted yet.
     *
     * @return void
     */
    private function bootIfNotBooted()
    {
        // Check if the model has already been booted
        if (! isset(static::$booted[static::class])) {
            // Mark the model as booted
            static::$booted[static::class] = true;

            // Fire the 'booting' event, allowing for any pre-boot logic
            $this->fireModelEvent('booting');

            // Call static methods for pre-boot, boot, and post-boot actions
            static::booting();
            static::boot();
            static::booted();

            // Fire the 'booted' event once booting is complete
            $this->fireModelEvent('booted');
        }
    }

    /**
     * Initialize traits that require initialization during model creation.
     *
     * This method loops over all traits used by the model and calls their initialization
     * methods (e.g., `initializeSomeTrait`) if they exist.
     *
     * @return void
     */
    private function initializeTraits()
    {
        // Loop through the traits that this model uses
        foreach (static::$traitInitializers[static::class] as $method) {
            // Call the initialization method for each trait
            $this->{$method}();
        }
    }

    /**
     * Resolve and set the model instance to the `model` property.
     *
     * This method checks if the subclass has set a `baseModel` property and uses
     * it to resolve the corresponding model. This allows the parent class to call
     * methods from the resolved model.
     *
     * @return void
     */
    private function resolveBaseModel()
    {
        // Check if the subclass has set a resolved model class
        if ($this->baseModel) {
            // Resolve the model and set it to the `model` property
            try {
                $this->setModel(Container::make($this->baseModel, ['data' => $this->getData()]));
            } catch (LocalizedException $e) {
                // Handle any resolution issues with the resolved model
                throw new LocalizedException(__('Unable to resolve the resolved model: %1', $this->baseModel));
            }
        }
    }

    /**
     * When a model is being unserialized, check if it needs to be booted.
     *
     * This method is invoked when the model object is unserialized from storage (e.g., session, cache).
     * It ensures that traits are initialized upon restoration.
     *
     * @return void
     */
    #[Override]
    public function __wakeup()
    {
        // Boot the model when the object is unserialized
        $this->bootIfNotBooted();

        // Initialize traits when the object is unserialized
        $this->initializeTraits();

        // Call the parent class's wakeup method to perform any additional restoration logic
        parent::__wakeup();
    }

    /**
     * Handle dynamic method calls to the resolved model.
     *
     * This method forwards unhandled method calls to the resolved `model`.
     * It allows the custom model to delegate functionality back to the resolved model.
     *
     * @param string $method The method name being called.
     * @param array $parameters The parameters for the method.
     *
     * @return mixed The result of the method call.
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        // Check if the method exists on the resolved model before forwarding
        if (Reflection::methodExists($this->model, $method)) {
            return $this->forwardDecoratedCallTo($this->model, $method, $parameters);
        }

        // If the method doesn't exist on the resolved model, fall back to the parent model's __call
        return parent::__call($method, $parameters);
    }
}
