<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Query;

use AllowDynamicProperties;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Query\Builder as BaseBuilder;
use LogicException;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Elasticsearch\Collection\ElasticCollection;
use Maginium\Framework\Elasticsearch\Collection\ElasticCollectionFactory;
use Maginium\Framework\Elasticsearch\Collection\ElasticResult;
use Maginium\Framework\Elasticsearch\Collection\ElasticResultFactory;
use Maginium\Framework\Elasticsearch\Collection\LazyElasticCollection;
use Maginium\Framework\Elasticsearch\Concerns\BuildsQueries;
use Maginium\Framework\Elasticsearch\Connection;
use Maginium\Framework\Elasticsearch\DSL\Results;
use Maginium\Framework\Elasticsearch\Helpers\Utilities;
use Maginium\Framework\Elasticsearch\Meta\QueryMetaDataFactory;
use Maginium\Framework\Elasticsearch\Schema\Schema;
use Maginium\Framework\Pagination\Facades\Paginator;
use Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface;
use Maginium\Framework\Pagination\Interfaces\PaginatorInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\LazyCollection;
use Maginium\Framework\Support\Validator;
use RuntimeException;

/**
 * Class Builder.
 *
 * The `Builder` class is responsible for constructing and executing Elasticsearch queries.
 * It extends Laravel's `BaseBuilder` and integrates Elasticsearch-specific functionality
 * such as managing search options, paginating results, and building complex query clauses.
 * This class allows for building, modifying, and executing queries with Elasticsearch,
 * including handling pagination, filtering, sorting, and custom query operations.
 *
 * @property Connection $connection
 * @property Processor $processor
 * @property Grammar $grammar
 */
#[AllowDynamicProperties]
class Builder extends BaseBuilder
{
    // Include Paginator functionality.
    use BuildsQueries;
    // Include Utilities functionality.
    use Utilities;

    /**
     * Options for configuring the query execution.
     *
     * @var array
     */
    public array $options = [];

    /**
     * Flag indicating if the query is currently paginating.
     *
     * @var bool
     */
    public bool $paginating = false;

    /**
     * The `search_after` value used for pagination in Elasticsearch queries.
     *
     * @var mixed
     */
    public mixed $searchAfter = null;

    /**
     * The cursor for pagination, storing the state of the query.
     *
     * @var array
     */
    public array $cursor = [];

    /**
     * Random score parameters for query result sorting.
     *
     * @var array
     */
    public array $randomScore = [];

    /**
     * The previous `search_after` value for pagination.
     *
     * @var mixed
     */
    public mixed $previousSearchAfter = null;

    /**
     * The search query string.
     *
     * @var string
     */
    public string $searchQuery = '';

    /**
     * The distinct type for the query.
     *
     * @var int
     */
    public int $distinctType = 0;

    /**
     * Additional search options such as filters, aggregations, etc.
     *
     * @var array
     */
    public array $searchOptions = [];

    /**
     * The minimum score for the query results.
     *
     * @var mixed
     */
    public mixed $minScore = null;

    /**
     * Fields to be included in the search results.
     *
     * @var array
     */
    public array $fields = [];

    /**
     * Filters to be applied to the search query.
     *
     * @var array
     */
    public array $filters = [];

    /**
     * Highlighting options for the query results.
     *
     * @var array
     */
    public array $highlights = [];

    /**
     * Clause operators for comparison operations in the query.
     *
     * @var string[]
     */
    public $operators = [
        // @inherited
        '=',
        '<',
        '>',
        '<=',
        '>=',
        '<>',
        '!=',
        '<=>',
        'like',
        'like binary',
        'not like',
        'ilike',
        '&',
        '|',
        '^',
        '<<',
        '>>',
        '&~',
        'rlike',
        'not rlike',
        'regexp',
        'not regexp',
        '~',
        '~*',
        '!~',
        '!~*',
        'similar to',
        'not similar to',
        'not ilike',
        '~~*',
        '!~~*',
        // @Elastic Search
        'exist',
        'regex',
    ];

    /**
     * The index to be queried in Elasticsearch.
     *
     * @var string
     */
    protected string $index = '';

    /**
     * The refresh option for Elasticsearch queries (default is 'wait_for').
     *
     * @var string|bool
     */
    protected string|bool $refresh = 'wait_for';

    /**
     * Factory for creating Elastic result instances.
     *
     * @var ElasticResultFactory
     */
    protected ElasticResultFactory $elasticResultFactory;

    /**
     * Factory for creating Elastic collection instances.
     *
     * @var ElasticCollectionFactory
     */
    protected ElasticCollectionFactory $elasticCollectionFactory;

    /**
     * Factory for creating query metadata instances.
     *
     * @var QueryMetaDataFactory
     */
    protected QueryMetaDataFactory $queryMetaDataFactory;

    /**
     * Mapping of operators to their Elasticsearch equivalent operators.
     *
     * @var array
     */
    protected array $conversion = [
        '=' => '=',
        '!=' => 'ne',
        '<>' => 'ne',
        '<' => 'lt',
        '<=' => 'lte',
        '>' => 'gt',
        '>=' => 'gte',
    ];

    /**
     * Constructor to initialize the builder with required dependencies.
     *
     * @param Processor $processor Handles query result processing.
     * @param Connection $connection The connection instance for Elasticsearch.
     * @param GrammarFactory $grammarFactory Factory for generating query grammar instances.
     * @param ElasticResultFactory $elasticResultFactory Factory for creating Elastic result instances.
     * @param QueryMetaDataFactory $queryMetaDataFactory Factory for creating query metadata instances.
     * @param ElasticCollectionFactory $elasticCollectionFactory Factory for creating Elastic collection instances.
     */
    public function __construct(
        Processor $processor,
        Connection $connection,
        GrammarFactory $grammarFactory,
        ElasticResultFactory $elasticResultFactory,
        QueryMetaDataFactory $queryMetaDataFactory,
        ElasticCollectionFactory $elasticCollectionFactory,
    ) {
        $this->processor = $processor;
        $this->connection = $connection;
        $this->queryMetaDataFactory = $queryMetaDataFactory;
        $this->elasticResultFactory = $elasticResultFactory;
        $this->elasticCollectionFactory = $elasticCollectionFactory;

        $this->grammar = $grammarFactory->create();
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param  int|Closure  $perPage
     * @param  array|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @param  Closure|int|null  $total
     *
     * @return LengthAwarePaginatorInterface
     */
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null): LengthAwarePaginatorInterface
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $total = func_num_args() === 5 ? value(func_get_arg(4)) : $this->getCountForPagination();

        $perPage = $perPage instanceof Closure ? $perPage($total) : $perPage;

        $results = $total ? $this->forPage($page, $perPage)->get($columns) : collect();

        return $this->paginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Retrieves data based on specified columns.
     *
     * @param array $columns The columns to retrieve (default is empty array for all columns).
     *
     * @return ElasticCollection|LazyCollection The collection of results.
     */
    public function get($columns = []): ElasticCollection|LazyCollection
    {
        // Call the private method to process and retrieve the data based on columns.
        return $this->_processGet($columns);
    }

    /**
     * Performs a search query using the defined search parameters.
     *
     * @param string|array $columns The columns to retrieve in the search results (default is '*').
     *
     * @throws RuntimeException If no search parameters are defined or if the search fails.
     *
     * @return ElasticCollection The collection of search results.
     */
    public function search($columns = '*'): ElasticCollection
    {
        // Retrieve search parameters, options, filters, and fields
        $searchParams = $this->searchQuery;

        // Throw an error if no search parameters are defined
        if (! $searchParams) {
            throw new RuntimeException('No search parameters. Add terms to search for.');
        }

        $searchOptions = $this->searchOptions;
        $wheres = $this->compileWheres();
        $options = $this->compileOptions();
        $fields = $this->fields;

        // Execute the search query using the connection
        $search = $this->connection->search($searchParams, $searchOptions, $wheres, $options, $fields, $columns);

        // If the search was successful, create and return the collection of results
        if ($search->isSuccessful()) {
            $data = $search->data;
            $collection = $this->elasticCollectionFactory->create(['items' => $data]);
            $collection->setQueryMeta($search->getMetaData());

            return $collection;
        }

        // If search fails, throw an error with the message
        throw new RuntimeException('Error: ' . $search->errorMessage);
    }

    /**
     * Finds a specific record by its ID.
     *
     * @param mixed $id The ID of the record to find.
     * @param array $columns The columns to retrieve for the record (optional).
     * @param string|null $softDeleteColumn The column used for soft delete handling (optional).
     *
     * @return Results The result of the find operation.
     */
    public function find($id, $columns = [], $softDeleteColumn = null): Results
    {
        // Call the connection's getId method to retrieve the record by its ID.
        return $this->connection->getId($id, $columns, $softDeleteColumn);
    }

    /**
     * Updates records with the given values and options.
     *
     * @param array $values The values to update the records with.
     * @param array $options Additional options for the update (optional).
     *
     * @return mixed The result of the update operation.
     */
    public function update(array $values, array $options = [])
    {
        // Ensure the values are valid before proceeding
        $this->_checkValues($values);

        // Call the private method to process the update operation
        return $this->_processUpdate($values, $options);
    }

    /**
     * Upserts a record by updating or inserting based on the given values and criteria.
     *
     * @param array $values The values to update or insert.
     * @param mixed $uniqueBy The unique identifier to check for an existing record.
     * @param mixed $update Optional update values if a record already exists.
     *
     * @throws LogicException If the upsert feature is not supported.
     */
    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        // Throw an error since the upsert feature is not supported for Elasticsearch
        throw new LogicException('The upsert feature for Elasticsearch is currently not supported. Please use updateAll()');
    }

    /**
     * Deletes a record or records.
     *
     * @param mixed $id The ID of the record to delete (optional).
     *
     * @return int The number of records deleted.
     */
    public function delete($id = null): int
    {
        // If an ID is provided, filter the records to delete by the ID
        if ($id !== null) {
            $this->where('_id', '=', $id);
        }

        // Call the private method to process the delete operation
        return $this->_processDelete();
    }

    /**
     * Increments a column by a specified amount.
     *
     * @param string $column The column to increment.
     * @param int $amount The amount to increment (default is 1).
     * @param array $extra Additional values to set (optional).
     * @param array $options Additional options for the increment operation (optional).
     *
     * @return mixed The result of the increment operation.
     */
    public function increment($column, $amount = 1, $extra = [], $options = [])
    {
        // Define the increment values and add any additional values
        $values = ['inc' => [$column => $amount]];

        if (! empty($extra)) {
            $values['set'] = $extra;
        }

        // Apply conditions for the increment operation (checking if column exists or is not null)
        $this->where(function($query) use ($column) {
            $query->where($column, 'exists', false);
            $query->orWhereNotNull($column);
        });

        // Call the private method to process the update operation with 'incrementMany' action
        return $this->_processUpdate($values, $options, 'incrementMany');
    }

    /**
     * Decrements a column by a specified amount (calls the increment method with negative value).
     *
     * @param string $column The column to decrement.
     * @param int $amount The amount to decrement (default is 1).
     * @param array $extra Additional values to set (optional).
     * @param array $options Additional options for the decrement operation (optional).
     *
     * @return mixed The result of the decrement operation.
     */
    public function decrement($column, $amount = 1, $extra = [], $options = [])
    {
        // Call increment method with negative amount to decrement
        return $this->increment($column, -1 * $amount, $extra, $options);
    }

    /**
     * Get a paginator only supporting simple next and previous links.
     *
     * This is more efficient on larger data-sets, etc.
     *
     * @param  int  $perPage
     * @param  array|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     *
     * @return PaginatorInterface
     */
    public function simplePaginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null): PaginatorInterface
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $this->offset(($page - 1) * $perPage)->limit($perPage + 1);

        return $this->simplePaginator($this->get($columns), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Retrieves the processor object.
     *
     * @return Processor The processor object.
     */
    public function getProcessor(): Processor
    {
        return $this->processor;
    }

    /**
     * Retrieves the connection object.
     *
     * @return Connection The connection object.
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Sets the refresh value for Elasticsearch operations.
     *
     * @param mixed $value The refresh value to set.
     */
    public function setRefresh($value): void
    {
        $this->refresh = $value;
    }

    /**
     * Initializes the cursor with page-related metadata.
     *
     * @param mixed $cursor The cursor data to initialize.
     *
     * @return array The initialized cursor data.
     */
    public function initCursor($cursor): array
    {
        // Default cursor initialization
        $this->cursor = [
            'page' => 1,
            'pages' => 0,
            'records' => 0,
            'sort_history' => [],
            'next_sort' => null,
            'ts' => 0,
        ];

        // If a cursor is provided, update with its parameters
        if (! empty($cursor)) {
            $this->cursor = [
                'page' => $cursor->parameter('page'),
                'pages' => $cursor->parameter('pages'),
                'records' => $cursor->parameter('records'),
                'sort_history' => $cursor->parameter('sort_history'),
                'next_sort' => $cursor->parameter('next_sort'),
                'ts' => $cursor->parameter('ts'),
            ];
        }

        return $this->cursor;
    }

    /**
     * Retrieves all records based on specified columns.
     *
     * @param array $columns The columns to retrieve (default is empty array for all columns).
     *
     * @return ElasticCollection The collection of all records.
     */
    public function all($columns = []): ElasticCollection
    {
        // Call the private method to process and retrieve all records based on columns.
        return $this->_processGet($columns);
    }

    /**
     * Retrieves the value of a specific column from the first record in the collection.
     *
     * @param string $column The name of the column to retrieve.
     *
     * @return mixed The value of the column or null if not found.
     */
    public function value($column)
    {
        // Retrieve the first record for the specified column
        $result = (array)$this->first([$column]);

        // Return the value of the column or null if it doesn't exist
        return Arr::get($result, $column);
    }

    /**
     * Executes an aggregation function on the specified columns.
     *
     * @param string $function The aggregation function to apply (e.g., 'sum', 'avg').
     * @param array $columns The columns to aggregate.
     *
     * @return mixed The aggregated result or null if no results are found.
     */
    public function aggregate($function, $columns = []): mixed
    {
        // Store the aggregation parameters
        $this->aggregate = compact('function', 'columns');

        // Store the previous columns and bindings before aggregation
        $previousColumns = $this->columns;
        $previousSelectBindings = $this->bindings['select'];

        // Clear the select bindings for aggregation
        $this->bindings['select'] = [];
        $results = $this->get($columns);

        // Restore the previous columns and bindings
        $this->aggregate = [];
        $this->columns = $previousColumns;
        $this->bindings['select'] = $previousSelectBindings;

        // If results are found, return the aggregated value
        if (isset($results[0])) {
            $result = (array)$results[0];

            /** @var ElasticResult $esResult */
            $esResult = $this->elasticResultFactory->create();
            $esResult->setQueryMeta($results->getQueryMeta());
            $esResult->setValue($result['aggregate']);

            // For now, return the result as is; in the future, return ElasticResult for meta access
            return $esResult->getValue();
        }

        return null; // Return null if no results are found
    }

    /**
     * Retrieves distinct values from the specified columns.
     *
     * @param bool $includeCount Whether to include a count of the distinct values.
     *
     * @return static The current instance with distinct settings applied.
     */
    public function distinct($includeCount = false): static
    {
        // Set the distinct type based on the includeCount parameter
        $this->distinctType = 1;

        if ($includeCount) {
            $this->distinctType = 2;
        }

        // Return the current instance to allow method chaining
        return $this; // Return the current instance for method chaining
    }

    /**
     * Executes a cursor-based query and returns a LazyCollection.
     *
     * @param array $columns The columns to select in the query.
     *
     * @throws RuntimeException If the query is not compatible with a cursor.
     *
     * @return LazyCollection The result as a LazyCollection.
     */
    public function cursor($columns = []): LazyCollection
    {
        // Process the query and retrieve the result
        $result = $this->_processGet($columns, true);

        // If the result is a LazyCollection, return it
        if ($result instanceof LazyCollection) {
            return $result;
        }

        // Throw an exception if the query is not compatible with a cursor
        throw new RuntimeException('Query not compatible with cursor');
    }

    /**
     * Checks if there are any records in the collection.
     *
     * @return bool True if records exist, false otherwise.
     */
    public function exists(): bool
    {
        // Return true if the first record is not null, indicating the collection is not empty
        return $this->first() !== null;
    }

    /**
     * Inserts new values into the collection.
     *
     * @param array $values The values to insert.
     * @param bool $returnData Whether to return the inserted data.
     *
     * @return ElasticCollection The resulting ElasticCollection.
     */
    public function insert(array $values, $returnData = false): ElasticCollection
    {
        // Process the insert operation and return the result
        return $this->_processInsert($values, $returnData, false);
    }

    /**
     * Inserts new values without refreshing the index.
     *
     * @param array $values The values to insert.
     * @param bool $returnData Whether to return the inserted data.
     *
     * @return ElasticCollection The resulting ElasticCollection.
     */
    public function insertWithoutRefresh(array $values, $returnData = false): ElasticCollection
    {
        // Process the insert operation without refreshing and return the result
        return $this->_processInsert($values, $returnData, true);
    }

    /**
     * Inserts new values and returns the inserted ID.
     *
     * @param array $values The values to insert.
     * @param string|null $sequence The sequence name (optional).
     *
     * @return int|array|string|null The inserted ID or data, or null if the insert fails.
     */
    public function insertGetId(array $values, $sequence = null): int|array|string|null
    {
        // Perform the insert operation and check for success
        $result = $this->connection->save($values, $this->refresh);

        if ($result->isSuccessful()) {
            // Return the inserted ID, or data based on the sequence parameter
            return $sequence ? $result->getInsertedId() : $result->data;
        }

        return null; // Return null if the insert operation fails
    }

    /**
     * Retrieves a collection of values from a specified column, optionally keyed by another column.
     *
     * @param string $column The column to retrieve.
     * @param string|null $key The column to use as the key for the returned collection (optional).
     *
     * @return Collection The resulting collection of values.
     */
    public function pluck($column, $key = null): Collection
    {
        // Retrieve the results with the specified columns
        $results = $this->get($key === null ? [$column] : [$column, $key]);

        // Convert ObjectID's to strings if the key is '_id'
        if ($key === '_id') {
            $results = $results->map(function($item) {
                $item['_id'] = (string)$item['_id']; // Convert _id to string

                return $item;
            });
        }

        // Return the plucked values as a collection
        $p = Arr::pluck($results, $column, $key);

        return Collection::make($p); // Convert to a Collection and return
    }

    /**
     * Executes a raw DSL query and returns the result.
     *
     * @param array $bodyParams The body parameters for the query.
     *
     * @return mixed The result of the raw DSL query.
     */
    public function rawDsl(array $bodyParams): mixed
    {
        // Execute the raw DSL search query and return the data
        $find = $this->connection->searchRaw($bodyParams, true);

        return $find->data;
    }

    /**
     * Executes a raw search query and returns the results.
     *
     * @param array $bodyParams The body parameters for the query.
     *
     * @return Results The resulting search results.
     */
    public function rawSearch(array $bodyParams): Results
    {
        // Execute the raw search query and return the results
        return $this->connection->searchRaw($bodyParams, false);
    }

    /**
     * Executes a raw aggregation query and returns the results.
     *
     * @param array $bodyParams The body parameters for the aggregation query.
     *
     * @return Collection The resulting collection of aggregation results.
     */
    public function rawAggregation(array $bodyParams): Collection
    {
        // Execute the raw aggregation query and return the data as a collection
        $find = $this->connection->aggregationRaw($bodyParams);
        $data = $find->data;

        return Collection::make($data); // Return the results as a collection
    }

    /**
     * Executes a matrix aggregation on the specified column.
     *
     * @param string|array $column The column to aggregate.
     *
     * @return mixed The aggregated result or 0 if no result.
     */
    public function matrix($column)
    {
        // Ensure the column is an array for the aggregation
        if (! Validator::isArray($column)) {
            $column = [$column];
        }

        // Perform the aggregation and return the result or 0 if not found
        $result = $this->aggregate(__FUNCTION__, $column);

        return $result ?: 0; // Return the result or 0 if no result
    }

    /**
     * Executes multiple aggregation functions on the specified column.
     *
     * @param array $functions The aggregation functions to apply.
     * @param string $column The column to aggregate.
     *
     * @throws RuntimeException If an invalid aggregate function is provided.
     *
     * @return array The aggregation results.
     */
    public function agg(array $functions, $column)
    {
        // Ensure the column is a string for aggregation
        if (Validator::isArray($column)) {
            throw new RuntimeException('Column must be a string');
        }

        // Define valid aggregate functions
        $aggregateTypes = ['sum', 'avg', 'min', 'max', 'matrix', 'count'];

        // Validate the provided aggregation functions
        foreach ($functions as $function) {
            if (! in_array($function, $aggregateTypes)) {
                throw new RuntimeException('Invalid aggregate type: ' . $function);
            }
        }

        // Compile the where and options for the query
        $wheres = $this->compileWheres();
        $options = $this->compileOptions();

        // Execute the multiple aggregate query and return the results
        $results = $this->connection->multipleAggregate($functions, $wheres, $options, $column);

        return $results->data ?? []; // Return the results or an empty array
    }

    /**
     * Converts the query to SQL format.
     *
     * @return array The SQL query representation.
     */
    public function toSql(): array
    {
        // Convert the query to DSL (which is similar to SQL)
        return $this->toDsl();
    }

    /**
     * Converts the current query into a DSL (Domain Specific Language) representation.
     * This method generates a query structure that can be sent to the database connection.
     * It either uses the search query or default query structure based on whether a search query is set.
     *
     * @return array The generated DSL query array.
     */
    public function toDsl(): array
    {
        // Compile the 'where' conditions of the query
        $wheres = $this->compileWheres();

        // Compile any additional query options (e.g., limits, sorts)
        $options = $this->compileOptions();

        // Compile the columns to be selected (or use empty array by default)
        $columns = $this->compileColumns([]);

        // If a search query exists, prepare the search-related parameters
        if ($this->searchQuery) {
            $searchParams = $this->searchQuery;
            $searchOptions = $this->searchOptions;
            $fields = $this->fields;

            // Use the connection method to convert the query to DSL for search
            return $this->connection->toDslForSearch($searchParams, $searchOptions, $wheres, $options, $fields, $columns);
        }

        // If no search query exists, use the standard DSL method
        return $this->connection->toDsl($wheres, $options, $columns);
    }

    /**
     * Creates a new query instance for building further queries.
     * This method returns a fresh instance of the current query builder.
     *
     * @return self A new instance of the query builder.
     */
    public function newQuery(): self
    {
        return Container::make(static::class);
    }

    /**
     * Adds a "phrase" type condition to the query.
     * This is typically used for exact phrase matching in text fields.
     *
     * @param string $column The column to apply the condition on.
     * @param string $value The value to compare against.
     * @param string $boolean The logical operator to use ('and' or 'or').
     *
     * @return static The current query builder instance.
     */
    public function wherePhrase($column, $value, $boolean = 'and'): static
    {
        $this->wheres[] = [
            'column' => $column,
            'type' => 'Basic',
            'value' => $value,
            'operator' => 'phrase',
            'boolean' => $boolean,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds a "phrase_prefix" type condition to the query.
     * This is typically used for prefix matching in text fields.
     *
     * @param string $column The column to apply the condition on.
     * @param string $value The value to compare against.
     * @param string $boolean The logical operator to use ('and' or 'or').
     *
     * @return static The current query builder instance.
     */
    public function wherePhrasePrefix($column, $value, $boolean = 'and'): static
    {
        $this->wheres[] = [
            'column' => $column,
            'type' => 'Basic',
            'value' => $value,
            'operator' => 'phrase_prefix',
            'boolean' => $boolean,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds an "exact" type condition to the query.
     * This is typically used for exact matches in text fields.
     *
     * @param string $column The column to apply the condition on.
     * @param string $value The value to compare against.
     * @param string $boolean The logical operator to use ('and' or 'or').
     *
     * @return static The current query builder instance.
     */
    public function whereExact($column, $value, $boolean = 'and'): static
    {
        $this->wheres[] = [
            'column' => $column,
            'type' => 'Basic',
            'value' => $value,
            'operator' => 'exact',
            'boolean' => $boolean,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds a "timestamp" condition to the query.
     * This is used to filter based on timestamp values and their operators (e.g., `=`, `>`, `<`).
     *
     * @param string $column The column to apply the condition on.
     * @param string|null $operator The operator to use for comparison (defaults to '=').
     * @param mixed|null $value The value to compare against.
     * @param string $boolean The logical operator to use ('and' or 'or').
     *
     * @return static The current query builder instance.
     */
    public function whereTimestamp($column, $operator = null, $value = null, $boolean = 'and'): static
    {
        [$value, $operator] = $this->prepareValueAndOperator($value, $operator, func_num_args() === 2);

        // Validate the operator, default to '=' if invalid
        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$operator, '='];
        }

        $this->wheres[] = [
            'column' => $column,
            'type' => 'Timestamp',
            'value' => $value,
            'operator' => $operator,
            'boolean' => $boolean,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds a "regex" condition to the query.
     * This is typically used for pattern matching in text fields.
     *
     * @param string $column The column to apply the condition on.
     * @param string $expression The regular expression pattern to match.
     * @param string $boolean The logical operator to use ('and' or 'or').
     *
     * @return static The current query builder instance.
     */
    public function whereRegex($column, $expression, $boolean = 'and'): static
    {
        $type = 'regex';
        $this->wheres[] = compact('column', 'type', 'expression', 'boolean');

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds a "between" condition to the query.
     * This is used for filtering values that fall between two given values.
     *
     * @param string $column The column to apply the condition on.
     * @param iterable $values The two values to compare against.
     * @param string $boolean The logical operator to use ('and' or 'or').
     * @param bool $not Whether to negate the condition (i.e., `NOT BETWEEN`).
     *
     * @return static The current query builder instance.
     */
    public function whereBetween($column, iterable $values, $boolean = 'and', $not = false): static
    {
        $type = 'between';

        $this->wheres[] = compact('column', 'type', 'boolean', 'values', 'not');

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds a nested query condition to the current query.
     * This allows for including complex, nested conditions within the main query.
     *
     * @param string $column The column to apply the nested condition on.
     * @param callable $callBack The callback function to define the nested query.
     *
     * @return static The current query builder instance.
     */
    public function queryNested($column, $callBack): static
    {
        $boolean = 'and';
        $query = $this->newQuery();

        // Execute the callback to build the nested query
        $callBack($query);
        $wheres = $query->compileWheres();
        $options = $query->compileOptions();

        $this->wheres[] = [
            'column' => $column,
            'type' => 'QueryNested',
            'wheres' => $wheres,
            'options' => $options,
            'boolean' => $boolean,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds a nested object query condition to the current query.
     * This is used for querying fields within nested objects.
     *
     * @param string $column The column to apply the condition on.
     * @param callable $callBack The callback function to define the nested query.
     * @param string $scoreMode The scoring mode to apply (e.g., 'avg', 'sum').
     *
     * @return static The current query builder instance.
     */
    public function whereNestedObject($column, $callBack, $scoreMode = 'avg'): static
    {
        $boolean = 'and';
        $query = $this->newQuery();

        // Execute the callback to build the nested query
        $callBack($query);
        $wheres = $query->compileWheres();

        $this->wheres[] = [
            'column' => $column,
            'type' => 'NestedObject',
            'wheres' => $wheres,
            'score_mode' => $scoreMode,
            'boolean' => $boolean,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds a not-nested object query condition to the current query.
     * This allows for querying fields that are not nested within objects.
     *
     * @param string $column The column to apply the condition on.
     * @param callable $callBack The callback function to define the query.
     * @param string $scoreMode The scoring mode to apply (e.g., 'avg', 'sum').
     *
     * @return static The current query builder instance.
     */
    public function whereNotNestedObject($column, $callBack, $scoreMode = 'avg'): static
    {
        $boolean = 'and';
        $query = $this->newQuery();

        // Execute the callback to build the nested query
        $callBack($query);
        $wheres = $query->compileWheres();

        $this->wheres[] = [
            'column' => $column,
            'type' => 'NotNestedObject',
            'wheres' => $wheres,
            'score_mode' => $scoreMode,
            'boolean' => $boolean,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds a 'phrase' condition with an 'OR' boolean operator.
     *
     * @param string $column The column to apply the condition to.
     * @param mixed  $value  The value to compare against.
     *
     * @return static
     */
    public function orWherePhrase($column, $value): static
    {
        return $this->wherePhrase($column, $value, 'or');
    }

    /**
     * Adds a 'phrase_prefix' condition with an 'OR' boolean operator.
     *
     * @param string $column The column to apply the condition to.
     * @param mixed  $value  The value to compare against.
     *
     * @return static
     */
    public function orWherePhrasePrefix($column, $value): static
    {
        return $this->wherePhrasePrefix($column, $value, 'or');
    }

    /**
     * Adds an 'exact' condition with an 'OR' boolean operator.
     *
     * @param string $column The column to apply the condition to.
     * @param mixed  $value  The value to compare against.
     *
     * @return static
     */
    public function orWhereExact($column, $value): static
    {
        return $this->whereExact($column, $value, 'or');
    }

    /**
     * Adds a 'timestamp' condition with an 'OR' boolean operator.
     *
     * @param string      $column   The column to apply the condition to.
     * @param string|null $operator The operator to use (e.g., '=', '<', '>').
     * @param mixed       $value    The value to compare against.
     *
     * @return static
     */
    public function orWhereTimestamp($column, $operator = null, $value = null): static
    {
        return $this->whereTimestamp($column, $operator, $value, 'or');
    }

    /**
     * Adds a 'regex' condition with an 'OR' boolean operator.
     *
     * @param string $column    The column to apply the condition to.
     * @param string $expression The regular expression to match.
     *
     * @return static
     */
    public function orWhereRegex($column, $expression): static
    {
        return $this->whereRegex($column, $expression, 'or');
    }

    /**
     * Searches for a value across multiple columns, determining whether it's a term or phrase search.
     *
     * @param string       $value    The search term or phrase.
     * @param array        $columns  The columns to search in.
     * @param array        $options  Additional search options.
     * @param string       $boolean  The boolean operator ('and' or 'or').
     *
     * @return static
     */
    public function searchFor($value, $columns = ['*'], $options = [], $boolean = 'and'): static
    {
        $values = explode(' ', $value);

        if (count($values) > 1) {
            return $this->searchPhrase($value, $columns, $options, $boolean);
        }

        return $this->searchTerm($value, $columns, $options, $boolean);
    }

    /**
     * Adds a 'term' search condition.
     *
     * @param string $term    The search term.
     * @param array  $fields  The fields to search in.
     * @param array  $options Additional search options.
     * @param string $boolean The boolean operator ('and' or 'or').
     *
     * @return static
     */
    public function searchTerm($term, $fields = ['*'], $options = [], $boolean = 'and'): static
    {
        $this->_ensureValueAsArray($fields);
        $this->wheres[] = [
            'column' => '*',
            'type' => 'Search',
            'value' => $term,
            'operator' => 'best_fields',
            'boolean' => $boolean,
            'fields' => $fields,
            'options' => $options,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds a 'term' search condition using the 'most_fields' operator.
     *
     * @param string $term    The search term.
     * @param array  $fields  The fields to search in.
     * @param array  $options Additional search options.
     * @param string $boolean The boolean operator ('and' or 'or').
     *
     * @return static
     */
    public function searchTermMost($term, $fields = ['*'], $options = [], $boolean = 'and'): static
    {
        $this->_ensureValueAsArray($fields);
        $this->wheres[] = [
            'column' => '*',
            'type' => 'Search',
            'value' => $term,
            'operator' => 'most_fields',
            'boolean' => $boolean,
            'fields' => $fields,
            'options' => $options,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds a 'term' search condition using the 'cross_fields' operator.
     *
     * @param string $term    The search term.
     * @param array  $fields  The fields to search in.
     * @param array  $options Additional search options.
     * @param string $boolean The boolean operator ('and' or 'or').
     *
     * @return static
     */
    public function searchTermCross($term, $fields = ['*'], $options = [], $boolean = 'and'): static
    {
        $this->_ensureValueAsArray($fields);
        $this->wheres[] = [
            'column' => '*',
            'type' => 'Search',
            'value' => $term,
            'operator' => 'cross_fields',
            'boolean' => $boolean,
            'fields' => $fields,
            'options' => $options,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds a 'phrase' search condition.
     *
     * @param string $phrase  The search phrase.
     * @param array  $fields  The fields to search in.
     * @param array  $options Additional search options.
     * @param string $boolean The boolean operator ('and' or 'or').
     *
     * @return static
     */
    public function searchPhrase($phrase, $fields = ['*'], $options = [], $boolean = 'and'): static
    {
        $this->_ensureValueAsArray($fields);
        $this->wheres[] = [
            'column' => '*',
            'type' => 'Search',
            'value' => $phrase,
            'operator' => 'phrase',
            'boolean' => $boolean,
            'fields' => $fields,
            'options' => $options,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds a 'phrase_prefix' search condition.
     *
     * @param string $phrase  The search phrase.
     * @param array  $fields  The fields to search in.
     * @param array  $options Additional search options.
     * @param string $boolean The boolean operator ('and' or 'or').
     *
     * @return static
     */
    public function searchPhrasePrefix($phrase, $fields = ['*'], $options = [], $boolean = 'and'): static
    {
        $this->_ensureValueAsArray($fields);
        $this->wheres[] = [
            'column' => '*',
            'type' => 'Search',
            'value' => $phrase,
            'operator' => 'phrase_prefix',
            'boolean' => $boolean,
            'fields' => $fields,
            'options' => $options,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Adds a 'bool_prefix' search condition.
     *
     * @param string $phrase  The search phrase.
     * @param array  $fields  The fields to search in.
     * @param array  $options Additional search options.
     * @param string $boolean The boolean operator ('and' or 'or').
     *
     * @return static
     */
    public function searchBoolPrefix($phrase, $fields = ['*'], $options = [], $boolean = 'and'): static
    {
        $this->_ensureValueAsArray($fields);
        $this->wheres[] = [
            'column' => '*',
            'type' => 'Search',
            'value' => $phrase,
            'operator' => 'bool_prefix',
            'boolean' => $boolean,
            'fields' => $fields,
            'options' => $options,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Perform an 'or' search operation based on the given value.
     *
     * This method is a wrapper for the `searchFor` method, passing 'or' as the boolean operator.
     *
     * @param string $value The search value.
     * @param array $columns The columns to search within. Defaults to ['*'] (all columns).
     * @param array $options Additional options for the search.
     *
     * @return static The current instance of the query builder, for method chaining.
     */
    public function orSearchFor($value, $columns = ['*'], $options = []): static
    {
        return $this->searchFor($value, $columns, $options, 'or');
    }

    /**
     * Perform an 'or' search for a term based on the given parameters.
     *
     * This method is a wrapper for the `searchTerm` method, passing 'or' as the boolean operator.
     *
     * @param string $term The search term.
     * @param array $fields The fields to search within. Defaults to ['*'] (all fields).
     * @param array $options Additional options for the search.
     *
     * @return static The current instance of the query builder, for method chaining.
     */
    public function orSearchTerm($term, $fields = ['*'], $options = []): static
    {
        return $this->searchTerm($term, $fields, $options, 'or');
    }

    /**
     * Perform an 'or' search for a term using the "most_fields" operator.
     *
     * This method is a wrapper for the `searchTermMost` method, passing 'or' as the boolean operator.
     *
     * @param string $term The search term.
     * @param array $fields The fields to search within. Defaults to ['*'] (all fields).
     * @param array $options Additional options for the search.
     *
     * @return static The current instance of the query builder, for method chaining.
     */
    public function orSearchTermMost($term, $fields = ['*'], $options = []): static
    {
        return $this->searchTermMost($term, $fields, $options, 'or');
    }

    /**
     * Perform an 'or' search for a term using the "cross_fields" operator.
     *
     * This method is a wrapper for the `searchTermCross` method, passing 'or' as the boolean operator.
     *
     * @param string $term The search term.
     * @param array $fields The fields to search within. Defaults to ['*'] (all fields).
     * @param array $options Additional options for the search.
     *
     * @return static The current instance of the query builder, for method chaining.
     */
    public function orSearchTermCross($term, $fields = ['*'], $options = []): static
    {
        return $this->searchTermCross($term, $fields, $options, 'or');
    }

    /**
     * Perform an 'or' search for a phrase based on the given parameters.
     *
     * This method is a wrapper for the `searchPhrase` method, passing 'or' as the boolean operator.
     *
     * @param string $term The search phrase.
     * @param array $fields The fields to search within. Defaults to ['*'] (all fields).
     * @param array $options Additional options for the search.
     *
     * @return static The current instance of the query builder, for method chaining.
     */
    public function orSearchPhrase($term, $fields = ['*'], $options = []): static
    {
        return $this->searchPhrase($term, $fields, $options, 'or');
    }

    /**
     * Perform an 'or' search for a phrase with a prefix based on the given parameters.
     *
     * This method is a wrapper for the `searchPhrasePrefix` method, passing 'or' as the boolean operator.
     *
     * @param string $term The search phrase.
     * @param array $fields The fields to search within. Defaults to ['*'] (all fields).
     * @param array $options Additional options for the search.
     *
     * @return static The current instance of the query builder, for method chaining.
     */
    public function orSearchPhrasePrefix($term, $fields = ['*'], $options = []): static
    {
        return $this->searchPhrasePrefix($term, $fields, $options, 'or');
    }

    /**
     * Perform an 'or' search with a boolean prefix operator based on the given parameters.
     *
     * This method is a wrapper for the `searchBoolPrefix` method, passing 'or' as the boolean operator.
     *
     * @param string $term The search phrase.
     * @param array $fields The fields to search within. Defaults to ['*'] (all fields).
     * @param array $options Additional options for the search.
     *
     * @return static The current instance of the query builder, for method chaining.
     */
    public function orSearchBoolPrefix($term, $fields = ['*'], $options = []): static
    {
        return $this->searchBoolPrefix($term, $fields, $options, 'or');
    }

    /**
     * Add highlighting to the search results.
     *
     * This method allows for specifying which fields to highlight and the HTML tags to use for highlighting.
     * The `preTag` and `postTag` parameters specify the tags to wrap the highlighted portions of the text.
     *
     * @param array $fields The fields to highlight.
     * @param string|array $preTag The tag(s) to wrap the highlighted portion of the text. Defaults to '<em>'.
     * @param string|array $postTag The tag(s) to close the highlighted portion. Defaults to '</em>'.
     * @param array $globalOptions Additional options to apply to the highlighting.
     *
     * @return static The current instance of the query builder, for method chaining.
     */
    public function withHighlights(array $fields = [], string|array $preTag = '<em>', string|array $postTag = '</em>', array $globalOptions = []): static
    {
        $highlightFields = [
            '*' => (object)[], // Default to highlighting all fields
        ];

        // If specific fields are provided, set them for highlighting
        if (! empty($fields)) {
            $highlightFields = [];

            foreach ($fields as $field => $payload) {
                if (Validator::isInt($field)) {
                    // If field is an integer (index), treat it as a field with empty options
                    $highlightFields[$payload] = (object)[];
                } else {
                    // Otherwise, use the specified payload for the field
                    $highlightFields[$field] = $payload;
                }
            }
        }

        // Ensure preTag and postTag are arrays
        if (! Validator::isArray($preTag)) {
            $preTag = [$preTag];
        }

        if (! Validator::isArray($postTag)) {
            $postTag = [$postTag];
        }

        // Prepare the highlight options
        $highlight = [];

        if ($globalOptions) {
            $highlight = $globalOptions; // Apply any global options first
        }
        $highlight['pre_tags'] = $preTag;
        $highlight['post_tags'] = $postTag;
        $highlight['fields'] = $highlightFields;

        // Assign the highlight options to the current query builder
        $this->highlights = $highlight;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Apply fuzzy matching to the current search query.
     *
     * This method allows for fuzziness to be applied to a search query, making it more lenient with spelling and typo errors.
     * The fuzziness level can be set, or it will default to 'auto'.
     *
     * @param int|null $depth The depth of fuzziness to apply (optional). If null, 'auto' will be used.
     *
     * @throws RuntimeException If no 'where' clause is found or if the query is not of type 'Search'.
     *
     * @return static The current instance of the query builder, for method chaining.
     */
    public function asFuzzy(?int $depth = null): static
    {
        if (! $depth) {
            $depth = 'auto'; // Default to 'auto' fuzziness if no depth is provided
        }

        $wheres = $this->wheres;

        if (! $wheres) {
            throw new RuntimeException('No where clause found'); // Ensure there is a where clause to apply the fuzziness to
        }

        $lastWhere = end($wheres);

        if ($lastWhere['type'] !== 'Search') {
            throw new RuntimeException('Fuzzy search can only be applied to Search type queries');
        }

        $this->_attachOption('fuzziness', $depth); // Attach the fuzziness option to the query

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the minimum number of terms that must match in a search query.
     *
     * This method allows for specifying a minimum number of terms that must match in the search result.
     *
     * @param int $value The minimum number of terms that must match.
     *
     * @throws RuntimeException If no 'where' clause is found or if the query is not of type 'Search'.
     *
     * @return static The current instance of the query builder, for method chaining.
     */
    public function setMinShouldMatch(int $value): static
    {
        $wheres = $this->wheres;

        if (! $wheres) {
            throw new RuntimeException('No where clause found'); // Ensure there is a where clause to apply the minimum match to
        }

        $lastWhere = end($wheres);

        if ($lastWhere['type'] !== 'Search') {
            throw new RuntimeException('Min Should Match can only be applied to Search type queries');
        }

        $this->_attachOption('minimum_should_match', $value); // Attach the minimum match value to the query

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the boost factor for the current query.
     * This will only apply to Search type queries and is used to adjust the relevance of the results.
     *
     * @param int $value The boost factor to apply.
     *
     * @throws RuntimeException If no "where" clause is found or the last "where" clause is not a Search type.
     *
     * @return $this The current instance for method chaining.
     */
    public function setBoost(int $value): static
    {
        $wheres = $this->wheres;

        if (! $wheres) {
            throw new RuntimeException('No where clause found');
        }
        $lastWhere = end($wheres);

        if ($lastWhere['type'] !== 'Search') {
            throw new RuntimeException('Boost can only be applied to Search type queries');
        }
        $this->_attachOption('boost', $value);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Order the results by a specified column in descending order.
     *
     * @param string $column The column name to order by.
     *
     * @return $this The current instance for method chaining.
     */
    public function orderByDesc($column): static
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * Order the results by a specified column in either ascending or descending order.
     *
     * @param string $column The column name to order by.
     * @param string $direction The direction to order by ('asc' or 'desc'). Default is 'asc'.
     *
     * @return $this The current instance for method chaining.
     */
    public function orderBy($column, $direction = 'asc'): static
    {
        if (Validator::isString($direction)) {
            $direction = (mb_strtolower($direction) === 'asc' ? 'asc' : 'desc');
        }

        // Add or update the order for the specified column
        $this->orders[$column] = [
            'order' => $direction,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Add custom sorting functionality for a specific column.
     * This allows you to define additional sort criteria beyond the default order.
     *
     * @param string $column The column name to sort by.
     * @param string $key The custom key for sorting.
     * @param mixed $value The value associated with the key.
     *
     * @return $this The current instance for method chaining.
     */
    public function withSort(string $column, string $key, mixed $value): static
    {
        $currentColOrder = $this->orders[$column] ?? [];
        $currentColOrder[$key] = $value;
        $this->orders[$column] = $currentColOrder;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Order the results based on geographical distance, in descending order.
     * This allows for sorting based on the proximity to a given geographical point.
     *
     * @param string $column The column to sort by.
     * @param array $pin The geographical coordinates (latitude and longitude) of the pin.
     * @param string $unit The unit for distance. Valid values are: 'km', 'mi', 'm', 'ft'. Default is 'km'.
     * @param string|null $mode The mode of aggregation for distances. Valid values: 'min', 'max', 'avg', 'sum'.
     * @param string|null $type The type of distance measurement. Valid values: 'arc', 'plane'.
     *
     * @return $this The current instance for method chaining.
     */
    public function orderByGeoDesc($column, $pin, $unit = 'km', $mode = null, $type = null): static
    {
        return $this->orderByGeo($column, $pin, 'desc', $unit, $mode, $type);
    }

    /**
     * Order the results based on geographical distance, in a specified direction (asc/desc).
     * This allows for sorting based on proximity to a given geographical point.
     *
     * @param string $column The column to sort by.
     * @param array $pin The geographical coordinates (latitude and longitude) of the pin.
     * @param string $direction The direction to sort in ('asc' or 'desc'). Default is 'asc'.
     * @param string $unit The unit for distance. Valid values are: 'km', 'mi', 'm', 'ft'. Default is 'km'.
     * @param string|null $mode The mode of aggregation for distances. Valid values: 'min', 'max', 'avg', 'sum'.
     * @param string|null $type The type of distance measurement. Valid values: 'arc', 'plane'.
     *
     * @return $this The current instance for method chaining.
     */
    public function orderByGeo($column, $pin, string $direction = 'asc', string $unit = 'km', ?string $mode = null, ?string $type = null): static
    {
        // Set the geo order parameters for the column
        $this->orders[$column] = [
            'is_geo' => true,
            'order' => $direction,
            'pin' => $pin,
            'unit' => $unit,
            'mode' => $mode,
            'type' => $type,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Order the results by a nested field in the specified direction.
     * Nested sorting allows sorting within nested objects or arrays.
     *
     * @param string $column The column to sort by.
     * @param string $direction The direction to sort in ('asc' or 'desc'). Default is 'asc'.
     * @param string|null $mode The mode of sorting for nested fields.
     *
     * @return $this The current instance for method chaining.
     */
    public function orderByNested($column, $direction = 'asc', $mode = null): static
    {
        $this->orders[$column] = [
            'is_nested' => true,
            'order' => $direction,
            'mode' => $mode,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Order the results randomly, with an optional seed for the randomization.
     * This allows for random sorting of results, which can be useful for use cases like random sampling.
     *
     * @param string $column The column to sort by.
     * @param int $seed The seed value for randomization. Default is 1.
     *
     * @return $this The current instance for method chaining.
     */
    public function orderByRandom($column, int $seed = 1): static
    {
        $this->randomScore = [
            'column' => $column,
            'seed' => $seed,
        ];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Group the results by specified columns.
     * This is typically used for aggregation purposes.
     *
     * @param string|array $groups The columns to group by.
     *
     * @return $this The current instance for method chaining.
     */
    public function groupBy(...$groups): self
    {
        if (Validator::isArray($groups[0])) {
            $groups = $groups[0];
        }

        // Add selected columns to the query
        $this->addSelect($groups);
        $this->distinctType = 1;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Add a column to the list of selected columns.
     * This allows additional columns to be included in the query results.
     *
     * @param string|array $column The column(s) to add to the select list.
     *
     * @return $this The current instance for method chaining.
     */
    public function addSelect($column): static
    {
        if (! Validator::isArray($column)) {
            $column = [$column];
        }

        $currentColumns = $this->columns;

        if ($currentColumns) {
            // Merge new columns with the existing ones
            return $this->select(Arr::merge($currentColumns, $column));
        }

        // Select the new columns
        return $this->select($column);
    }

    /**
     * Set the columns to select in the query.
     *
     * @param array|string $columns Columns to be selected, default is ['*'].
     *
     * @return $this
     */
    public function select($columns = ['*']): static
    {
        // If the $columns parameter is not an array, convert it into one
        $columns = Validator::isArray($columns) ? $columns : [$columns];

        // Store the selected columns
        $this->columns = $columns;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Apply a geo bounding box filter.
     *
     * @param string $field The field to apply the geo filter on.
     * @param array $topLeft Coordinates of the top-left corner of the box.
     * @param array $bottomRight Coordinates of the bottom-right corner of the box.
     *
     * @return void
     */
    public function filterGeoBox($field, $topLeft, $bottomRight): void
    {
        // Add a geo box filter to the filters array
        $this->filters['filterGeoBox'] = [
            'field' => $field,
            'topLeft' => $topLeft,
            'bottomRight' => $bottomRight,
        ];
    }

    /**
     * Apply a geo point filter.
     *
     * @param string $field The field to apply the geo filter on.
     * @param string $distance The distance to filter by.
     * @param array $geoPoint The point to filter by.
     *
     * @return void
     */
    public function filterGeoPoint($field, $distance, $geoPoint): void
    {
        // Add a geo point filter to the filters array
        $this->filters['filterGeoPoint'] = [
            'field' => $field,
            'distance' => $distance,
            'geoPoint' => $geoPoint,
        ];
    }

    /**
     * Set custom options for the query.
     *
     * @param array $options Array of custom options.
     *
     * @return $this
     */
    public function options(array $options): static
    {
        // Assign the custom options to the class property
        $this->options = $options;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Build the search query.
     *
     * @param string $term The search term to query for.
     * @param float|null $boostFactor Optional boost factor for the search term.
     * @param string|null $clause Optional clause for combining terms (AND/OR).
     * @param string $type Type of query ('term', 'fuzzy', 'regex', 'phrase').
     *
     * @return void
     */
    public function searchQuery($term, $boostFactor = null, $clause = null, $type = 'term'): void
    {
        // Validate the query sequencing based on the type of query
        if (! $clause && ! empty($this->searchQuery)) {
            // Throw an exception if the query sequence is incorrect for the type
            throw match ($type) {
                'fuzzy' => new RuntimeException('Incorrect query sequencing, fuzzyTerm() should only start the ORM chain'),
                'regex' => new RuntimeException('Incorrect query sequencing, regEx() should only start the ORM chain'),
                'phrase' => new RuntimeException('Incorrect query sequencing, phrase() should only start the ORM chain'),
                default => new RuntimeException('Incorrect query sequencing, term() should only start the ORM chain'),
            };
        }

        // Validate if a clause exists but the search query is empty
        if ($clause && empty($this->searchQuery)) {
            // Throw an exception if the query sequence is incorrect for the type
            throw match ($type) {
                'fuzzy' => new RuntimeException('Incorrect query sequencing, andFuzzyTerm()/orFuzzyTerm() cannot start the ORM chain'),
                'regex' => new RuntimeException('Incorrect query sequencing, andRegEx()/orRegEx() cannot start the ORM chain'),
                'phrase' => new RuntimeException('Incorrect query sequencing, andPhrase()/orPhrase() cannot start the ORM chain'),
                default => new RuntimeException('Incorrect query sequencing, andTerm()/orTerm() cannot start the ORM chain'),
            };
        }

        // Format the search term based on the query type
        $nextTerm = match ($type) {
            'fuzzy' => '(' . self::_escape($term) . '~)',  // Fuzzy search format
            'regex' => '(/' . $term . '/)',                // Regex search format
            'phrase' => '("' . self::_escape($term) . '")', // Phrase search format
            default => '(' . self::_escape($term) . ')',   // Default term search format
        };

        // Apply boost factor if provided
        if ($boostFactor) {
            $nextTerm .= '^' . $boostFactor;
        }

        // Combine the next term with the clause, if any
        if ($clause) {
            $this->searchQuery = $this->searchQuery . ' ' . mb_strtoupper($clause) . ' ' . $nextTerm;
        } else {
            // If no clause, set the search query to the next term
            $this->searchQuery = $nextTerm;
        }
    }

    /**
     * Set the minimum should match condition.
     *
     * @param string $value The minimum should match value.
     *
     * @return void
     */
    public function minShouldMatch($value): void
    {
        // Set the minimum should match option for the search query
        $this->searchOptions['minimum_should_match'] = $value;
    }

    /**
     * Set the minimum score for the query.
     *
     * @param float $value The minimum score value.
     *
     * @return void
     */
    public function minScore($value): void
    {
        // Set the minimum score for the query
        $this->minScore = $value;
    }

    /**
     * Set a boost factor for a specific field.
     *
     * @param string $field The field to boost.
     * @param float $factor The boost factor for the field.
     *
     * @return void
     */
    public function boostField($field, $factor): void
    {
        // Set the boost factor for the specified field
        $this->fields[$field] = $factor ?? 1;
    }

    /**
     * Set boost factors for multiple search fields.
     *
     * @param array $fields List of fields to boost.
     *
     * @return void
     */
    public function searchFields(array $fields): void
    {
        // Loop through each field and set its boost factor to 1 if not already set
        foreach ($fields as $field) {
            if (empty($this->fields[$field])) {
                $this->fields[$field] = 1;
            }
        }
    }

    /**
     * Set the boost factor for a single search field.
     *
     * @param string $field The field to boost.
     * @param float|null $boostFactor Optional boost factor.
     *
     * @return void
     */
    public function searchField($field, $boostFactor = null): void
    {
        // Set the boost factor for the specified field
        $this->fields[$field] = $boostFactor ?? 1;
    }

    /**
     * Highlights the specified fields in the search results.
     *
     * This method allows you to define custom tags (like `<em>`) around the matched terms in the search results for the specified fields.
     * You can also pass global options for highlighting, such as pre and post tags.
     *
     * @param array $fields An associative array of fields to be highlighted and their respective options. If empty, all fields are highlighted by default.
     * @param string|array $preTag The tag to be inserted before the highlighted term(s).
     * @param string|array $postTag The tag to be inserted after the highlighted term(s).
     * @param array $globalOptions Optional global options to be applied to highlighting, such as additional settings.
     *
     * @return void
     */
    public function highlight(array $fields = [], string|array $preTag = '<em>', string|array $postTag = '</em>', array $globalOptions = []): void
    {
        $highlightFields = [
            '*' => (object)[], // Default to highlighting all fields
        ];

        // If specific fields are provided, assign them to the highlightFields array
        if (! empty($fields)) {
            $highlightFields = [];

            foreach ($fields as $field => $payload) {
                if (Validator::isInt($field)) {
                    // If the field is an integer, treat it as a simple field with no additional options
                    $highlightFields[$payload] = (object)[];
                } else {
                    // Otherwise, add the field and its associated options
                    $highlightFields[$field] = $payload;
                }
            }
        }

        // Ensure that preTag and postTag are arrays (they may be passed as a single string)
        if (! Validator::isArray($preTag)) {
            $preTag = [$preTag];
        }

        if (! Validator::isArray($postTag)) {
            $postTag = [$postTag];
        }

        $highlight = [];

        // Apply any global options provided for highlighting
        if ($globalOptions) {
            $highlight = $globalOptions;
        }

        // Add preTags, postTags, and the fields to the highlight configuration
        $highlight['pre_tags'] = $preTag;
        $highlight['post_tags'] = $postTag;
        $highlight['fields'] = $highlightFields;

        // Store the highlight configuration in the search options
        $this->searchOptions['highlight'] = $highlight;
    }

    /**
     * Set the index for the query.
     *
     * This method allows you to specify which index to query in Elasticsearch. Optionally, you can provide an alias for the index.
     *
     * @param string $index The name of the index to query.
     * @param string|null $as An optional alias for the index.
     *
     * @return static The current instance of the class for method chaining.
     */
    public function from($index, $as = null): static
    {
        if ($index) {
            // Set the connection index and retrieve the actual index
            $this->connection->setIndex($index);
            $this->index = $this->connection->getIndex();
        }

        return parent::from($index);
    }

    /**
     * Truncates the current index by deleting all its documents.
     *
     * This method deletes all documents in the current index. It returns the number of deleted documents on success.
     *
     * @return int The number of documents deleted from the index.
     */
    public function truncate(): int
    {
        $result = $this->connection->deleteAll([]);

        // Return the number of deleted documents if the operation was successful
        if ($result->isSuccessful()) {
            return $result->getDeletedCount();
        }

        // Return 0 if the deletion was not successful
        return 0;
    }

    /**
     * Deletes the index entirely.
     *
     * This method deletes the entire index from Elasticsearch. If the index does not exist, it returns false.
     *
     * @return bool Returns true if the index was deleted successfully, false otherwise.
     */
    public function deleteIndex(): bool
    {
        return Schema::connection($this->connection->getName())->delete($this->index);
    }

    /**
     * Deletes the index if it exists.
     *
     * This method attempts to delete the index only if it exists in Elasticsearch.
     *
     * @return bool Returns true if the index was deleted, false if it did not exist.
     */
    public function deleteIndexIfExists(): bool
    {
        return Schema::connection($this->connection->getName())->deleteIfExists($this->index);
    }

    /**
     * Retrieves the mappings of the current index.
     *
     * This method retrieves the field mappings of the specified index in Elasticsearch.
     *
     * @return array The mappings of the index.
     */
    public function getIndexMappings(): array
    {
        return Schema::connection($this->connection->getName())->getMappings($this->index);
    }

    /**
     * Retrieves the mapping for a specific field or all fields in the index.
     *
     * This method retrieves the field mapping for a specific field or for all fields in the index.
     *
     * @param string|array $field The name of the field to retrieve mapping for. If '*' is provided, all fields are returned.
     * @param bool $raw Whether to return raw mapping data or processed data.
     *
     * @return array The field mappings.
     */
    public function getFieldMapping(string|array $field = '*', bool $raw = false): array
    {
        return Schema::connection($this->connection->getName())->getFieldMapping($this->index, $field, $raw);
    }

    /**
     * Retrieves the settings of the current index.
     *
     * This method fetches the settings for the current index from Elasticsearch.
     *
     * @return array The settings of the index.
     */
    public function getIndexSettings(): array
    {
        return Schema::connection($this->connection->getName())->getSettings($this->index);
    }

    /**
     * Creates a new index if it does not already exist.
     *
     * This method creates a new index in Elasticsearch with the provided settings if the index does not already exist.
     *
     * @param array $settings The settings for the new index.
     *
     * @return bool Returns true if the index was created, false if it already exists.
     */
    public function createIndex(array $settings = []): bool
    {
        if (! $this->indexExists()) {
            // If the index does not exist, create it
            $this->connection->indexCreate($settings);

            return true;
        }

        // Return false if the index already exists
        return false;
    }

    /**
     * Checks if the current index exists in Elasticsearch.
     *
     * This method checks if the specified index exists in Elasticsearch.
     *
     * @return bool Returns true if the index exists, false otherwise.
     */
    public function indexExists(): bool
    {
        return Schema::connection($this->connection->getName())->hasIndex($this->index);
    }

    /**
     * Opens a Point-in-Time (PIT) for consistent searches.
     *
     * This method opens a PIT for Elasticsearch searches, which allows you to perform consistent searches on the same data even if the data is being updated during the search.
     *
     * @param string $keepAlive The amount of time the PIT should be kept alive (default is '5m' for 5 minutes).
     *
     * @return string The PIT ID used for subsequent searches.
     */
    public function openPit($keepAlive = '5m'): string
    {
        return $this->connection->openPit($keepAlive);
    }

    /**
     * Performs a PIT search to find documents.
     *
     * This method performs a search using a Point-in-Time (PIT), ensuring that the search is consistent even with ongoing data changes.
     *
     * @param int $count The number of documents to retrieve.
     * @param string $pitId The ID of the PIT to use for the search.
     * @param array|null $after Optional parameter to specify the starting point for the search (pagination).
     * @param string $keepAlive The amount of time to keep the PIT alive.
     *
     * @return Results The search results.
     */
    public function pitFind(int $count, string $pitId, ?array $after = null, string $keepAlive = '5m'): Results
    {
        $wheres = $this->compileWheres();
        $options = $this->compileOptions();
        $fields = $this->fields;
        $options['limit'] = $count;

        return $this->connection->pitFind($wheres, $options, $fields, $pitId, $after, $keepAlive);
    }

    /**
     * Closes an open Point-in-Time (PIT).
     *
     * This method closes the specified PIT, freeing up resources.
     *
     * @param string $id The PIT ID to close.
     *
     * @return bool Returns true if the PIT was successfully closed, false otherwise.
     */
    public function closePit($id): bool
    {
        return $this->connection->closePit($id);
    }

    /**
     * {@inheritdoc}
     */
    public function forPageAfterId($perPage = 15, $lastId = 0, $column = '_id')
    {
        return parent::forPageAfterId($perPage, $lastId, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function groupByRaw($sql, array $bindings = [])
    {
        throw new LogicException('groupByRaw() is currently not supported');
    }

    /**
     * Handles "where exists" queries, which are not supported by Elasticsearch.
     *
     * Since Elasticsearch does not support the SQL "where exists" query, this method throws an exception.
     *
     * @param array $where The conditions for the "where exists" query.
     *
     * @throws LogicException Always throws an exception because this type of query is not supported in Elasticsearch.
     */
    public function _parseWhereExists(array $where)
    {
        throw new LogicException('SQL type "where exists" query is not valid for Elasticsearch. Use whereNotNull() or whereNull() to query the existence of a field');
    }

    /**
     * Handles "where not exists" queries, which are not supported by Elasticsearch.
     *
     * Since Elasticsearch does not support the SQL "where not exists" query, this method throws an exception.
     *
     * @param array $where The conditions for the "where not exists" query.
     *
     * @throws LogicException Always throws an exception because this type of query is not supported in Elasticsearch.
     */
    public function _parseWhereNotExists(array $where)
    {
        throw new LogicException('SQL type "where exists" query is not valid for Elasticsearch. Use whereNotNull() or whereNull() to query the existence of a field');
    }

    /**
     * Processes the get operation, applying filters, options, and aggregation as needed.
     *
     * @param array|string $columns   The columns to select (defaults to all columns).
     * @param bool $returnLazy        If true, returns a LazyElasticCollection (for lazy loading).
     *
     * @throws RuntimeException If there is an error with the aggregation or the query execution.
     *
     * @return ElasticCollection|LazyElasticCollection|void A collection of results, either eagerly or lazily loaded, or void if no data is returned.
     */
    protected function _processGet(array|string $columns = [], bool $returnLazy = false)
    {
        // Compile where conditions for the query.
        $wheres = $this->compileWheres();

        // Compile additional options for the query.
        $options = $this->compileOptions();

        // Compile selected columns for the query.
        $columns = $this->compileColumns($columns);

        // Check if grouping is used, which is not supported.
        if ($this->groups) {
            throw new RuntimeException('Groups are not used');
        }

        // If aggregate operation is specified, process it.
        if ($this->aggregate) {
            $function = $this->aggregate['function'];
            $aggColumns = $this->aggregate['columns'];

            // Remove '*' from aggregate columns if present.
            if (in_array('*', $aggColumns)) {
                $aggColumns = null;
            }

            // If specific columns are defined for aggregation, use them.
            if ($aggColumns) {
                $columns = $aggColumns;
            }

            // Execute the aggregation query, with distinct options if required.
            if ($this->distinctType) {
                $totalResults = $this->connection->distinctAggregate($function, $wheres, $options, $columns);
            } else {
                $totalResults = $this->connection->aggregate($function, $wheres, $options, $columns);
            }

            // Check if aggregation was successful and handle the results.
            if (! $totalResults->isSuccessful()) {
                throw new RuntimeException($totalResults->errorMessage);
            }

            // Create a result array for the aggregation data.
            $results = [
                [
                    '_id' => null,
                    'aggregate' => $totalResults->data,
                ],
            ];
            $result = $this->elasticCollectionFactory->create(['items' => $results]);
            $result->setQueryMeta($totalResults->getMetaData());

            // Return the aggregation results as a collection.
            return $result;
        }

        // If distinct query is requested, handle it.
        if ($this->distinctType) {
            // Ensure columns are specified when using distinct.
            if (empty($columns[0]) || $columns[0] === '*') {
                throw new RuntimeException('Columns are required for term aggregation when using distinct()');
            }

            // Perform the distinct query based on the distinct type.
            if ($this->distinctType === 2) {
                $find = $this->connection->distinct($wheres, $options, $columns, true);
            } else {
                $find = $this->connection->distinct($wheres, $options, $columns);
            }
        } else {
            // Perform a normal find query.
            $find = $this->connection->find($wheres, $options, $columns);
        }

        // If the find query is successful, process the results.
        if ($find->isSuccessful()) {
            $data = $find->data;

            // If lazy loading is requested, return a LazyElasticCollection.
            if ($returnLazy) {
                if ($data) {
                    $lazy = LazyElasticCollection::make(function() use ($data) {
                        foreach ($data as $item) {
                            yield $item;
                        }
                    });
                    $lazy->setQueryMeta($find->getMetaData());

                    return $lazy;
                }
            }

            // Otherwise, return the results as an eager-loaded collection.
            $collection = $this->elasticCollectionFactory->create(['items' => $data]);
            $collection->setQueryMeta($find->getMetaData());

            return $collection;
        }

        // If the query fails, throw an exception with the error message.
        throw new RuntimeException('Error: ' . $find->errorMessage);
    }

    /**
     * Processes the delete operation by deleting all matching documents.
     *
     * @return int The number of documents deleted.
     */
    protected function _processDelete(): int
    {
        // Compile where conditions for the delete query.
        $wheres = $this->compileWheres();

        // Compile additional options for the delete query.
        $options = $this->compileOptions();

        // Perform the delete operation.
        $result = $this->connection->deleteAll($wheres, $options);

        // If the operation is successful, return the number of deleted documents.
        if ($result->isSuccessful()) {
            return $result->getDeletedCount();
        }

        // Return 0 if no documents were deleted or if the operation failed.
        return 0;
    }

    /**
     * Processes the update operation by updating matching documents.
     *
     * @param mixed $values The values to update in the documents.
     * @param array $options Options for the update operation.
     * @param string $method The method to use for the update (default is 'updateMany').
     *
     * @return int The number of documents modified.
     */
    protected function _processUpdate($values, array $options = [], $method = 'updateMany'): int
    {
        // Default to updating multiple documents unless otherwise specified.
        if (! Arr::exists($options, 'multiple')) {
            $options['multiple'] = true;
        }

        // Compile where conditions for the update query.
        $wheres = $this->compileWheres();

        // Perform the update operation using the specified method.
        $result = $this->connection->{$method}($wheres, $values, $options, $this->refresh);

        // If the update operation is successful, return the number of modified documents.
        if ($result->isSuccessful()) {
            return $result->getModifiedCount();
        }

        // Return 0 if no documents were modified or if the operation failed.
        return 0;
    }

    /**
     * Compiles the where conditions for the query.
     *
     * @throws RuntimeException If the where conditions are invalid or improperly structured.
     *
     * @return array The compiled where conditions.
     */
    protected function compileWheres(): array
    {
        $wheres = $this->wheres ?: [];
        $compiledWheres = [];

        // If where conditions are provided, process them.
        if ($wheres) {
            // Check that the query does not start with an "OR" statement.
            if ($wheres[0]['boolean'] === 'or') {
                throw new RuntimeException('Cannot start a query with an OR statement');
            }

            // If there's only one where condition, process it.
            if (count($wheres) === 1) {
                return $this->{'_parseWhere' . $wheres[0]['type']}($wheres[0]);
            }

            // Process AND and OR conditions separately.
            $and = [];
            $or = [];

            // Iterate through the where conditions.
            foreach ($wheres as $where) {
                // If an OR condition is found, push the current AND conditions to the OR bucket and reset the AND bucket.
                if ($where['boolean'] === 'or') {
                    $or[] = $and;
                    $and = [];
                }

                // Process the where condition based on its type.
                $result = $this->{'_parseWhere' . $where['type']}($where);
                $and[] = $result;
            }

            // If there are OR conditions, compile them with the AND conditions.
            if ($or) {
                $or[] = $and;

                // Compile the OR conditions.
                foreach ($or as $and) {
                    $compiledWheres['or'][] = $this->_prepAndBucket($and);
                }
            } else {
                // If no OR conditions, just compile the AND conditions.
                $compiledWheres = $this->_prepAndBucket($and);
            }
        }

        return $compiledWheres;
    }

    /**
     * Compiles options for the query.
     *
     * @return array The compiled options including sorting, pagination, filters, and other query-specific options.
     */
    protected function compileOptions(): array
    {
        $options = [];

        if ($this->orders) {
            $options['sort'] = $this->orders;
        }

        if ($this->offset) {
            $options['skip'] = $this->offset;
        }

        if ($this->limit) {
            $options['limit'] = $this->limit;
            //Check if it's first() with no ordering,
            //Set order to created_at -> asc for consistency
            //TODO
        }

        if ($this->cursor) {
            $options['_meta']['cursor'] = $this->cursor;

            if (! empty($this->cursor['next_sort'])) {
                $options['search_after'] = $this->cursor['next_sort'];
            }
        }

        if ($this->previousSearchAfter) {
            $options['prev_search_after'] = $this->previousSearchAfter;
        }

        if ($this->minScore) {
            $options['minScore'] = $this->minScore;
        }

        if ($this->searchOptions) {
            $options['searchOptions'] = $this->searchOptions;
        }

        if ($this->filters) {
            $options['filters'] = $this->filters;
        }

        if ($this->highlights) {
            $options['highlights'] = $this->highlights;
        }

        if ($this->randomScore) {
            $options['random_score'] = $this->randomScore;
        }

        return $options;
    }

    /**
     * Compiles the selected columns for the query.
     *
     * @param array|string $columns The columns to select for the query. Can be an array or a string.
     *
     * @return array The compiled list of columns.
     */
    protected function compileColumns($columns): array
    {
        $final = [];

        if ($this->columns) {
            foreach ($this->columns as $col) {
                $final[] = $col;
            }
        }

        if ($columns) {
            if (! Validator::isArray($columns)) {
                $columns = [$columns];
            }

            foreach ($columns as $col) {
                $final[] = $col;
            }
        }

        if (! $final) {
            return ['*'];
        }

        $final = Arr::values(Arr::unique($final));

        if (($key = Arr::search('*', $final)) !== false) {
            unset($final[$key]);
        }

        return $final;
    }

    //----------------------------------------------------------------------
    /**
     * Parses a basic 'where' clause.
     *
     * @param array $where The 'where' clause to parse, containing 'operator', 'column', 'value', and optional 'boolean' value.
     *
     * @throws RuntimeException if the operator is invalid or a closure is provided for the column.
     *
     * @return array The parsed query condition.
     */
    protected function _parseWhereBasic(array $where): array
    {
        $operator = $where['operator'];
        $column = $where['column'];
        $value = $where['value'];
        $boolean = $where['boolean'] ?? null;

        if ($boolean === 'and not') {
            $operator = '!=';
        }

        if ($boolean === 'or not') {
            $operator = '!=';
        }

        if ($operator === 'not like') {
            $operator = 'not_like';
        }

        if (! isset($operator) || $operator === '=') {
            $query = [$column => $value];
        } elseif (Arr::exists($this->conversion, $operator)) {
            $query = [$column => [$this->conversion[$operator] => $value]];
        } else {
            if (is_callable($column)) {
                throw new RuntimeException('Invalid closure for where clause');
            }
            $query = [$column => [$operator => $value]];
        }

        return $query;
    }

    /**
     * Parses a 'search' where clause for a multi-field query.
     *
     * @param array $where The 'where' clause to parse, containing 'operator', 'value', 'options', and 'fields'.
     *
     * @return array The parsed multi-match query.
     */
    protected function _parseWhereSearch(array $where): array
    {
        $operator = $where['operator'];
        $value = $where['value'];
        $options = $where['options'] ?? [];
        $fields = $where['fields'] ?? [];

        return ['multi_match' => [
            'query' => $value,
            'fields' => $fields,
            'type' => $operator,
            'options' => $options,
        ]];
    }

    /**
     * Parses a 'not null' where clause by converting it to an 'exists' operator.
     *
     * @param array $where The 'where' clause to parse.
     *
     * @return array The parsed query condition.
     */
    protected function _parseWhereNotNull(array $where): array
    {
        $where['operator'] = 'exists';
        $where['value'] = null;

        return $this->_parseWhereBasic($where);
    }

    /**
     * Parses a nested 'where' clause, applying a boolean operator to a group of conditions.
     *
     * @param array $where The 'where' clause to parse, containing 'boolean' and 'query'.
     *
     * @throws RuntimeException if the boolean operator is not supported for parameter grouping.
     *
     * @return array The parsed nested query condition.
     */
    protected function _parseWhereNested(array $where): array
    {
        $boolean = $where['boolean'];

        if ($boolean === 'and not') {
            $boolean = 'not';
        }
        $must = match ($boolean) {
            'and' => 'must',
            'not', 'or not' => 'must_not',
            'or' => 'should',
            default => throw new RuntimeException($boolean . ' is not supported for parameter grouping'),
        };

        $query = $where['query'];
        $wheres = $query->compileWheres();

        return [
            $must => ['group' => ['wheres' => $wheres]],
        ];
    }

    /**
     * Parses a nested query within another query, typically used for more advanced conditions.
     *
     * @param array $where The 'where' clause to parse, containing 'column', 'wheres', and 'options'.
     *
     * @return array The parsed inner nested query condition.
     */
    protected function _parseWhereQueryNested(array $where): array
    {
        return [
            $where['column'] => [
                'innerNested' => [
                    'wheres' => $where['wheres'],
                    'options' => $where['options'],
                ],
            ],
        ];
    }

    /**
     * Handles the "IN" condition for a WHERE clause.
     *
     * This function constructs a query that checks if a given column's value is present
     * in a list of values.
     *
     * @param array $where The condition array containing column and values to check.
     *
     * @return array The formatted query array with the 'in' condition.
     */
    protected function _parseWhereIn(array $where): array
    {
        $column = $where['column'];
        $values = $where['values'];

        return [$column => ['in' => Arr::values($values)]];
    }

    /**
     * Handles the "NOT IN" condition for a WHERE clause.
     *
     * This function constructs a query that checks if a given column's value is not present
     * in a list of values.
     *
     * @param array $where The condition array containing column and values to check.
     *
     * @return array The formatted query array with the 'nin' condition.
     */
    protected function _parseWhereNotIn(array $where): array
    {
        $column = $where['column'];
        $values = $where['values'];

        return [$column => ['nin' => Arr::values($values)]];
    }

    /**
     * Handles the "IS NULL" condition for a WHERE clause.
     *
     * This function transforms the "NULL" condition into a "not_exists" condition for query parsing.
     *
     * @param array $where The condition array to be transformed.
     *
     * @return array The formatted query array with the 'not_exists' condition.
     */
    protected function _parseWhereNull(array $where): array
    {
        $where['operator'] = 'not_exists';
        $where['value'] = null;

        return $this->_parseWhereBasic($where);
    }

    /**
     * Handles the "BETWEEN" condition for a WHERE clause.
     *
     * This function constructs a query that checks if a given column's value is between two values,
     * with an optional "NOT" condition for exclusion.
     *
     * @param array $where The condition array containing the column, values, and optional "not" flag.
     *
     * @return array The formatted query array with the 'between' or 'not_between' condition.
     */
    protected function _parseWhereBetween(array $where): array
    {
        $not = $where['not'] ?? false;
        $values = $where['values'];
        $column = $where['column'];

        if ($not) {
            return [
                $column => [
                    'not_between' => [$values[0], $values[1]],
                ],
            ];
        }

        return [
            $column => [
                'between' => [$values[0], $values[1]],
            ],
        ];
    }

    /**
     * Handles date conditions in a WHERE clause.
     *
     * This function simply returns a normal WHERE condition for date values.
     *
     * @param array $where The condition array containing the column and date value.
     *
     * @return array The normal WHERE condition.
     */
    protected function _parseWhereDate(array $where): array
    {
        //return a normal where clause
        return $this->_parseWhereBasic($where);
    }

    /**
     * Handles timestamp conditions in a WHERE clause.
     *
     * This function formats the timestamp value before passing it to the basic WHERE handler.
     *
     * @param array $where The condition array containing the column and timestamp value.
     *
     * @return array The formatted query array with the timestamp condition.
     */
    protected function _parseWhereTimestamp(array $where): array
    {
        $where['value'] = $this->_formatTimestamp($where['value']);

        return $this->_parseWhereBasic($where);
    }

    /**
     * Handles regular expression conditions for a WHERE clause.
     *
     * This function constructs a query that checks if a column matches a given regular expression.
     *
     * @param array $where The condition array containing the column and regular expression.
     *
     * @return array The formatted query array with the 'regex' condition.
     */
    protected function _parseWhereRegex(array $where): array
    {
        $value = $where['expression'];
        $column = $where['column'];

        return [$column => ['regex' => $value]];
    }

    /**
     * Handles nested object conditions in a WHERE clause.
     *
     * This function constructs a query for nested objects by accepting nested WHERE conditions and score mode.
     *
     * @param array $where The condition array containing the column, nested WHERE conditions, and score mode.
     *
     * @return array The formatted query array with the 'nested' condition.
     */
    protected function _parseWhereNestedObject(array $where): array
    {
        $wheres = $where['wheres'];
        $column = $where['column'];
        $scoreMode = $where['score_mode'];

        return [
            $column => ['nested' => ['wheres' => $wheres, 'score_mode' => $scoreMode]],
        ];
    }

    /**
     * Handles conditions for not nested objects in a WHERE clause.
     *
     * This function constructs a query for non-nested objects, handling nested WHERE conditions and score mode.
     *
     * @param array $where The condition array containing the column, nested WHERE conditions, and score mode.
     *
     * @return array The formatted query array with the 'not_nested' condition.
     */
    protected function _parseWhereNotNestedObject(array $where): array
    {
        $wheres = $where['wheres'];
        $column = $where['column'];
        $scoreMode = $where['score_mode'];

        return [
            $column => ['not_nested' => ['wheres' => $wheres, 'score_mode' => $scoreMode]],
        ];
    }

    /**
     * Processes the insertion of data into the database.
     *
     * This function takes care of chunking and inserting data in bulk, and provides detailed statistics on the insertion process.
     *
     * @param array $values The data to insert.
     * @param bool $returnData Flag to specify whether to return inserted data.
     * @param bool $saveWithoutRefresh Flag to determine if refresh should be skipped after insertion.
     *
     * @return ElasticCollection The result of the bulk insert operation.
     */
    protected function _processInsert(array $values, bool $returnData, bool $saveWithoutRefresh): ElasticCollection
    {
        $response = [
            'hasErrors' => false,
            'took' => 0,
            'total' => 0,
            'success' => 0,
            'created' => 0,
            'modified' => 0,
            'failed' => 0,
            'data' => [],
            'error_bag' => [],
        ];

        if (empty($values)) {
            return $this->_parseBulkInsertResult($response, $returnData);
        }

        if ($saveWithoutRefresh) {
            $this->refresh = false;
        }

        if (! Validator::isArray(reset($values))) {
            $values = [$values];
        }
        $this->applyBeforeQueryCallbacks();

        $insertChunkSize = $this->getConnection()->getInsertChunkSize();

        collect($values)->chunk($insertChunkSize)->each(callback: function($chunk) use (&$response, $returnData) {
            $result = $this->connection->insertBulk($chunk->toArray(), $returnData, $this->refresh);

            if ((bool)$result['hasErrors']) {
                $response['hasErrors'] = true;
            }
            $response['total'] += $result['total'];
            $response['took'] += $result['took'];
            $response['success'] += $result['success'];
            $response['failed'] += $result['failed'];
            $response['created'] += $result['created'];
            $response['modified'] += $result['modified'];
            $response['data'] = Arr::merge($response['data'], $result['data']);

            $response['error_bag'] = Arr::merge($response['error_bag'], $result['error_bag']);
        });

        return $this->_parseBulkInsertResult($response, $returnData);
    }

    /**
     * Parses the result of a bulk insert operation and prepares the response.
     *
     * @param array $response The response from the bulk insert operation.
     * @param bool $returnData Determines if the data should be returned or the metadata.
     *
     * @return ElasticCollection The parsed result, either with or without the data.
     */
    protected function _parseBulkInsertResult($response, $returnData): ElasticCollection
    {
        /** @var ElasticCollection $result */
        $result = $this->elasticCollectionFactory->create(['items' => $response['data']]);

        $queryMetadata = $this->queryMetaDataFactory->create(['meta' => []]);

        // Set metadata for the query
        $result->setQueryMeta($queryMetadata);
        $result->getQueryMeta()->setSuccess();
        $result->getQueryMeta()->setCreated($response['created']);
        $result->getQueryMeta()->setModified($response['modified']);
        $result->getQueryMeta()->setFailed($response['failed']);
        $result->getQueryMeta()->setQuery('InsertBulk');
        $result->getQueryMeta()->setTook($response['took']);
        $result->getQueryMeta()->setTotal($response['total']);

        // If there were errors in the bulk insert, set the error message
        if ($response['hasErrors']) {
            $errorMessage = 'Bulk insert failed for all values';

            if ($response['success'] > 0) {
                $errorMessage = 'Bulk insert failed for some values';
            }
            $result->getQueryMeta()->setError($response['error_bag'], $errorMessage);
        }

        // If $returnData is false, return only metadata
        if (! $returnData) {
            $data = $result->getQueryMetaAsArray();
            unset($data['query']);
            $response['data'] = $data;

            return $this->_parseBulkInsertResult($response, true);
        }

        return $result;
    }

    /**
     * Runs a pagination count query to determine the total count of the result set.
     *
     * @param array $columns The columns to count (default is all columns).
     *
     * @return Closure|array The result of the count query.
     */
    protected function runPaginationCountQuery($columns = ['*']): Closure|array
    {
        if ($this->distinctType) {
            $clone = $this->cloneForPaginationCount();
            $currentCloneCols = $clone->columns;

            if ($columns && $columns !== ['*']) {
                $currentCloneCols = Arr::merge($currentCloneCols, $columns);
            }

            return $clone->setAggregate('count', $currentCloneCols)->get()->all();
        }

        // Determine which parts of the query should be excluded
        $without = $this->unions ? ['orders', 'limit', 'offset'] : ['columns', 'orders', 'limit', 'offset'];

        return $this->cloneWithout($without)
            ->cloneWithoutBindings($this->unions ? ['order'] : ['select', 'order'])
            ->setAggregate('count', $this->withoutSelectAliases($columns))
            ->get()->all();
    }

    /**
     * Throws an exception as whereMonth clause is not available.
     *
     * @param array $where The condition array.
     *
     * @throws LogicException
     *
     * @return void
     */
    protected function _parseWhereMonth(array $where): array
    {
        throw new LogicException('whereMonth clause is not available yet');
    }

    /**
     * Throws an exception as whereDay clause is not available.
     *
     * @param array $where The condition array.
     *
     * @throws LogicException
     *
     * @return void
     */
    protected function _parseWhereDay(array $where): array
    {
        throw new LogicException('whereDay clause is not available yet');
    }

    /**
     * Throws an exception as whereYear clause is not available.
     *
     * @param array $where The condition array.
     *
     * @throws LogicException
     *
     * @return void
     */
    protected function _parseWhereYear(array $where): array
    {
        throw new LogicException('whereYear clause is not available yet');
    }

    /**
     * Throws an exception as whereTime clause is not available.
     *
     * @param array $where The condition array.
     *
     * @throws LogicException
     *
     * @return void
     */
    protected function _parseWhereTime(array $where): array
    {
        throw new LogicException('whereTime clause is not available yet');
    }

    /**
     * Throws an exception as whereRaw clause is not available.
     *
     * @param array $where The condition array.
     *
     * @throws LogicException
     *
     * @return void
     */
    protected function _parseWhereRaw(array $where): array
    {
        throw new LogicException('whereRaw clause is not available yet');
    }

    /**
     * Attaches an option to the last "where" condition.
     *
     * @param string $key The option key.
     * @param mixed $value The option value.
     *
     * @return void
     */
    private function _attachOption($key, $value): void
    {
        $wheres = $this->wheres;
        $where = Arr::pop($wheres);

        if (! isset($where['options'])) {
            $where['options'] = [];
        }
        $where['options'][$key] = $value;
        $wheres[] = $where;
        $this->wheres = $wheres;
    }

    /**
     * Prepares and processes an "and" bucket for conditions.
     *
     * @param array $andData The data for the "and" condition.
     *
     * @return array The processed "and" data in the required format.
     */
    private function _prepAndBucket($andData): array
    {
        $data = [];

        // Process each key-value pair and add to the 'and' condition
        foreach ($andData as $key => $ops) {
            $data['and'][$key] = $ops;
        }

        return $data;
    }

    /**
     * Validates and checks the values for the correct format.
     *
     * @param array $values The values to check.
     *
     * @throws RuntimeException
     *
     * @return bool True if values are valid, otherwise throws an exception.
     */
    private function _checkValues($values): true
    {
        unset($values['updated_at'], $values['created_at']);

        if (! $this->_isAssociative($values)) {
            throw new RuntimeException('Invalid value format. Expected associative array, got sequential array');
        }

        return true;
    }

    /**
     * Checks if an array is associative.
     *
     * @param array $arr The array to check.
     *
     * @return bool True if the array is associative, false if sequential.
     */
    private function _isAssociative(array $arr): bool
    {
        if ($arr === []) {
            return true;
        }

        return Arr::keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Formats a timestamp value to a valid format.
     *
     * @param mixed $value The value to format.
     *
     * @throws LogicException If the value is invalid.
     *
     * @return string|int The formatted timestamp.
     */
    private function _formatTimestamp($value): string|int
    {
        if (Validator::isNumeric($value)) {
            // Convert to integer if it's a string
            $value = (int)$value;

            // Check if value is in milliseconds
            if ($value > 10000000000) {
                return $value;
            }

            // Return timestamp as string if it's in seconds
            return (string)Carbon::createFromTimestamp($value)->timestamp;
        }

        // If not numeric, assume the value is a date string and convert it
        try {
            return (string)Carbon::parse($value)->timestamp;
        } catch (Exception $e) {
            throw new LogicException('Invalid date or timestamp');
        }
    }

    /**
     * Ensures that the given value is an array.
     *
     * @param mixed $value The value to check and convert.
     *
     * @return void
     */
    private function _ensureValueAsArray(&$value): void
    {
        if (! Validator::isArray($value)) {
            $value = [$value];
        }
    }
}
