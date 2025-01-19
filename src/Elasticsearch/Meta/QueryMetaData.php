<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Meta;

use Maginium\Framework\Support\Facades\Json;

/**
 * Class QueryMetaData.
 *
 * This class encapsulates metadata related to an Elasticsearch query execution,
 * including results, status, errors, and additional information such as index,
 * query, shards, and time taken. It allows setting, getting, and manipulating
 * Elasticsearch query metadata.
 */
final class QueryMetaData
{
    /**
     * @var string The name of the Elasticsearch index used in the query.
     */
    private string $index = '';

    /**
     * @var string The query string that was executed.
     */
    private string $query = '';

    /**
     * @var bool Indicates whether the query was successful or not.
     */
    private bool $success = false;

    /**
     * @var bool Indicates whether the query timed out.
     */
    private bool $timed_out = false;

    /**
     * @var int The time taken for the query to execute (in milliseconds).
     */
    private int $took = -1;

    /**
     * @var int The total number of hits for the query.
     */
    private int $total = -1;

    /**
     * @var string The maximum score for the query results.
     */
    private string $max_score = '';

    /**
     * @var mixed The document ID, if available.
     */
    private mixed $_id = '';

    /**
     * @var mixed Shards information, detailing the shards involved in the query.
     */
    private mixed $shards = [];

    /**
     * @var array The DSL (Domain Specific Language) query used in the Elasticsearch request.
     */
    private array $dsl = [];

    /**
     * @var array Results of the query.
     */
    private array $results = [];

    /**
     * @var array Meta-information related to the query, not covered by other fields.
     */
    private array $_meta = [];

    /**
     * @var array Errors encountered during the query execution, if any.
     */
    private array $error = [];

    /**
     * @var string Error message if the query execution fails.
     */
    private string $errorMessage = '';

    /**
     * @var array Sorting information used in the query results.
     */
    private array $sort = [];

    /**
     * @var array Pagination cursor, used for paginated results.
     */
    private array $cursor = [];

    /**
     * QueryMetaData constructor.
     *
     * @param array $meta The metadata array containing various information related to the query execution.
     */
    public function __construct($meta)
    {
        // Set 'timed_out' to false if not present
        $this->timed_out = $meta['timed_out'] ?? false;
        unset($meta['timed_out']); // Remove 'timed_out' from $meta

        // Set 'took' to -1 if not present
        $this->took = $meta['took'] ?? -1;
        unset($meta['took']); // Remove 'took' from $meta

        // Set 'total' to -1 if not present
        $this->total = $meta['total'] ?? -1;
        unset($meta['total']); // Remove 'total' from $meta

        // Set 'max_score' to empty string if not present
        $this->max_score = (string)($meta['max_score'] ?? '');
        unset($meta['max_score']); // Remove 'max_score' from $meta

        // Set 'shards' to an empty array if not present
        $this->shards = $meta['shards'] ?? [];
        unset($meta['shards']); // Remove 'shards' from $meta

        // Set 'sort' to an empty array if not present
        $this->sort = $meta['sort'] ?? [];
        unset($meta['sort']); // Remove 'sort' from $meta

        // Set 'cursor' to an empty array if not present
        $this->cursor = $meta['cursor'] ?? [];
        unset($meta['cursor']); // Remove 'cursor' from $meta

        // Set '_id' to empty string if not present
        $this->_id = $meta['_id'] ?? '';
        unset($meta['_id']); // Remove '_id' from $meta

        // Set 'index' to empty string if not present
        $this->index = $meta['index'] ?? '';
        unset($meta['index']); // Remove 'index' from $meta

        // If any metadata remains, assign it to _meta
        if ($meta) {
            $this->_meta = $meta;
        }
    }

    /**
     * Gets the document ID.
     *
     * @return mixed|null The document ID, or null if not set.
     */
    public function getId(): mixed
    {
        return $this->_id ?? null;
    }

    //----------------------------------------------------------------------
    // Getters
    //----------------------------------------------------------------------

    /**
     * Gets the Elasticsearch index used in the query.
     *
     * @return string|null The index name, or null if not set.
     */
    public function getIndex(): mixed
    {
        return $this->index ?? null;
    }

    /**
     * Gets the modified count from the results.
     *
     * @return int The modified count, default is 0 if not available.
     */
    public function getModified(): int
    {
        return $this->getResults('modified') ?? 0;
    }

    /**
     * Gets the deleted count from the results.
     *
     * @return int The deleted count, default is 0 if not available.
     */
    public function getDeleted(): int
    {
        return $this->getResults('deleted') ?? 0;
    }

    /**
     * Gets the created count from the results.
     *
     * @return int The created count, default is 0 if not available.
     */
    public function getCreated(): int
    {
        return $this->getResults('created') ?? 0;
    }

    /**
     * Checks if the query was successful.
     *
     * @return bool True if successful, false otherwise.
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Gets the sorting information used in the query.
     *
     * @return array|null The sort array, or null if not set.
     */
    public function getSort(): ?array
    {
        return $this->sort;
    }

    /**
     * Gets the pagination cursor for the query.
     *
     * @return array|null The cursor array, or null if not set.
     */
    public function getCursor(): ?array
    {
        return $this->cursor;
    }

    /**
     * Gets the query string.
     *
     * @return string The query string.
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Gets the Elasticsearch DSL query.
     *
     * @return array The DSL query array.
     */
    public function getDsl(): array
    {
        return $this->dsl;
    }

    /**
     * Gets the time taken for the query execution.
     *
     * @return int The time in milliseconds.
     */
    public function getTook(): int
    {
        return $this->took;
    }

    /**
     * Gets the total number of results.
     *
     * @return int The total number of results.
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Gets the maximum score of the query results.
     *
     * @return string The max score.
     */
    public function getMaxScore(): string
    {
        return $this->max_score;
    }

    /**
     * Gets the shard information.
     *
     * @return mixed Shards information.
     */
    public function getShards(): mixed
    {
        return $this->shards;
    }

    /**
     * Gets the error message, if any.
     *
     * @return string The error message.
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Gets the error details, if any.
     *
     * @return array The error details.
     */
    public function getError(): array
    {
        return $this->error;
    }

    /**
     * Converts the QueryMetaData object to an associative array.
     *
     * @return array The array representation of the query metadata.
     */
    public function asArray(): array
    {
        // Create the return array with all the necessary data.
        $return = [
            'index' => $this->index,
            'query' => $this->query,
            'success' => $this->success,
            'timed_out' => $this->timed_out,
            'took' => $this->took,
            'total' => $this->total,
        ];

        // Add additional properties to the return array, if set.
        if ($this->max_score) {
            $return['max_score'] = $this->max_score;
        }

        if ($this->shards) {
            $return['shards'] = $this->shards;
        }

        if ($this->dsl) {
            $return['dsl'] = $this->dsl;
        }

        if ($this->_id) {
            $return['_id'] = $this->_id;
        }

        // Add query results, if available.
        if ($this->results) {
            foreach ($this->results as $key => $value) {
                $return[$key] = $value;
            }
        }

        // Add error details if available.
        if ($this->error) {
            $return['error'] = $this->error;
            $return['errorMessage'] = $this->errorMessage;
        }

        // Add sort and cursor information if available.
        if ($this->sort) {
            $return['sort'] = $this->sort;
        }

        if ($this->cursor) {
            $return['cursor'] = $this->cursor;
        }

        // Add additional meta information if available.
        if ($this->_meta) {
            $return['_meta'] = $this->_meta;
        }

        return $return;
    }

    /**
     * Retrieves the results from the query.
     *
     * @param string|null $key The key to get a specific result, or null to get all results.
     *
     * @return mixed The result value, or null if not set.
     */
    public function getResults($key = null)
    {
        if ($key) {
            return $this->results[$key] ?? null;
        }

        return $this->results;
    }

    //----------------------------------------------------------------------
    // Setters
    //----------------------------------------------------------------------

    /**
     * Sets the Elasticsearch index.
     *
     * @param string $index The index name.
     */
    public function setIndex(string $index): void
    {
        $this->index = $index;
    }

    /**
     * Sets the ID of the object.
     *
     * @param mixed $id The ID to be set.
     */
    public function setId($id): void
    {
        $this->_id = $id;
    }

    /**
     * Sets the time taken for the operation.
     *
     * @param int $took The time taken in milliseconds.
     */
    public function setTook(int $took): void
    {
        $this->took = $took;
    }

    /**
     * Sets the total number of results.
     *
     * @param int $total The total number of results.
     */
    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    /**
     * Sets the query string.
     *
     * @param mixed $query The query string to be set.
     */
    public function setQuery($query): void
    {
        $this->query = $query;
    }

    /**
     * Sets the success flag to true.
     */
    public function setSuccess(): void
    {
        $this->success = true;
    }

    /**
     * Sets a result key-value pair.
     *
     * @param string $key   The key of the result.
     * @param mixed  $value The value of the result.
     */
    public function setResult($key, $value): void
    {
        $this->results[$key] = $value;
    }

    /**
     * Sets the number of modified results.
     *
     * @param int $count The count of modified results.
     */
    public function setModified(int $count): void
    {
        $this->setResult('modified', $count);
    }

    /**
     * Sets the number of created results.
     *
     * @param int $count The count of created results.
     */
    public function setCreated(int $count): void
    {
        $this->setResult('created', $count);
    }

    /**
     * Sets the number of deleted results.
     *
     * @param int $count The count of deleted results.
     */
    public function setDeleted(int $count): void
    {
        $this->setResult('deleted', $count);
    }

    /**
     * Sets the number of failed results.
     *
     * @param int $count The count of failed results.
     */
    public function setFailed(int $count): void
    {
        $this->setResult('failed', $count);
    }

    /**
     * Sets the sorting criteria.
     *
     * @param array $sort The sorting criteria.
     */
    public function setSort(array $sort): void
    {
        $this->sort = $sort;
    }

    /**
     * Sets the cursor for pagination.
     *
     * @param array $cursor The pagination cursor.
     */
    public function setCursor(array $cursor): void
    {
        $this->cursor = $cursor;
    }

    /**
     * Sets the DSL (Domain Specific Language) query.
     *
     * @param mixed $params The DSL query parameters.
     */
    public function setDsl($params)
    {
        $this->dsl = $params;
    }

    /**
     * Sets the error and error message for the query.
     *
     * @param array  $error        The error details.
     * @param string $errorMessage The error message.
     */
    public function setError(array $error, string $errorMessage = ''): void
    {
        $this->success = false;
        $this->error = $error;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Parses and sets the error from a message.
     *
     * @param mixed  $error     The error message.
     * @param string $errorCode The error code.
     */
    public function parseAndSetError($error, $errorCode)
    {
        $errorMessage = $error;
        $this->success = false;

        // Decode the error message into structured details
        $details = $this->_decodeError($errorMessage);
        $error = [
            'msg' => $details['msg'],
            'data' => $details['data'],
            'code' => $errorCode,
        ];
        $this->error = $error;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Decodes an error message into structured data.
     *
     * @param string $error The error message to be decoded.
     *
     * @return array        The decoded error details.
     */
    private function _decodeError($error): array
    {
        // Initialize the return array with default message and empty data
        $return['msg'] = $error;
        $return['data'] = [];

        // Extract the JSON part of the error message, if any
        $jsonStartPos = mb_strpos($error, ': ') + 2;
        $response = $error;
        $title = mb_substr($response, 0, $jsonStartPos);
        $jsonString = mb_substr($response, $jsonStartPos);

        // Check if the extracted string is a valid JSON string
        if ($this->_isJson($jsonString)) {
            $errorArray = Json::decode($jsonString, true);
        } else {
            $errorArray = [$jsonString];
        }

        // If JSON decoding was successful, further process the error details
        if (json_last_error() === JSON_ERROR_NONE) {
            $errorReason = $errorArray['error']['reason'] ?? null;

            if (! $errorReason) {
                return $return;
            }

            $return['msg'] = $title . $errorReason;
            $cause = $errorArray['error']['root_cause'][0]['reason'] ?? null;

            if ($cause) {
                $return['msg'] .= ' - ' . $cause;
            }

            $return['data'] = $errorArray;
        }

        return $return;
    }

    /**
     * Validates if the given string is a valid JSON.
     *
     * @param string $string The string to check.
     *
     * @return bool          True if valid JSON, false otherwise.
     */
    private function _isJson($string): bool
    {
        return json_validate($string);
    }
}
