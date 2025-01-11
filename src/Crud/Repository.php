<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Foundation\Exceptions\NoSuchEntityException;
use Maginium\Framework\Crud\Abstracts\AbstractRepository;
use Maginium\Framework\Crud\Exceptions\RepositoryException;
use Maginium\Framework\Database\Eloquent\Collection;
use Maginium\Framework\Database\Eloquent\Model;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Elasticsearch\Eloquent\Model as ElasticModel;
use Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface;
use Maginium\Framework\Pagination\Interfaces\PaginatorInterface;
use Maginium\Framework\Pagination\Paginator;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\DB;
use Maginium\Framework\Support\Facades\Event;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Validator;

/**
 * Repository class for handling CRUD operations.
 *
 * This class provides a concrete implementation of the abstract repository
 * pattern. It serves as a central point for managing data operations, such
 * as querying, creating, updating, and deleting records, as well as handling
 * relationships and pagination.
 */
class Repository extends AbstractRepository
{
    /**
     * AbstractRepository constructor.
     *
     * @param ModelInterface $model The model model interface.
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
     * @throws NoSuchEntityException If the pagination fails or no records are found.
     *
     * @return LengthAwarePaginatorInterface The paginated result set.
     */
    public function paginate(?int $perPage = null, array $attributes = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginatorInterface
    {
        try {
            // Resolve the current page if it's not provided.
            $page = $page ?: Paginator::resolveCurrentPage($pageName);

            // Prepare and execute the query to paginate the results based on the given parameters.
            $result = $this->executeCallback(
                static::class, // Repository class
                __FUNCTION__, // Current function name
                Arr::merge(
                    func_get_args(),
                    compact('page'), // Merge arguments with the current page
                ),
                function() use ($perPage, $attributes, $pageName, $page) {
                    // Prepare the query with the model instance and execute the pagination
                    return $this->prepareQuery($this->createModel())
                        ->paginate($perPage, $attributes, $pageName, $page);
                },
            );

            // If no results are found, throw a NoSuchEntityException exception.
            if (! $result) {
                throw NoSuchEntityException::make('No records found for the given criteria.');
            }

            // Return the paginated results.
            return $result;
        } catch (Exception $e) {
            // Catch any exceptions that occur and throw a NoSuchEntityException exception with a detailed message.
            throw LocalizedException::make(
                'Failed to retrieve paginated results. Please try again later.',
                $e, // Pass the original exception for better traceability
            );
        }
    }

    /**
     * Find an model by its ID.
     *
     * This method prepares and executes a query to find an model by its ID,
     * using the specified attributes for selection. It throws a NoSuchEntityException
     * exception if the model is not found.
     *
     * @param mixed $id The ID of the model to find.
     * @param array $attributes The attributes to retrieve (default: all columns).
     *
     * @throws NoSuchEntityException If the model cannot be found with the given ID.
     *
     * @return ModelInterface The found model.
     */
    public function find(int|string|array $id, array $attributes = ['*']): ModelInterface
    {
        try {
            // Execute the callback and prepare the query to find the model by its ID.
            $result = $this->executeCallback(
                static::class, // The repository class
                __FUNCTION__, // The method being called
                func_get_args(), // The arguments passed to the method
                fn() => $this->prepareQuery($this->createModel())->find($id, $attributes), // The callback to find the model
            );

            // Check if the result is null, meaning the model was not found.
            if (! $result) {
                // Throw a NoSuchEntityException exception if no model is found.
                throw NoSuchEntityException::make('Model with ID %1 not found.', $id);
            }

            // Return the found model.
            return $result;
        } catch (Exception $e) {
            // Catch any exceptions and rethrow them with a more specific exception (NoSuchEntityException)
            throw LocalizedException::make("An error occurred while retrieving the model: {$e->getMessage()}", $e, $e->getCode());
        }
    }

    /**
     * Find an model by its ID or fail.
     *
     * This method finds an model by ID or throws an exception if the model
     * cannot be found. It can handle arrays of IDs and check for missing entities.
     *
     * @param mixed $id The ID(s) of the model to find. This can be a single ID or an array of IDs.
     * @param array $attributes The attributes to retrieve (default: all attributes).
     *
     * @throws NoSuchEntityException If the model cannot be found.
     *
     * @return ModelInterface|ModelInterface[] The found model or array of entities.
     */
    public function findOrFail(int|string|array $id, array $attributes = ['*']): ModelInterface|array
    {
        try {
            // Handle the case where multiple IDs are provided
            if (Validator::isArray($id)) {
                /** @var ModelInterface[] $results */
                // TODO: FIX TypeError: Elasticsearch\Utility::urlencode(): Argument #1 ($url) must be of type string, array given

                // Perform the search with multiple IDs
                $results = $this->find($id, $attributes);

                // Ensure all entities are found (compare lengths of arrays)
                if (count($results) === count($id)) {
                    // Return the found entities if the count matches
                    return $results;
                }

                // If the count doesn't match, throw an exception for missing entities
                throw new NoSuchEntityException(
                    __('Some entities were not found for the provided IDs: $1', implode(', ', $id)),
                );
            }

            // Search for a single model
            $result = $this->find($id, $attributes);

            // If the model is not found, throw an exception
            if ($result === null) {
                throw new NoSuchEntityException(
                    __('Model not found for the provided ID: {$id}'),
                );
            }

            // Return the found model
            return $result;
        } catch (NoSuchEntityException $e) {
            // Log the exception or perform any necessary actions
            // Re-throw the exception for upstream handling
            throw $e;
        } catch (Exception $e) {
            // Catch any other exceptions and throw a more generic one
            throw new NoSuchEntityException(
                __('An error occurred while finding the model: :message', ['message' => $e->getMessage()]),
            );
        }
    }

    /**
     * Find an model by its ID or create a new instance if not found.
     *
     * This method attempts to find an model by ID. If it doesn't exist,
     * it creates and returns a new instance of the model.
     *
     * @param mixed $id The ID of the model to find.
     * @param array $attributes The attributes to retrieve.
     *
     * @return ModelInterface The found model or a new instance of the model.
     */
    public function findOrNew(int|string $id, array $attributes = ['*']): ModelInterface
    {
        // Getting the model
        $model = $this->find($id, $attributes);

        // Check if the model is not null
        if ($model !== null) {
            return $model;
        }

        // Return a new instance if the model wasn't found
        return $this->createModel();
    }

    /**
     * Find the first model based on the provided attributes.
     * This method returns the first model or throws a NoSuchEntityException exception if no model is found.
     *
     * @param array $attributes The columns to select (default: all columns).
     *
     * @throws NoSuchEntityException If no model is found.
     *
     * @return ModelInterface|null The first found model, or throws NoSuchEntityException if not found.
     */
    public function findFirst(array $attributes = ['*']): ?ModelInterface
    {
        try {
            // Attempt to retrieve the first model matching the criteria
            $result = $this->executeCallback(static::class, __FUNCTION__, func_get_args(), fn() => $this->prepareQuery($this->createModel())
                ->first($attributes));

            // If no model is found, throw a custom NoSuchEntityException exception
            if (! $result) {
                throw NoSuchEntityException::make(__('Model not found.'));
            }

            // Return the first model found
            return $result;
        } catch (Exception $e) {
            // Catch any general exception and throw it with a custom message
            throw LocalizedException::make(__('Failed to find model. Please try again later.'), $e, $e->getCode());
        }
    }

    /**
     * Find entities that match a specific condition (e.g., where, operator, value).
     * This method allows you to find entities based on a custom condition.
     *
     * @param array $where The condition to apply (e.g., ['attribute', '=', 'value']).
     * @param array $attributes The columns to select (default: all columns).
     *
     * @throws NoSuchEntityException If no matching entities are found.
     *
     * @return Collection The collection of matching entities.
     */
    public function findWhere(array $where, array $attributes = ['*']): Collection
    {
        try {
            // Parse the condition (attribute, operator, value, and boolean)
            [$attribute, $operator, $value, $boolean] = Arr::pad($where, 4, null);

            // Apply the condition (can be customized)
            $this->where($attribute, $operator, $value, $boolean);

            // Retrieve entities that match the condition
            $result = $this->executeCallback(static::class, __FUNCTION__, func_get_args(), fn(): mixed => $this->prepareQuery($this->createModel())->get($attributes));

            // If no entities are found, throw a custom NoSuchEntityException exception
            if (! $result) {
                throw NoSuchEntityException::make(__('No matching entities found.'));
            }

            // Return the collection of matching entities
            return $result;
        } catch (Exception $e) {
            // Catch any general exception and throw it with a custom message
            throw LocalizedException::make(__('Failed to find matching entities. Please try again later.'), $e, $e->getCode());
        }
    }

    /**
     * Find entities where a specific attribute is within a given list of values.
     * This method allows you to find entities that match any value in a list.
     *
     * @param array $where The condition with attribute and values (e.g., ['attribute', ['value1', 'value2']]).
     * @param array $attributes The columns to select (default: all columns).
     *
     * @throws NoSuchEntityException If no matching entities are found.
     *
     * @return Collection The collection of matching entities.
     */
    public function findWhereIn(array $where, array $attributes = ['*']): Collection
    {
        try {
            // Parse the condition (attribute, values, boolean, and negation)
            [$attribute, $values, $boolean, $not] = Arr::pad($where, 4, null);

            // Apply the whereIn condition using the parsed parameters
            $this->whereIn($attribute, $values, $boolean, $not);

            // Execute the query to retrieve entities that match the condition
            $result = $this->prepareQuery($this->createModel())->get($attributes);

            // Check if any entities were found, if not, throw a NoSuchEntityException exception
            if (! $result) {
                throw NoSuchEntityException::make(__('No matching entities found for the provided values.'));
            }

            // Return the collection of matching entities
            return $result;
        } catch (Exception $e) {
            // Catch any general exception and throw it with a custom message
            throw LocalizedException::make(__('Failed to find matching entities. Please try again later.'), $e, $e->getCode());
        }
    }

    /**
     * Find entities where a specific attribute is not in a given list of values.
     * This method allows you to find entities that don't match any value in a list.
     *
     * @param array $where The condition with attribute and values (e.g., ['attribute', ['value1', 'value2']]).
     * @param array $attributes The columns to select (default: all columns).
     *
     * @throws NoSuchEntityException If no matching entities are found.
     *
     * @return Collection The collection of matching entities.
     */
    public function findWhereNotIn(array $where, array $attributes = ['*']): Collection
    {
        try {
            // Parse the condition (attribute, values, and boolean)
            [$attribute, $values, $boolean] = Arr::pad($where, 3, null);

            // Apply the whereNotIn condition using the parsed parameters
            $this->whereNotIn($attribute, $values, $boolean);

            // Execute the query to retrieve entities that do not match the condition
            $result = $this->prepareQuery($this->createModel())->get($attributes);

            // Check if any entities were found, if not, throw a NoSuchEntityException exception
            if (! $result) {
                throw NoSuchEntityException::make(__('No entities found that do not match the provided values.'));
            }

            // Return the collection of entities that do not match the provided values
            return $result;
        } catch (Exception $e) {
            // Catch any general exception and throw it with a custom message
            throw LocalizedException::make(__('Failed to find entities not matching the provided values. Please try again later.'), $e, $e->getCode());
        }
    }

    /**
     * Find an model by a specific attribute and value.
     * This method returns the first matching result based on the given attribute and value.
     *
     * @param string $attribute The attribute to search by.
     * @param mixed $value The value to search for.
     * @param array $attributes The columns to select (default: all columns).
     *
     * @throws NoSuchEntityException If the model is not found.
     *
     * @return ModelInterface|null The found model, or null if not found.
     */
    public function findBy(
        string $attribute,
        string|int|float|bool|null $value,
        array $attributes = ['*'],
    ): ?ModelInterface {
        try {
            // Execute the query to find the model
            $result = $this->executeCallback(
                static::class,
                __FUNCTION__,
                func_get_args(),
                fn() => $this->prepareQuery($this->createModel())
                    ->where($attribute, '=', $value)
                    ->first($attributes),
            );

            // Check if the result is null
            if (! $result) {
                throw NoSuchEntityException::make(
                    __('No model found with %1 = %2.', $attribute, $value),
                );
            }

            // Return the found model
            return $result;
        } catch (NoSuchEntityException $e) {
            // Re-throw the exception to be handled by the caller
            throw $e;
        } catch (Exception $e) {
            throw LocalizedException::make(
                __('Failed to find model due to an unexpected error.'),
                $e,
            );
        }
    }

    /**
     * Counts the number of records in the database.
     *
     * This method performs a `count` query on the database, returning the total number of records that match the criteria.
     * The method can count any column, with the default being `*` (all columns).
     *
     * @param string $columns The column to count. Defaults to '*' for counting all rows.
     *
     * @throws LocalizedException If an unexpected error occurs during the query.
     *
     * @return int The number of records found.
     */
    public function count(string $columns = '*'): int
    {
        try {
            // Execute the count query and store the result in the $result variable.
            $result = $this->executeCallback(
                static::class, // Current class name for context.
                __FUNCTION__, // Name of the function being executed.
                func_get_args(), // Arguments passed to the function.
                fn() => $this->prepareQuery(
                    $this->createModel(), // Create a new model instance for the query.
                )->count($columns), // Perform the count query on the specified column(s).
            );

            // Check if the result is null
            if (! $result) {
                throw NoSuchEntityException::make(
                    __('No model found.'),
                );
            }

            // Return the result of the count query.
            return $result;
        } catch (Exception $e) {
            // Throw a LocalizedException with a user-friendly message.
            throw LocalizedException::make(
                __('Failed to count records. Please try again later.'),
                $e,
            );
        }
    }

    /**
     * Retrieves the sum of a given column.
     *
     * This method performs a query to calculate the sum of values in a specific column.
     *
     * @param string $column The column to sum.
     *
     * @throws LocalizedException If an unexpected error occurs during the query.
     *
     * @return float The sum of values in the column.
     */
    public function sum(string $column): float
    {
        try {
            // Execute the sum query and store the result in the $result variable.
            $result = $this->executeCallback(
                static::class,// Current class name for context.
                __FUNCTION__, // Name of the function being executed.
                func_get_args(),// Arguments passed to the function.
                fn() => $this->prepareQuery(
                    $this->createModel(),      // Create a new model instance for the query.
                )->sum($column),// Perform the sum query on the specified column.
            );

            // Check if the result is null
            if (! $result) {
                throw NoSuchEntityException::make(
                    __('No model found.'),
                );
            }

            // Return the result of the sum query.
            return $result;
        } catch (Exception $e) {
            // Throw a LocalizedException with a user-friendly message.
            throw LocalizedException::make(
                __("Failed to retrieve the sum for column '{$column}'. Please try again later."),
                $e,
            );
        }
    }

    /**
     * Retrieves the maximum value of a given column.
     *
     * This method performs a query to get the maximum value of a specific column.
     *
     * @param string $column The column to retrieve the maximum value from.
     *
     * @throws LocalizedException If an unexpected error occurs during the query.
     *
     * @return mixed The maximum value of the column.
     */
    public function max(string $column): mixed
    {
        try {
            // Execute the max query and store the result in the $result variable.
            $result = $this->executeCallback(
                static::class,// Current class name for context.
                __FUNCTION__, // Name of the function being executed.
                func_get_args(),// Arguments passed to the function.
                fn() => $this->prepareQuery(
                    $this->createModel(),      // Create a new model instance for the query.
                )->max($column),// Perform the max query on the specified column.
            );

            // Check if the result is null
            if (! $result) {
                throw NoSuchEntityException::make(
                    __('No model found.'),
                );
            }

            // Return the result of the max query.
            return $result;
        } catch (Exception $e) {
            // Throw a LocalizedException with a user-friendly message.
            throw LocalizedException::make(
                __(argc: "Failed to retrieve the maximum value for column '{$column}'. Please try again later."),
                $e,
            );
        }
    }

    /**
     * Retrieves the minimum value of a given column.
     *
     * This method performs a query to get the minimum value of a specific column.
     *
     * @param string $column The column to retrieve the minimum value from.
     *
     * @throws LocalizedException If an unexpected error occurs during the query.
     *
     * @return mixed The minimum value of the column.
     */
    public function min(string $column): mixed
    {
        try {
            // Execute the min query and store the result in the $result variable.
            $result = $this->executeCallback(
                static::class,// Current class name for context.
                __FUNCTION__, // Name of the function being executed.
                func_get_args(),// Arguments passed to the function.
                fn() => $this->prepareQuery(
                    $this->createModel(),      // Create a new model instance for the query.
                )->min($column),// Perform the min query on the specified column.
            );

            // Check if the result is null
            if (! $result) {
                throw NoSuchEntityException::make(
                    __('No model found.'),
                );
            }

            // Return the result of the min query.
            return $result;
        } catch (Exception $e) {
            // Throw a LocalizedException with a user-friendly message.
            throw LocalizedException::make(
                __("Failed to retrieve the minimum value for column '{$column}'. Please try again later."), // Error code (default is 0).
                $e,
            );
        }
    }

    /**
     * Create a new model instance and save it to the database.
     *
     * This method handles creating a new model, dispatching events,
     * syncing relationships, and saving the model to the database.
     *
     * @param array $attributes Attributes for the new model.
     * @param bool $syncRelations Whether to sync the relationships with the model.
     *
     * @throws LocalizedException If the model could not be created.
     *
     * @return ModelInterface The created model or false if the save operation failed.
     */
    public function create(array $attributes = [], bool $syncRelations = false): ModelInterface
    {
        try {
            /** @var Model|ElasticModel $model */

            // Create a new instance of the model.
            $model = $this->createModel();

            // Dispatch the 'creating' event before saving the model.
            Event::dispatch($this->getRepositoryId() . '.model.creating', [$this, $model]);

            // Extract and remove relationships from the attributes if syncing is required.
            if ($syncRelations) {
                $relations = $this->extractRelations($model, $attributes);
                Arr::forget($attributes, Arr::keys($relations));
            }

            // Fill the model with the provided attributes.
            $model->fill($attributes);

            // Save the model to the database and store the result.
            $result = $model->save();

            // Sync relationships after saving the model, if required.
            if ($syncRelations && isset($relations)) {
                $this->syncRelations($model, $relations);
            }

            // Dispatch the 'created' event after the model has been saved.
            Event::dispatch($this->getRepositoryId() . '.model.created', [$this, $model]);

            // Return the created model or throw an exception if the save operation failed.
            if ($result) {
                return $model;
            }

            throw LocalizedException::make('Failed to save the model.');
        } catch (Exception $e) {
            // Throw a LocalizedException with a user-friendly message.
            throw LocalizedException::make(
                __('Failed to create model. Please try again later.'),
                $e,
            );
        }
    }

    /**
     * Update an existing model in the database.
     *
     * This method finds an existing model by ID, fills it with new attributes,
     * checks for changes, dispatches events, and updates the model in the database.
     *
     * @param mixed $id The ID of the model to update.
     * @param array $attributes Attributes to update the model with.
     * @param bool $syncRelations Whether to sync the relationships with the model.
     *
     * @throws LocalizedException If the model could not be updated.
     *
     * @return ModelInterface The updated model or false if the update failed.
     */
    public function update(int|string $id, array $attributes = [], bool $syncRelations = false): ModelInterface
    {
        try {
            // Find the model by ID or use the given model if it's already an instance.
            /** @var Model|ElasticModel $model */
            $model = $id instanceof ModelInterface ? $id : $this->find($id);

            // If the model is not found, throw a NoSuchEntityException exception.
            if (! $model) {
                throw NoSuchEntityException::make(__('Model not found for ID: %1', $id));
            }

            // Dispatch the 'updating' event before updating the model.
            Event::dispatch($this->getRepositoryId() . '.model.updating', [$this, $model]);

            // Extract and remove relationships from the attributes if syncing is required.
            if ($syncRelations) {
                $relations = $this->extractRelations($model, $attributes);
                Arr::forget($attributes, Arr::keys($relations));
            }

            // Fill the model with the new attributes.
            $model->fill($attributes);

            // Track the dirty attributes (attributes that have been modified).
            $dirty = $model->getDirty();

            // Save the updated model to the database and store the result.
            $result = $model->save();

            // Sync relationships after saving if required.
            if ($syncRelations && isset($relations)) {
                $this->syncRelations($model, $relations);
            }

            // If any attributes were updated, dispatch the 'updated' event.
            if (count($dirty) > 0) {
                Event::dispatch($this->getRepositoryId() . '.model.updated', [$this, $model]);
            }

            // Return the updated model.
            return $model;
        } catch (Exception $e) {
            // Throw a LocalizedException with a user-friendly message.
            throw LocalizedException::make(
                __('Failed to update model. Please try again later.'),
                $e,
            );
        }
    }

    /**
     * Deletes the model by its ID.
     *
     * This method attempts to find the model either by its ID or directly if the provided argument
     * is already an instance of a model. If the model is found, it triggers the `deleting` event,
     * deletes the model, and triggers the `deleted` event afterward. If no model is found,
     * it throws a NoSuchEntityException exception.
     *
     * @param int|string|ModelInterface $id The ID or instance of the model to be deleted.
     *
     * @throws NoSuchEntityException If the model does not exist.
     *
     * @return ModelInterface The deleted model.
     */
    public function delete(int|string|ModelInterface $id): ModelInterface
    {
        try {
            // Attempt to find the model by ID or directly use the provided model instance.
            /** @var Model|null $model */
            $model = $id instanceof ModelInterface ? $id : $this->find($id);

            // If the model is not found, throw a NoSuchEntityException exception.
            if (! $model) {
                throw NoSuchEntityException::make(__('Model not found for ID: %1', $id));
            }

            // Trigger the 'deleting' event to allow pre-deletion logic to run.
            Event::dispatch($this->getRepositoryId() . '.model.deleting', [$this, $model]);

            // Perform the deletion operation.
            $isDeleted = $model->delete();

            // If deletion is successful, trigger the 'deleted' event and set the result.
            if ($isDeleted) {
                Event::dispatch($this->getRepositoryId() . '.model.deleted', [$this, $model]);

                // Set the result to the deleted model.
                $result = $model;
            } else {
                // Log or handle cases where deletion fails unexpectedly.
                throw LocalizedException::make(__('Failed to delete the model with ID: %1', $id));
            }

            // Return the deleted model as the result.
            return $result;
        } catch (NoSuchEntityException $e) {
            // Handle specific case where the model is not found.
            // Optionally, rethrow or log the exception based on application needs.
            throw $e;
        } catch (Exception $e) {
            // Catch any other unexpected exceptions and log or handle them.
            // Optionally, wrap the exception in a custom repository exception.
            throw LocalizedException::make(__('An error occurred while deleting the model: %1', $e->getMessage()), $e, $e->getCode());
        }
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
        /** @var Model|null $model */
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
     * @throws RepositoryException If an error occurs during the query execution.
     *
     * @return Collection The collection of found entities.
     */
    public function findAll(array $attributes = ['*']): Collection
    {
        try {
            // Execute the callback to retrieve all entities with the selected attributes
            $result = $this->executeCallback(
                static::class,
                __FUNCTION__,
                func_get_args(),
                fn() => $this->prepareQuery($this->createModel())->get($attributes),
            );

            // Check if the result is null
            if (! $result) {
                throw NoSuchEntityException::make(
                    __('No model found.'),
                );
            }

            // Return the find all result.
            return $result;
        } catch (\Exception $e) {
            // Catch any errors and throw a RepositoryException with the error message
            throw LocalizedException::make(__('An error occurred while fetching all entities: %1', $e->getMessage()), $e, $e->getCode());
        }
    }

    /**
     * Simplified pagination with fewer features, ideal for smaller datasets.
     * This method provides a simpler paginated result, suitable for when full pagination is unnecessary.
     *
     * @param int|null $perPage The number of items to display per page (default: null).
     * @param array $attributes The columns to select (default: all columns).
     * @param string $pageName The name of the page parameter for pagination (default: 'page').
     * @param int|null $page The current page number (optional).
     *
     * @throws RepositoryException If any error occurs during pagination.
     *
     * @return PaginatorInterface The simpler paginated result set.
     */
    public function simplePaginate(
        ?int $perPage = null,
        array $attributes = ['*'],
        string $pageName = 'page',
        ?int $page = null,
    ): PaginatorInterface {
        try {
            // Resolve the current page if not explicitly provided.
            $page = $page ?: Paginator::resolveCurrentPage($pageName);

            // Execute the callback for simple pagination.
            $result = $this->executeCallback(
                static::class,
                __FUNCTION__,
                Arr::merge(func_get_args(), compact('page')),
                fn() => $this->prepareQuery($this->createModel())
                    ->simplePaginate($perPage, $attributes, $pageName, $page),
            );

            // Check if the result is null
            if (! $result) {
                throw NoSuchEntityException::make(
                    __('No model found.'),
                );
            }

            // Return the paginated result.
            return $result;
        } catch (Exception $e) {
            // Catch any errors and throw a RepositoryException with additional context.
            throw LocalizedException::make(__('An error occurred during pagination: %1', $e->getMessage()), $e, $e->getCode());
        }
    }

    /**
     * Find entities that have a related model matching specific conditions.
     * This method allows you to find entities with a relation that matches the given condition.
     *
     * @param array $where The condition to apply (e.g., ['relation', callback, operator, count]).
     * @param array $attributes The columns to select (default: all columns).
     *
     * @throws RepositoryException If an error occurs while finding related entities.
     *
     * @return Collection The collection of matching entities.
     */
    public function findWhereHas(array $where, array $attributes = ['*']): Collection
    {
        try {
            // Execute the callback for finding related entities.
            $result = $this->executeCallback(static::class, __FUNCTION__, func_get_args(), function() use ($where, $attributes) {
                // Parse the relation and condition (relation, callback, operator, count).
                [$relation, $callback, $operator, $count] = Arr::pad($where, 4, null);

                // Apply the whereHas condition to filter by the related model.
                $this->whereHas($relation, $callback, $operator, $count);

                // Retrieve entities with a related model matching the condition.
                return $this->prepareQuery($this->createModel())->get($attributes);
            });

            // Check if the result is null
            if (! $result) {
                throw NoSuchEntityException::make(
                    __('No model found.'),
                );
            }

            // Return the collection of matching entities.
            return $result;
        } catch (Exception $e) {
            // Handle any errors and throw a RepositoryException.
            throw LocalizedException::make(__('An error occurred while finding entities: %1', $e->getMessage()), $e, $e->getCode());
        }
    }

    /**
     * Restores the deleted model by its ID.
     *
     * This method attempts to restore a previously soft-deleted model. If the model is found, the `restoring` event is fired,
     * followed by the restoration of the model. After restoring, the `restored` event is triggered.
     *
     * @param int|string|ModelInterface $id The ID or instance of the model to be restored.
     *
     * @throws NoSuchEntityException If the model does not exist.
     * @throws RepositoryException If any other error occurs during restoration.
     *
     * @return ModelInterface The restored model.
     */
    public function restore(int|string|ModelInterface $id): ModelInterface
    {
        try {
            // Find the given instance, either by ID or directly using the provided model.
            /** @var Model|null $model */
            $model = $id instanceof ModelInterface ? $id : $this->find($id);

            // If the model is not found, throw a NoSuchEntityException.
            if (! $model) {
                throw NoSuchEntityException::make(__('Model not found for ID: %1', $id));
            }

            // Trigger the 'restoring' event to allow pre-restoration logic to run.
            Event::dispatch($this->getRepositoryId() . '.model.restoring', [$this, $model]);

            // Attempt to restore the model.
            if ($model->restore()) {
                // Trigger the 'restored' event after successful restoration.
                Event::dispatch($this->getRepositoryId() . '.model.restored', [$this, $model]);

                // Set the result to the restored model.
                $result = $model;
            } else {
                // Log or handle cases where restoration fails unexpectedly.
                throw LocalizedException::make(__('Failed to restore the model with ID: %1', $id));
            }

            // Return the restored model.
            return $result;
        } catch (NoSuchEntityException $e) {
            // Handle specific case where the model is not found.
            throw $e;
        } catch (Exception $e) {
            // Catch any other unexpected errors and rethrow as a RepositoryException.
            throw LocalizedException::make(__('An error occurred while restoring the model: %1', $e->getMessage()), $e, $e->getCode());
        }
    }

    /**
     * Retrieves the average value of a given column.
     *
     * This method performs a query to calculate the average value of a specific column.
     *
     * @param string $column The column to calculate the average value from.
     *
     * @throws RepositoryException If an error occurs during the query execution.
     *
     * @return float The average value of the column.
     */
    public function avg(string $column): float
    {
        try {
            // Execute the callback to calculate the average value for the specified column.
            $result = $this->executeCallback(
                static::class,
                __FUNCTION__,
                func_get_args(),
                fn() => $this->prepareQuery($this->createModel())->avg($column),
            );

            // Check if the result is null
            if (! $result) {
                throw NoSuchEntityException::make(
                    __('No model found.'),
                );
            }

            // Return the calculated average value.
            return $result;
        } catch (Exception $e) {
            // Catch any errors and throw a RepositoryException with the error message.
            throw LocalizedException::make(__('An error occurred while calculating the average: %1', $e->getMessage()), $e, $e->getCode());
        }
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
     * Extracts relationships from the given model based on the provided attributes.
     *
     * This method checks the provided attributes for potential relationships by comparing them against the fillable attributes
     * of the model. If a relationship method exists on the model, it is added to the list of relationships.
     *
     * @param mixed $model The model from which relationships should be extracted.
     * @param array $attributes The attributes to check for relationships.
     *
     * @return array An associative array of relationships found in the model.
     */
    protected function extractRelations($model, array $attributes): array
    {
        $relations = [];
        $potential = Arr::diff(Arr::keys($attributes), $model->getFillable());

        Arr::walk($potential, function($relation) use ($model, $attributes, &$relations): void {
            // Check if the relationship method exists on the model
            if (method_exists($model, $relation)) {
                // Add relationship to the relations array
                $relations[$relation] = [
                    'values' => $attributes[$relation], // Store values of the relationship
                    'class' => get_class($model->{$relation}()), // Store the class name of the relationship
                ];
            }
        });

        // Return the list of relationships
        return $relations;
    }

    /**
     * Syncs the given relationships with the model.
     *
     * This method iterates over the provided relationships and synchronizes them with the model.
     * By default, it uses the `sync` method for `BelongsToMany` relationships.
     *
     * @param mixed $model The model whose relationships should be synced.
     * @param array $relations The relationships to sync.
     * @param bool  $detaching Whether to detach existing relationships. Defaults to true.
     *
     * @return void
     */
    protected function syncRelations($model, array $relations, $detaching = true): void
    {
        foreach ($relations as $method => $relation) {
            switch ($relation['class']) {
                // Handle the BelongsToMany relationship type
                case 'Illuminate\Database\Eloquent\Relations\BelongsToMany':
                default:
                    // Synchronize the relationship values
                    $model->{$method}()->sync((array)$relation['values'], $detaching);

                    break;
            }
        }
    }
}
