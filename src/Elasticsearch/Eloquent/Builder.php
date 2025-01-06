<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Eloquent;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder as BaseEloquentBuilder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Models;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HigherOrderTapProxy;
use Maginium\Framework\Elasticsearch\Collection\ElasticCollection;
use Maginium\Framework\Elasticsearch\Concerns\BuildsQueries;
use Maginium\Framework\Elasticsearch\Exceptions\MissingOrderException;
use Maginium\Framework\Elasticsearch\Helpers\QueriesRelationships;
use Maginium\Framework\Elasticsearch\Pagination\SearchAfterPaginator;
use Maginium\Framework\Elasticsearch\Query\Builder as QueryBuilder;
use Maginium\Framework\Pagination\Facades\Paginator;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Reflection;
use RuntimeException;

/**
 * Builder for Elasticsearch queries, extending the default Eloquent Builder.
 *
 * @property QueryBuilder $query
 * @property Model $model
 *
 * @template TModel of Model
 * @template TCollection of ElasticCollection
 */
class Builder extends BaseEloquentBuilder
{
    // Include Paginator functionality.
    use BuildsQueries;
    // Include Elasticsearch specific query handling functionality.
    use QueriesRelationships;

    /**
     * List of methods that should be passed through to the query builder, including both
     * common database queries and Elasticsearch-specific methods.
     *
     * @var array<string>
     */
    protected $passthru = [
        'aggregate', 'average', 'avg', 'count', 'dd', 'doesntexist', 'dump', 'exists',
        'getbindings', 'getconnection', 'getgrammar', 'insert', 'insertgetid', 'insertorignore',
        'insertusing', 'max', 'min', 'pluck', 'pull', 'push', 'raw', 'sum', 'tosql',
        // Elasticsearch-specific methods:
        'matrix', 'query', 'rawdsl', 'rawsearch', 'rawaggregation', 'getindexsettings',
        'getindexmappings', 'getfieldmapping', 'deleteindexifexists', 'deleteindex',
        'truncate', 'indexexists', 'createindex', 'search', 'todsl', 'agg', 'insertwithoutrefresh',
    ];

    /**
     * Get the results of the query with the specified columns. This method handles
     * applying scopes, fetching the models, and eager loading relationships.
     *
     * @param  string[]  $columns  Columns to fetch from the query.
     *
     * @return TCollection  Returns an instance of ElasticCollection containing the results.
     */
    public function get($columns = ['*']): ElasticCollection
    {
        // Apply any additional query scopes to the builder.
        $builder = $this->applyScopes();

        // Execute the query to fetch models and their meta data.
        $fetch = $builder->getModels($columns);
        $meta = $fetch['meta'];

        // If models are found, load their relations (if any).
        if (count($models = $fetch['results']) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        // Create a new ElasticCollection instance with the models.
        $elasticCollection = $builder->getModel()->newCollection($models);

        // Set the meta data for the collection.
        $elasticCollection->setQueryMeta($meta);

        return $elasticCollection;
    }

    /**
     * Search for models using Elasticsearch, similar to the 'get' method but with specific search
     * behavior for Elasticsearch.
     *
     * @see get($columns = ['*'])
     *
     * @param  string[]  $columns  Columns to fetch from the query.
     *
     * @return ElasticCollection  Returns an instance of ElasticCollection containing the search results.
     */
    public function search($columns = ['*']): ElasticCollection
    {
        // Apply query scopes and execute the search.
        $builder = $this->applyScopes();
        $fetch = $builder->searchModels($columns);
        $meta = $fetch['meta'];

        // If models are found, eager load relations.
        if (count($models = $fetch['results']) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        // Create a new ElasticCollection with the results and set the meta data.
        $elasticCollection = $builder->getModel()->newCollection($models);
        $elasticCollection->setQueryMeta($meta);

        return $elasticCollection;
    }

    /**
     * Find a model by its primary key. If not found, return null.
     *
     * @param  mixed  $id  The ID of the model to find.
     * @param  string[]  $columns  The columns to select.
     *
     * @return Model|null  The found model, or null if not found.
     */
    public function find($id, $columns = ['*']): ?Model
    {
        $softDeleteColumn = null;

        // Check if the model supports soft deletes and handle accordingly.
        if (Reflection::methodExists($this->model, 'getQualifiedDeletedAtColumn')) {
            $softDeleteColumn = $this->model->getQualifiedDeletedAtColumn();

            // Remove the soft delete scope if it has been explicitly removed.
            if (in_array(SoftDeletingScope::class, $this->removedScopes)) {
                $softDeleteColumn = null;
            }
        }

        // Execute the query to find the model by its ID.
        $find = $this->query->find($id, $columns, $softDeleteColumn);

        // If the find operation was successful, instantiate the model and set meta data.
        if ($find->isSuccessful()) {
            $instance = $this->newModelInstance();
            $model = $instance->newFromBuilder($find->data);
            $model->setMeta($find->getMetaDataAsArray());
            $model->setRecordIndex($find->getMetaData()->getIndex());
            $model->setIndex($find->getMetaData()->getIndex());

            return $model;
        }

        // Return null if no model was found.
        return null;
    }

    /**
     * Finds a model by its ID or throws a ModelNotFoundException if not found.
     *
     * @param  mixed  $id  The primary key of the model to retrieve.
     * @param  array  $columns  The columns to retrieve, defaults to all columns ('*').
     *
     * @throws ModelNotFoundException  If no model with the specified ID is found.
     *
     * @return Model  The found model instance.
     */
    public function findOrFail($id, $columns = ['*']): Model
    {
        // Attempt to find the model by ID
        $result = $this->find($id, $columns);

        // If no result is found, throw a ModelNotFoundException
        if ($result === null) {
            throw (new ModelNotFoundException)->setModel(
                get_class($this->model),
                $id,
            );
        }

        // Return the found model
        return $result;
    }

    /**
     * Finds a model by its ID or returns a new model instance with the provided ID if not found.
     *
     * @param  mixed  $id  The primary key of the model to retrieve.
     * @param  array  $columns  The columns to retrieve, defaults to all columns ('*').
     *
     * @return Model  The found model instance or a new instance with the provided ID.
     */
    public function findOrNew($id, $columns = ['*']): Model
    {
        // If the model is found, return it
        if (null !== ($model = $this->find($id, $columns))) {
            return $model;
        }

        // If not found, create a new model instance and set the ID
        $model = $this->newModelInstance();

        // Set the ID to the model
        $model->_id = $id;

        return $model;
    }

    /**
     * Creates a new model instance with the given attributes and saves it to the database.
     *
     * @param  array  $attributes  The attributes to initialize the model with.
     *
     * @return Model  The created and saved model instance.
     */
    public function create(array $attributes = []): Model
    {
        // Create a new model instance, then save it within the closure
        return tap($this->newModelInstance($attributes), function($instance) {
            // Save the instance to the database
            $instance->save();
        });
    }

    /**
     * Gets the database connection instance for the current query.
     *
     * @return ConnectionInterface  The connection instance.
     */
    public function getConnection(): ConnectionInterface
    {
        // Retrieve the connection from the query object
        return $this->query->getConnection();
    }

    /**
     * Gets the model instance associated with the query builder.
     *
     * @return Model  The model instance.
     */
    public function getModel(): Model
    {
        // Return the model instance
        return $this->model;
    }

    /**
     * Retrieves models from the database and returns them with their metadata.
     *
     * @param  array  $columns  The columns to retrieve, defaults to all columns ('*').
     *
     * @return array  An array containing the models and metadata.
     */
    public function getModels($columns = ['*']): array
    {
        // Perform the query to get the models and their metadata
        $data = $this->query->get($columns);

        // Extract query metadata
        $meta = $data->getQueryMeta();

        // Hydrate the models
        $results = $this->model->hydrate($data->all())->all();

        // Return the models and metadata in an array
        return [
            'results' => $results,
            'meta' => $meta,
        ];
    }

    /**
     * Attempts to find the first model matching the given attributes or creates it with specified values.
     *
     * @param  array  $attributes  The attributes to search for the model.
     * @param  array  $values  Additional values to set when creating a new model.
     *
     * @return Model  The found or created model instance.
     */
    public function firstOrCreate(array $attributes = [], array $values = []): Model
    {
        // Try to find an existing model based on the attributes
        $instance = $this->_instanceBuilder($attributes);

        // If the model is found, return it
        if ($instance !== null) {
            return $instance;
        }

        // If not found, create a new model and merge the attributes with values
        return $this->create(array_merge($attributes, $values));
    }

    /**
     * Updates the model without triggering a refresh of the index.
     *
     * @param  array  $attributes  The attributes to update the model with.
     *
     * @return int  The number of affected rows.
     */
    public function updateWithoutRefresh(array $attributes = []): int
    {
        $query = $this->toBase();

        // Disable refresh
        $query->setRefresh(false);

        // Perform the update query and return the number of affected rows
        return $query->update($this->addUpdatedAtColumn($attributes));
    }

    /**
     * Attempts to find the first model matching the given attributes or creates it without refreshing the index.
     *
     * @param  array  $attributes  The attributes to search for the model.
     * @param  array  $values  Additional values to set when creating a new model.
     *
     * @return Model  The found or created model instance.
     */
    public function firstOrCreateWithoutRefresh(array $attributes = [], array $values = [])
    {
        // Try to find an existing model based on the attributes
        $instance = $this->_instanceBuilder($attributes);

        // If the model is found, return it
        if ($instance !== null) {
            return $instance;
        }

        // If not found, create a new model without refreshing the index
        return $this->createWithoutRefresh(array_merge($attributes, $values));
    }

    /**
     * Fast create method for 'write and forget' functionality without refreshing the index.
     *
     * @param  array  $attributes  The attributes to initialize the model with.
     *
     * @return Model|HigherOrderTapProxy|null|self  The created model instance.
     */
    public function createWithoutRefresh(array $attributes = []): Model|HigherOrderTapProxy|null|self
    {
        // Create a new model instance and save it without refreshing the index
        return tap($this->newModelInstance($attributes), function($instance) {
            // Save without refresh
            $instance->saveWithoutRefresh();
        });
    }

    /**
     * Performs a raw search using the provided body parameters and returns the results as an ElasticCollection.
     *
     * @param  array  $bodyParams  The body parameters to use for the search.
     *
     * @return TCollection  The results of the raw search, wrapped in an ElasticCollection.
     */
    public function rawSearch(array $bodyParams): ElasticCollection
    {
        // Perform the raw search with the given parameters
        $data = $this->query->rawSearch($bodyParams);

        // Hydrate the models from the response data
        $elasticCollection = $this->hydrate($data->data);

        // Extract metadata from the response
        $meta = $data->getMetaData();

        // Set query metadata
        $elasticCollection->setQueryMeta($meta);

        // Return the results wrapped in an ElasticCollection
        return $elasticCollection;
    }

    /**
     * Hydrates the models from the provided array of items, setting the necessary metadata.
     *
     * @param  array  $items  The items to hydrate into model instances.
     *
     * @return ElasticCollection  The collection of hydrated model instances.
     */
    public function hydrate(array $items): ElasticCollection
    {
        // Create a new model instance
        $instance = $this->newModelInstance();

        // Hydrate each item and return the collection
        return $instance->newCollection(array_map(function($item) use ($items, $instance) {
            $recordIndex = null;

            // If the item contains an index, extract it
            if (is_array($item)) {
                $recordIndex = ! empty($item['_index']) ? $item['_index'] : null;

                if ($recordIndex) {
                    // Remove the index from the item
                    unset($item['_index']);
                }
            }

            $meta = [];

            // If the item contains meta data, extract it
            if (isset($item['_meta'])) {
                $meta = $item['_meta'];

                // Remove the meta data from the item
                unset($item['_meta']);
            }

            // Set the meta data for the model
            $instance->setMeta($meta);

            // Create a new model from the item
            $model = $instance->newFromBuilder($item);

            // Set the record index if available
            if ($recordIndex) {
                $model->setRecordIndex($recordIndex);
                $model->setIndex($recordIndex);
            }

            // Set meta data if available
            if ($meta) {
                $model->setMeta($meta);
            }

            // If there are multiple items, prevent lazy loading
            if (count($items) > 1) {
                $model->preventsLazyLoading = Model::preventsLazyLoading();
            }

            return $model;
        }, $items));
    }

    /**
     * This method handles chunking the query results by a given ID.
     *
     * It removes any sorting, handles PIT (Point in Time) when the column is '_id',
     * and performs a paginated query for the results.
     *
     * @param mixed $count Number of items to process per chunk
     * @param callable $callback A callback to process each chunk of results
     * @param mixed $column The column to base chunking on (default: '_id')
     * @param mixed $alias Optional alias for the column
     * @param string $keepAlive The duration for PIT (default: '5m')
     *
     * @return bool True on success
     */
    public function chunkById(mixed $count, callable $callback, mixed $column = '_id', mixed $alias = null, string $keepAlive = '5m'): bool
    {
        // Set default column name if not provided
        $column ??= $this->defaultKeyName();
        $alias ??= $column;

        // Remove any existing sort orders from the query
        $this->query->orders = [];

        // If the column is '_id', use PIT for efficient chunking
        if ($column === '_id') {
            return $this->_chunkByPit($count, $callback, $keepAlive);
        }

        $lastId = null;
        $page = 1;

        // Process results in chunks
        do {
            // Clone the query to avoid affecting the original query
            $clone = clone $this;
            // Get the results for the current page after the last processed ID
            $results = $clone->forPageAfterId($count, $lastId, $column)->get();
            $countResults = $results->count();

            // Exit the loop if no results are found
            if ($countResults === 0) {
                break;
            }

            // Call the callback with the current results
            //@phpstan-ignore-next-line
            if ($callback($results, $page) === false) {
                return true; // Stop processing if the callback returns false
            }

            // Clean the alias if it ends with '.keyword'
            $aliasClean = $alias;

            if (str_ends_with($aliasClean, '.keyword')) {
                $aliasClean = mb_substr($aliasClean, 0, -8);
            }

            // Set the last processed ID for the next chunk
            $lastId = data_get($results->last(), $aliasClean);

            // Throw an exception if the last ID is not found
            if ($lastId === null) {
                throw new RuntimeException("The chunkById operation was aborted because the [{$aliasClean}] column is not present in the query result.");
            }

            unset($results);
            $page++; // Increment the page counter
        } while ($countResults === $count); // Continue if the result count matches the requested chunk size

        return true; // Return success
    }

    /**
     * Perform chunking with default PIT method.
     *
     * @param mixed $count The number of items per chunk
     * @param callable $callback The callback to process each chunk
     * @param string $keepAlive The PIT duration (default: '5m')
     *
     * @return bool True on success
     */
    public function chunk(mixed $count, callable $callback, string $keepAlive = '5m'): bool
    {
        // Use the PIT method to chunk results
        return $this->_chunkByPit($count, $callback, $keepAlive);
    }

    /**
     * Apply a geo bounding box filter to the query.
     *
     * @param string $field The field name for the geo filter
     * @param array $topLeft The coordinates of the top-left corner
     * @param array $bottomRight The coordinates of the bottom-right corner
     *
     * @return $this The current instance for method chaining
     */
    public function filterGeoBox(string $field, array $topLeft, array $bottomRight): self
    {
        $this->query->filterGeoBox($field, $topLeft, $bottomRight);

        return $this;
    }

    /**
     * Apply a geo point filter with a specified distance.
     *
     * @param string $field The field name for the geo point filter
     * @param string $distance The distance from the point
     * @param array $geoPoint The geo point (lat, lon)
     *
     * @return $this The current instance for method chaining
     */
    public function filterGeoPoint(string $field, string $distance, array $geoPoint): self
    {
        $this->query->filterGeoPoint($field, $distance, $geoPoint);

        return $this;
    }

    /**
     * Add a term query to the search.
     *
     * @param string $term The search term
     * @param int|null $boostFactor Optional boost factor
     *
     * @return $this The current instance for method chaining
     */
    public function term(string $term, ?int $boostFactor = null): self
    {
        $this->query->searchQuery($term, $boostFactor);

        return $this;
    }

    /**
     * Perform a search and return the models and metadata.
     *
     * @param array $columns The columns to retrieve
     *
     * @return array An array containing the search results and metadata
     */
    public function searchModels($columns = ['*']): array
    {
        $data = $this->query->search($columns); // Perform the search query
        $results = $this->model->hydrate($data->all())->all(); // Hydrate the results into models
        $meta = $data->getQueryMeta(); // Get query metadata

        return [
            'results' => $results, // Return the results
            'meta' => $meta, // Return the query metadata
        ];
    }

    /**
     * Create a new model instance.
     *
     * @param array $attributes The attributes for the new instance
     *
     * @return Model The new model instance
     */
    public function newModelInstance($attributes = []): Model
    {
        return $this->model->newInstance($attributes)->setConnection($this->query->getConnection()->getName());
    }

    /**
     * Get the base query builder instance.
     *
     * @return QueryBuilder The query builder instance
     */
    public function toBase(): QueryBuilder
    {
        return $this->applyScopes()->getQuery();
    }

    /**
     * Internal method to handle cursor pagination.
     *
     * @param int|null $perPage The number of items per page
     * @param array $columns The columns to retrieve
     * @param string $cursorName The cursor name
     * @param mixed $cursor The cursor for pagination
     *
     * @throws MissingOrderException If no order is set in the query
     * @throws BindingResolutionException If dependency resolution fails
     *
     * @return SearchAfterPaginator The paginator instance
     */
    public function cursorPaginate($perPage = null, $columns = ['*'], $cursorName = 'cursor', $cursor = null): SearchAfterPaginator
    {
        // Ensure there is an order set for pagination, defaulting to created_at or updated_at
        if (empty($this->query->orders)) {
            if (! $this->inferSort()) {
                throw new MissingOrderException;
            }
        }

        // Handle the cursor pagination logic
        $this->query->limit($perPage);
        $cursorPayload = $this->query->initCursor($cursor);
        $age = time() - $cursorPayload['ts'];

        // Refresh the cursor if it's older than 5 minutes
        $ttl = 300; // 5 minutes

        if ($age > $ttl) {
            $clone = $this->clone();
            $cursorPayload['records'] = $clone->count();
            $cursorPayload['pages'] = (int)ceil($cursorPayload['records'] / $perPage);
            $cursorPayload['ts'] = time();
        }

        $this->query->cursor = $cursorPayload;
        $search = $this->get($columns);

        return $this->searchAfterPaginator($search, $perPage, $cursor, [
            'path' => Paginator::resolveCurrentPath(),
            'cursorName' => 'cursor',
            'records' => $cursorPayload['records'],
            'totalPages' => $cursorPayload['pages'],
            'currentPage' => $cursorPayload['page'],
        ]);
    }

    /**
     * Infers the sort order for the query based on available fields.
     *
     * @return bool Returns true if sorting was applied, false otherwise.
     */
    protected function inferSort(): bool
    {
        // Initialize a flag to track if sorting is found.
        $found = false;

        // Get the index mappings from the query object.
        $indexMappings = $this->query->getIndexMappings();

        // Get the first mapping (assuming the first one is the relevant one).
        $mappings = reset($indexMappings);

        // Extract the fields from the mapping.
        $fields = $mappings['mappings']['properties'];

        // Check if the 'created_at' field is present, and apply sorting.
        if (! empty($fields['created_at'])) {
            $this->query->orderBy('created_at');
            $found = true; // Set found to true since sorting is applied.
        }

        // Check if the 'updated_at' field is present, and apply sorting.
        if (! empty($fields['updated_at'])) {
            $this->query->orderBy('updated_at');
            $found = true; // Set found to true since sorting is applied.
        }

        // Return whether sorting was applied or not.
        return $found;
    }

    /**
     * Handles pagination using the 'SearchAfterPaginator' class.
     *
     * @param mixed $items The items to paginate.
     * @param int $perPage The number of items per page.
     * @param mixed $cursor The cursor for pagination.
     * @param array $options Additional options for pagination.
     *
     * @throws BindingResolutionException
     *
     * @return mixed An instance of the SearchAfterPaginator class.
     */
    protected function searchAfterPaginator($items, $perPage, $cursor, $options)
    {
        // Use the container to create an instance of the SearchAfterPaginator class with the provided options.
        return Container::make(SearchAfterPaginator::class, compact('items', 'perPage', 'cursor', 'options'));
    }

    /**
     * Adds the 'updated_at' column to the given values if the model uses timestamps.
     *
     * @param array $values The existing values to add the 'updated_at' column to.
     *
     * @return array The values with the 'updated_at' column added.
     */
    protected function addUpdatedAtColumn(array $values): array
    {
        // Check if the model uses timestamps, and if the 'updated_at' column is defined.
        if (! $this->model->usesTimestamps() || $this->model->getUpdatedAtColumn() === null) {
            return $values; // Return the values unchanged if no timestamps are used or the 'updated_at' column is not defined.
        }

        // Get the name of the 'updated_at' column.
        $column = $this->model->getUpdatedAtColumn();

        // Add the 'updated_at' column with the current timestamp to the values.
        return array_merge([$column => $this->model->freshTimestampString()], $values);
    }

    /**
     * Performs a chunked search with a Pit (Point in Time) to ensure consistent results over time.
     *
     * @param mixed $count The number of items to retrieve per search.
     * @param callable $callback The callback function to handle the results.
     * @param string $keepAlive The duration to keep the PIT alive.
     *
     * @return bool Always returns true after completing the process.
     */
    private function _chunkByPit(mixed $count, callable $callback, string $keepAlive = '5m'): bool
    {
        // Open a PIT (Point in Time) with the specified keep-alive duration.
        $pitId = $this->query->openPit($keepAlive);

        // Initialize searchAfter and page variables for pagination.
        $searchAfter = null;
        $page = 1;

        do {
            // Clone the current instance to avoid modifying the original object.
            $clone = clone $this;

            // Perform a search using the PIT and retrieve the results.
            $search = $clone->query->pitFind($count, $pitId, $searchAfter, $keepAlive);

            // Extract metadata from the search response.
            $meta = $search->getMetaData();

            // Get the sort values for the next page of results.
            $searchAfter = $meta->getSort();

            // Hydrate the results (convert raw data to models).
            $results = $this->hydrate($search->data);

            // Count the number of results retrieved.
            $countResults = $results->count();

            // Break the loop if no results are found.
            if ($countResults === 0) {
                break;
            }

            // If the callback returns false, stop the iteration early.
            if ($callback($results, $page) === false) {
                return true;
            }

            // Free up memory by unsetting the results.
            unset($results);

            // Increment the page number for the next iteration.
            $page++;
        } while ($countResults === $count); // Continue while the number of results matches the requested count.

        // Close the PIT after the search is completed.
        $this->query->closePit($pitId);

        // Return true after completing the search process.
        return true;
    }

    /**
     * Builds an instance with the specified attributes, applying the appropriate 'where' conditions.
     *
     * @param array $attributes The attributes to apply to the instance.
     *
     * @return mixed The first instance matching the conditions.
     */
    private function _instanceBuilder(array $attributes = [])
    {
        // Clone the current instance to avoid modifying the original object.
        $instance = clone $this;

        // Loop through the provided attributes and apply 'where' conditions.
        foreach ($attributes as $field => $value) {
            // Determine the appropriate method based on the type of value (string or array).
            $method = is_string($value) ? 'whereExact' : 'where';

            // If the value is an array, apply a 'where' condition for each item.
            if (is_array($value)) {
                foreach ($value as $v) {
                    $specificMethod = is_string($v) ? 'whereExact' : 'where';
                    $instance = $instance->{$specificMethod}($field, $v);
                }
            } else {
                // Apply the 'where' condition for the single value.
                $instance = $instance->{$method}($field, $value);
            }
        }

        // Return the first instance that matches the applied conditions.
        return $instance->first();
    }
}
