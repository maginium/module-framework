<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Concerns;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Database\Helpers\Model as ModelHelper;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Database\Traits\Identifiable;
use Maginium\Framework\Database\Traits\Searchable;
use Maginium\Framework\Database\Traits\Traitable;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Validator;
use Override;

/**
 * Abstract class representing a custom model in the application.
 *
 * This class extends Magento's `AbstractModel` and incorporates various traits and macros
 * to extend functionality such as global query scopes, timestamps, UUID handling, and more.
 * It serves as a foundational class for custom model models that need additional features
 * beyond the default Magento model.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @property string $slugKey The key used for model slugs, typically used for URL slugs.
 * @property string $resourceModel The fully qualified class name of the resource model associated with the model.
 * @property string $table The table associated with the model.
 * @property string $primaryKey The name of the primary key field for the model.
 * @property string $keyType The "type" of the primary key ID.
 * @property string $eventPrefix The event prefix used when firing model-related events.
 * @property string $eventObject The event object type for the model's events.
 * @property string $dtoClass The Data Transfer Object (DTO) class associated with the model.
 */
trait HasEnhancedModel
{
    // Adds conditional logic support.
    use Conditionable;
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
    // Adds traitable functionality to the model.
    use Traitable {
        boot as traitBoot;
        __wakeup as traitWakeup;
        _construct as traitConstruct;
    }

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
     * The array of booted models.
     *
     * Tracks the models that have been booted to avoid multiple booting calls.
     *
     * @var array
     */
    protected static $booted = [];

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
        $instance = Container::make(static::class, ['data' => $attributes]);

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
    protected static function booting(): void
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
    protected static function boot(): void
    {
        static::traitBoot();
    }

    /**
     * Perform any actions required after the model boots.
     *
     * This method is triggered after the model is fully initialized.
     *
     * @return void
     */
    protected static function booted(): void
    {
        // Placeholder for post-boot actions.
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
     * @return static The current instance of the model after save operation.
     */
    public function save(): static
    {
        // Delegate the saving process to the resource model.
        $this->getResource()->save($this);

        return $this;
    }

    /**
     * Retrieve model resource.
     *
     * @return AbstractDb|mixed
     */
    public function getResource(): mixed
    {
        return $this->_getResource();
    }

    /**
     * Load object data.
     *
     * @param int $modelId
     * @param null|string $field
     *
     * @return $this
     */
    public function load($modelId, $field = null): static
    {
        $this->_getResource()->load($this, $modelId, $field);

        return $this;
    }

    /**
     * Get the identities for caching purposes.
     *
     * This method returns an array of unique identifiers used for caching the model.
     * The identity is typically a combination of the model's cache tag and its ID.
     *
     * @return array The array containing the model's cache identities.
     */
    public function getIdentities(): array
    {
        return [$this->_cacheTag . '_' . $this->getId()];
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
    public function getIdFieldName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Id field name getter.
     *
     * @return string
     */
    public function getKeyName(): string
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
    public function qualifyColumns($columns): array
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
        $data = collect(value: $this->getData());

        // Return the full data if no specific keys are provided or '*' is included
        if (Validator::isEmpty($keys) || Validator::inArray('*', $keys, true)) {
            return $this->getData();
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
        return parent::toArray($keys);
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
    public function _construct(): void
    {
        // Call the parent constructor to ensure necessary initialization
        parent::_construct();

        // Ensure the model is booted if it hasn't already been booted
        $this->bootIfNotBooted();

        // Initialize any traits on the model that require initialization
        $this->traitConstruct();

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
    public function fireModelEvent(string $event): void
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
    private function setEventProperties(): void
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
    private function initializeResourceModel(): void
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
    private function dispatch(string $event, array $data = []): mixed
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
    private function bootIfNotBooted(): void
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
     * When a model is being unserialized, check if it needs to be booted.
     *
     * This method is invoked when the model object is unserialized from storage (e.g., session, cache).
     * It ensures that traits are initialized upon restoration.
     *
     * @return void
     */
    // #[Override]
    // public function __wakeup()
    // {
    //     // Boot the model when the object is unserialized
    //     $this->bootIfNotBooted();

    //     // Initialize traits when the object is unserialized
    //     $this->traitWakeup();

    //     // Call the parent class's wakeup method to perform any additional restoration logic
    //     parent::__wakeup();
    // }

    /**
     * Dynamically handle method calls to the instance.
     *
     * This method is invoked when a method is called on the instance that does not exist.
     * It first checks if the method is a registered macro and calls it if found.
     * If the method is not a macro, it forwards the call to the parent class's `__call` method.
     *
     * @param string $method The name of the method being called.
     * @param array $parameters The parameters passed to the method.
     *
     * @return mixed|null The result of the method call, or `null` if the method does not exist.
     */
    public function __call($method, $parameters)
    {
        // If the method is a registered macro, call it
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        // Forward the call to the parent class if the method does not exist
        return parent::__call($method, $parameters);
    }

    /**
     * Handle dynamic static method calls to the model.
     *
     * This method is triggered when a static method is called that does not exist.
     * An instance of the class is created using the container, and the method is called
     * on the instance if it exists.
     *
     * @param string $method The name of the static method being called.
     * @param array $parameters The parameters passed to the static method.
     *
     * @return mixed|null The result of the method call on the resolved instance, or `null` if the method does not exist.
     */
    public static function __callStatic($method, $parameters)
    {
        // Create an instance of the class using the container
        $instance = static::make();

        // Call the method on the resolved instance
        return $instance->{$method}(...$parameters);
    }
}
