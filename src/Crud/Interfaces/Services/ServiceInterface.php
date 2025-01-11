<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces\Services;

use Maginium\Framework\Crud\Exceptions\EntityNotFoundException;
use Maginium\Framework\Crud\Exceptions\RepositoryException;
use Maginium\Framework\Crud\Interfaces\Repositories\RepositoryInterface;
use Maginium\Framework\Database\Eloquent\Collection;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface;
use Maginium\Framework\Pagination\Interfaces\PaginatorInterface;

/**
 * Interface ServiceInterface.
 *
 * This interface defines the core contract for CRUD service classes,
 * providing methods for managing entities, performing database operations,
 * and handling pagination. Implementations of this interface ensure
 * standardization across service layers within the application.
 */
interface ServiceInterface
{
    /**
     * Paginate the results based on a specified number of items per page.
     * This method returns a paginated result set, which includes metadata like total pages, current page, etc.
     *
     * @param int|null $perPage The number of items to display per page (default: null).
     * @param array $attributes The columns to select (default: all columns).
     * @param int|null $page The current page number (optional).
     * @param string $pageName The name of the page parameter for pagination (default: 'page').
     *
     * @return LengthAwarePaginatorInterface The paginated result set.
     */
    public function paginate(?int $perPage = null, array $attributes = ['*'], ?int $page = null, string $pageName = 'page'): LengthAwarePaginatorInterface;

    /**
     * Find an entity by its ID.
     *
     * @param int|string $id The ID of the entity to find.
     * @param array $attributes The attributes to retrieve.
     *
     * @return ModelInterface The found entity or null if not found.
     */
    public function find(int|string $id, array $attributes = ['*']): ModelInterface;

    /**
     * Find an entity by its ID or fail.
     *
     * @param int|string|array $id The ID(s) of the entity to find. This can be a single ID or an array of IDs.
     * @param array $attributes The attributes to retrieve.
     *
     * @throws EntityNotFoundException If the entity cannot be found.
     *
     * @return ModelInterface|ModelInterface[] The found entity or array of entities.
     */
    public function findOrFail(int|string|array $id, array $attributes = ['*']): ModelInterface|array;

    /**
     * Find an entity by its ID or create a new instance if not found.
     *
     * @param int|string $id The ID of the entity to find.
     * @param array $attributes The attributes to retrieve.
     *
     * @return ModelInterface The found entity or a new instance of the model.
     */
    public function findOrNew(int|string $id, array $attributes = ['*']): ModelInterface;

    /**
     * Find the first entity based on the provided attributes.
     *
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return ModelInterface|null The first found entity, or null if no entities exist.
     */
    public function findFirst(array $attributes = ['*']): ?ModelInterface;

    /**
     * Find entities that match a specific condition.
     *
     * @param array $where The condition to apply (e.g., ['attribute', '=', 'value']).
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return Collection The collection of matching entities.
     */
    public function findWhere(array $where, array $attributes = ['*']): Collection;

    /**
     * Find entities where a specific attribute is within a given list of values.
     *
     * @param array $where The condition with attribute and values (e.g., ['attribute', ['value1', 'value2']]).
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return Collection The collection of matching entities.
     */
    public function findWhereIn(array $where, array $attributes = ['*']): Collection;

    /**
     * Find entities where a specific attribute is not in a given list of values.
     *
     * @param array $where The condition with attribute and values (e.g., ['attribute', ['value1', 'value2']]).
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return Collection The collection of matching entities.
     */
    public function findWhereNotIn(array $where, array $attributes = ['*']): Collection;

    /**
     * Find an entity by a specific attribute and value.
     *
     * @param string $attribute The attribute to search by.
     * @param string|int|float|bool|null $value The value to search for.
     * @param array $attributes The columns to select (default: all columns).
     *
     * @return ModelInterface|null The found entity, or null if not found.
     */
    public function findBy(string $attribute, string|int|float|bool|null $value, array $attributes = ['*']): ?ModelInterface;

    /**
     * Counts the number of records in the database.
     *
     * @param string $columns The column to count. Defaults to '*' for counting all rows.
     *
     * @return int The number of records found.
     */
    public function count(string $columns = '*'): int;

    /**
     * Retrieves the sum of a given column.
     *
     * @param string $column The column to sum.
     *
     * @return float The sum of values in the column.
     */
    public function sum(string $column): float;

    /**
     * Retrieves the maximum value of a given column.
     *
     * @param string $column The column to retrieve the maximum value from.
     *
     * @return mixed The maximum value of the column.
     */
    public function max(string $column): mixed;

    /**
     * Retrieves the minimum value of a given column.
     *
     * @param string $column The column to retrieve the minimum value from.
     *
     * @return mixed The minimum value of the column.
     */
    public function min(string $column): mixed;

    /**
     * Create a new entity instance and save it to the database.
     *
     * @param array $attributes Attributes for the new entity.
     * @param bool $syncRelations Whether to sync the relationships with the entity.
     *
     * @return ModelInterface The created entity.
     */
    public function create(array $attributes = [], bool $syncRelations = false): ModelInterface;

    /**
     * Update an existing entity in the database.
     *
     * @param int|string $id The ID of the entity to update.
     * @param array $attributes Attributes to update the entity with.
     * @param bool $syncRelations Whether to sync the relationships with the entity.
     *
     * @return ModelInterface The updated entity.
     */
    public function update(int|string $id, array $attributes = [], bool $syncRelations = false): ModelInterface;

    /**
     * Deletes the entity by its ID.
     *
     * This method will attempt to find the entity either by its ID or if the provided argument is already an instance of a model.
     * Once the entity is found, the `deleting` event is fired, and the entity is deleted from the database.
     * After deletion, the `deleted` event is triggered. The method returns the deleted entity or false if the deletion failed.
     *
     * @param int|string|ModelInterface $id The ID (int|string) or instance of the entity to be deleted.
     *
     * @return ModelInterface The deleted entity or false if the deletion failed.
     */
    public function delete(int|string|ModelInterface $id): ModelInterface;

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
    public function createModel(): ModelInterface;

    /**
     * Find all entities, retrieving all records from the database.
     * This method returns all matching entities, typically used when no filters are applied.
     *
     * @param array<string> $attributes The columns to select (default: all columns).
     *
     * @return Collection The collection of found entities.
     */
    public function findAll(array $attributes = ['*']): Collection;

    /**
     * Simplified pagination with fewer features, ideal for smaller datasets.
     * This method provides a simpler paginated result, suitable for when you don't need full pagination features.
     *
     * @param int|null $perPage The number of items to display per page (default: null).
     * @param array<string> $attributes The columns to select (default: all columns).
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
    ): PaginatorInterface;

    /**
     * Find entities that have a related model matching specific conditions.
     * This method allows you to find entities with a relation that matches the given condition.
     *
     * @param array<string, mixed> $where The condition to apply (e.g., ['relation', callback, operator, count]).
     * @param array<string> $attributes The columns to select (default: all columns).
     *
     * @return Collection The collection of matching entities.
     */
    public function findWhereHas(array $where, array $attributes = ['*']): Collection;

    /**
     * Restores the deleted entity by its ID.
     *
     * This method attempts to restore a previously soft-deleted entity. If the entity is found, the `restoring` event is fired,
     * followed by the restoration of the entity. After restoring, the `restored` event is triggered.
     * The method returns the restored entity or false if the restoration failed.
     *
     * @param int|string|ModelInterface $id The ID (int|string) or instance of the entity to be restored.
     *
     * @return ModelInterface The restored entity or false if the restoration failed.
     */
    public function restore(int|string|ModelInterface $id): ModelInterface;

    /**
     * Retrieves the average value of a given column.
     *
     * This method performs a query to get the average value of a specific column.
     *
     * @param string $column The column to calculate the average value from.
     *
     * @return float The average value of the column.
     */
    public function avg(string $column): float;

    /**
     * Get the model name from a given class, lowercased.
     *
     * @return string The lowercased base class name.
     */
    public function getEntityName(): string;

    /**
     * Retrieve the repository associated with the current model.
     *
     * @return RepositoryInterface The repository instance associated with the model.
     */
    public function getRepository(): RepositoryInterface;
}
