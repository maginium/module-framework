<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces\Data;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Interfaces\DataObjectInterface;
use Maginium\Framework\Database\Interfaces\SearchableInterface;

/**
 * Interface ModelInterface.
 *
 * This interface defines the contract for entities.
 *
 * @method static self make(array $attributes = []) Create a new instance of the model with the provided attributes.
 * @method mixed getId() Get the Entity ID.
 * @method self save() Save object data.
 * @method self delete() Delete object from the database.
 * @method array getIdentities() Get the identities associated with the model.
 * @method AbstractDb|null getResource() Get the resource model associated with the current model.
 * @method string getKeyName() Get the key name for the model.
 * @method self setKeyName(string $key) Set the key name for the model.
 * @method string getQualifiedKeyName() Get the fully qualified key name for the model.
 * @method string getKeyType() Get the key type for the model.
 * @method self setKeyType(string $type) Set the key type for the model.
 * @method string qualifyColumn(string $column) Qualify a column name by adding the table prefix or other namespace information.
 * @method array qualifyColumns(array $columns) Qualify multiple column names by adding table prefixes or namespace information.
 * @method bool getIncrementing() Check if the model has incrementing keys (e.g., auto-increment IDs).
 * @method self setIncrementing(bool $value) Set whether the model has incrementing keys (e.g., auto-increment IDs).
 * @method array toArray(array $keys = ['*']) Convert the model's data to an array format.
 * @method array toDataArray(array $keys = ['*']) Convert the model's data to an array format.
 * @method self fill(array $data) Fill the model with the given data.
 * @method self setId(int $id) Set the Entity ID.
 * @method DataObjectInterface loadBy(mixed $value, ?string $field = null) Load object data by model ID or another field.
 * @method string getTableName() Get the main table name associated with the resource model.
 * @method bool isDirty(string|array|null $keys = null) Check if the object has changes in its data.
 * @method bool isClean(string|array|null $keys = null) Check if the object data is clean (unchanged).
 * @method bool isObjectNew(bool|null $flag = null) Check object state (true - if it is object without ID, just created).
 * @method void fireEvent(string $event, mixed $data) Fire the specified event.
 * @method void fireModelEvent(string $event) Fire the specified model event.
 * @method void setIdFieldName(string $name) Set the ID field name.
 * @method bool isDeleted(bool|null $isDeleted = null) Check if the model is marked as deleted.
 * @method bool hasDataChanges() Check if there are any data changes.
 * @method self setData(string $key, mixed $value = null) Set model data.
 * @method self unsetData(string|null $key = null) Unset model data.
 * @method void setDataChanges(bool $value) Set the data changes flag.
 * @method mixed getOrigData(string|null $key = null) Get the original data for a field.
 * @method void setOrigData(string|null $key = null, mixed|null $data = null) Set the original data for a field.
 * @method bool dataHasChangedFor(string $field) Check if data has changed for a specific field.
 * @method string getResourceName() Get the resource name for the model.
 * @method AbstractCollection getResourceCollection() Get the resource collection.
 * @method AbstractCollection getCollection() Get the collection of model instances.
 * @method void load(mixed $modelId, string|null $field = null) Load a model by its ID.
 * @method void beforeLoad(mixed $identifier, string|null $field = null) Perform actions before loading a model.
 * @method void afterLoad() Perform actions after loading a model.
 * @method bool isSaveAllowed() Check if saving the model is allowed.
 *
 * @property string $table The table associated with the model.
 * @property string $primaryKey The primary key for the model.
 * @property string $keyType The "type" of the primary key ID.
 */
interface ModelInterface extends DataObjectInterface, IdentityInterface, SearchableInterface
{
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
    public static function make(array $attributes = []): self;

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
    public function save(): self;

    /**
     * Get the identities for caching purposes.
     *
     * This method returns an array of unique identifiers used for caching the model.
     * The identity is typically a combination of the model's cache tag and its ID.
     *
     * @return array The array containing the model's cache identities.
     */
    public function getIdentities(): array;

    /**
     * Get the event object type for the model's events.
     *
     * This method returns the event object type associated with the model's events.
     * The event object is used when dispatching or handling model events.
     *
     * @return string|null The event object type.
     */
    public function getEventObject(): ?string;

    /**
     * Retrieve the model's resource (database handler).
     *
     * This method returns the resource associated with the model. The resource
     * is used to interact with the database (fetch, save, or delete data).
     * If the resource is not already set, the method attempts to retrieve it
     * from the container.
     *
     * @throws LocalizedException If neither `_resourceName` nor `_resource` is set.
     *
     * @return AbstractDb|null The resource model or null if not available.
     */
    public function getResource(): mixed;

    /**
     * Get the primary key name for the model.
     *
     * This method returns the primary key field name for the model, typically
     * "id". It is used to identify records in the database and perform actions
     * such as querying, updating, or deleting.
     *
     * @return string The name of the primary key field.
     */
    public function getKeyName(): string;

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
    public function setKeyName(string $key): self;

    /**
     * Get the fully qualified key name for the model's primary key.
     *
     * This method returns the fully qualified name of the primary key field,
     * including the table name if necessary. This is useful for SQL queries
     * involving joins or when the primary key is referenced in other tables.
     *
     * @return string The fully qualified key name.
     */
    public function getQualifiedKeyName(): string;

    /**
     * Get the auto-incrementing key type for the model.
     *
     * This method returns the data type of the primary key. The type is typically
     * set to "int" but could also be "string" if UUIDs are used as primary keys.
     *
     * @return string|null The data type of the primary key.
     */
    public function getKeyType(): ?string;

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
    public function setKeyType(string $type): self;

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
    public function qualifyColumn(string $column): string;

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
    public function qualifyColumns($columns);

    /**
     * Get the main table name associated with the resource model.
     *
     * This method returns the table name that the model is associated with. It is used
     * for generating SQL queries and identifying which table the model refers to.
     * It defaults to the static `$table` property of the model class.
     *
     * @return string|null The table name associated with the model.
     */
    public function getTableName(): ?string;

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
    public function isDirty($keys = null): bool;

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
    public function isClean($keys = null): bool;

    /**
     * Get the instance as an array.
     *
     * This method converts the model's instance into an array representation, optionally including specific keys.
     * It allows easy manipulation or output of model data as an array.
     *
     * @param array $keys Optional array of keys to include in the resulting array.
     *                    Defaults to all columns (`*`).
     *
     * @return array The model's data as an array.
     */
    public function toDataArray(array $keys = ['*']): array;

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
    public function fill(array $data): self;

    /**
     * Returns eventPrefix.
     *
     * This method simply returns the event prefix for this model, which is typically
     * used to differentiate events for different types of models or entities.
     *
     * @return string|null The event prefix for the model.
     */
    public function getEventPrefix(): ?string;

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
    public function fireEvent(string $event, $data): void;

    /**
     * Fire the specified model event.
     *
     * Similar to `fireEvent`, but this method specifically fires events related to
     * the model, potentially adding model-specific data to the event.
     *
     * @param string $event The name of the event.
     *
     * @return void
     */
    public function fireModelEvent(string $event): void;

    /**
     * Initialize the model.
     *
     * This method is called during object construction to ensure the model is properly initialized.
     * It initializes traits, sets the resource model, defines the ID field name, and sets event-related properties.
     */
    public function _construct(): void;

    /**
     * Set the ID field name.
     *
     * Sets the name of the field that acts as the ID for the model.
     *
     * @param string $name The name of the ID field.
     *
     * @return ModelInterface The loaded model instance.
     */
    public function setIdFieldName($name);

    /**
     * Get the ID field name.
     *
     * Returns the name of the ID field for the model.
     *
     * @return string The name of the ID field.
     */
    public function getIdFieldName();

    /**
     * Check if the model is marked as deleted.
     *
     * @param bool|null $isDeleted Optionally set the deleted flag.
     *
     * @return bool True if the model is deleted, false otherwise.
     */
    public function isDeleted($isDeleted = null);

    /**
     * Check if there are any data changes.
     *
     * @return bool True if there are changes to the model's data, false otherwise.
     */
    public function hasDataChanges();

    /**
     * Set model data.
     *
     * @param string $key The key for the data.
     * @param mixed $value The value to set.
     *
     * @return ModelInterface The current instance.
     */
    public function setData($key, $value = null);

    /**
     * Unset model data.
     *
     * @param string|null $key The key to unset.
     *
     * @return ModelInterface The current instance.
     */
    public function unsetData($key = null);

    /**
     * Set the data changes flag.
     *
     * @param bool $value The flag indicating whether data has changed.
     *
     * @return ModelInterface The loaded model instance.
     */
    public function setDataChanges($value);

    /**
     * Get the original data for a field.
     *
     * @param string|null $key The field to retrieve the original value for.
     *
     * @return mixed The original value of the field or null if not set.
     */
    public function getOrigData($key = null);

    /**
     * Set the original data for a field.
     *
     * @param string|null $key The field to set the original value for.
     * @param mixed|null $data The original data value.
     *
     * @return ModelInterface The loaded model instance.
     */
    public function setOrigData($key = null, $data = null);

    /**
     * Check if data has changed for a specific field.
     *
     * @param string $field The field to check for changes.
     *
     * @return bool True if the field has changed, false otherwise.
     */
    public function dataHasChangedFor($field);

    /**
     * Get the resource name for the model.
     *
     * @return string The resource name.
     */
    public function getResourceName();

    /**
     * Get the resource collection.
     *
     * @return AbstractCollection The resource collection.
     */
    public function getResourceCollection();

    /**
     * Get the collection of model instances.
     *
     * @return AbstractCollection The model collection.
     */
    public function getCollection();

    /**
     * Load a model by its ID.
     *
     * @param mixed $modelId The ID of the model to load.
     * @param string|null $field Optional. The field to load by, defaults to primary key.
     *
     * @return ModelInterface The loaded model instance.
     */
    public function load($modelId, $field = null);

    /**
     * Perform actions before loading a model.
     *
     * @param mixed $identifier The identifier to load.
     * @param string|null $field Optional. The field to load by, defaults to primary key.
     *
     * @return ModelInterface The loaded model instance.p
     */
    public function beforeLoad($identifier, $field = null);

    /**
     * Perform actions after loading a model.
     *
     * @return ModelInterface The loaded model instance.p
     */
    public function afterLoad();

    /**
     * Check if saving the model is allowed.
     *
     * @return bool True if saving is allowed, false otherwise.
     */
    public function isSaveAllowed();

    /**
     * Set the flag for data changes.
     *
     * @param bool $flag The flag indicating whether data has changed.
     *
     * @return void
     */
    public function setHasDataChanges($flag);
}
