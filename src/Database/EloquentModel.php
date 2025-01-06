<?php

declare(strict_types=1);

namespace Maginium\Framework\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Scope;
use Maginium\Framework\Database\Eloquent\Builder as EloquentBuilder;
use Maginium\Framework\Database\Query\Builder as QueryBuilder;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Publisher;
use Maginium\Framework\Support\Traits\DataObject;
use Maginium\Framework\Support\Validator;

/**
 * Class EloquentModel.
 *
 * Extends the base Laravel Eloquent Model class and provides additional functionality
 * for custom query builders, global scope management, and connection handling. This class
 * ensures queries are built with enhanced flexibility and alignment with the framework's architecture.
 *
 * @property string|null $createdAtKey Custom field name for created_at timestamp
 * @property string|null $updatedAtKey Custom field name for updated_at timestamp
 */
class EloquentModel extends BaseModel // implements DataObjectInterface
{
    // use DataObject;

    /**
     * Create a new instance of the model and optionally populate it with the provided data.
     *
     * This is a factory-style method that allows you to instantiate the model and populate it
     * with data. It returns the newly created instance, which can then be used as needed.
     *
     * @param array $attributes An optional array of attributes to initialize the model with.
     *                          These attributes can be set on the instance upon creation.
     *
     * @return static The newly created instance of the model, populated with the provided attributes.
     */
    public static function make(array $attributes = []): static
    {
        // Create a new instance of the model with the given attributes
        $instance = Container::make(static::class, ['data' => $attributes]);

        // Return the created instance
        return $instance;
    }

    /**
     * Begin querying the model on a given connection.
     *
     * @param  string|null  $connection
     *
     * @return EloquentBuilder
     */
    public static function on($connection = null)
    {
        return parent::on($connection);
    }

    /**
     * Begin querying the model on the write connection.
     *
     * @return EloquentBuilder
     */
    public static function onWriteConnection()
    {
        return parent::onWriteConnection();
    }

    /**
     * Begin querying a model with eager loading.
     *
     * @param  array|string  $relations
     *
     * @return EloquentBuilder
     */
    public static function with($relations)
    {
        return parent::with($relations);
    }

    /**
     * Begin querying the model.
     *
     * @return EloquentBuilder
     */
    public static function query()
    {
        return parent::query();
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @return EloquentBuilder
     */
    public function newQuery()
    {
        return parent::newQuery();
    }

    /**
     * Get a new query builder that doesn't have any global scopes or eager loading.
     *
     * @return EloquentBuilder|static
     */
    public function newModelQuery()
    {
        return parent::newModelQuery();
    }

    /**
     * Get a new query builder with no relationships loaded.
     *
     * @return EloquentBuilder
     */
    public function newQueryWithoutRelationships()
    {
        return parent::newQueryWithoutRelationships();
    }

    /**
     * Register the global scopes for this builder instance.
     *
     * @param  EloquentBuilder  $builder
     *
     * @return EloquentBuilder
     */
    public function registerGlobalScopes($builder)
    {
        return parent::registerGlobalScopes($builder);
    }

    /**
     * Get a new query builder that doesn't have any global scopes.
     *
     * @return EloquentBuilder|static
     */
    public function newQueryWithoutScopes()
    {
        return parent::newQueryWithoutScopes();
    }

    /**
     * Get a new query instance without a given scope.
     *
     * @param  Scope|string  $scope
     *
     * @return EloquentBuilder
     */
    public function newQueryWithoutScope($scope)
    {
        return parent::newQueryWithoutScope($scope);
    }

    /**
     * Get a new query to restore one or more models by their queueable IDs.
     *
     * @param  array|int  $ids
     *
     * @return EloquentBuilder
     */
    public function newQueryForRestoration($ids)
    {
        return parent::newQueryForRestoration($ids);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  QueryBuilder  $query
     *
     * @return EloquentBuilder|static
     */
    public function newEloquentBuilder($query)
    {
        return Container::make(EloquentBuilder::class, ['query' => $query]);
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  Model|EloquentBuilder|Relation  $query
     * @param  mixed  $value
     * @param  string|null  $field
     *
     * @return Relation
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field);
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
    public function toArray(array $keys = ['*']): array
    {
        // Get the initial data source.
        $data = collect(parent::toArray());

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
     * @return static Returns the current instance of the model for method chaining.
     */
    public function fill(array $data): static
    {
        // Set the data for the model, mapping the array values to the model's properties.
        // $this->setData($data);

        // Return the current instance for method chaining.
        return $this;
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
     * Perform a model update operation.
     *
     * @param  EloquentBuilder  $query
     *
     * @return bool
     */
    protected function performUpdate(mixed $query)
    {
        return parent::performUpdate($query);
    }

    /**
     * Set the keys for a select query.
     *
     * @param  EloquentBuilder  $query
     *
     * @return EloquentBuilder
     */
    protected function setKeysForSelectQuery($query)
    {
        return parent::setKeysForSelectQuery($query);
    }

    /**
     * Set the keys for a save update query.
     *
     * @param  EloquentBuilder  $query
     *
     * @return EloquentBuilder
     */
    protected function setKeysForSaveQuery($query)
    {
        return parent::setKeysForSaveQuery($query);
    }

    /**
     * Perform a model insert operation.
     *
     * @param  EloquentBuilder  $query
     *
     * @return bool
     */
    protected function performInsert(mixed $query)
    {
        return parent::performInsert($query);
    }

    /**
     * Insert the given attributes and set the ID on the model.
     *
     * @param  EloquentBuilder  $query
     * @param  array  $attributes
     *
     * @return void
     */
    protected function insertAndSetId(mixed $query, $attributes)
    {
        parent::insertAndSetId($query, $attributes);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return QueryBuilder
     */
    protected function newBaseQueryBuilder()
    {
        return parent::newBaseQueryBuilder();
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
            return Publisher::dispatch($event, $data);
        }

        // If no data is provided, dispatch the event with the model instance
        return Publisher::dispatch($event, ['model' => $this]);
    }
}
