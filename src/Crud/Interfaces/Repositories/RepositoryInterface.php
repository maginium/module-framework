<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces\Repositories;

use Closure;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Database\Eloquent\Collection;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface;
use Maginium\Framework\Pagination\Interfaces\PaginatorInterface;

/**
 * Interface RepositoryInterface
 * Defines a contract for CRUD operations and repository configurations.
 *
 * @method LengthAwarePaginatorInterface paginate(?int $perPage = null, array $attributes = ['*'], string $pageName = 'page', ?int $page = null) Paginate the results based on the specified number of items per page.
 * @method ModelInterface find(int|string|array $id, array $attributes = ['*']) Find an entity by its ID.
 * @method ModelInterface|array findOrFail(int|string|array $id, array $attributes = ['*']) Find an entity by its ID or fail.
 * @method ModelInterface findOrNew(int|string $id, array $attributes = ['*']) Find an entity by its ID or create a new instance if not found.
 * @method ?ModelInterface findFirst(array $attributes = ['*']) Find the first entity based on the provided attributes.
 * @method Collection findWhere(array $where, array $attributes = ['*']) Find entities that match a specific condition.
 * @method Collection findWhereIn(array $where, array $attributes = ['*']) Find entities where a specific attribute is within a given list of values.
 * @method Collection findWhereNotIn(array $where, array $attributes = ['*']) Find entities where a specific attribute is not in a given list of values.
 * @method ?ModelInterface findBy(string $attribute, string|int|float|bool|null $value, array $attributes = ['*']) Find an entity by a specific attribute and value.
 * @method int count(string $columns = '*') Count the number of records in the database.
 * @method float sum(string $column) Retrieve the sum of a given column.
 * @method mixed max(string $column) Retrieve the maximum value of a given column.
 * @method mixed min(string $column) Retrieve the minimum value of a given column.
 * @method ModelInterface create(array $attributes = [], bool $syncRelations = false) Create a new entity instance and save it to the database.
 * @method ModelInterface update(int|string $id, array $attributes = [], bool $syncRelations = false) Update an existing entity in the database.
 * @method ModelInterface delete(int|string|ModelInterface $id) Delete the entity by its ID.
 * @method ModelInterface createModel() Create a new model instance.
 * @method Collection findAll(array $attributes = ['*']) Find all entities, retrieving all records from the database.
 * @method PaginatorInterface simplePaginate(?int $perPage = null, array $attributes = ['*'], string $pageName = 'page', ?int $page = null) Simplified pagination with fewer features.
 * @method Collection findWhereHas(array $where, array $attributes = ['*']) Find entities that have a related model matching specific conditions.
 * @method ModelInterface restore(int|string|ModelInterface $id) Restore the deleted entity by its ID.
 * @method void beginTransaction() Begin a database transaction.
 * @method void commit() Commit the current database transaction.
 * @method void rollBack() Roll back the current database transaction.
 * @method float avg(string $column) Retrieve the average value of a given column.
 */
interface RepositoryInterface
{
    /**
     * Set the scope for the repository.
     *
     * A scope is a predefined set of conditions or filters that can be applied to queries.
     * This method allows you to set a custom scope for query manipulation.
     *
     * @param string $name The name of the scope.
     * @param array  $parameters Parameters to be passed to the scope.
     *
     * @return static The current instance of the repository to allow for method chaining.
     */
    public function scope(string $name, array $parameters = []): static;

    /**
     * Store or update the repository data.
     *
     * This method handles both creating and updating records in the repository.
     * If an ID is provided, it updates the existing record; otherwise, it creates a new one.
     *
     * @param mixed  $id               The identifier of the record.
     * @param array  $attributes       The attributes to store or update.
     * @param bool   $syncRelations    Whether to sync related models (default: false).
     *
     * @return mixed The created or updated model.
     */
    public function store(mixed $id, array $attributes = [], bool $syncRelations = false): mixed;

    /**
     * Add a "where" condition to the query.
     *
     * This method is used to add "where" clauses for filtering the query results.
     *
     * @param string $attribute The attribute to filter by.
     * @param string|null $operator The operator for comparison (e.g., '=', '>', etc.).
     * @param mixed $value The value to compare the attribute against.
     * @param string $boolean The logical operator to combine with other conditions (default: 'and').
     *
     * @return static The current instance of the repository for method chaining.
     */
    public function where(string $attribute, ?string $operator = null, mixed $value = null, string $boolean = 'and'): static;

    /**
     * Add a "where in" condition to the query.
     *
     * This method adds a condition that filters the results based on whether a specified
     * attribute's value is in a list of values.
     *
     * @param string $attribute The attribute to filter by.
     * @param array $values The list of values to compare the attribute against.
     * @param string $boolean The logical operator to combine with other conditions (default: 'and').
     * @param bool $not Whether to negate the condition (default: false).
     *
     * @return static The current instance of the repository for method chaining.
     */
    public function whereIn(string $attribute, array $values, string $boolean = 'and', bool $not = false): static;

    /**
     * Add a "where not in" condition to the query.
     *
     * This method adds a condition that filters the results based on whether a specified
     * attribute's value is not in a list of values.
     *
     * @param string $attribute The attribute to filter by.
     * @param array $values The list of values to compare the attribute against.
     * @param string $boolean The logical operator to combine with other conditions (default: 'and').
     *
     * @return static The current instance of the repository for method chaining.
     */
    public function whereNotIn(string $attribute, array $values, string $boolean = 'and'): static;

    /**
     * Get the repository model.
     *
     * This method retrieves the model class associated with the repository.
     *
     * @return ModelInterface The model class.
     */
    public function getModel(): ModelInterface;

    /**
     * Set the connection name for the repository.
     *
     * This method sets which database connection to use for the repository operations.
     *
     * @param string $name The name of the connection to set.
     *
     * @return static The current instance for method chaining.
     */
    public function setConnection(string $name): static;

    /**
     * Get the connection name for the repository.
     *
     * This method retrieves the current connection name used for repository operations.
     *
     * @return string The connection name.
     */
    public function getConnection(): string;

    /**
     * Set the repository identifier.
     *
     * This method assigns a unique identifier to the repository. The identifier can be used
     * to track or manage the repository.
     *
     * @param string $repositoryId The identifier of the repository.
     *
     * @return static The current instance for method chaining.
     */
    public function setRepositoryId(string $repositoryId): static;

    /**
     * Get the repository identifier.
     *
     * This method retrieves the current repository identifier.
     *
     * @return string The repository identifier.
     */
    public function getRepositoryId(): string;

    /**
     * Set relationships to be eager-loaded.
     *
     * This method allows you to specify which relationships should be eager-loaded when querying the repository.
     *
     * @param mixed $relations A list of relations to eager-load. Can be a string or an array.
     *
     * @return static The current instance for method chaining.
     */
    public function with(string|array $relations): static;

    /**
     * Add a "where has" condition to the query.
     *
     * This method allows you to filter results based on the presence of a related model
     * satisfying certain conditions. You can pass a closure to define specific constraints
     * on the related model query.
     *
     * @param string  $relation   The name of the relation to check.
     * @param Closure|null $callback  An optional closure to apply additional constraints to the relation query.
     * @param string  $operator   The operator to use for the condition (default is '>=').
     * @param int     $count      The minimum number of related records required (default is 1).
     *
     * @return static The current instance for method chaining.
     */
    public function whereHas(string $relation, ?Closure $callback = null, string $operator = '>=', int $count = 1): static;

    /**
     * Set the offset for the query result.
     *
     * The offset defines where to start returning results, typically used for pagination.
     *
     * @param int $offset The number of records to skip.
     *
     * @return static The current instance for method chaining.
     */
    public function offset(int $offset): static;

    /**
     * Set the limit for the query result.
     *
     * The limit restricts the number of records returned by the query, typically used for pagination.
     *
     * @param int $limit The maximum number of records to return.
     *
     * @return static The current instance for method chaining.
     */
    public function limit(int $limit): static;

    /**
     * Add an "order by" condition to the query.
     *
     * The order by clause specifies the attribute by which to order the results and the direction (asc/desc).
     *
     * @param string $attribute The attribute to order by.
     * @param string $direction The direction of sorting (default is 'asc').
     *
     * @return static The current instance for method chaining.
     */
    public function orderBy(string $attribute, string $direction = 'asc'): static;

    /**
     * Add a "group by" condition to the query.
     *
     * The group by clause allows you to group results by one or more attributes.
     *
     * @param string|array $column The column(s) to group by. Can be a single column or an array of columns.
     *
     * @return static The current instance for method chaining.
     */
    public function groupBy(string|array $column): static;

    /**
     * Add a "having" condition to the query.
     *
     * The having clause is used to filter results after grouping, typically to filter aggregated values.
     *
     * @param string $column   The column to filter.
     * @param string|null $operator The operator to use (default is null).
     * @param mixed $value    The value to compare the column against.
     * @param string $boolean The logical operator to join the condition with (default is 'and').
     *
     * @return static The current instance for method chaining.
     */
    public function having(string $column, ?string $operator = null, mixed $value = null, string $boolean = 'and'): static;

    /**
     * Add an "or having" condition to the query.
     *
     * This method is similar to the "having" method, but it uses 'or' as the default boolean operator.
     *
     * @param string $column   The column to filter.
     * @param string|null $operator The operator to use (default is null).
     * @param mixed $value    The value to compare the column against.
     * @param string $boolean The logical operator to join the condition with (default is 'or').
     *
     * @return static The current instance for method chaining.
     */
    public function orHaving(string $column, ?string $operator = null, mixed $value = null, string $boolean = 'and'): static;

    /**
     * Get the model name from a given class, lowercased.
     *
     * @return string The lowercased base class name.
     */
    public function getEntityName(): string;

    /**
     * Check for SQL injection in a field.
     *
     * @param string $field The field value to check
     *
     * @throws Exception If SQL injection is detected
     *
     * @return string The sanitized field value
     */
    public function checkSqlInjection(string $field): string;
}
