<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\DSL;

use Maginium\Framework\Elasticsearch\Meta\QueryMetaData;
use Maginium\Framework\Support\Facades\Container;

class Results
{
    /**
     * Holds the actual result data returned from Elasticsearch.
     *
     * @var mixed
     */
    public mixed $data;

    /**
     * Holds any error message if the query fails.
     *
     * @var mixed
     */
    public mixed $errorMessage;

    /**
     * Private instance of QueryMetaData to store meta information about the query.
     *
     * @var QueryMetaData
     */
    private QueryMetaData $_meta;

    /**
     * Constructor to initialize the Results object with data, metadata, query parameters, and a query tag.
     *
     * @param mixed $data The actual data returned from Elasticsearch.
     * @param mixed $meta Metadata related to the query execution.
     * @param array $params Parameters used for the query.
     * @param string $queryTag A tag associated with the query.
     */
    public function __construct($data, $meta, $params, $queryTag)
    {
        // Initialize data property with the provided data
        $this->data = $data;

        // Initialize _meta property with new QueryMetaData object and set the query-related data
        $this->_meta = Container::make(QueryMetaData::class, ['meta' => $meta]);
        $this->_meta->setQuery($queryTag);
        $this->_meta->setSuccess();
        $this->_meta->setDsl($params);

        // Optionally set the index from the params if it exists
        if (! empty($params['index'])) {
            $this->_meta->setIndex($params['index']);
        }

        // Set the ID from the data if it's available
        if (! empty($data['_id'])) {
            $this->_meta->setId($data['_id']);
        }

        // Optionally set the deleted count from metadata if it exists
        if (! empty($meta['deleteCount'])) {
            $this->_meta->setDeleted($meta['deleteCount']);
        }

        // Optionally set the modified count from metadata if it exists
        if (! empty($meta['modified'])) {
            $this->_meta->setModified($meta['modified']);
        }

        // Optionally set the failed count from metadata if it exists
        if (! empty($meta['failed'])) {
            $this->_meta->setFailed($meta['failed']);
        }
    }

    /**
     * Sets an error message and code to the metadata.
     *
     * @param string $error The error message.
     * @param int $errorCode The error code associated with the error.
     */
    public function setError($error, $errorCode): void
    {
        // Parse and set the error details in the metadata
        $this->_meta->parseAndSetError($error, $errorCode);
    }

    /**
     * Checks if the query execution was successful.
     *
     * @return bool Returns true if the query was successful, false otherwise.
     */
    public function isSuccessful(): bool
    {
        // Check success status in the metadata
        return $this->_meta->isSuccessful();
    }

    /**
     * Returns the QueryMetaData object containing metadata about the query.
     *
     * @return QueryMetaData The metadata object.
     */
    public function getMetaData(): QueryMetaData
    {
        return $this->_meta;
    }

    /**
     * Returns the metadata as an associative array.
     *
     * @return array The metadata as an array.
     */
    public function getMetaDataAsArray(): array
    {
        return $this->_meta->asArray();
    }

    /**
     * Returns a formatted version of the metadata with a 'logged_' prefix for each key.
     *
     * @return array The formatted metadata for logging.
     */
    public function getLogFormattedMetaData(): array
    {
        $return = [];

        // Get the metadata as an array
        $meta = $this->getMetaDataAsArray();

        // Format the metadata for logging by prefixing 'logged_' to each key
        foreach ($meta as $key => $value) {
            $return['logged_' . $key] = $value;
        }

        return $return;
    }

    /**
     * Gets the inserted ID from the metadata, if available.
     *
     * @return mixed The inserted ID.
     */
    public function getInsertedId(): mixed
    {
        return $this->_meta->getId();
    }

    /**
     * Gets the number of modified records from the metadata, if available.
     *
     * @return int The number of modified records.
     */
    public function getModifiedCount(): int
    {
        return $this->_meta->getModified();
    }

    /**
     * Gets the number of deleted records from the metadata, if available.
     *
     * @return int The number of deleted records.
     */
    public function getDeletedCount(): int
    {
        return $this->_meta->getDeleted();
    }
}
