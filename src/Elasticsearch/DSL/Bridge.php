<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\DSL;

use Elasticsearch\Client;
use Elasticsearch\Exception\ClientResponseException;
use Elasticsearch\Exception\MissingParameterException;
use Elasticsearch\Exception\ServerResponseException;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Elasticsearch\Connection;
use Maginium\Framework\Elasticsearch\DSL\exceptions\ParameterException;
use Maginium\Framework\Elasticsearch\DSL\exceptions\QueryException;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;

/**
 * Bridge class for interacting with Elasticsearch.
 *
 * This class provides methods for interpreting index data and building queries
 * to interact with Elasticsearch. It acts as a mediator between the application
 * and Elasticsearch, utilizing traits like `IndexInterpreter` and `QueryBuilder`
 * to handle specific operations.
 */
class Bridge
{
    // Provides methods for interpreting index data
    use IndexInterpreter;
    // Provides methods for building queries
    use QueryBuilder;

    /**
     * Property to hold the Elasticsearch connection.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * Property to hold the Elasticsearch client.
     *
     * @var Client|null
     */
    protected ?Client $client;

    /**
     * Optional property to hold the error logging index.
     *
     * @var string|null
     */
    protected ?string $errorLogger;

    /**
     * Maximum size of results, default is 10 as per Elasticsearch settings.
     *
     * @var int|null
     */
    protected ?int $maxSize = 10;

    /**
     * Property to hold the current index.
     *
     * @var string|null
     */
    private ?string $index;

    /**
     * Property to store metadata temporarily.
     *
     * @var array|null
     */
    private ?array $stashedMeta;

    /**
     * Cache for keyword fields used in queries.
     *
     * @var Collection|null
     */
    private ?Collection $cachedKeywordFields = null;

    /**
     * Optional property for setting an index prefix.
     *
     * @var string|null
     */
    private ?string $indexPrefix;

    /**
     * Optional property for setting an index prefix.
     *
     * @var ResultsFactory
     */
    private ResultsFactory $resultsFactory;

    /**
     * Constructor for initializing the Bridge class.
     *
     * @param Connection $connection The connection object to Elasticsearch
     * @param ResultsFactory $resultsFactory Factory for results instance
     */
    public function __construct(Connection $connection, ResultsFactory $resultsFactory)
    {
        $this->connection = $connection;
        $this->resultsFactory = $resultsFactory;

        // Get the client from the connection, used for Elasticsearch requests
        $this->client = $this->connection->getClient();

        // Get the index from the connection, which specifies the index to interact with
        $this->index = $this->connection->getIndex();

        // Get the maximum size for the results, fetched from the connection
        $this->maxSize = $this->connection->getMaxSize();

        // Get the index prefix, useful for namespacing indices
        $this->indexPrefix = $this->connection->getIndexPrefix();

        // Get the error logging index, where errors can be logged for debugging
        $this->errorLogger = $this->connection->getErrorLoggingIndex();
    }

    /**
     * Processes the creation of a Point-In-Time (PIT) and returns the PIT ID.
     *
     * @param string $keepAlive Duration to keep the PIT alive (default: '5m').
     *
     * @throws QueryException If an error occurs while processing the PIT.
     *
     * @return string The ID of the created PIT.
     */
    public function processOpenPit($keepAlive = '5m'): string
    {
        // Set parameters for the PIT creation request
        $params = [
            'index' => $this->index,        // Specify the index to use
            'keep_alive' => $keepAlive,      // Set the duration to keep the PIT alive
        ];
        $res = []; // Initialize the response variable

        try {
            // Make the request to create the PIT
            $res = $this->client->openPointInTime($params);

            // Check if the PIT ID is returned, else throw an error
            if (empty($res['id'])) {
                throw Exception::make('Error on PIT creation. No ID returned.');
            }
        } catch (Exception $e) {
            // Handle any exceptions by throwing a custom error with the details
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Return the PIT ID from the response
        return $res['id'];
    }

    /**
     * Processes a search query using a Point-In-Time (PIT) ID, with optional sorting and pagination.
     *
     * @param array $wheres Filter conditions for the search.
     * @param array $options Additional options for the search (e.g., sort, limit).
     * @param array $columns Columns to retrieve in the search results.
     * @param string $pitId The ID of the PIT to use for the search.
     * @param mixed $searchAfter Value to paginate results after.
     * @param string $keepAlive Duration to keep the PIT alive (default: '5m').
     *
     * @throws QueryException If an error occurs during the search.
     * @throws ParameterException If there are issues with the parameters.
     *
     * @return Results The search results.
     */
    public function processPitFind($wheres, $options, $columns, $pitId, $searchAfter = false, $keepAlive = '5m'): Results
    {
        // Build the parameters for the search query
        $params = $this->buildParams($this->index, $wheres, $options, $columns);
        unset($params['index']); // Remove the index parameter as it's set explicitly in the request body

        // Add the PIT details to the request body
        $params['body']['pit'] = [
            'id' => $pitId,            // Specify the PIT ID
            'keep_alive' => $keepAlive, // Set the duration to keep the PIT alive
        ];

        // Ensure there is a sort order (if none is provided)
        if (empty($params['body']['sort'])) {
            $params['body']['sort'] = [];
        }
        // Add a shard document sort to optimize results retrieval
        $params['body']['sort'][] = ['_shard_doc' => ['order' => 'asc']];

        // If pagination is requested, include the search_after value
        if ($searchAfter) {
            $params['body']['search_after'] = $searchAfter;
        }

        $process = []; // Initialize the search result variable

        try {
            // Execute the search query using the constructed parameters
            $process = $this->client->search($params);
        } catch (Exception $e) {
            // Handle exceptions and throw a custom error with the request details
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Sanitize and return the processed search response
        return $this->_sanitizePitSearchResponse($process, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Processes the closure of a Point-In-Time (PIT) by its ID.
     *
     * @param string $id The PIT ID to close.
     *
     * @throws QueryException If an error occurs while closing the PIT.
     *
     * @return bool Whether the PIT was successfully closed.
     */
    public function processClosePit($id): bool
    {
        // Set parameters for the PIT closure request
        $params = [
            'index' => $this->index,       // Specify the index
            'body' => [
                'id' => $id,                // Specify the PIT ID to close
            ],
        ];
        $res = []; // Initialize the response variable

        try {
            // Make the request to close the PIT
            $res = $this->client->closePointInTime($params);
        } catch (Exception $e) {
            // Handle any exceptions by throwing a custom error with the details
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Return whether the PIT closure succeeded
        return $res['succeeded'];
    }

    /**
     * Processes a raw search query, with an option to return the raw response.
     *
     * @param array $bodyParams The body of the search query.
     * @param bool $returnRaw Whether to return the raw search response.
     *
     * @throws Exception If an error occurs during the search.
     *
     * @return Results The search results, or raw response if requested.
     */
    public function processSearchRaw($bodyParams, $returnRaw): Results
    {
        // Set the parameters for the raw search query
        $params = [
            'index' => $this->index,        // Specify the index
            'body' => $bodyParams,          // Include the body of the query
        ];
        $process = []; // Initialize the result variable

        try {
            // Execute the search query
            $process = $this->client->search($params);

            // If the raw response is requested, return it directly
            if ($returnRaw) {
                return $this->_return($process, [], $params, $this->_queryTag(__FUNCTION__));
            }
        } catch (Exception $e) {
            // Handle any exceptions by throwing a custom error with the details
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Return the sanitized search response
        return $this->_sanitizeSearchResponse($process, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Processes a raw aggregation query and returns the results.
     *
     * @param array $bodyParams The body of the aggregation query.
     *
     * @throws QueryException If an error occurs during the aggregation query.
     *
     * @return Results The aggregation results.
     */
    public function processAggregationRaw($bodyParams): Results
    {
        // Set parameters for the raw aggregation query
        $params = [
            'index' => $this->index,       // Specify the index
            'body' => $bodyParams,         // Include the body of the aggregation
        ];
        $process = []; // Initialize the result variable

        try {
            // Execute the aggregation query
            $process = $this->client->search($params);
        } catch (Exception $e) {
            // Handle any exceptions by throwing a custom error with the details
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Return the sanitized aggregation response
        return $this->_sanitizeRawAggsResponse($process, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Processes an Elasticsearch query for a specific indices method.
     *
     * @param string $method The method to call on the Elasticsearch indices API.
     * @param array $params Parameters to pass to the indices method.
     *
     * @throws QueryException If an error occurs during the query.
     *
     * @return Results The response from the indices API.
     */
    public function processIndicesDsl($method, $params): Results
    {
        $process = []; // Initialize the result variable

        try {
            // Call the appropriate indices method dynamically
            $process = $this->client->indices()->{$method}($params);
        } catch (Exception $e) {
            // Handle any exceptions by throwing a custom error with the details
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Return the sanitized response from the indices query
        return $this->_sanitizeSearchResponse($process, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Processes and builds the parameters for a DSL query.
     *
     * @param array $wheres Filter conditions for the query.
     * @param array $options Additional options for the query.
     * @param array $columns Columns to retrieve in the query.
     *
     * @throws ParameterException If there is an issue with the query parameters.
     * @throws QueryException If an error occurs during the parameter building.
     *
     * @return array The built query parameters.
     */
    public function processToDsl($wheres, $options, $columns): array
    {
        // Build the query parameters using the specified filters, options, and columns
        return $this->buildParams($this->index, $wheres, $options, $columns);
    }

    /**
     * Processes and builds parameters for a search query using DSL.
     *
     * @param array $searchParams Search query parameters.
     * @param array $searchOptions Search query options.
     * @param array $wheres Filter conditions for the search.
     * @param array $opts Additional options for the search.
     * @param array $fields Fields to retrieve in the search.
     * @param array $cols Columns to retrieve in the search.
     *
     * @throws ParameterException If there is an issue with the search parameters.
     * @throws QueryException If an error occurs during parameter building.
     *
     * @return array The built search parameters.
     */
    public function processToDslForSearch($searchParams, $searchOptions, $wheres, $opts, $fields, $cols): array
    {
        // Build search parameters using the specified search query details
        return $this->buildSearchParams($this->index, $searchParams, $searchOptions, $wheres, $opts, $fields, $cols);
    }

    /**
     * Processes a query to get a document by ID.
     *
     * @param string $id The document ID to retrieve.
     * @param array $columns Columns to retrieve for the document.
     * @param string $softDeleteColumn The column used to track soft deletes.
     *
     * @throws QueryException If an error occurs during the query.
     *
     * @return Results The document retrieval results.
     */
    public function processGetId($id, $columns, $softDeleteColumn): Results
    {
        // Set parameters for the document retrieval query
        $params = [
            'index' => $this->index,      // Specify the index
            'id' => $id,                  // Specify the document ID
        ];

        // Ensure the columns parameter is set to an array
        if (empty($columns)) {
            $columns = ['*'];
        }

        if (! Validator::isArray($columns)) {
            $columns = [$columns];
        }
        $allColumns = $columns[0] === '*';  // Check if all columns are requested

        // If soft delete column is provided, ensure it is included in the columns
        if ($softDeleteColumn && ! $allColumns && ! in_array($softDeleteColumn, $columns)) {
            $columns[] = $softDeleteColumn;
        }
        $params['_source'] = $columns;  // Set the columns to retrieve

        $process = [];  // Initialize the result variable

        try {
            // Execute the query to get the document by ID
            $process = $this->client->get($params);
        } catch (Exception $e) {
            // If a 404 error occurs, ignore it; otherwise, handle it
            if ($e->getCode() !== 404) {
                $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
            }
        }

        // Return the sanitized document response
        return $this->_sanitizeGetResponse($process, $params, $softDeleteColumn, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Processes a search query with the specified filter, options, and columns.
     *
     * @param array $wheres Filter conditions for the search.
     * @param array $options Additional options for the search.
     * @param array $columns Columns to retrieve in the search results.
     *
     * @throws QueryException If an error occurs during the search.
     * @throws ParameterException If there is an issue with the query parameters.
     *
     * @return Results The search results.
     */
    public function processFind($wheres, $options, $columns): Results
    {
        // Build the parameters for the search query
        $params = $this->buildParams($this->index, $wheres, $options, $columns);

        // Return the search results
        return $this->_returnSearch($params, __FUNCTION__);
    }

    /**
     * Handles the search operation with the given search parameters, options, and column selections.
     *
     * @param mixed $searchParams Parameters to specify the search query.
     * @param mixed $searchOptions Options to control the search query, such as pagination or filters.
     * @param mixed $wheres The conditions for filtering the results.
     * @param mixed $opts Additional options for customization.
     * @param mixed $fields The specific fields to retrieve from the search.
     * @param mixed $cols The columns to select in the search result.
     *
     * @throws QueryException If there's an error with the query execution.
     * @throws ParameterException If there's an issue with the provided parameters.
     *
     * @return Results The search results wrapped in a Results object.
     */
    public function processSearch($searchParams, $searchOptions, $wheres, $opts, $fields, $cols): Results
    {
        // Building search parameters using the provided input
        $params = $this->buildSearchParams($this->index, $searchParams, $searchOptions, $wheres, $opts, $fields, $cols);

        // Return search results by calling the private _returnSearch method
        return $this->_returnSearch($params, __FUNCTION__);
    }

    /**
     * Handles the distinct search operation to return unique values based on specified columns and options.
     *
     * @param mixed $wheres Conditions for filtering the data.
     * @param mixed $options Options for additional customization like sorting and pagination.
     * @param mixed $columns The columns to apply the distinct operation on.
     * @param bool $includeDocCount Whether or not to include the document count in the response.
     *
     * @throws QueryException If there's an error with the query execution.
     * @throws ParameterException If there's an issue with the provided parameters.
     *
     * @return Results The distinct search results wrapped in a Results object.
     */
    public function processDistinct($wheres, $options, $columns, $includeDocCount = false): Results
    {
        // Ensure that the columns are always in an array format
        if ($columns && ! Validator::isArray($columns)) {
            $columns = [$columns];
        }

        // Extract sorting, skipping, and limiting options from the $options parameter
        $sort = $options['sort'] ?? [];
        $skip = $options['skip'] ?? 0;
        $limit = $options['limit'] ?? 0;
        unset($options['sort'], $options['skip'], $options['limit']);

        // Set sorting direction if specified
        if ($sort) {
            $sortField = key($sort);
            $sortDir = $sort[$sortField]['order'] ?? 'asc';
            $sort = [$sortField => $sortDir];
        }

        // Build parameters for the search query
        $params = $this->buildParams($this->index, $wheres, $options);
        $data = [];
        $response = [];

        try {
            // Add aggregations to the search parameters to perform the distinct operation
            $params['body']['aggs'] = $this->createNestedAggs($columns, $sort);

            // Perform the search query with the prepared parameters
            $response = $this->client->search($params);

            // If there are aggregations, sanitize and process the distinct results
            if (! empty($response['aggregations'])) {
                $data = $this->_sanitizeDistinctResponse($response['aggregations'], $columns, $includeDocCount);
            }

            // Apply limit and skip to the distinct results if necessary
            if ($skip || $limit) {
                $data = Arr::slice($data, $skip, $limit);
            }
        } catch (Exception $e) {
            // Handle errors during the search operation
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Return the processed distinct results along with the response and parameters
        return $this->_return($data, $response, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Processes a single record save operation.
     *
     * This method saves a document to the index, either by creating a new document
     * or updating an existing one if the document ID is provided.
     * It also handles removing internal metadata fields before saving.
     *
     * @param array $data Data to be saved. It should be in the form of a key-value array.
     * @param string|bool|null $refresh Optional flag indicating whether to refresh the index after the operation.
     *                                  If set to true, the index will be refreshed immediately.
     *                                  If set to false or null, the index won't be refreshed.
     *
     * @throws QueryException If there is an error while processing the request.
     *
     * @return Results The response after the operation, including saved data and metadata.
     */
    public function processSave($data, $refresh): Results
    {
        $id = null;

        // Check if the data contains an '_id' and handle it separately
        if (isset($data['_id'])) {
            $id = $data['_id'];
            unset($data['_id']);  // Remove internal _id before saving
        }

        // Remove any internal fields that should not be part of the saved data
        if (isset($data['_index'])) {
            unset($data['_index']);
        }

        if (isset($data['_meta'])) {
            unset($data['_meta']);
        }

        // Prepare the parameters for the save operation
        $params = [
            'index' => $this->index, // Use the index specified for the object
            'body' => $data,         // The body of the request containing the data
        ];

        // If an ID exists, include it in the request parameters
        if ($id) {
            $params['id'] = $id;
        }

        // If refresh is requested, add it to the parameters
        if ($refresh) {
            $params['refresh'] = $refresh;
        }

        $response = [];
        $savedData = [];

        try {
            // Perform the save operation and get the response
            $response = $this->client->index($params);
            // Add the _id to the saved data
            $savedData = ['_id' => $response['_id']] + $data;
        } catch (Exception $e) {
            // Handle errors during the save operation
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Return the response after the save operation
        return $this->_return($savedData, $response, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Inserts multiple records into the index using the Bulk API for better performance.
     *
     * This method uses the Elasticsearch bulk indexing API to insert multiple records in one request.
     * It prepares a batch of data to be indexed and handles the response for each record.
     *
     * More information on the Bulk API:
     * - https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/indexing_documents.html#_bulk_indexing
     * - https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
     *
     * @param array $records Array of records to be inserted.
     * @param bool $returnData Whether to return the data after insertion. If true, the inserted data will be returned.
     * @param string|bool|null $refresh Optional flag to refresh the index after the operation.
     *                                  If true, the index will be refreshed immediately.
     *                                  If false or null, the index won't be refreshed.
     *
     * @throws QueryException If there is an error during the bulk insert operation.
     *
     * @return array The response after the bulk insert operation, including counts and any error information.
     */
    public function processInsertBulk(array $records, bool $returnData = false, string|bool|null $refresh = null): array
    {
        // Initialize the parameters for the bulk request
        $params = ['body' => []];

        // If refresh is requested, include it in the parameters
        if ($refresh) {
            $params['refresh'] = $refresh;
        }

        // Prepare the data for each record to be inserted
        foreach ($records as $data) {
            // Set the index for each record
            $recordHeader['_index'] = $this->index;

            // Handle the record ID if it exists
            if (isset($data['_id'])) {
                $recordHeader['_id'] = $data['_id'];
                unset($data['_id']); // Remove _id before adding to the bulk data
            }

            // Remove internal fields
            if (isset($data['_index'])) {
                unset($data['_index']);
            }

            if (isset($data['_meta'])) {
                unset($data['_meta']);
            }

            // Add the metadata and record to the bulk request body
            $params['body'][] = [
                'index' => $recordHeader,
            ];
            $params['body'][] = $data;
        }

        // Initialize the final response structure
        $finalResponse = [
            'hasErrors' => false,
            'total' => 0,
            'took' => 0,
            'success' => 0,
            'created' => 0,
            'modified' => 0,
            'failed' => 0,
            'data' => [],
            'error_bag' => [],
        ];

        try {
            // Perform the bulk insert operation
            $response = $this->client->bulk($params);
            // Set the error flag based on the response
            $finalResponse['hasErrors'] = $response['errors'];
            $finalResponse['took'] = $response['took'];

            // Process each item in the bulk response
            foreach ($response['items'] as $count => $hit) {
                $finalResponse['total']++;
                $payload = $params['body'][($count * 2) + 1]; // Get the record payload
                $id = $hit['index']['_id']; // Extract the ID from the response
                $record = ['_id' => $id] + $payload;

                // Handle any errors that occurred during the bulk insert
                if (! empty($hit['index']['error'])) {
                    $finalResponse['failed']++;
                    $finalResponse['error_bag'][] = [
                        'error' => $hit['index']['error'],
                        'payload' => $record,
                    ];
                } else {
                    // Increment success counters and categorize results
                    $finalResponse['success']++;

                    if ($hit['index']['result'] === 'created') {
                        $finalResponse['created']++;
                    } else {
                        $finalResponse['modified']++;
                    }

                    // Optionally return the inserted data
                    if ($returnData) {
                        $finalResponse['data'][] = $record;
                    }
                }
            }
        } catch (Exception $e) {
            // Handle errors during the bulk insert operation
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Return the response after the bulk insert operation
        return $finalResponse;
    }

    /**
     * Inserts a single record into the index.
     *
     * This method is a shorthand for `processSave()` and inserts a single record into the index.
     * It uses the same save logic but focuses on inserting one record at a time.
     *
     * @param array $values The data to be inserted. It should be in the form of a key-value array.
     * @param string|bool|null $refresh Optional flag indicating whether to refresh the index after the operation.
     *                                  If true, the index will be refreshed immediately.
     *                                  If false or null, the index won't be refreshed.
     *
     * @throws QueryException If there is an error while processing the request.
     *
     * @return Results The response after the operation, including saved data and metadata.
     */
    public function processInsertOne($values, $refresh): Results
    {
        // Reuse the processSave method to insert the single record
        return $this->processSave($values, $refresh);
    }

    /**
     * Processes the update operation for multiple records based on specified conditions.
     *
     * This method takes in conditions for selecting the records to update, the new values to apply, and any options
     * related to the query. It then attempts to update each record individually and returns the results.
     *
     * @param array $wheres The conditions used to find the records to update.
     * @param array $newValues The new values to apply to the selected records.
     * @param array $options Additional options for the query (e.g., sorting, limiting).
     * @param bool|null $refresh If set to true, the update will refresh the record after saving.
     *
     * @throws QueryException If a query-related error occurs.
     * @throws ParameterException If there is an issue with the parameters provided.
     *
     * @return Results The results of the update operation, including the number of modified and failed updates.
     */
    public function processUpdateMany($wheres, $newValues, $options, $refresh = null): Results
    {
        $resultMeta['modified'] = 0;
        $resultMeta['failed'] = 0;
        $resultData = [];

        // Find the data that matches the 'wheres' conditions
        $data = $this->processFind($wheres, $options, []);

        if (! empty($data->data)) {
            // Iterate through each data record and apply updates
            foreach ($data->data as $currentData) {
                // Apply the new values to the current data
                foreach ($newValues as $field => $value) {
                    $currentData[$field] = $value;
                }

                // Save the updated data
                $updated = $this->processSave($currentData, $refresh);

                // Check if the update was successful
                if ($updated->isSuccessful()) {
                    $resultMeta['modified']++;
                    $resultData[] = $updated->data;
                } else {
                    $resultMeta['failed']++;
                }
            }
        }

        // Build the query parameters for returning the result
        $params['query'] = $this->_buildQuery($wheres);
        $params['queryOptions'] = $options;
        $params['updateValues'] = $newValues;

        // Return the results of the update operation
        return $this->_return($resultData, $resultMeta, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Processes the increment operation for multiple records.
     *
     * This method increments a specific field in multiple records and applies any additional "set" operations.
     * It handles the case where fields are incremented by a specified value and also updates additional fields if provided.
     *
     * @param array $wheres The conditions used to find the records to increment.
     * @param array $newValues The new values, including both increment operations and "set" operations.
     * @param array $options Additional options for the query (e.g., sorting, limiting).
     * @param bool $refresh If set to true, the incremented records will be refreshed after saving.
     *
     * @throws QueryException If a query-related error occurs.
     * @throws ParameterException If there is an issue with the parameters provided.
     *
     * @return Results The results of the increment operation, including the number of modified and failed increments.
     */
    public function processIncrementMany($wheres, $newValues, $options, $refresh): Results
    {
        //TODO: Consider handling increment on nested objects

        $incField = '';

        // Determine the field to increment
        foreach ($newValues['inc'] as $field => $incValue) {
            $incField = $field;
        }

        $resultMeta['modified'] = 0;
        $resultMeta['failed'] = 0;
        $resultData = [];

        // Find the data matching the 'wheres' conditions
        $data = $this->processFind($wheres, $options, []);

        if (! empty($data->data)) {
            // Iterate through each data record and apply increments
            foreach ($data->data as $currentData) {
                // Increment the field value
                $currentValue = $currentData[$incField] ?? 0;
                $currentValue += $newValues['inc'][$incField];
                $currentData[$incField] = (int)$currentValue;

                // Apply additional 'set' operations if provided
                if (! empty($newValues['set'])) {
                    foreach ($newValues['set'] as $field => $value) {
                        $currentData[$field] = $value;
                    }
                }

                // Save the updated data
                $updated = $this->processSave($currentData, $refresh);

                // Check if the update was successful
                if ($updated->isSuccessful()) {
                    $resultMeta['modified']++;
                    $resultData[] = $updated->data;
                } else {
                    $resultMeta['failed']++;
                }
            }
        }

        // Build the query parameters for returning the result
        $params['query'] = $this->_buildQuery($wheres);
        $params['queryOptions'] = $options;
        $params['updateValues'] = $newValues;

        // Return the results of the increment operation
        return $this->_return($resultData, $resultMeta, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Processes the deletion of multiple records based on specified conditions.
     *
     * This method handles both single-record and multi-record deletions. It can delete records by ID or based on
     * query conditions.
     *
     * @param array $wheres The conditions used to find the records to delete.
     * @param array $options Additional options for the query (e.g., sorting, limiting).
     *
     * @throws QueryException If a query-related error occurs.
     * @throws ParameterException If there is an issue with the parameters provided.
     *
     * @return Results The results of the deletion operation, including the number of deleted records.
     */
    public function processDeleteAll($wheres, $options = []): Results
    {
        if (isset($wheres['_id'])) {
            // Handle single record deletion by ID
            $params = [
                'index' => $this->index,
                'id' => $wheres['_id'],
            ];

            try {
                $responseObject = $this->client->delete($params);
                $response = $responseObject;
                $response['deleteCount'] = $response['result'] === 'deleted' ? 1 : 0;

                return $this->_return($response['deleteCount'], $response, $params, $this->_queryTag(__FUNCTION__));
            } catch (Exception $e) {
                $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
            }
        }

        // Handle deletion based on query conditions
        $response = [];
        $params = $this->buildParams($this->index, $wheres, $options);

        try {
            $responseObject = $this->client->deleteByQuery($params);
            $response = $responseObject;
            $response['deleteCount'] = $response['deleted'] ?? 0;
        } catch (Exception $e) {
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        return $this->_return($response['deleteCount'], $response, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Retrieves the indices from Elasticsearch.
     *
     * This method returns the indices present in the Elasticsearch server.
     * If the $all parameter is true, it fetches all indices. Otherwise,
     * it fetches the specific index defined in the class.
     *
     * @param bool $all Flag to indicate whether to fetch all indices or a specific index.
     *
     * @throws ClientResponseException If there is a client-side error.
     * @throws ServerResponseException If there is a server-side error.
     * @throws MissingParameterException If required parameters are missing.
     *
     * @return array The response containing the index data.
     */
    public function processGetIndices($all): array
    {
        $index = $this->index;

        // Fetch all indices if $all is true
        if ($all) {
            $index = '*';
        }

        // Fetch indices using the client
        $response = $this->client->indices()->get(['index' => $index]);

        return $response;
    }

    /**
     * Checks if a specified index exists in Elasticsearch.
     *
     * This method checks whether a given index exists on the Elasticsearch server.
     * If an exception occurs during the check, it returns false.
     *
     * @param string $index The name of the index to check.
     *
     * @return bool Returns true if the index exists, false otherwise.
     */
    public function processIndexExists($index): bool
    {
        $params = ['index' => $index];

        try {
            // Check if the index exists
            $test = $this->client->indices()->exists($params);

            return $test;
        } catch (Exception $e) {
            // Return false if the index does not exist or any other error occurs
            return false;
        }
    }

    /**
     * Retrieves settings for a specific index in Elasticsearch.
     *
     * This method fetches the settings for the specified index. It processes the
     * response and returns the sanitized settings data.
     *
     * @param string $index The index name for which to fetch settings.
     *
     * @throws QueryException If the query to Elasticsearch fails.
     *
     * @return array The index settings.
     */
    public function processIndexSettings($index): array
    {
        $params = ['index' => $index];
        $response = [];

        try {
            // Retrieve the settings for the index
            $response = $this->client->indices()->getSettings($params);
            $result = $this->_return($response, $response, $params, $this->_queryTag(__FUNCTION__));
            $response = $result->data;
        } catch (Exception $e) {
            // Throw an error if the query fails
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        return $response;
    }

    /**
     * Creates a new index with the specified settings.
     *
     * This method creates a new index with the given settings. It returns true if
     * the index creation was successful, or false if it failed.
     *
     * @param array $settings The settings for the new index.
     *
     * @throws QueryException If there is an issue creating the index.
     *
     * @return bool True if the index was created successfully, false otherwise.
     */
    public function processIndexCreate($settings): bool
    {
        $params = $this->buildIndexMap($this->index, $settings);
        $created = false;

        try {
            // Create the index with the given settings
            $response = $this->client->indices()->create($params);
            $created = $response;
        } catch (Exception $e) {
            // Throw an error if index creation fails
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        return ! empty($created);
    }

    /**
     * Deletes an existing index.
     *
     * This method deletes the specified index from Elasticsearch. Returns true
     * if the deletion was successful.
     *
     * @throws QueryException If there is an issue deleting the index.
     *
     * @return bool True if the index was deleted successfully.
     */
    public function processIndexDelete(): bool
    {
        $params = ['index' => $this->index];

        try {
            // Delete the index
            $this->client->indices()->delete($params);
        } catch (Exception $e) {
            // Throw an error if index deletion fails
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        return true;
    }

    /**
     * Modifies the settings of an existing index.
     *
     * This method updates the index mappings and other settings. It modifies
     * the properties and mappings for the specified index.
     *
     * @param array $settings The settings for modifying the index.
     *
     * @throws QueryException If there is an issue modifying the index.
     *
     * @return bool True if the index was modified successfully.
     */
    public function processIndexModify($settings): bool
    {
        $params = $this->buildIndexMap($this->index, $settings);
        $params['body']['_source']['enabled'] = true;
        $props = $params['body']['mappings']['properties'];
        unset($params['body']['mappings']);
        $params['body']['properties'] = $props;

        try {
            // Update the index mappings
            $response = $this->client->indices()->putMapping($params);
            $result = $this->_return(true, $response, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {
            // Throw an error if index modification fails
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        return true;
    }

    /**
     * Reindexes data from an old index to a new index.
     *
     * This method copies the data from an old index to a new one. It returns the
     * result of the reindex operation, including the number of created, updated,
     * and deleted documents.
     *
     * @param string $oldIndex The old index name.
     * @param string $newIndex The new index name.
     *
     * @throws QueryException If there is an issue during the reindex operation.
     *
     * @return Results The result of the reindex operation.
     */
    public function processReIndex($oldIndex, $newIndex): Results
    {
        $prefix = $this->indexPrefix;

        // Add the prefix if defined
        if ($prefix) {
            $oldIndex = $prefix . '_' . $oldIndex;
            $newIndex = $prefix . '_' . $newIndex;
        }

        $params['body']['source']['index'] = $oldIndex;
        $params['body']['dest']['index'] = $newIndex;
        $resultData = [];
        $result = [];

        try {
            // Perform the reindex operation
            $response = $this->client->reindex($params);
            $result = $response;
            $resultData = [
                'took' => $result['took'],
                'total' => $result['total'],
                'created' => $result['created'],
                'updated' => $result['updated'],
                'deleted' => $result['deleted'],
                'batches' => $result['batches'],
                'version_conflicts' => $result['version_conflicts'],
                'noops' => $result['noops'],
                'retries' => $result['retries'],
            ];
        } catch (Exception $e) {
            // Throw an error if reindexing fails
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        return $this->_return($resultData, $result, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Updates the analyzer settings for the index.
     *
     * This method applies new analyzer settings to the index. The index is
     * temporarily closed, the settings are applied, and then the index is reopened.
     *
     * @param array $settings The new analyzer settings.
     *
     * @throws QueryException If there is an issue updating the analyzer settings.
     *
     * @return bool True if the analyzer settings were updated successfully.
     */
    public function processIndexAnalyzerSettings($settings): bool
    {
        $params = $this->buildAnalyzerSettings($this->index, $settings);

        try {
            // Close the index before updating settings
            $this->client->indices()->close(['index' => $this->index]);
            $response = $this->client->indices()->putSettings($params);
            $result = $this->_return(true, $response, $params, $this->_queryTag(__FUNCTION__));

            // Reopen the index after applying settings
            $this->client->indices()->open(['index' => $this->index]);
        } catch (Exception $e) {
            // Throw an error if updating analyzer settings fails
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        return true;
    }

    /**
     * Retrieves mappings for a specific index.
     *
     * This method fetches the mappings for a given index, which define the
     * structure of the data stored in the index.
     *
     * @param string $index The index name to retrieve mappings for.
     *
     * @throws QueryException If there is an issue retrieving the mappings.
     *
     * @return array The mappings for the specified index.
     */
    public function processIndexMappings($index): array
    {
        $params = ['index' => $index];
        $result = [];

        try {
            // Retrieve the mappings for the index
            $responseObject = $this->client->indices()->getMapping($params);
            $response = $responseObject;
            $result = $this->_return($response, $response, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {
            // Throw an error if retrieving mappings fails
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        return $result->data;
    }

    /**
     * Processes field mapping for a given index and field(s).
     *
     * This method retrieves the field mapping from the Elasticsearch index, processes it,
     * and returns either the parsed field map or the raw result based on the `$raw` flag.
     *
     * @param string $index The Elasticsearch index to retrieve the field mapping from.
     * @param string|array $field The field(s) for which the mapping is requested.
     * @param bool $raw Flag to indicate whether to return the raw response or parsed field map.
     *
     * @throws QueryException Throws an exception if an error occurs during the query execution.
     *
     * @return array|Collection Returns the parsed field map or raw response data.
     */
    public function processFieldMapping(string $index, string|array $field, bool $raw = false): array|Collection
    {
        $params = ['index' => $index, 'fields' => $field];
        $result = [];

        try {
            // Attempt to retrieve the field mapping from the Elasticsearch client
            $responseObject = $this->client->indices()->getFieldMapping($params);
            $response = $responseObject;
            // Process and return the result
            $result = $this->_return($response, $response, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {
            // Handle any exceptions that occur during the query
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Return either raw result or parsed field map
        if ($raw) {
            return $result->data;
        }

        return $this->_parseFieldMap($result->data);
    }

    /**
     * Processes multiple aggregation requests for the given functions and filters.
     *
     * This method handles the execution of multiple aggregation queries, combining
     * various functions and filters, and returns the results.
     *
     * @param mixed $functions Aggregation functions to execute.
     * @param mixed $wheres Conditions or filters to apply to the query.
     * @param mixed $options Additional options for the aggregation.
     * @param mixed $column Columns to aggregate.
     *
     * @throws QueryException Throws an exception if an error occurs during the query execution.
     * @throws ParameterException Throws an exception if the query parameters are invalid.
     *
     * @return Results Returns the results of the aggregation query.
     */
    public function processMultipleAggregate($functions, $wheres, $options, $column): Results
    {
        $params = $this->buildParams($this->index, $wheres, $options);
        $process = [];

        try {
            // Add the aggregation functions to the query parameters
            $params['body']['aggs'] = ParameterBuilder::multipleAggregations($functions, $column);
            // Execute the query using the Elasticsearch client
            $process = $this->client->search($params);
        } catch (Exception $e) {
            // Handle any exceptions during query execution
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Return the processed aggregation results
        return $this->_return($process['aggregations'] ?? [], $process, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Entry point for handling aggregate functions. This method dynamically calls the
     * appropriate aggregate function based on the provided `$function` name.
     *
     * @param string $function The name of the aggregate function to process (e.g., 'count', 'max').
     * @param mixed $wheres Conditions or filters to apply to the query.
     * @param mixed $options Additional options for the aggregation.
     * @param mixed $columns Columns to aggregate.
     *
     * @return Results Returns the results of the aggregation query.
     */
    public function processAggregate($function, $wheres, $options, $columns): Results
    {
        // Dynamically call the appropriate aggregate function based on the $function argument
        return $this->{'_' . $function . 'Aggregate'}($wheres, $options, $columns);
    }

    /**
     * Handles count aggregation for the provided conditions, options, and columns.
     *
     * This method processes the 'count' aggregation, counting the number of matching
     * documents in the specified index.
     *
     * @param mixed $wheres Conditions or filters to apply to the query.
     * @param mixed $options Additional options for the aggregation.
     * @param mixed $columns Columns to aggregate.
     *
     * @throws QueryException Throws an exception if an error occurs during the query execution.
     * @throws ParameterException Throws an exception if the query parameters are invalid.
     *
     * @return Results Returns the results of the count aggregation query.
     */
    public function _countAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres);
        $process = [];

        try {
            // Execute the count query using the Elasticsearch client
            $process = $this->client->count($params);
        } catch (Exception $e) {
            // Handle any exceptions during the count query
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Return the processed count aggregation results
        return $this->_return($process['count'] ?? 0, $process, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Processes distinct aggregation queries based on the provided function, filters, and columns.
     *
     * This method dynamically calls the appropriate distinct aggregate function based on
     * the `$function` argument.
     *
     * @param string $function The distinct aggregation function to use.
     * @param mixed $wheres Conditions or filters to apply to the query.
     * @param mixed $options Additional options for the aggregation.
     * @param mixed $columns Columns to aggregate.
     *
     * @return Results Returns the results of the distinct aggregation query.
     */
    public function processDistinctAggregate($function, $wheres, $options, $columns): Results
    {
        // Dynamically call the appropriate distinct aggregate function based on the $function argument
        return $this->{'_' . $function . 'DistinctAggregate'}($wheres, $options, $columns);
    }

    /**
     * Parses and checks if a specific field requires a keyword mapping, returning the field's keyword version.
     *
     * This method checks whether a field has a '.keyword' property, or if it's a keyword field,
     * and returns the appropriate keyword mapping.
     *
     * @param string $field The field to check for a keyword mapping.
     *
     * @return string|null Returns the keyword field or null if no keyword mapping exists.
     */
    public function parseRequiredKeywordMapping($field): ?string
    {
        // Check if the keyword fields are cached
        if (! $this->cachedKeywordFields instanceof Collection) {
            // Retrieve the keyword fields and cache them
            $mapping = $this->processFieldMapping($this->index, '*');
            $fullMap = Collection::make($mapping);
            $keywordFields = $fullMap->filter(fn($value) => $value === 'keyword');
            $this->cachedKeywordFields = $keywordFields;
        }

        $keywordFields = $this->cachedKeywordFields;

        if ($keywordFields->isEmpty()) {
            // No keyword fields found
            return null;
        }

        // Check if the field is a keyword or has a keyword property
        if ($keywordFields->has($field)) {
            return $field;
        }

        if ($keywordFields->has($field . '.keyword')) {
            return $field . '.keyword';
        }

        return null;
    }

    /**
     * Sanitizes the response of a 'get' request, handling soft deletes and missing data.
     *
     * This method checks if the document exists, and whether it has been soft deleted.
     * If the document is found and not soft deleted, it sanitizes the response data.
     *
     * @param array $response The response data from Elasticsearch.
     * @param array $params The parameters used for the query.
     * @param string|null $softDeleteColumn The column used for soft deletes (if applicable).
     * @param string $queryTag A unique query tag for logging.
     *
     * @return Results Returns the sanitized response wrapped in a Results object.
     */
    public function _sanitizeGetResponse($response, $params, $softDeleteColumn, $queryTag)
    {
        $data['_id'] = $params['id'];
        $softDeleted = false;

        // Check if the document has been soft deleted
        if ($softDeleteColumn) {
            $softDeleted = ! empty($response['_source'][$softDeleteColumn]);
        }

        if (! $response || $softDeleted) {
            // Document not found or soft deleted
            $result = $this->_return($data, [], $params, $queryTag);
            $result->setError($data['_id'] . ' not found', 404);

            return $result;
        }

        // Add the document's fields to the response data
        if (! empty($response['_source'])) {
            foreach ($response['_source'] as $key => $value) {
                $data[$key] = $value;
            }
        }

        // Remove soft delete column from the data
        if ($softDeleteColumn) {
            unset($data[$softDeleteColumn]);
        }

        // Return the sanitized response
        return $this->_return($data, [], $params, $queryTag);
    }

    /**
     * Performs a search operation with the given parameters and source,
     * and sanitizes the response.
     *
     * @param array $params The search parameters.
     * @param mixed $source The source of the query.
     *
     * @throws QueryException If the search query fails.
     *
     * @return Results The sanitized search results.
     */
    protected function _returnSearch($params, $source): Results
    {
        // If no size is specified in the parameters, set it to the maximum allowed size
        if (empty($params['size'])) {
            $params['size'] = $this->maxSize;
        }

        $process = [];

        try {
            // Execute the search using the client
            $process = $this->client->search($params);
        } catch (Exception $e) {
            // If an error occurs, throw a detailed error with the params and query tag
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Sanitize and return the search response
        return $this->_sanitizeSearchResponse($process, $params, $this->_queryTag($source));
    }

    /**
     * Performs a max aggregation on the specified columns with the given conditions and options.
     *
     * @param array $wheres The conditions for the aggregation.
     * @param array $options Additional options for the query.
     * @param array $columns The columns on which to perform the aggregation.
     *
     * @throws ParameterException If the parameters for the aggregation are invalid.
     * @throws QueryException If the query execution fails.
     *
     * @return Results The sanitized aggregation results.
     */
    private function _maxAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres, $options);

        // Ensure that $columns is a flat array of column names
        if (Validator::isArray($columns[0])) {
            $columns = $columns[0];
        }

        $process = [];

        try {
            // Add max aggregation for each column
            foreach ($columns as $column) {
                $params['body']['aggs']['max_' . $column] = ParameterBuilder::maxAggregation($column);
            }
            // Execute the search with the aggregation parameters
            $process = $this->client->search($params);
        } catch (Exception $e) {
            // Handle errors and throw detailed exception with the query tag
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Sanitize and return the aggregation results
        return $this->_sanitizeAggsResponse($process, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Performs a min aggregation on the specified columns with the given conditions and options.
     *
     * @param array $wheres The conditions for the aggregation.
     * @param array $options Additional options for the query.
     * @param array $columns The columns on which to perform the aggregation.
     *
     * @throws ParameterException If the parameters for the aggregation are invalid.
     * @throws QueryException If the query execution fails.
     *
     * @return Results The sanitized aggregation results.
     */
    private function _minAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres, $options);

        // Ensure that $columns is a flat array of column names
        if (Validator::isArray($columns[0])) {
            $columns = $columns[0];
        }

        $process = [];

        try {
            // Add min aggregation for each column
            foreach ($columns as $column) {
                $params['body']['aggs']['min_' . $column] = ParameterBuilder::minAggregation($column);
            }
            // Execute the search with the aggregation parameters
            $process = $this->client->search($params);
        } catch (Exception $e) {
            // Handle errors and throw detailed exception with the query tag
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Sanitize and return the aggregation results
        return $this->_sanitizeAggsResponse($process, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Performs a sum aggregation on the specified columns with the given conditions and options.
     *
     * @param array $wheres The conditions for the aggregation.
     * @param array $options Additional options for the query.
     * @param array $columns The columns on which to perform the aggregation.
     *
     * @throws ParameterException If the parameters for the aggregation are invalid.
     * @throws QueryException If the query execution fails.
     *
     * @return Results The sanitized aggregation results.
     */
    private function _sumAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres, $options);

        // Ensure that $columns is a flat array of column names
        if (Validator::isArray($columns[0])) {
            $columns = $columns[0];
        }

        $process = [];

        try {
            // Add sum aggregation for each column
            foreach ($columns as $column) {
                $params['body']['aggs']['sum_' . $column] = ParameterBuilder::sumAggregation($column);
            }
            // Execute the search with the aggregation parameters
            $process = $this->client->search($params);
        } catch (Exception $e) {
            // Handle errors and throw detailed exception with the query tag
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Sanitize and return the aggregation results
        return $this->_sanitizeAggsResponse($process, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Performs an average aggregation on the specified columns with the given conditions and options.
     *
     * @param array $wheres The conditions for the aggregation.
     * @param array $options Additional options for the query.
     * @param array $columns The columns on which to perform the aggregation.
     *
     * @throws ParameterException If the parameters for the aggregation are invalid.
     * @throws QueryException If the query execution fails.
     *
     * @return Results The sanitized aggregation results.
     */
    private function _avgAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres, $options);

        // Ensure that $columns is a flat array of column names
        if (Validator::isArray($columns[0])) {
            $columns = $columns[0];
        }

        $process = [];

        try {
            // Add avg aggregation for each column
            foreach ($columns as $column) {
                $params['body']['aggs']['avg_' . $column] = ParameterBuilder::avgAggregation($column);
            }
            // Execute the search with the aggregation parameters
            $process = $this->client->search($params);
        } catch (Exception $e) {
            // Handle errors and throw detailed exception with the query tag
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Sanitize and return the aggregation results
        return $this->_sanitizeAggsResponse($process, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Performs a matrix aggregation query on the Elasticsearch index and processes the result.
     *
     * This method builds the parameters for a matrix aggregation query, executes it against
     * the Elasticsearch client, and returns the result of the aggregation. If an error occurs
     * during the query, it will throw an exception and process the error.
     *
     * @param array $wheres An array of filter conditions for the query.
     * @param array $options Additional options for the query.
     * @param array $columns The columns to use in the matrix aggregation.
     *
     * @throws QueryException If there is an error in the query process.
     * @throws ParameterException If there are invalid query parameters.
     *
     * @return Results The processed results of the aggregation.
     */
    private function _matrixAggregate(array $wheres, array $options, array $columns): Results
    {
        // Build the parameters for the query, including the index, filters, and options
        $params = $this->buildParams($this->index, $wheres, $options);

        // Initialize the process array that will hold the response from Elasticsearch
        $process = [];

        try {
            // Add the matrix aggregation to the query parameters
            $params['body']['aggs']['statistics'] = ParameterBuilder::matrixAggregation($columns);

            // Execute the search query and capture the response
            $process = $this->client->search($params);
        } catch (Exception $e) {
            // Handle any exceptions that occur during the query execution
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        // Return the aggregation data or an empty array if not found
        return $this->_return($process['aggregations']['statistics'] ?? [], $process, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Sanitizes the response from an aggregation query to extract meaningful metadata and data.
     *
     * This method processes the raw response from an Elasticsearch aggregation query,
     * extracting key metadata (such as timeouts and total count) and aggregation results.
     * It also handles the possibility of multiple aggregations or a single aggregation.
     *
     * @param array $response The raw response from the Elasticsearch query.
     * @param array $params The parameters used in the query.
     * @param string $queryTag A tag for the query, typically used for logging or error handling.
     *
     * @return Results The sanitized results including aggregation data and metadata.
     */
    private function _sanitizeAggsResponse(array $response, array $params, string $queryTag): Results
    {
        // Extract metadata about the response, including timeout, total hits, and max score
        $meta['timed_out'] = $response['timed_out'];
        $meta['total'] = $response['hits']['total']['value'] ?? 0;
        $meta['max_score'] = $response['hits']['max_score'] ?? 0;
        $meta['sorts'] = [];  // Initialize an empty array for sorts, if applicable

        // Extract the aggregation data from the response
        $aggs = $response['aggregations'];

        // If there is exactly one aggregation, return its value. Otherwise, map over multiple aggregations.
        $data = (count($aggs) === 1)
            ? reset($aggs)['value'] ?? 0
            : Arr::map($aggs, fn($value) => $value['value'] ?? 0);

        // Return the sanitized data along with the metadata, query parameters, and query tag
        return $this->_return($data, $meta, $params, $queryTag);
    }

    /**
     * Perform a count distinct aggregation on the specified columns.
     *
     * @param array $wheres The filter conditions to apply.
     * @param array $options Additional options for the query.
     * @param array $columns The columns to aggregate.
     *
     * @throws ParameterException If there is an issue with the parameters.
     * @throws QueryException If there is an error executing the query.
     *
     * @return Results The result of the aggregation.
     */
    private function _countDistinctAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres);
        $count = 0;
        $meta = [];

        try {
            // Process distinct values and count the results
            $process = $this->processDistinct($wheres, $options, $columns);
            $count = count($process->data);
            $meta = $process->getMetaData();
        } catch (Exception $e) {
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        return $this->_return($count, $meta, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Perform a max distinct aggregation on the specified columns.
     *
     * @param array $wheres The filter conditions to apply.
     * @param array $options Additional options for the query.
     * @param array $columns The columns to aggregate.
     *
     * @throws ParameterException If there is an issue with the parameters.
     * @throws QueryException If there is an error executing the query.
     *
     * @return Results The result of the aggregation.
     */
    private function _maxDistinctAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres);
        $max = 0;
        $meta = [];

        try {
            // Process distinct values and calculate the maximum
            $process = $this->processDistinct($wheres, $options, $columns);

            if (! empty($process->data)) {
                foreach ($process->data as $datum) {
                    if (! empty($datum[$columns[0]]) && is_numeric($datum[$columns[0]])) {
                        $max = max($max, $datum[$columns[0]]);
                    }
                }
            }
            $meta = $process->getMetaData();
        } catch (Exception $e) {
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        return $this->_return($max, $meta, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Perform a min distinct aggregation on the specified columns.
     *
     * @param array $wheres The filter conditions to apply.
     * @param array $options Additional options for the query.
     * @param array $columns The columns to aggregate.
     *
     * @throws ParameterException If there is an issue with the parameters.
     * @throws QueryException If there is an error executing the query.
     *
     * @return Results The result of the aggregation.
     */
    private function _minDistinctAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres);
        $min = 0;
        $meta = [];

        try {
            // Process distinct values and calculate the minimum
            $process = $this->processDistinct($wheres, $options, $columns);
            $hasBeenSet = false;

            if (! empty($process->data)) {
                foreach ($process->data as $datum) {
                    if (! empty($datum[$columns[0]]) && is_numeric($datum[$columns[0]])) {
                        if (! $hasBeenSet) {
                            $min = $datum[$columns[0]];
                            $hasBeenSet = true;
                        } else {
                            $min = min($min, $datum[$columns[0]]);
                        }
                    }
                }
            }
            $meta = $process->getMetaData();
        } catch (Exception $e) {
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        return $this->_return($min, $meta, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Perform a sum distinct aggregation on the specified columns.
     *
     * @param array $wheres The filter conditions to apply.
     * @param array $options Additional options for the query.
     * @param array $columns The columns to aggregate.
     *
     * @throws ParameterException If there is an issue with the parameters.
     * @throws QueryException If there is an error executing the query.
     *
     * @return Results The result of the aggregation.
     */
    private function _sumDistinctAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres);
        $sum = 0;
        $meta = [];

        try {
            // Process distinct values and calculate the sum
            $process = $this->processDistinct($wheres, $options, $columns);

            if (! empty($process->data)) {
                foreach ($process->data as $datum) {
                    if (! empty($datum[$columns[0]]) && is_numeric($datum[$columns[0]])) {
                        $sum += $datum[$columns[0]];
                    }
                }
            }
            $meta = $process->getMetaData();
        } catch (Exception $e) {
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        return $this->_return($sum, $meta, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Perform an average distinct aggregation on the specified columns.
     *
     * @param array $wheres The filter conditions to apply.
     * @param array $options Additional options for the query.
     * @param array $columns The columns to aggregate.
     *
     * @throws ParameterException If there is an issue with the parameters.
     * @throws QueryException If there is an error executing the query.
     *
     * @return Results The result of the aggregation.
     */
    private function _avgDistinctAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres);
        $sum = 0;
        $count = 0;
        $avg = 0;
        $meta = [];

        try {
            // Process distinct values and calculate the average
            $process = $this->processDistinct($wheres, $options, $columns);

            if (! empty($process->data)) {
                foreach ($process->data as $datum) {
                    if (! empty($datum[$columns[0]]) && is_numeric($datum[$columns[0]])) {
                        $count++;
                        $sum += $datum[$columns[0]];
                    }
                }
            }

            if ($count > 0) {
                $avg = $sum / $count;
            }
            $meta = $process->getMetaData();
        } catch (Exception $e) {
            $this->_throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

        return $this->_return($avg, $meta, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * Placeholder method for a matrix distinct aggregate (not supported).
     *
     * @param array $wheres The filter conditions to apply.
     * @param array $options Additional options for the query.
     * @param array $columns The columns to aggregate.
     *
     * @throws QueryException Throws an exception as this operation is not supported.
     */
    private function _matrixDistinctAggregate($wheres, $options, $columns)
    {
        $this->_throwError(Exception::make(message: 'Matrix distinct aggregate not supported', code: 500), [], $this->_queryTag(__FUNCTION__));
    }

    /**
     * Helper method to return the results in the desired format.
     *
     * @param mixed $data The aggregated data.
     * @param mixed $meta Meta information about the query.
     * @param mixed $params The parameters used in the query.
     * @param string $queryTag A unique identifier for the query.
     *
     * @return Results The formatted result object.
     */
    private function _return($data, $meta, $params, $queryTag): Results
    {
        if (Validator::isObject($meta)) {
            $metaAsArray = [];

            // Check if the meta data can be converted to an array
            if (Reflection::methodExists($meta, 'asArray')) {
                $metaAsArray = $meta;
            }

            $results = $this->resultsFactory->create([
                'data' => $data,
                'meta' => $metaAsArray,
                'params' => $params,
                'queryTag' => $queryTag,
            ]);
        } else {
            $results = $this->resultsFactory->create([
                'data' => $data,
                'meta' => $meta,
                'params' => $params,
                'queryTag' => $queryTag,
            ]);
        }

        return $results;
    }

    /**
     * Generate a query tag based on the function name.
     *
     * @param string $function The function name to extract the tag from.
     *
     * @return string The generated query tag.
     */
    private function _queryTag($function): string
    {
        return str_replace('process', '', $function);
    }

    /**
     * Sanitize and format the PIT (Point In Time) search response.
     *
     * @param array $response The raw response from the PIT search.
     * @param array $params The search parameters.
     * @param string $queryTag A tag to associate with the query.
     *
     * @return mixed Processed response data and metadata.
     */
    private function _sanitizePitSearchResponse($response, $params, $queryTag)
    {
        $meta['timed_out'] = $response['timed_out'];
        $meta['total'] = $response['hits']['total']['value'] ?? 0;
        $meta['max_score'] = $response['hits']['max_score'] ?? 0;
        $meta['sort'] = null;
        $data = [];

        // Process hits if available
        if (! empty($response['hits']['hits'])) {
            foreach ($response['hits']['hits'] as $hit) {
                $datum = [];
                $datum['_index'] = $hit['_index'];
                $datum['_id'] = $hit['_id'];

                // Add fields from _source
                if (! empty($hit['_source'])) {
                    foreach ($hit['_source'] as $key => $value) {
                        $datum[$key] = $value;
                    }
                }

                // Store sorting data if available
                if (! empty($hit['sort'][0])) {
                    $meta['sort'] = $hit['sort'];
                }
                $data[] = $datum;
            }
        }

        return $this->_return($data, $meta, $params, $queryTag);
    }

    /**
     * Sanitize and format the basic search response.
     *
     * @param array $response The raw search response.
     * @param array $params The search parameters.
     * @param string $queryTag A tag to associate with the query.
     *
     * @return mixed Processed response data and metadata.
     */
    private function _sanitizeSearchResponse($response, $params, $queryTag)
    {
        $meta['took'] = $response['took'] ?? 0;
        $meta['timed_out'] = $response['timed_out'];
        $meta['total'] = $response['hits']['total']['value'] ?? 0;
        $meta['max_score'] = $response['hits']['max_score'] ?? 0;
        $meta['shards'] = $response['_shards'] ?? [];
        $data = [];

        // Process hits if available
        if (! empty($response['hits']['hits'])) {
            foreach ($response['hits']['hits'] as $hit) {
                $datum = [];
                $datum['_index'] = $hit['_index'];
                $datum['_id'] = $hit['_id'];

                // Add fields from _source
                if (! empty($hit['_source'])) {
                    foreach ($hit['_source'] as $key => $value) {
                        $datum[$key] = $value;
                    }
                }

                // Handle inner hits if available
                if (! empty($hit['inner_hits'])) {
                    foreach ($hit['inner_hits'] as $innerKey => $innerHit) {
                        $datum[$innerKey] = $this->_filterInnerHits($innerHit);
                    }
                }

                // Add highlights to metadata
                if (! empty($hit['highlight'])) {
                    $datum['_meta']['highlights'] = $this->_sanitizeHighlights($hit['highlight']);
                }

                $datum['_meta']['_index'] = $hit['_index'];
                $datum['_meta']['_id'] = $hit['_id'];

                if (! empty($hit['_score'])) {
                    $datum['_meta']['_score'] = $hit['_score'];
                }
                $datum['_meta']['_query'] = $meta;

                // Store sorting data in meta if available
                if (! empty($hit['sort'])) {
                    $datum['_meta']['sort'] = $hit['sort'];
                }
                $datum['_meta'] = $this->_attachStashedMeta($datum['_meta']);
                $data[] = $datum;
            }
        }

        return $this->_return($data, $meta, $params, $queryTag);
    }

    /**
     * Sanitize and format the distinct response.
     *
     * @param array $response The raw response from the distinct query.
     * @param array $columns The columns to process for distinct values.
     * @param bool $includeDocCount Whether to include document count in results.
     *
     * @return array Processed response data.
     */
    private function _sanitizeDistinctResponse($response, $columns, $includeDocCount): array
    {
        $keys = [];

        // Generate keys based on column names
        foreach ($columns as $column) {
            $keys[] = 'by_' . $column;
        }

        return $this->_processBuckets($columns, $keys, $response, 0, $includeDocCount);
    }

    /**
     * Process response buckets for aggregation-style data.
     *
     * @param array $columns The columns to process.
     * @param array $keys The keys representing the aggregation buckets.
     * @param array $response The raw response data.
     * @param int $index The current index in the columns.
     * @param bool $includeDocCount Whether to include document count in results.
     * @param array $currentData The current data being processed (for recursion).
     *
     * @return array Processed bucket data.
     */
    private function _processBuckets($columns, $keys, $response, $index, $includeDocCount, $currentData = []): array
    {
        $data = [];

        // Process each bucket if available
        if (! empty($response[$keys[$index]]['buckets'])) {
            foreach ($response[$keys[$index]]['buckets'] as $res) {
                $datum = $currentData;

                $col = $columns[$index];

                // Clean up .keyword fields for better handling
                if (str_contains($col, '.keyword')) {
                    $col = str_replace('.keyword', '', $col);
                }

                $datum[$col] = $res['key'];

                // Include doc count if necessary
                if ($includeDocCount) {
                    $datum[$col . '_count'] = $res['doc_count'];
                }

                // Recursively process nested buckets
                if (isset($columns[$index + 1])) {
                    $nestedData = $this->_processBuckets($columns, $keys, $res, $index + 1, $includeDocCount, $datum);

                    if (! empty($nestedData)) {
                        $data = Arr::merge($data, $nestedData);
                    } else {
                        $data[] = $datum;
                    }
                } else {
                    $data[] = $datum;
                }
            }
        }

        return $data;
    }

    /**
     * Sanitize and format the raw aggregation response.
     *
     * @param array $response The raw aggregation response.
     * @param array $params The search parameters.
     * @param string $queryTag A tag to associate with the query.
     *
     * @return mixed Processed aggregation data and metadata.
     */
    private function _sanitizeRawAggsResponse($response, $params, $queryTag)
    {
        $meta['timed_out'] = $response['timed_out'];
        $meta['total'] = $response['hits']['total']['value'] ?? 0;
        $meta['max_score'] = $response['hits']['max_score'] ?? '';
        $meta['sorts'] = [];
        $data = [];

        // Process aggregations if available
        if (! empty($response['aggregations'])) {
            foreach ($response['aggregations'] as $key => $values) {
                $data[$key] = $this->_formatAggs($key, $values)[$key];
            }
        }

        return $this->_return($data, $meta, $params, $queryTag);
    }

    /**
     * Sanitize and clean up highlight data.
     *
     * @param array $highlights The raw highlight data.
     *
     * @return array Processed highlight data.
     */
    private function _sanitizeHighlights($highlights)
    {
        // Remove .keyword fields from highlights if found
        foreach ($highlights as $field => $vals) {
            if (str_contains($field, '.keyword')) {
                $cleanField = str_replace('.keyword', '', $field);

                if (isset($highlights[$cleanField])) {
                    unset($highlights[$field]);
                } else {
                    $highlights[$cleanField] = $vals;
                }
            }
        }

        return $highlights;
    }

    /**
     * Filter inner hits and return cleaned data.
     *
     * @param array $innerHit The inner hit data to process.
     *
     * @return array Filtered inner hit data.
     */
    private function _filterInnerHits($innerHit)
    {
        $hits = [];

        // Process inner hits if available
        foreach ($innerHit['hits']['hits'] as $inner) {
            $innerDatum = [];

            // Extract fields from _source for each inner hit
            if (! empty($inner['_source'])) {
                foreach ($inner['_source'] as $innerSourceKey => $innerSourceValue) {
                    $innerDatum[$innerSourceKey] = $innerSourceValue;
                }
            }
            $hits[] = $innerDatum;
        }

        return $hits;
    }

    /**
     * Format aggregation data recursively.
     *
     * @param string $key The aggregation key.
     * @param mixed $values The values associated with the aggregation.
     *
     * @return array Formatted aggregation data.
     */
    private function _formatAggs($key, $values)
    {
        $data[$key] = [];
        $aggTypes = ['buckets', 'values'];

        // Recursively format nested aggregation values
        foreach ($values as $subKey => $value) {
            if (in_array($subKey, $aggTypes)) {
                $data[$key] = $this->_formatAggs($subKey, $value)[$subKey];
            } elseif (Validator::isArray($value)) {
                $data[$key][$subKey] = $this->_formatAggs($subKey, $value)[$subKey];
            } else {
                $data[$key][$subKey] = $value;
            }
        }

        return $data;
    }

    /**
     * Parse and map the fields from the provided field mapping.
     *
     * @param array $mapping An associative array representing the field mappings.
     *
     * @return array A sorted array where each key represents a field name and its value is the field's type.
     */
    private function _parseFieldMap(array $mapping): array
    {
        $fields = []; // Array to hold the parsed fields
        $mapping = reset($mapping); // Reset the mapping array to get the first element

        // Check if the 'mappings' key exists in the mapping and is not empty
        if (! empty($mapping['mappings'])) {
            foreach ($mapping['mappings'] as $key => $item) {
                // Check if 'mapping' key exists within each item and is not empty
                if (! empty($item['mapping'])) {
                    foreach ($item['mapping'] as $details) {
                        // If a 'type' key is found, store the field with its type
                        if (isset($details['type'])) {
                            $fields[$key] = $details['type'];
                        }

                        // Check if the field has nested fields and map them as well
                        if (isset($details['fields'])) {
                            foreach ($details['fields'] as $subField => $subDetails) {
                                // Create a nested key by concatenating the main key with the subfield name
                                $subFieldName = $key . '.' . $subField;
                                $fields[$subFieldName] = $subDetails['type'];
                            }
                        }
                    }
                }
            }
        }

        // Use a collection to sort the fields by their keys for consistent ordering
        $mappings = Collection::make($fields);
        $mappings = $mappings->sortKeys();

        // Return the sorted fields as an array
        return $mappings->toArray();
    }

    /**
     * Handle errors by creating a custom error response and logging the details.
     *
     * @param Exception $exception The exception that was thrown.
     * @param mixed $params The parameters associated with the query that caused the error.
     * @param string $queryTag The query tag for logging purposes.
     *
     * @throws QueryException Throws a QueryException with the error details.
     *
     * @return QueryException The thrown QueryException.
     */
    private function _throwError(Exception $exception, $params, $queryTag): QueryException
    {
        // Capture details from the exception
        $previous = get_class($exception);
        $errorMsg = $exception->getMessage();
        $errorCode = $exception->getCode();
        $queryTag = str_replace('_', '', $queryTag); // Clean up the query tag by removing underscores

        // Create an error result object with relevant information
        $error = $this->resultsFactory->create([
            'data' => [],
            'meta' => [],
            'params' => $params,
            'queryTag' => $queryTag,
        ]);

        // Set the error message and code on the error object
        $error->setError($errorMsg, $errorCode);

        // Get meta data for error details
        $meta = $error->getMetaDataAsArray();
        $details = [
            'error' => $meta['error']['msg'],
            'details' => $meta['error']['data'],
            'code' => $errorCode,
            'exception' => $previous,
            'query' => $queryTag,
            'params' => $params,
            'original' => $errorMsg,
        ];

        // Log the error details if an error logger is available
        if ($this->errorLogger) {
            $this->_logQuery($error, $details);
        }

        // Throw a QueryException with the captured error details
        throw new QueryException(message: __($meta['error']['msg']), cause: new $previous, statusCode: $errorCode,  details: $details);
    }

    /**
     * Log query details and errors into the error logging system.
     *
     * @param Results $results The results object containing query data.
     * @param array $details Additional error details to log.
     *
     * @return void
     */
    private function _logQuery(Results $results, $details): void
    {
        // Get the log-formatted meta data from the results object
        $body = $results->getLogFormattedMetaData();

        // If additional details were provided, add them to the log body
        if ($details) {
            $body['details'] = (array)$details;
        }

        // Prepare parameters for indexing the log in the error logger
        $params = [
            'index' => $this->errorLogger,
            'body' => $body,
        ];

        try {
            // Try to index the log into the error logging system
            $this->client->index($params);
        } catch (Exception $e) {
            // If there is an issue with writing the query log, silently ignore it
        }
    }

    /**
     * Stash metadata for future use.
     *
     * @param array $meta The metadata to stash.
     *
     * @return void
     */
    private function _stashMeta($meta): void
    {
        $this->stashedMeta = $meta; // Store the metadata in the class property for future use
    }

    /**
     * Attach previously stashed metadata to the current metadata.
     *
     * @param array $meta The current metadata to which the stashed metadata will be added.
     *
     * @return mixed The updated metadata with the stashed data merged.
     */
    private function _attachStashedMeta($meta): mixed
    {
        // If there is any stashed metadata, merge it with the current metadata
        if (! empty($this->stashedMeta)) {
            $meta = Arr::merge($meta, $this->stashedMeta);
        }

        // Return the merged metadata
        return $meta;
    }

    /**
     * (Optional) Parse the sort parameters for query sorting.
     *
     * @param array $sort The sorting parameters.
     * @param array $sortParams Additional sorting configurations.
     *
     * @return array Sorted field parameters.
     */
    private function _parseSort($sort, $sortParams): array
    {
        $sortValues = [];

        // Loop through the sort array and apply sorting parameters
        foreach ($sort as $key => $value) {
            $sortValues[Arr::key_first($sortParams[$key])] = $value;
        }

        // Return the sorted values
        return $sortValues;
    }
}
