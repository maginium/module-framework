<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud;

use Illuminate\Pagination\Paginator;
use Maginium\Framework\Crud\Abstracts\AbstractRepository;
use Maginium\Framework\Crud\Exceptions\EntityNotFoundException;
use Maginium\Framework\Crud\Exceptions\RepositoryException;
use Maginium\Framework\Database\Eloquent\Collection;
use Maginium\Framework\Database\Eloquent\Model;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface;
use Maginium\Framework\Pagination\Interfaces\PaginatorInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\DB;
use Maginium\Framework\Support\Facades\Event;
use Maginium\Framework\Support\Facades\Log;

class Repository extends AbstractRepository
{
    /**
     * AbstractRepository constructor.
     *
     * @param ModelInterface $model The entity model interface.
     */
    public function __construct(ModelInterface $model)
    {
        parent::__construct($model);

        // Set up the class name for logging purposes.
        Log::setClassName(static::class);
    }

    /**
     * Paginate the results based on a specified number of items per page.
     * This method returns a paginated result set, which includes metadata like total pages, current page, etc.
     *
     * @param int|null $perPage The number of items to display per page (default: null).
     * @param array $attributes The columns to select (default: all columns).
     * @param string $pageName The name of the page parameter for pagination (default: 'page').
     * @param int|null $page The current page number (optional).
     *
     * @return LengthAwarePaginatorInterface The paginated result set.
     */
    public function paginate($perPage = null, $attributes = ['*'], $pageName = 'page', $page = null): LengthAwarePaginatorInterface
    {
        // Resolve the current page if not provided
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        return $this->executeCallback(static::class, __FUNCTION__, Arr::merge(func_get_args(), compact('page')), fn() => $this->prepareQuery($this->createModel())
        // Paginate results based on the given parameters
            ->paginate($perPage, $attributes, $pageName, $page));
    }

    /**
     * Find an entity by its ID.
     *
     * This method prepares and executes a query to find an entity by its ID,
     * using the specified attributes for selection.
     *
     * @param mixed $id The ID of the entity to find.
     * @param array $attributes The attributes to retrieve.
     *
     * @return ModelInterface The found entity or null if not found.
     */
    public function find($id, $attributes = ['*']): ModelInterface
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), fn() => $this->prepareQuery($this->createModel())->find($id, $attributes));
    }

    /**
     * Find an entity by its ID or fail.
     *
     * This method finds an entity by ID or throws an exception if the entity
     * cannot be found. It can handle arrays of IDs and check for missing entities.
     *
     * @param mixed $id The ID(s) of the entity to find. This can be a single ID or an array of IDs.
     * @param array $attributes The attributes to retrieve.
     *
     * @throws EntityNotFoundException If the entity cannot be found.
     *
     * @return ModelInterface|ModelInterface[] The found entity or array of entities.
     */
    public function findOrFail($id, $attributes = ['*']): ModelInterface|array
    {
        // Handle the case where multiple IDs are given
        if (is_array($id)) {
            /** @var ModelInterface[] $results */
            // TODO: FIX TypeError: Elasticsearch\Utility::urlencode(): Argument #1 ($url) must be of type string, array given
            $results = $this->find($id, $attributes);

            // Ensure all entities are found (compare lengths of arrays)
            if (count($results) === count($id)) {
                return $results;
            }

            // Throw an exception if not all entities were found
            throw EntityNotFoundException::make($this->getEntityName(), id: $id);
        }

        // Handle single ID lookup
        $result = $this->find($id, $attributes);

        // If the entity is not found, throw an exception
        if ($result === null) {
            throw EntityNotFoundException::make($this->getEntityName(), $id);
        }

        return $result;
    }

    /**
     * Find an entity by its ID or create a new instance if not found.
     *
     * This method attempts to find an entity by ID. If it doesn't exist,
     * it creates and returns a new instance of the model.
     *
     * @param mixed $id The ID of the entity to find.
     * @param array $attributes The attributes to retrieve.
     *
     * @return ModelInterface The found entity or a new instance of the model.
     */
    public function findOrNew($id, $attributes = ['*']): ModelInterface
    {
        // Getting the entity
        $entity = $this->find($id, $attributes);

        // Check if the entity is not null
        if ($entity !== null) {
            return $entity;
        }

        // Return a new instance if the entity wasn't found
        return $this->createModel();
    }

    /**
     * Find the first entity based on the provided attributes.
     * This method returns the first entity without any specific condition.
     *
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return ModelInterface|null The first found entity, or null if no entities exist.
     */
    public function findFirst($attributes = ['*'])
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), fn() => $this->prepareQuery($this->createModel())
        // Retrieve the first result with selected attributes
            ->first($attributes));
    }

    /**
     * Find entities that match a specific condition (e.g., where, operator, value).
     * This method allows you to find entities based on a custom condition.
     *
     * @param array $where The condition to apply (e.g., ['attribute', '=', 'value']).
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return Collection The collection of matching entities.
     */
    public function findWhere(array $where, $attributes = ['*']): Collection
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), function() use ($where, $attributes) {
            // Parse the condition (attribute, operator, value, and boolean)
            [$attribute, $operator, $value, $boolean] = Arr::pad($where, 4, null);

            // Apply the condition (can be customized)
            $this->where($attribute, $operator, $value, $boolean);

            return $this->prepareQuery($this->createModel())
            // Retrieve entities that match the condition
                ->get($attributes);
        });
    }

    /**
     * Find entities where a specific attribute is within a given list of values.
     * This method allows you to find entities that match any value in a list.
     *
     * @param array $where The condition with attribute and values (e.g., ['attribute', ['value1', 'value2']]).
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return Collection The collection of matching entities.
     */
    public function findWhereIn(array $where, $attributes = ['*']): Collection
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), function() use ($where, $attributes) {
            // Parse the condition (attribute, values, boolean, and negation)
            [$attribute, $values, $boolean, $not] = Arr::pad($where, 4, null);

            // Apply whereIn condition
            $this->whereIn($attribute, $values, $boolean, $not);

            return $this->prepareQuery($this->createModel())
            // Retrieve entities where the attribute is in the provided values
                ->get($attributes);
        });
    }

    /**
     * Find entities where a specific attribute is not in a given list of values.
     * This method allows you to find entities that don't match any value in a list.
     *
     * @param array $where The condition with attribute and values (e.g., ['attribute', ['value1', 'value2']]).
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return Collection The collection of matching entities.
     */
    public function findWhereNotIn(array $where, $attributes = ['*']): Collection
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), function() use ($where, $attributes) {
            // Parse the condition (attribute, values, and boolean)
            [$attribute, $values, $boolean] = Arr::pad($where, 3, null);

            // Apply whereNotIn condition
            $this->whereNotIn($attribute, $values, $boolean);

            return $this->prepareQuery($this->createModel())
            // Retrieve entities where the attribute is not in the provided values
                ->get($attributes);
        });
    }

    /**
     * Find an entity by a specific attribute and value.
     * This method returns the first matching result based on the given attribute and value.
     *
     * @param string $attribute The attribute to search by.
     * @param mixed $value The value to search for.
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return ModelInterface|null The found entity, or null if not found.
     */
    public function findBy($attribute, $value, $attributes = ['*'])
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), fn() => $this->prepareQuery($this->createModel())
            ->where($attribute, '=', $value) // Apply the condition to match the attribute with the value

            // Return the first result, with selected attributes
            ->first($attributes));
    }

    /**
     * Counts the number of records in the database.
     *
     * This method performs a `count` query on the database, returning the total number of records that match the criteria.
     * The method can count any column, with the default being `*` (all columns).
     *
     * @param string $columns The column to count. Defaults to '*' for counting all rows.
     *
     * @return int The number of records found.
     */
    public function count($columns = '*'): int
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), fn() => $this->prepareQuery($this->createModel())->count($columns));
    }

    /**
     * Retrieves the sum of a given column.
     *
     * This method performs a query to calculate the sum of values in a specific column.
     *
     * @param string $column The column to sum.
     *
     * @return ModelInterface The sum of values in the column.
     */
    public function sum($column): ModelInterface
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), fn() => $this->prepareQuery($this->createModel())->sum($column));
    }

    /**
     * Retrieves the maximum value of a given column.
     *
     * This method performs a query to get the maximum value of a specific column.
     *
     * @param string $column The column to retrieve the maximum value from.
     *
     * @return ModelInterface The maximum value of the column.
     */
    public function max($column): ModelInterface
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), fn() => $this->prepareQuery($this->createModel())->max($column));
    }

    /**
     * Retrieves the minimum value of a given column.
     *
     * This method performs a query to get the minimum value of a specific column.
     *
     * @param string $column The column to retrieve the minimum value from.
     *
     * @return ModelInterface The minimum value of the column.
     */
    public function min($column): ModelInterface
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), fn() => $this->prepareQuery($this->createModel())->min($column));
    }

    /**
     * Create a new entity instance and save it to the database.
     *
     * This method handles creating a new entity, dispatching events,
     * syncing relationships, and saving the entity to the database.
     *
     * @param array $attributes Attributes for the new entity.
     * @param bool $syncRelations Whether to sync the relationships with the entity.
     *
     * @return ModelInterface The created entity or false if the save operation failed.
     */
    public function create(array $attributes = [], bool $syncRelations = false): ModelInterface
    {
        // Create a new instance of the model
        /** @var Model $entity */
        $entity = $this->createModel();

        // Dispatch the 'creating' event before saving the entity
        Event::dispatch($this->getRepositoryId() . '.entity.creating', [$this, $entity]);

        // Extract and remove relationships from the attributes if syncing is required
        if ($syncRelations) {
            $relations = $this->extractRelations($entity, $attributes);
            Arr::forget($attributes, Arr::keys($relations));
        }

        // Fill the entity with the provided attributes
        $entity->fill($attributes);

        // Save the entity to the database
        $created = $entity->save();

        // Sync relationships after saving the entity if required
        if ($syncRelations && isset($relations)) {
            $this->syncRelations($entity, $relations);
        }

        // Dispatch the 'created' event after the entity has been saved
        Event::dispatch($this->getRepositoryId() . '.entity.created', [$this, $entity]);

        // Return the created entity or false if save failed
        return $created ? $entity : $created;
    }

    /**
     * Update an existing entity in the database.
     *
     * This method finds an existing entity by ID, fills it with new attributes,
     * checks for changes, dispatches events, and updates the entity in the database.
     *
     * @param mixed $id The ID of the entity to update.
     * @param array $attributes Attributes to update the entity with.
     * @param bool $syncRelations Whether to sync the relationships with the entity.
     *
     * @return ModelInterface The updated entity or false if the update failed.
     */
    public function update($id, array $attributes = [], bool $syncRelations = false): ModelInterface
    {
        $updated = false;

        // Find the entity by ID or use the given model if it's already an instance
        /** @var Model $entity */
        $entity = $id instanceof ModelInterface ? $id : $this->find($id);

        if ($entity) {
            // Dispatch the 'updating' event before updating the entity
            Event::dispatch($this->getRepositoryId() . '.entity.updating', [$this, $entity]);

            // Extract and remove relationships from the attributes if syncing is required
            if ($syncRelations) {
                $relations = $this->extractRelations($entity, $attributes);
                Arr::forget($attributes, Arr::keys($relations));
            }

            // Fill the entity with the new attributes
            $entity->fill($attributes);

            // Track the dirty attributes (attributes that have been modified)
            $dirty = $entity->getDirty();

            // Save the updated entity to the database
            $updated = $entity->save();

            // Sync relationships after saving if required
            if ($syncRelations && isset($relations)) {
                $this->syncRelations($entity, $relations);
            }

            // If any attributes were updated, dispatch the 'updated' event
            if (count($dirty) > 0) {
                Event::dispatch($this->getRepositoryId() . '.entity.updated', [$this, $entity]);
            }
        }

        // Return the updated entity or false if update failed
        return $updated ? $entity : $updated;
    }

    /**
     * Deletes the entity by its ID.
     *
     * This method will attempt to find the entity either by its ID or if the provided argument is already an instance of a model.
     * Once the entity is found, the `deleting` event is fired, and the entity is deleted from the database.
     * After deletion, the `deleted` event is triggered. The method returns the deleted entity or false if the deletion failed.
     *
     * @param mixed $id The ID or instance of the entity to be deleted.
     *
     * @return ModelInterface The deleted entity or false if the deletion failed.
     */
    public function delete($id): ModelInterface
    {
        $deleted = false;

        // Find the given instance, either by ID or directly using the provided model
        /** @var Model $entity */
        $entity = $id instanceof ModelInterface ? $id : $this->find($id);

        if ($entity) {
            // Fire the deleting event
            Event::dispatch($this->getRepositoryId() . '.entity.deleting', [$this, $entity]);

            // Perform the deletion
            $deleted = $entity->delete();

            // Fire the deleted event
            Event::dispatch($this->getRepositoryId() . '.entity.deleted', [$this, $entity]);
        }

        // Return the deleted entity or false if deletion failed
        return $deleted ? $entity : $deleted;
    }

    /**
     * Create a new model instance.
     *
     * This method ensures that the correct model class is created, handles
     * dependency injection for the model, and sets the connection if provided.
     *
     * @throws RepositoryException If the model class does not exist or is invalid.
     *
     * @return ModelInterface A new instance of the model.
     */
    public function createModel(): ModelInterface
    {
        // Getting the model
        /** @var Model $model */
        $model = $this->getModel();

        // Set the model's connection if specified
        if (! empty($this->connection)) {
            $model = $model->setConnection($this->connection);
        }

        // Return the created model instance
        return $model;
    }

    /**
     * Find all entities, retrieving all records from the database.
     * This method returns all matching entities, typically used when no filters are applied.
     *
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return Collection The collection of found entities.
     */
    public function findAll($attributes = ['*']): Collection
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), fn() => $this->prepareQuery($this->createModel())
        // Retrieve all matching entities with selected attributes
            ->get($attributes));
    }

    /**
     * Simplified pagination with fewer features, ideal for smaller datasets.
     * This method provides a simpler paginated result, suitable for when you don't need full pagination features.
     *
     * @param int|null $perPage The number of items to display per page (default: null).
     * @param array $attributes The columns to select (default: all columns).
     * @param string $pageName The name of the page parameter for pagination (default: 'page').
     * @param int|null $page The current page number (optional).
     *
     * @return PaginatorInterface The simpler paginated result set.
     */
    public function simplePaginate($perPage = null, $attributes = ['*'], $pageName = 'page', $page = null): PaginatorInterface
    {
        // Resolve the current page if not provided
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        return $this->executeCallback(static::class, __FUNCTION__, Arr::merge(func_get_args(), compact('page')), fn() => $this->prepareQuery($this->createModel())
        // Simple pagination without extra metadata
            ->simplePaginate($perPage, $attributes, $pageName, $page));
    }

    /**
     * Find entities that have a related model matching specific conditions.
     * This method allows you to find entities with a relation that matches the given condition.
     *
     * @param array $where The condition to apply (e.g., ['relation', callback, operator, count]).
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return Collection The collection of matching entities.
     */
    public function findWhereHas(array $where, $attributes = ['*']): Collection
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), function() use ($where, $attributes) {
            // Parse the relation and condition (relation, callback, operator, count)
            [$relation, $callback, $operator, $count] = Arr::pad($where, 4, null);

            // Apply whereHas condition to filter by related model
            $this->whereHas($relation, $callback, $operator, $count);

            return $this->prepareQuery($this->createModel())
            // Retrieve entities with a related model matching the condition
                ->get($attributes);
        });
    }

    /**
     * Restores the deleted entity by its ID.
     *
     * This method attempts to restore a previously soft-deleted entity. If the entity is found, the `restoring` event is fired,
     * followed by the restoration of the entity. After restoring, the `restored` event is triggered.
     * The method returns the restored entity or false if the restoration failed.
     *
     * @param mixed $id The ID or instance of the entity to be restored.
     *
     * @return ModelInterface The restored entity or false if the restoration failed.
     */
    public function restore($id): ModelInterface
    {
        $restored = false;

        // Find the given instance, either by ID or directly using the provided model
        /** @var Model $entity */
        $entity = $id instanceof ModelInterface ? $id : $this->find($id);

        if ($entity) {
            // Fire the restoring event
            Event::dispatch($this->getRepositoryId() . '.entity.restoring', [$this, $entity]);

            // Restore the entity
            $restored = $entity->restore();

            // Fire the restored event
            Event::dispatch($this->getRepositoryId() . '.entity.restored', [$this, $entity]);
        }

        // Return the restored entity or false if restoration failed
        return $restored ? $entity : $restored;
    }

    /**
     * Begins a database transaction.
     *
     * This method starts a new database transaction using the database connection container.
     * It ensures that all subsequent database operations can be committed or rolled back together.
     */
    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    /**
     * Commits the current database transaction.
     *
     * This method commits all the changes made during the current database transaction.
     * It will persist all the changes to the database.
     */
    public function commit(): void
    {
        DB::commit();
    }

    /**
     * Rolls back the current database transaction.
     *
     * This method rolls back all changes made during the current database transaction.
     * It is useful when there is an error or failure, and you need to discard all changes made so far.
     */
    public function rollBack(): void
    {
        DB::rollBack();
    }

    /**
     * Retrieves the average value of a given column.
     *
     * This method performs a query to get the average value of a specific column.
     *
     * @param string $column The column to calculate the average value from.
     *
     * @return ModelInterface The average value of the column.
     */
    public function avg($column): ModelInterface
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), fn() => $this->prepareQuery($this->createModel())->avg($column));
    }

    /**
     * Extracts relationships from the given entity based on the provided attributes.
     *
     * This method checks the provided attributes for potential relationships by comparing them against the fillable attributes
     * of the entity. If a relationship method exists on the entity, it is added to the list of relationships.
     *
     * @param mixed $entity The entity from which relationships should be extracted.
     * @param array $attributes The attributes to check for relationships.
     *
     * @return array An associative array of relationships found in the entity.
     */
    protected function extractRelations($entity, array $attributes): array
    {
        $relations = [];
        $potential = Arr::diff(Arr::keys($attributes), $entity->getFillable());

        Arr::walk($potential, function($relation) use ($entity, $attributes, &$relations): void {
            // Check if the relationship method exists on the entity
            if (method_exists($entity, $relation)) {
                // Add relationship to the relations array
                $relations[$relation] = [
                    'values' => $attributes[$relation], // Store values of the relationship
                    'class' => get_class($entity->{$relation}()), // Store the class name of the relationship
                ];
            }
        });

        // Return the list of relationships
        return $relations;
    }

    /**
     * Syncs the given relationships with the entity.
     *
     * This method iterates over the provided relationships and synchronizes them with the entity.
     * By default, it uses the `sync` method for `BelongsToMany` relationships.
     *
     * @param mixed $entity The entity whose relationships should be synced.
     * @param array $relations The relationships to sync.
     * @param bool  $detaching Whether to detach existing relationships. Defaults to true.
     *
     * @return void
     */
    protected function syncRelations($entity, array $relations, $detaching = true): void
    {
        foreach ($relations as $method => $relation) {
            switch ($relation['class']) {
                // Handle the BelongsToMany relationship type
                case 'Illuminate\Database\Eloquent\Relations\BelongsToMany':
                default:
                    // Synchronize the relationship values
                    $entity->{$method}()->sync((array)$relation['values'], $detaching);

                    break;
            }
        }
    }
}
