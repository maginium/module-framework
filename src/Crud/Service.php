<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud;

use AllowDynamicProperties;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Crud\Abstracts\AbstractService;
use Maginium\Framework\Crud\Exceptions\EntityNotFoundException;
use Maginium\Framework\Crud\Exceptions\RepositoryException;
use Maginium\Framework\Crud\Interfaces\Repositories\RepositoryInterface;
use Maginium\Framework\Crud\Interfaces\Services\ServiceInterface;
use Maginium\Framework\Database\Eloquent\Collection;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface;
use Maginium\Framework\Pagination\Interfaces\PaginatorInterface;
use Maginium\Framework\Support\Facades\Log;

/**
 * Class Service.
 *
 * A generic service class for managing CRUD operations for models.
 * This class interacts with the repository layer to perform operations such as
 * retrieving, saving, deleting, and creating models.
 */
#[AllowDynamicProperties]
class Service extends AbstractService implements ServiceInterface
{
    /**
     * Repository instance for interacting with the database.
     *
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repository;

    /**
     * Constructor for the Service class.
     * Initializes the repository and sets up logging and model naming.
     *
     * @param RepositoryInterface $repository The repository for the model.
     */
    public function __construct(
        RepositoryInterface $repository,
    ) {
        $this->repository = $repository;

        // Set the class name for log context
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
    public function paginate(?int $perPage = null, array $attributes = ['*'], ?int $page = null, string $pageName = 'page'): LengthAwarePaginatorInterface
    {
        try {
            return $this->repository->paginate($perPage, $attributes, $pageName, $page);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function find(int|string|array $id, array $attributes = ['*']): ModelInterface
    {
        try {
            return $this->repository->find($id, $attributes = ['*']);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function findOrFail(int|string|array $id, array $attributes = ['*']): ModelInterface|array
    {
        try {
            return $this->repository->findOrFail($id, $attributes);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function findOrNew(int|string $id, array $attributes = ['*']): ModelInterface
    {
        try {
            return $this->repository->findOrNew($id, $attributes);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Find the first entity based on the provided attributes.
     * This method returns the first entity without any specific condition.
     *
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return ModelInterface|null The first found entity, or null if no entities exist.
     */
    public function findFirst(array $attributes = ['*']): ?ModelInterface
    {
        try {
            return $this->repository->findFirst($attributes);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function findWhere(array $where, array $attributes = ['*']): Collection
    {
        try {
            return $this->repository->findWhere($where, $attributes);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function findWhereIn(array $where, array $attributes = ['*']): Collection
    {
        try {
            return $this->repository->findWhereIn($where, $attributes);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function findWhereNotIn(array $where, array $attributes = ['*']): Collection
    {
        try {
            return $this->repository->findWhereNotIn($where, $attributes);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function findBy(string $attribute, string|int|float|bool|null $value, array $attributes = ['*']): ?ModelInterface
    {
        try {
            return $this->repository->findBy($attribute, $value, $attributes);
        } catch (Exception $e) {
            throw $e;
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
     * @return int The number of records found.
     */
    public function count(string $columns = '*'): int
    {
        try {
            return $this->repository->count($columns);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieves the sum of a given column.
     *
     * This method performs a query to calculate the sum of values in a specific column.
     *
     * @param string $column The column to sum.
     *
     * @return float The sum of values in the column.
     */
    public function sum(string $column): float
    {
        try {
            return $this->repository->sum($column);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function max(string $column): mixed
    {
        try {
            return $this->repository->max($column);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function min(string $column): mixed
    {
        try {
            return $this->repository->min($column);
        } catch (Exception $e) {
            throw $e;
        }
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
        try {
            return $this->repository->create($attributes, $syncRelations);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function update(int|string $id, array $attributes = [], bool $syncRelations = false): ModelInterface
    {
        try {
            return $this->repository->update($id, $attributes, $syncRelations);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function delete(int|string|ModelInterface $id): ModelInterface
    {
        try {
            return $this->repository->delete($id);
        } catch (Exception $e) {
            throw $e;
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
        try {
            return $this->repository->createModel();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Find all entities, retrieving all records from the database.
     * This method returns all matching entities, typically used when no filters are applied.
     *
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return Collection The collection of found entities.
     */
    public function findAll(array $attributes = ['*']): Collection
    {
        try {
            return $this->repository->findAll($attributes);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function simplePaginate(
        ?int $perPage = null,
        array $attributes = ['*'],
        string $pageName = 'page',
        ?int $page = null,
    ): PaginatorInterface {
        try {
            return $this->repository->simplePaginate($perPage, $attributes, $pageName, $page);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function findWhereHas(array $where, array $attributes = ['*']): Collection
    {
        try {
            return $this->repository->findWhereHas($where, $attributes = ['*']);
        } catch (Exception $e) {
            throw $e;
        }
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
    public function restore(int|string|ModelInterface $id): ModelInterface
    {
        try {
            return $this->repository->restore($id);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieves the average value of a given column.
     *
     * This method performs a query to get the average value of a specific column.
     *
     * @param string $column The column to calculate the average value from.
     *
     * @return float The average value of the column.
     */
    public function avg(string $column): float
    {
        try {
            return $this->repository->avg($column);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get the model name from a given class, lowercased.
     *
     * @return string The lowercased base class name.
     */
    public function getEntityName(): string
    {
        return $this->repository->getEntityName();
    }

    /**
     * Retrieve the repository associated with the current model.
     *
     * @return RepositoryInterface The repository instance associated with the model.
     */
    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }
}
