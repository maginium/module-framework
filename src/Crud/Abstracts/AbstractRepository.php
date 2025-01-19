<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Abstracts;

use Closure;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Crud\Interfaces\Repositories\CacheableInterface;
use Maginium\Framework\Crud\Interfaces\Repositories\RepositoryInterface;
use Maginium\Framework\Crud\Traits\Cacheable;
use Maginium\Framework\Database\Eloquent\Model;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Elasticsearch\Eloquent\Model as ElasticModel;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Class AbstractRepository
 * Implements common functionality for repository classes.
 */
abstract class AbstractRepository implements CacheableInterface, RepositoryInterface
{
    use Cacheable;

    /**
     * The connection name for the repository.
     *
     * @var string
     */
    protected string $connection;

    /**
     * The repository identifier.
     *
     * @var string
     */
    protected string $repositoryId;

    /**
     * The repository model.
     *
     * @var ModelInterface
     */
    protected ModelInterface $model;

    /**
     * The relations to eager load on query execution.
     *
     * @var array<string>
     */
    protected array $relations = [];

    /**
     * The query where clauses.
     *
     * @var array<string, mixed>
     */
    protected array $where = [];

    /**
     * The query whereIn clauses.
     *
     * @var array<string, array<mixed>>
     */
    protected array $whereIn = [];

    /**
     * The query whereNotIn clauses.
     *
     * @var array<string, array<mixed>>
     */
    protected array $whereNotIn = [];

    /**
     * The query whereHas clauses.
     *
     * @var array<string, mixed>
     */
    protected array $whereHas = [];

    /**
     * The query scopes.
     *
     * @var array<string, mixed>
     */
    protected array $scopes = [];

    /**
     * The "offset" value of the query.
     *
     * @var int|null
     */
    protected ?int $offset = 0;

    /**
     * The "limit" value of the query.
     *
     * @var int|null
     */
    protected ?int $limit = 15;

    /**
     * The column to order results by.
     *
     * @var array<string, string>
     */
    protected array $orderBy = [];

    /**
     * The column to group results by.
     *
     * @var array<string>
     */
    protected array $groupBy = [];

    /**
     * The query having clauses.
     *
     * @var array<string, mixed>
     */
    protected array $having = [];

    /**
     * Cache Skipping URI.
     *
     * @var string
     */
    protected string $skipUri = 'skipCache';

    /**
     * Regex patterns to detect SQL injection.
     */
    protected $sqlInjectionRegEx = [
        '/(%27)|(\')|(--)|(%23)|(#)/',
        '/((%3D)|(=))[^\n]*((%27)|(\')|(--)|(%3B)|(;))/',
        '/w*((%27)|(\'))((%6F)|o|(%4F))((%72)|r|(%52))/',
        '/((%27)|(\'))union/',
    ];

    /**
     * AbstractRepository constructor.
     *
     * @param ModelInterface $model The entity model interface.
     */
    public function __construct(ModelInterface $model)
    {
        $this->model = $model;

        // Set up the class name for logging purposes.
        Log::setClassName(static::class);
    }

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
    public function scope(string $name, array $parameters = []): static
    {
        // Store the scope in the $scopes array with its associated parameters.
        $this->scopes[$name] = $parameters;

        // Return the current instance to allow method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function store(mixed $id, array $attributes = [], bool $syncRelations = false): mixed
    {
        // If no ID is provided, create a new record; otherwise, update the existing one.
        return ! $id ? $this->create($attributes, $syncRelations) : $this->update($id, $attributes, $syncRelations);
    }

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
    public function where(string $attribute, ?string $operator = null, mixed $value = null, string $boolean = 'and'): static
    {
        // Add the where condition to the internal $where array.
        // The last `$boolean` expression ensures correct handling of logical operators.
        $this->where[] = [$attribute, $operator, $value, $boolean ?: 'and'];

        // Return the current instance to allow method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function whereIn(string $attribute, array $values, string $boolean = 'and', bool $not = false): static
    {
        // Add the whereIn condition to the internal $whereIn array.
        // The `$boolean` and `$not` expressions are added to ensure logical operators are handled correctly.
        $this->whereIn[] = [$attribute, $values, $boolean ?: 'and', (bool)$not];

        // Return the current instance to allow method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function whereNotIn(string $attribute, array $values, string $boolean = 'and'): static
    {
        // Add the whereNotIn condition to the internal $whereNotIn array.
        // The `$boolean` expression is used to ensure correct handling of logical operators.
        $this->whereNotIn[] = [$attribute, $values, $boolean ?: 'and'];

        // Return the current instance to allow method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the repository model.
     *
     * This method retrieves the model class associated with the repository.
     *
     * @return ModelInterface The model class.
     */
    public function getModel(): ModelInterface
    {
        return $this->model;
    }

    /**
     * Set the connection name for the repository.
     *
     * This method sets which database connection to use for the repository operations.
     *
     * @param string $name The name of the connection to set.
     *
     * @return static The current instance for method chaining.
     */
    public function setConnection(string $name): static
    {
        // Store the connection name for later use in queries.
        $this->connection = $name;

        // Return the current instance to allow method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the connection name for the repository.
     *
     * This method retrieves the current connection name used for repository operations.
     *
     * @return string The connection name.
     */
    public function getConnection(): string
    {
        // Return the current connection name.
        return $this->connection;
    }

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
    public function setRepositoryId(string $repositoryId): static
    {
        // Store the repository ID for later use.
        $this->repositoryId = $repositoryId;

        // Return the current instance to allow method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the repository identifier.
     *
     * This method retrieves the current repository identifier.
     *
     * @return string The repository identifier.
     */
    public function getRepositoryId(): string
    {
        // Return the repository ID, or default to the class name if not set.
        return $this->repositoryId ?: static::class;
    }

    /**
     * Set relationships to be eager-loaded.
     *
     * This method allows you to specify which relationships should be eager-loaded when querying the repository.
     *
     * @param mixed $relations A list of relations to eager-load. Can be a string or an array.
     *
     * @return static The current instance for method chaining.
     */
    public function with(string|array $relations): static
    {
        // If the relations are provided as a string, convert them to an array using func_get_args().
        if (Validator::isString($relations)) {
            $relations = func_get_args();
        }

        // Store the relations to be eager-loaded.
        $this->relations = $relations;

        // Return the current instance to allow method chaining.

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function whereHas(string $relation, ?Closure $callback = null, string $operator = '>=', int $count = 1): static
    {
        // Store the relation along with its callback, operator, and count for future query building.
        // The last `$operator` & `$count` expressions are intentional to fix list() & Arr::pad() results
        $this->whereHas[] = [$relation, $callback, $operator ?: '>=', $count ?: 1];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the offset for the query result.
     *
     * The offset defines where to start returning results, typically used for pagination.
     *
     * @param int $offset The number of records to skip.
     *
     * @return static The current instance for method chaining.
     */
    public function offset(int $offset): static
    {
        // Store the offset value to be used later in the query building process.
        $this->offset = $offset;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the limit for the query result.
     *
     * The limit restricts the number of records returned by the query, typically used for pagination.
     *
     * @param int $limit The maximum number of records to return.
     *
     * @return static The current instance for method chaining.
     */
    public function limit(int $limit): static
    {
        // Store the limit value to be used later in the query building process.
        $this->limit = $limit;

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function orderBy(string $attribute, string $direction = 'asc'): static
    {
        // Store the attribute and direction for the order by condition.
        $this->orderBy[] = [$attribute, $direction ?: 'asc'];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Add a "group by" condition to the query.
     *
     * The group by clause allows you to group results by one or more attributes.
     *
     * @param string|array $column The column(s) to group by. Can be a single column or an array of columns.
     *
     * @return static The current instance for method chaining.
     */
    public function groupBy(string|array $column): static
    {
        // Merge the provided column(s) with the existing group by conditions.
        $this->groupBy = Arr::merge((array)$this->groupBy, Validator::isArray($column) ? $column : [$column]);

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function having(string $column, ?string $operator = null, mixed $value = null, string $boolean = 'and'): static
    {
        // Store the having condition along with the column, operator, value, and boolean logic.
        $this->having[] = [$column, $operator, $value, $boolean ?: 'and'];

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function orHaving(string $column, ?string $operator = null, mixed $value = null, string $boolean = 'and'): static
    {
        // Reuse the having method to add an "or" condition.
        return $this->having($column, $operator, $value, 'or');
    }

    /**
     * Get the model name from a given class, lowercased.
     *
     * @return string The lowercased base class name.
     */
    public function getEntityName(): string
    {
        // Use ReflectionClass to get the base class of the given class
        $baseClass = Reflection::getShortName($this->model);

        // Return the base class name in lowercase
        return Str::lower($baseClass);
    }

    /**
     * Check for SQL injection in a field.
     *
     * @param string $field The field value to check
     *
     * @throws Exception If SQL injection is detected
     *
     * @return string The sanitized field value
     */
    public function checkSqlInjection(string $field): string
    {
        foreach ($this->sqlInjectionRegEx as $regex) {
            if (Str::match($regex, $field)) {
                // Throw the exception
                // Throw the exception
                throw Exception::make('SQL injection detected: ' . $field);
            }
        }

        return $field;
    }

    /**
     * Execute the given callback and return the result, with cache handling.
     *
     * This method is responsible for executing the callback while respecting the caching logic.
     * It checks if caching is enabled and whether the current request should bypass the cache.
     *
     * @param string   $class   The class that contains the method being called.
     * @param string   $method  The method to be executed.
     * @param array    $args    The arguments to be passed to the method.
     * @param Closure $closure The closure representing the callback to be executed.
     *
     * @return mixed The result of executing the callback.
     */
    protected function executeCallback(string $class, string $method, array $args, Closure $closure): mixed
    {
        // Check if cache is enabled and if the current request should not skip the cache.
        if ($this->getCacheLifetime() && Request::query($this->skipUri, 'false') !== 'true') {
            // If cache is enabled, use the cacheCallback method to handle caching.
            return $this->cacheCallback($class, $method, $args, $closure);
        }

        // If cache is disabled, directly execute the query and return the result.
        $result = call_user_func($closure);

        // After execution, reset the repository to ensure no side effects for future queries.
        $this->resetRepository();

        return $result;
    }

    /**
     * Reset the repository to its default state.
     *
     * This method clears all stored query parameters and resets the repository to its initial state,
     * ensuring no leftover state persists between queries.
     *
     * @return static The current instance for method chaining.
     */
    protected function resetRepository(): static
    {
        // Reset all query-related properties to their default values.
        $this->where = [];
        $this->scopes = [];
        $this->having = [];
        $this->orderBy = [];
        $this->groupBy = [];
        $this->whereIn = [];
        $this->limit = null;
        $this->offset = null;
        $this->whereHas = [];
        $this->relations = [];
        $this->whereNotIn = [];

        // If the repository has a flushCriteria method, call it to clean up additional criteria.
        if (Reflection::methodExists($this, 'flushCriteria')) {
            $this->flushCriteria();
        }

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Prepare and build the query based on the applied conditions and configurations.
     * This method applies various clauses to the query model such as where, whereIn, whereNotIn,
     * whereHas, scopes, offset, limit, orderBy, groupBy, and having.
     *
     * @param ModelInterface $model The model to prepare the query for.
     *
     * @return mixed The modified model with the applied query conditions.
     */
    protected function prepareQuery(ModelInterface $model): mixed
    {
        // Check if there are any relationships to eager load
        // This ensures that related models are loaded in a single query
        /** @var Model|ElasticModel $model */
        if (! empty($this->relations)) {
            // Apply eager loading for relations
            $model = $model->with($this->relations);
        }

        // Apply basic where clauses to the query
        // Loops through all conditions stored in the $this->where array
        foreach ($this->where as $where) {
            // Pad the where array to ensure all elements are present (attribute, operator, value, boolean)
            [$attribute, $operator, $value, $boolean] = Arr::pad($where, 4, null);

            // Add the where condition to the model
            $model = $model->where($attribute, $operator, $value, $boolean);
        }

        // Apply "where in" clauses to the query
        // Loops through the $this->whereIn array which holds conditions for "where in" clauses
        foreach ($this->whereIn as $whereIn) {
            // Pad the whereIn array to ensure all elements are present (attribute, values, boolean, not)
            [$attribute, $values, $boolean, $not] = Arr::pad($whereIn, 4, null);

            // Add the whereIn condition to the model
            if ($model instanceof ElasticModel) {
                $model = $model->whereIn($attribute, $values);
            } else {
                $model = $model->whereIn($attribute, $values, $boolean, $not);
            }
        }

        // Apply "where not in" clauses to the query
        // Similar to whereIn, but ensures the condition is "not in"
        foreach ($this->whereNotIn as $whereNotIn) {
            // Pad the whereNotIn array to ensure all elements are present (attribute, values, boolean)
            [$attribute, $values, $boolean] = Arr::pad($whereNotIn, 3, null);

            // Add the whereNotIn condition to the model
            $model = $model->whereNotIn($attribute, $values, $boolean);
        }

        // Apply "where has" clauses to the query
        // Loops through the $this->whereHas array which holds conditions for "whereHas" clauses
        foreach ($this->whereHas as $whereHas) {
            // Pad the whereHas array to ensure all elements are present (relation, callback, operator, count)
            [$relation, $callback, $operator, $count] = Arr::pad($whereHas, 4, null);

            // Add the whereHas condition to the model
            $model = $model->whereHas($relation, $callback, $operator, $count);
        }

        // Apply any scopes that have been defined
        // Loops through the $this->scopes array and applies each scope to the model
        foreach ($this->scopes as $scope => $parameters) {
            // Dynamically call the scope method
            $model = $model->{$scope}(...$parameters);
        }

        // Set the "offset" value for the query, which determines the number of records to skip
        if ($this->offset > 0) {
            // Apply the offset to the model
            $model = $model->offset($this->offset);
        }

        // Set the "limit" value for the query, which limits the number of records to retrieve
        if ($this->limit > 0) {
            // Apply the limit to the model
            $model = $model->limit($this->limit);
        }

        // Apply ordering to the query by the specified attributes and direction (ascending or descending)
        foreach ($this->orderBy as $orderBy) {
            // Destructure the orderBy array into attribute and direction
            [$attribute, $direction] = $orderBy;

            // Add the orderBy condition to the model
            $model = $model->orderBy($attribute, $direction);
        }

        // Apply grouping to the query by the specified columns
        if (! empty($this->groupBy)) {
            foreach ($this->groupBy as $group) {
                // Apply group by condition
                $model = $model->groupBy($group);
            }
        }

        // Apply having clauses to filter the results after grouping
        foreach ($this->having as $having) {
            // Pad the having array to ensure all elements are present (column, operator, value, boolean)
            [$column, $operator, $value, $boolean] = Arr::pad($having, 4, null);

            // Add the having condition to the model
            $model = $model->having($column, $operator, $value, $boolean);
        }

        // Apply any custom criteria if the 'applyCriteria' method exists
        if (Reflection::methodExists($this, 'applyCriteria')) {
            // Apply custom criteria to the model
            $model = $this->applyCriteria($model, $this);
        }

        // Return the fully prepared model with all the query conditions applied
        return $model;
    }

    /**
     * Handle dynamic static method calls.
     * This method allows for calling methods on this class statically, and it passes parameters to the method
     * being called dynamically. It instantiates the class and then calls the specified method with the given parameters.
     *
     * @param string $method The method name being called.
     * @param array  $parameters The parameters to pass to the method.
     *
     * @return mixed The result of the dynamic method call.
     */
    public static function __callStatic($method, $parameters): mixed
    {
        // Create new instance of the class
        $instance = Container::make(static::class);

        // Instantiate the class and call the method with the given parameters
        return call_user_func_array([$instance, $method], $parameters);
    }

    /**
     * Handle dynamic method calls on an instance of the class.
     * This method allows calling dynamic methods on an instance, including scope methods
     * (e.g., scopeActive) and standard methods. It checks if a scope method exists and, if so,
     * calls the scope method. Otherwise, it delegates to the model's method.
     *
     * @param string $method The method name being called.
     * @param array  $parameters The parameters to pass to the method.
     *
     * @return mixed The result of the dynamic method call.
     */
    public function __call($method, $parameters): mixed
    {
        // Build the scope method
        $scopeMethod = 'scope' . Str::capital($method);

        // Check if a scope method exists for the given method name
        if (Reflection::methodExists($this->createModel(),  $scopeMethod)) {
            // If scope exists, apply it with the provided parameters
            $this->scope($method, $parameters);

            // Return the current instance to allow method chaining
            return $this;
        }

        // If no scope method exists, call the method on the model
        return call_user_func_array([$this->createModel(), $method], $parameters);
    }
}
