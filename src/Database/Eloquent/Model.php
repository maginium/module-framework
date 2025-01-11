<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Eloquent;

use Illuminate\Contracts\Database\Eloquent\Builder as BaseBuilder;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Scope;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\AbstractModel;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Database\Concerns\HasAttributes;
use Maginium\Framework\Database\Concerns\HasTimestamps;
use Maginium\Framework\Database\Eloquent\Docs\ModelDocs;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Database\Query\Builder as QueryBuilder;
use Maginium\Framework\Database\Traits\Identifiable;
use Maginium\Framework\Database\Traits\Searchable;
use Maginium\Framework\Dto\DataTransferObject;
use Maginium\Framework\Elasticsearch\Eloquent\Model as ElasticModel;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Event;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Traits\DataObject;
use Maginium\Framework\Support\Validator;

/**
 * Class Model.
 *
 * Extends the base Laravel Eloquent Model class and provides additional functionality
 * for custom query builders, global scope management, and connection handling. This class
 * ensures queries are built with enhanced flexibility and alignment with the framework's architecture.
 *
 * @property string $slugKey
 *
 * @mixin ModelDocs
 */
class Model extends BaseModel implements ModelInterface
{
    // Provides methods to manage model data.
    use DataObject {
        __call as dataObjectCall;
    }

    // Adding Attributes functionality for the model.
    use HasAttributes;
    // Handles timestamp fields (`created_at`, `updated_at`).
    use HasTimestamps;
    // Handles unique identifiers for the model.
    use Identifiable;
    // Enables search functionality for the model.
    use Searchable;

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
     * The class name of the Elastic model associated with this instance.
     *
     * Used for Elasticsearch integration, enabling indexing and querying of model data.
     *
     * @var class-string<ElasticModel>|null
     */
    protected ?string $elasticModel = null;

    /**
     * The class name of the base model associated with this instance.
     *
     * Provides a base configuration model for resource handling or fallback logic.
     *
     * @var class-string<AbstractModel>|class-string<AbstractExtensibleModel>|null
     */
    protected ?string $baseModel = null;

    /**
     * The class name of the Data Transfer Object (DTO) associated with this instance.
     *
     * DTOs are used for structured data exchange between layers.
     *
     * @var class-string<DataTransferObject>|null
     */
    protected ?string $dtoClass = null;

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
        // Call the parent constructor to handle basic attribute initialization.
        parent::__construct($attributes);
    }

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
     * Begin querying the model on a given connection.
     *
     * @param  string|null  $connection
     *
     * @return Builder
     */
    public static function on($connection = null)
    {
        // First we will just create a fresh instance of this model, and then we can set the
        // connection on the model so that it is used for the queries we execute, as well
        // as being set on every relation we retrieve without a custom connection name.
        $instance = new static;

        $instance->setConnection($connection);

        return $instance->newQuery();
    }

    /**
     * Begin querying the model on the write connection.
     *
     * @return Builder
     */
    public static function onWriteConnection()
    {
        return static::query()->useWritePdo();
    }

    /**
     * Begin querying a model with eager loading.
     *
     * @param  array|string  $relations
     *
     * @return Builder
     */
    public static function with($relations)
    {
        return static::query()->with(
            Validator::isString($relations) ? func_get_args() : $relations,
        );
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return Dispatcher
     */
    public static function getEventDispatcher()
    {
        return static::$dispatcher;
    }

    /**
     * Get all of the models from the database.
     *
     * @param  array|string  $columns
     *
     * @return Collection<int, static>
     */
    public static function all($columns = ['*'])
    {
        return static::query()->get(
            Validator::isArray($columns) ? $columns : func_get_args(),
        );
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     *
     * @return Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Begin querying the model.
     *
     * @return Builder
     */
    public static function query()
    {
        return (new static)->newQuery();
    }

    /**
     * reload the model attributes from the database.
     *
     * @return static
     */
    public function reload(): static
    {
        if (! $this->exists) {
            $this->syncOriginal();
        } elseif ($fresh = static::find($this->getKey())) {
            $this->setRawAttributes($fresh->getAttributes(), true);
        }

        return $this;
    }

    /**
     * Returns eventPrefix.
     *
     * This method simply returns the event prefix for this model, which is typically
     * used to differentiate events for different types of models or entities.
     *
     * @return string|null The event prefix for the model.
     */
    public function getEventPrefix(): ?string
    {
        return static::$eventPrefix;
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
     * Retrieve the instance of the base model associated with this class.
     *
     * This method resolves the factory class associated with the base model,
     * invokes its `create` method, and passes the given arguments.
     *
     * @param array $args Arguments to pass to the factory's create method.
     *
     * @throws RuntimeException If the factory class is not found or cannot be resolved.
     *
     * @return string|null The created instance of the base model or null if not set.
     */
    public function getBaseModel(array $args = []): ?string
    {
        // Ensure the base model is defined
        if (empty($this->baseModel)) {
            return null;
        }

        // Construct the factory class name
        $factoryClass = "{$this->baseModel}Factory";

        // Resolve the factory class from the container
        $factory = Container::resolve($factoryClass);

        // Validate the resolved factory
        if (! Reflection::methodExists($factory, 'create')) {
            throw RuntimeException::make("The factory class '{$factoryClass}' does not have a 'create' method.");
        }

        // Create and return the base model instance
        return $factory->create($args);
    }

    /**
     * Retrieve the class name of the Elastic model associated with this instance.
     *
     * @return ElasticModel|null The class name of the Elastic model or null if not set.
     */
    public function getElasticModel(): ?ElasticModel
    {
        return $this->elasticModel;
    }

    /**
     * Retrieve the class name of the Data Transfer Object (DTO) associated with this instance.
     *
     * @return DataTransferObject|null The class name of the DTO or null if not set.
     */
    public function getDtoClass(): ?DataTransferObject
    {
        return $this->dtoClass;
    }

    /**
     * Set the base model for this instance.
     *
     * @param string $baseModel The class name of the base model to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setBaseModel(string $baseModel): self
    {
        $this->baseModel = $baseModel;

        return $this;
    }

    /**
     * Set the Elastic model for this instance.
     *
     * @param string $elasticModel The class name of the Elastic model to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setElasticModel(string $elasticModel): self
    {
        $this->elasticModel = $elasticModel;

        return $this;
    }

    /**
     * Set the Data Transfer Object (DTO) class for this instance.
     *
     * @param string $dtoClass The class name of the DTO to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setDtoClass(string $dtoClass): self
    {
        $this->dtoClass = $dtoClass;

        return $this;
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @return Builder
     */
    public function newQuery()
    {
        return $this->registerGlobalScopes($this->newQueryWithoutScopes());
    }

    /**
     * Get a new query builder that doesn't have any global scopes or eager loading.
     *
     * @return Builder|static
     */
    public function newModelQuery()
    {
        return $this->newEloquentBuilder(
            $this->newBaseQueryBuilder(),
        )->setModel($this);
    }

    /**
     * Get a new query builder with no relationships loaded.
     *
     * @return Builder
     */
    public function newQueryWithoutRelationships()
    {
        return $this->registerGlobalScopes($this->newModelQuery());
    }

    /**
     * Register the global scopes for this builder instance.
     *
     * @param  Builder  $builder
     *
     * @return Builder
     */
    public function registerGlobalScopes($builder)
    {
        foreach ($this->getGlobalScopes() as $identifier => $scope) {
            $builder->withGlobalScope($identifier, $scope);
        }

        return $builder;
    }

    /**
     * Get a new query builder that doesn't have any global scopes.
     *
     * @return Builder|static
     */
    public function newQueryWithoutScopes()
    {
        return $this->newModelQuery()
            ->with($this->with)
            ->withCount($this->withCount);
    }

    /**
     * Get a new query instance without a given scope.
     *
     * @param  Scope|string  $scope
     *
     * @return Builder
     */
    public function newQueryWithoutScope($scope)
    {
        return $this->newQuery()->withoutGlobalScope($scope);
    }

    /**
     * Get a new query to restore one or more models by their queueable IDs.
     *
     * @param  array|int  $ids
     *
     * @return Builder
     */
    public function newQueryForRestoration($ids)
    {
        return $this->newQueryWithoutScopes()->whereKey($ids);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  Query\Builder  $query
     *
     * @return Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return Container::make(Builder::class, ['query' => $query]);
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  BaseModel|\Illuminate\Contracts\Database\Builder|Relation  $query
     * @param  mixed  $value
     * @param  string|null  $field
     *
     * @return Relation
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return $query->where($field ?? $this->getRouteKeyName(), $value);
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
        // Get the initial data source (either `dataArray` or model data)
        $data = collect($this->getData());

        // Return the full data if no specific keys are provided or '*' is included
        if (Validator::isEmpty($keys) || $this->isWildcard($keys)) {
            return $data->toArray();
        }

        // Filter and return only the specified keys
        return $data->only($keys)->toArray();
    }

    /**
     * Fire the given event for the model.
     *
     * @param  string  $event
     * @param  bool  $halt
     *
     * @return mixed
     */
    protected function fireModelEvent($event, $halt = true)
    {
        // First, we will get the proper method to call on the event dispatcher, and then we
        // will attempt to fire a custom, object based event for the given event. If that
        // returns a result we can return that result, or we'll call the string events.
        $method = $halt ? 'dispatch' : 'dispatch';

        $result = $this->filterModelEventResults(
            $this->fireCustomModelEvent($event, $method),
        );

        if ($result === false) {
            return false;
        }

        return ! empty($result) ? $result : Event::{$method}(
            "eloquent.{$event}: " . static::class,
            $this->_getEventData()
        );
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
     * Fire a custom model event for the given event.
     *
     * @param  string  $event
     * @param  string  $method
     *
     * @return mixed|null
     */
    protected function fireCustomModelEvent($event, $method)
    {
        if (! isset($this->dispatchesEvents[$event])) {
            return;
        }

        $result = Event::{$method}(new $this->dispatchesEvents[$event]($this));

        if ($result !== null) {
            return $result;
        }
    }

    /**
     * Perform a model update operation.
     *
     * @param  BaseBuilder  $query
     *
     * @return bool
     */
    protected function performUpdate(BaseBuilder $query)
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.
        $dirty = $this->getDirtyForUpdate();

        if (count($dirty) > 0) {
            $this->setKeysForSaveQuery($query)->update($dirty);

            $this->syncChanges();

            $this->fireModelEvent('updated', false);
        }

        return true;
    }

    /**
     * Set the keys for a select query.
     *
     * @param  Builder  $query
     *
     * @return Builder
     */
    protected function setKeysForSelectQuery($query)
    {
        $query->where($this->getKeyName(), '=', $this->getKeyForSelectQuery());

        return $query;
    }

    /**
     * Set the keys for a save update query.
     *
     * @param  Builder  $query
     *
     * @return Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery());

        return $query;
    }

    /**
     * Perform a model insert operation.
     *
     * @param  BaseBuilder  $query
     *
     * @return bool
     */
    protected function performInsert(BaseBuilder $query)
    {
        if ($this->usesUniqueIds()) {
            $this->setUniqueIds();
        }

        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        $attributes = $this->getAttributesForInsert();

        if ($this->getIncrementing()) {
            $this->insertAndSetId($query, $attributes);
        }

        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
        else {
            if (empty($attributes)) {
                return true;
            }

            /** @var QueryBuilder $query */
            $query->insert($attributes);
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false);

        return true;
    }

    /**
     * Insert the given attributes and set the ID on the model.
     *
     * @param  QueryBuilder  $query
     * @param  array  $attributes
     *
     * @return void
     */
    protected function insertAndSetId(BaseBuilder $query, $attributes)
    {
        $id = $query->insertGetId($attributes, $keyName = $this->getKeyName());

        $this->setAttribute($keyName, $id);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * This method intercepts calls to methods that are not explicitly defined in the class.
     * It first checks if the method matches one of the dynamically supported patterns
     * (e.g., `get`, `set`, `uns`, `has`) using the `hasMethod` function. If a match is found,
     * it delegates the call to the `DataObject` trait's `__call` implementation. Otherwise,
     * it forwards the call to the parent class's `__call` method for further handling.
     *
     * @param string $method The name of the method being called.
     * @param array $parameters The parameters passed to the method.
     *
     * @return mixed The result of the dynamic method call.
     */
    public function __call($method, $parameters): mixed
    {
        // Check if the method is supported dynamically using the `hasMethod` function.
        if ($this->hasMethod($method)) {
            // Delegate the call to the `DataObject` trait's implementation.
            return $this->dataObjectCall($method, $parameters);
        }

        // If the method is not supported, pass it to the parent class's `__call` implementation.
        return parent::__call($method, $parameters);
    }
}
