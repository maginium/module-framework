<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Eloquent;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Magento\Elasticsearch\Model\Adapter\Index\IndexNameResolver;
use Maginium\Framework\Elasticsearch\Connection;
use Maginium\Framework\Elasticsearch\Eloquent\Docs\ModelDocs;
use Maginium\Framework\Elasticsearch\Meta\ModelMetaData;
use Maginium\Framework\Elasticsearch\Query\Builder as QueryBuilder;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\StoreManager;
use Maginium\Framework\Support\Str;
use RuntimeException;

/**
 * Abstract base model class for Elasticsearch-based Eloquent models.
 *
 * This class extends the default Eloquent `Model` to incorporate Elasticsearch-specific logic.
 * It manages model attributes, indexing, and primary key handling tailored for Elasticsearch.
 * Additionally, it integrates custom relations, document metadata, and collection handling.
 *
 * @property object $searchHighlights The search highlights for the model instance.
 * @property array $searchHighlightsAsArray The search highlights represented as an array.
 * @property object $withHighlights The highlights to be included with the model instance.
 */
abstract class Model extends BaseModel
{
    use HasCollection;
    use HybridRelations;
    use ModelDocs;

    /**
     * The maximum size of results that can be retrieved from Elasticsearch.
     * This constant can be used to set the `size` parameter for Elasticsearch queries.
     *
     * @var int
     */
    public const MAX_SIZE = 1000;

    /**
     * The Elasticsearch index associated with the model.
     * This is typically set to the name of the Elasticsearch index this model represents.
     *
     * @var string|null
     */
    protected $index;

    /**
     * The record index used for internal purposes.
     * This may be different from the actual Elasticsearch index, depending on the model's context.
     *
     * @var string|null
     */
    protected ?string $recordIndex;

    /**
     * The primary key for the model, which defaults to '_id' in Elasticsearch.
     *
     * @var string
     */
    protected $primaryKey = '_id';

    /**
     * The data type of the primary key. Set to 'string' for Elasticsearch.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The parent relation for the model, if any.
     * This property is used in scenarios where models are part of a larger hierarchical structure.
     *
     * @var Relation|null
     */
    protected ?Relation $parentRelation;

    /**
     * The model's metadata, which may include additional configuration for Elasticsearch operations.
     *
     * @var ModelMetaData|null
     */
    protected ?ModelMetaData $_meta;

    /**
     * Model constructor.
     *
     * Initializes the model by performing essential setup tasks, including:
     * - Setting initial model attributes.
     * - Setting the index used for the model.
     * - Setting the record index to ensure proper organization within the index.
     * - Ensuring the correct primary key is enforced.
     *
     * @param array $attributes Initial model attributes.
     */
    public function __construct(array $attributes = [])
    {
        // Store the initial attributes in the $data property.
        $this->data = $attributes;

        // Call the parent constructor to handle basic attribute initialization.
        parent::__construct($attributes);

        // Set the index for the model, typically used in search or data indexing systems.
        $this->setIndex();

        // Set the record index, ensuring unique and consistent indexing within the model.
        $this->setRecordIndex();

        // Force the use of the defined primary key, overriding any default behavior.
        $this->forcePrimaryKey();
    }

    /**
     * Force the model to use '_id' as the primary key.
     *
     * This method overrides the default primary key handling in Eloquent and ensures that
     * the model always uses '_id' for Elasticsearch documents.
     */
    public function forcePrimaryKey(): void
    {
        $this->primaryKey = '_id';
    }

    /**
     * Get the record index used for internal reference.
     *
     * This method returns the record index, which may either be the `index` or a custom value.
     *
     * @return string|null The record index, or null if not set.
     */
    public function getRecordIndex(): ?string
    {
        return $this->recordIndex;
    }

    /**
     * Set the record index for the model.
     *
     * This method sets the record index to either a provided value or defaults to the `index` value.
     * The record index is typically used for internal reference, while the `index` is used for Elasticsearch-specific operations.
     *
     * @param string|null $recordIndex The custom record index value. If not provided, defaults to the model's `index`.
     *
     * @return string|null The set record index value.
     */
    public function setRecordIndex($recordIndex = null): ?string
    {
        // If a custom record index is provided, use it; otherwise, default to the model's `index`.
        if ($recordIndex) {
            return $this->recordIndex = $recordIndex;
        }

        return $this->recordIndex = $this->index;
    }

    /**
     * Set the table (index) associated with the model.
     *
     * This method allows explicitly setting the Elasticsearch index name. In Elasticsearch, the term `index` is used to represent
     * the equivalent of a database table in relational models, so the `table` property is unset to avoid confusion.
     *
     * @param string $index The name of the Elasticsearch index.
     *
     * @return $this The current instance of the model.
     */
    public function setTable($index)
    {
        // Set the model's `index` to the provided value and unset the `table` property used in standard Eloquent models.
        $this->index = $index;
        unset($this->table);

        return $this;
    }

    /**
     * Get the value of the model's ID attribute.
     *
     * This method ensures that if no value is explicitly provided for the ID, it will fall back to the `_id` field,
     * which is standard for Elasticsearch documents. This is helpful when retrieving models from Elasticsearch as they often use `_id`
     * as the primary identifier instead of a standard numeric ID.
     *
     * @param mixed|null $value The ID value, if available.
     *
     * @return mixed The model's ID value.
     */
    public function getIdAttribute($value = null)
    {
        // If no ID value is provided, return the Elasticsearch `_id` if available.
        if (! $value && array_key_exists('_id', $this->attributes)) {
            $value = $this->attributes['_id'];
        }

        return $value;
    }

    /**
     * Get the qualified key name for the model.
     *
     * This method overrides the default behavior in Eloquent to return the key name for Elasticsearch models,
     * which is typically '_id'.
     *
     * @return string The qualified key name, which is '_id'.
     */
    public function getQualifiedKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Get the model's metadata.
     *
     * This method returns the metadata associated with the model, which includes configurations and additional
     * information for Elasticsearch operations.
     *
     * @return ModelMetaData The model's metadata instance.
     */
    public function getMeta(): ModelMetaData
    {
        return $this->_meta;
    }

    /**
     * Get the model's metadata as an array.
     *
     * This method converts the metadata to an array format for easier handling, such as when passing the metadata to
     * a view or another system that expects data in array form.
     *
     * @return array The model's metadata as an array.
     */
    public function getMetaAsArray(): array
    {
        return $this->_meta->asArray();
    }

    /**
     * Set the model's metadata.
     *
     * This method allows setting the model's metadata, which may include custom Elasticsearch settings or configurations.
     *
     * @param mixed $meta The metadata to be set for the model.
     *
     * @return static The current instance of the model.
     */
    public function setMeta($meta): static
    {
        // Create a new ModelMetaData instance and assign it to the model's metadata.
        $this->_meta = Container::make(ModelMetaData::class, ['meta' => $meta]);

        return $this;
    }

    /**
     * Get the search highlights for the model.
     *
     * This accessor retrieves the search highlights for the model, which are often used in Elasticsearch search results.
     * Highlights are typically used to emphasize certain parts of the document that matched the search query.
     *
     * @return object|null The search highlights for the model.
     */
    public function getSearchHighlightsAttribute(): ?object
    {
        return $this->_meta->parseHighlights();
    }

    /**
     * Get the search highlights as an array.
     *
     * This accessor retrieves the search highlights in array format for easier manipulation or display.
     *
     * @return array The search highlights as an array.
     */
    public function getSearchHighlightsAsArrayAttribute(): array
    {
        return $this->_meta->getHighlights();
    }

    /**
     * Get the highlights for the model, including all mutated attributes.
     *
     * This accessor retrieves all attributes, including any custom attributes that have been mutated,
     * and then returns the highlights formatted in an object. This is used for providing more detailed
     * search results, including metadata and other relevant information.
     *
     * @return object The highlights for the model, including mutated attributes.
     */
    public function getWithHighlightsAttribute(): object
    {
        // Collect all model data and exclude attributes that are part of search highlights.
        $data = $this->attributes;
        $mutators = array_values(array_diff($this->getMutatedAttributes(), [
            'id',
            'search_highlights',
            'search_highlights_as_array',
            'with_highlights',
        ]));

        // Add any mutated attributes to the model data.
        if ($mutators) {
            foreach ($mutators as $mutator) {
                $data[$mutator] = $this->{$mutator};
            }
        }

        // Return the highlights parsed from the model data.
        return (object)$this->_meta->parseHighlights($data);
    }

    /**
     * Get the fresh timestamp for the model.
     *
     * This method returns the current timestamp in the correct format, which is used when updating the model
     * or creating a new model instance with a timestamped field.
     *
     * @return string The current timestamp in the format specified by the model.
     */
    public function freshTimestamp(): string
    {
        // Return the current timestamp in the model's date format.
        return Carbon::now()->format($this->getDateFormat());
    }

    /**
     * Get the date format for the model's timestamps.
     *
     * This method returns the date format that is used for timestamp fields. By default, it is the standard
     * `Y-m-d H:i:s` format, but this can be customized.
     *
     * @return string The date format for timestamps.
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat ?: 'Y-m-d H:i:s';
    }

    /**
     * Get the Elasticsearch index associated with the model.
     *
     * This method returns the name of the Elasticsearch index. If no specific `index` is set on the model,
     * it will default to the model's table name. The final index name is resolved through the `IndexNameResolver`
     * service and incorporates the store ID.
     *
     * @return string The resolved Elasticsearch index name.
     */
    public function getIndex(): string
    {
        // Determine the index name: use the model's `index` property or fall back to the table name.
        $index = $this->index ?: parent::getTable();

        // Resolve the index name using the `IndexNameResolver` service, incorporating the store ID.
        $storeId = StoreManager::getStore()->getId();
        $resolvedIndexName = Container::get(IndexNameResolver::class)->getIndexName($storeId, $index, []);

        return $resolvedIndexName;
    }

    /**
     * Set the Elasticsearch index for the model.
     *
     * This method allows setting the index for the model, which is essential for querying Elasticsearch.
     * If no index is provided, it defaults to the table name.
     *
     * @param string|null $index The name of the Elasticsearch index.
     */
    public function setIndex($index = null)
    {
        // Set the index, or default to the table name if no index is provided.
        if ($index) {
            return $this->index = $index;
        }
        $this->index ??= $this->getTable();
        unset($this->table);
    }

    /**
     * Get the table (index) name for the model.
     *
     * This method overrides the standard Eloquent method to return the Elasticsearch index name instead of
     * the traditional database table name.
     *
     * @return string The Elasticsearch index name.
     */
    public function getTable(): string
    {
        return $this->getIndex();
    }

    /**
     * Get the value of a model attribute by key.
     *
     * This method supports dot notation for nested attributes and ensures that attributes are retrieved
     * correctly, even if they are mutated or have custom accessors.
     *
     * @param string $key The name of the attribute.
     *
     * @return mixed The value of the attribute.
     */
    public function getAttribute($key): mixed
    {
        if (! $key) {
            return null;
        }

        // Check for dot notation support (nested attributes).
        if (Str::contains($key, '.') && Arr::has($this->attributes, $key)) {
            return $this->getAttributeValue($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * Set an attribute on the model.
     *
     * This method handles setting a value to an attribute on the model.
     * It supports dot notation for nested attributes and automatically converts date fields.
     *
     * @param string $key The name of the attribute.
     * @param mixed $value The value to assign to the attribute.
     *
     * @return mixed The result of setting the attribute.
     */
    public function setAttribute($key, $value): mixed
    {
        // If the key contains dot notation (i.e., nested attribute), handle accordingly.
        if (Str::contains($key, '.')) {
            // If the attribute is a date field, convert it to a date format.
            if (in_array($key, $this->getDates()) && $value) {
                $value = $this->fromDateTime($value);
            }

            // Set the attribute value using dot notation.
            Arr::set($this->attributes, $key, $value);

            return null;
        }

        // If the key does not contain dot notation, use the parent method.
        return parent::setAttribute($key, $value);
    }

    /**
     * Convert the value to a Carbon instance for date handling.
     *
     * This method converts a value into a Carbon instance for date manipulation.
     *
     * @param mixed $value The value to convert to a date.
     *
     * @return Carbon The converted date value.
     */
    public function fromDateTime(mixed $value): Carbon
    {
        return parent::asDateTime($value);
    }

    /**
     * Get the casts for the model's attributes.
     *
     * This method returns the attribute casts for the model, which define how
     * attributes should be cast when retrieved.
     *
     * @return array The list of attribute casts.
     */
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * Check if the original value of an attribute is equivalent to its current value.
     *
     * This method checks if the original value of an attribute is equivalent to its
     * current value, taking into account any cast transformations.
     *
     * @param string $key The name of the attribute.
     *
     * @return bool True if the original and current values are equivalent, false otherwise.
     */
    public function originalIsEquivalent($key): bool
    {
        // If the key does not exist in the original attributes, return false.
        if (! array_key_exists($key, $this->original)) {
            return false;
        }

        // Get the current and original values of the attribute.
        $attribute = Arr::get($this->attributes, $key);
        $original = Arr::get($this->original, $key);

        // If the current and original values are the same, return true.
        if ($attribute === $original) {
            return true;
        }

        // If the current value is null, return false.
        if ($attribute === null) {
            return false;
        }

        // If the attribute is castable, check if the casted values are equivalent.
        if ($this->hasCast($key, static::$primitiveCastTypes)) {
            return $this->castAttribute($key, $attribute) === $this->castAttribute($key, $original);
        }

        // If the attribute is numeric, compare them as numbers.
        return is_numeric($attribute) && is_numeric($original) && strcmp((string)$attribute, (string)$original) === 0;
    }

    /**
     * Get the foreign key name for the model.
     *
     * This method returns the name of the foreign key based on the class name.
     * It uses the snake_case version of the model class name followed by the primary key.
     *
     * @return string The foreign key name.
     */
    public function getForeignKey(): string
    {
        return Str::snake(class_basename($this)) . '_' . ltrim($this->primaryKey, '_');
    }

    /**
     * Save the model without refreshing it.
     *
     * This method saves the model without triggering a refresh operation, which
     * can improve performance in certain situations.
     *
     * @param array $options Additional options for the save operation.
     *
     * @return bool True if the model was saved successfully, false otherwise.
     */
    public function saveWithoutRefresh(array $options = []): bool
    {
        // Merge any cached attribute casts.
        $this->mergeAttributesFromCachedCasts();

        // Create a new query instance.
        $query = $this->newModelQuery();

        // Disable refresh for the query.
        //@phpstan-ignore-next-line
        $query->setRefresh(false);

        // If the model already exists, perform an update if it's dirty; otherwise, perform an insert.
        if ($this->exists) {
            $saved = ! $this->isDirty() || $this->performUpdate($query);
        } else {
            $saved = $this->performInsert($query);
        }

        // If the model was saved successfully, finalize the save operation.
        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }

    /**
     * Get the database connection instance.
     *
     * This method returns the connection instance used for querying the database.
     * Throws an exception if the connection is not valid.
     *
     * @throws RuntimeException If the connection settings are invalid.
     *
     * @return Connection The connection instance.
     */
    public function getConnection(): Connection
    {
        // Retrieve the connection based on whether the resolver is set.
        $connection = $this->resolveConnectionInstance();

        // Validate the connection instance.
        $this->validateConnectionInstance($connection);

        return $connection;
    }

    /**
     * Get the maximum size for queries.
     *
     * This method returns the maximum size of a query result, which can be adjusted
     * for Elasticsearch or other databases.
     *
     * @return int The maximum query size.
     */
    public function getMaxSize(): int
    {
        return static::MAX_SIZE;
    }

    /**
     * Get the parent relation of the model.
     *
     * This method returns the parent relation, if available.
     *
     * @return Relation|null The parent relation, or null if none exists.
     */
    public function getParentRelation(): ?Relation
    {
        return $this->parentRelation ?? null;
    }

    /**
     * Set the parent relation of the model.
     *
     * This method sets the parent relation for the model, which can be used to
     * define relationships between models.
     *
     * @param Relation $relation The relation to set as the parent.
     *
     * @return void
     */
    public function setParentRelation(Relation $relation): void
    {
        $this->parentRelation = $relation;
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
        return $this->toArray();
    }

    /**
     * Resolve the connection instance.
     *
     * This method checks if the connection resolver is set and returns the resolved connection.
     *
     * @return Connection The resolved connection instance.
     */
    protected function resolveConnectionInstance(): Connection
    {
        if (static::$resolver) {
            // Clone the resolved connection.
            /** @var Connection $connection */
            $connection = clone static::resolveConnection($this->getConnectionName());

            return $connection;
        }

        // Otherwise, fetch the connection from the container.
        return Container::resolve(Connection::class);
    }

    /**
     * Validate the connection instance.
     *
     * This method checks if the resolved connection is a valid instance of Connection.
     * Throws an exception if the connection settings are invalid.
     *
     * @param mixed $connection The connection to validate.
     *
     * @throws RuntimeException If the connection settings are invalid.
     */
    protected function validateConnectionInstance($connection): void
    {
        // If the connection is not an instance of Connection, throw an exception.
        if (! $connection instanceof Connection) {
            $config = $connection->getConfig() ?? null;

            $driver = $config['driver'] ?? 'unknown';
            $message = "Invalid connection settings; expected \"elasticsearch\", got \"{$driver}\"";

            throw new RuntimeException($message);
        }
    }

    /**
     * Convert a value to a Carbon instance for date handling.
     *
     * This method overrides the parent `asDateTime` method to ensure proper date
     * formatting.
     *
     * @param mixed $value The value to convert to a date.
     *
     * @return Carbon The converted date value.
     */
    protected function asDateTime($value): Carbon
    {
        return parent::asDateTime($value);
    }

    /**
     * Append one or more values to an attribute and sync with the original.
     *
     * This method adds one or more values to an existing attribute, ensuring
     * that the values are unique (if specified) and synced with the original.
     *
     * @param string $column The attribute to update.
     * @param array $values The values to append.
     * @param bool $unique Whether to ensure uniqueness.
     *
     * @return void
     */
    protected function pushAttributeValues(string $column, array $values, bool $unique = false): void
    {
        $current = $this->getAttributeFromArray($column) ?: [];

        // Append values to the current array, ensuring uniqueness if specified.
        foreach ($values as $value) {
            if ($unique && (! is_array($current) || in_array($value, $current))) {
                continue;
            }

            $current[] = $value;
        }

        $this->attributes[$column] = $current;

        $this->syncOriginalAttribute($column);
    }

    /**
     * Get an attribute from the model's array.
     *
     * This method retrieves an attribute from the model's array, supporting
     * dot notation for nested attributes.
     *
     * @param string $key The name of the attribute.
     *
     * @return mixed The attribute value.
     */
    protected function getAttributeFromArray($key): mixed
    {
        // Handle dot notation for nested attributes.
        if (Str::contains($key, '.')) {
            return Arr::get($this->attributes, $key);
        }

        return parent::getAttributeFromArray($key);
    }

    /**
     * Remove one or more values from an attribute and sync with the original.
     *
     * This method removes specified values from an attribute and ensures the
     * original attribute is synced with the changes.
     *
     * @param string $column The attribute to update.
     * @param array $values The values to remove.
     *
     * @return void
     */
    protected function pullAttributeValues(string $column, array $values): void
    {
        $current = $this->getAttributeFromArray($column) ?: [];

        if (is_array($current)) {
            // Remove the specified values from the current array.
            foreach ($values as $value) {
                $keys = array_keys($current, $value);

                foreach ($keys as $key) {
                    unset($current[$key]);
                }
            }
        }

        $this->attributes[$column] = array_values($current);

        $this->syncOriginalAttribute($column);
    }

    /**
     * Create a new base query builder instance.
     *
     * This method creates a new query builder instance, configuring the
     * connection settings such as index and max size.
     *
     * @return QueryBuilder The new query builder instance.
     */
    protected function newBaseQueryBuilder(): mixed
    {
        /** @phpstan-var Connection $connection */
        $connection = $this->getConnection();
        $connection->setIndex($this->getTable());
        $connection->setMaxSize($this->getMaxSize());

        return Container::resolve(QueryBuilder::class);
    }

    /**
     * Remove the table name from the key.
     *
     * This method removes the table name from the key, which is useful when
     * working with keys that include both the table and the primary key.
     *
     * @param string $key The key to modify.
     *
     * @return string The modified key without the table name.
     */
    protected function removeTableFromKey($key): string
    {
        return $key;
    }

    /**
     * Get the loaded relations without the parent relation.
     *
     * This method retrieves all loaded relations, excluding the parent relation.
     *
     * @return array The relations excluding the parent relation.
     */
    protected function getRelationsWithoutParent(): array
    {
        $relations = $this->getRelations();

        $parentRelation = $this->getParentRelation();

        if ($parentRelation instanceof Relation) {
            //@phpstan-ignore-next-line
            unset($relations[$parentRelation->getQualifiedForeignKeyName()]);
        }

        return $relations;
    }

    /**
     * Determine if the column is guardable.
     *
     * This method checks if the specified column can be guarded or not.
     * It can be overridden to customize which columns are guardable.
     *
     * @param string $key The column name.
     *
     * @return bool Always returns true, indicating the column is guardable.
     */
    protected function isGuardableColumn($key): bool
    {
        return true;
    }
}
