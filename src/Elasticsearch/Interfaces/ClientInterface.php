<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Interfaces;

use Elasticsearch\Client as ESClient;
use Maginium\Foundation\Exceptions\NotFoundException;

/**
 * Interface ClientInterface.
 *
 * Defines methods to interact with Elasticsearch.
 */
interface ClientInterface
{
    /**
     * Perform a search operation in Elasticsearch.
     *
     * @param array $params Search parameters.
     *
     * @return array Search results.
     */
    public function search(array $params): array;

    /**
     * Gets the Elasticsearch client instance.
     *
     * This method returns the Elasticsearch client, building it if necessary.
     *
     * @return ESClient|null The Elasticsearch client instance.
     */
    public function getClient(): ?ESClient;

    /**
     * Retrieve information about the Elasticsearch cluster.
     *
     * @return array Elasticsearch cluster information.
     */
    public function info(): array;

    /**
     * Ping the Elasticsearch cluster to check if it is reachable.
     *
     * @return bool True if the cluster is reachable, false otherwise.
     */
    public function ping(): bool;

    /**
     * Retrieve all indexes in Elasticsearch.
     *
     * @return array List of index names.
     */
    public function getIndexes(): array;

    /**
     * Retrieve details about a specific index in Elasticsearch.
     *
     * @param string $indexName Name of the index.
     *
     * @throws NotFoundException If the index does not exist.
     *
     * @return array Index details.
     */
    public function getIndex(string $indexName): array;

    /**
     * Check if an index exists in Elasticsearch.
     *
     * @param string $indexName Name of the index to check.
     *
     * @return bool True if the index exists, false otherwise.
     */
    public function indexExists(string $indexName): bool;

    /**
     * Create an Elasticsearch index with specified settings.
     *
     * @param string $indexName Name of the index to create.
     * @param array $indexSettings Index settings.
     */
    public function createIndex(string $indexName, array $indexSettings): array;

    /**
     * Delete an Elasticsearch index.
     *
     * @param string $indexName Name of the index to delete.
     */
    public function deleteIndex(string $indexName): array;

    /**
     * Set settings for an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     * @param array $indexSettings Index settings to set.
     */
    public function putIndexSettings(string $indexName, array $indexSettings): array;

    /**
     * Set mapping for an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     * @param array $mapping Mapping settings.
     */
    public function putMapping(string $indexName, array $mapping): array;

    /**
     * Retrieve mapping for an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     *
     * @return array Mapping information.
     */
    public function getMapping(string $indexName): array;

    /**
     * Retrieve settings for an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     *
     * @return array Index settings.
     */
    public function getSettings(string $indexName): array;

    /**
     * Perform a force merge on an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     */
    public function forceMerge(string $indexName): array;

    /**
     * Refresh an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     */
    public function refreshIndex(string $indexName): array;

    /**
     * Retrieve all indices associated with a given alias.
     *
     * @param string $indexAlias Alias name.
     *
     * @return array List of index names.
     */
    public function getIndicesNameByAlias(string $indexAlias): array;

    /**
     * Retrieve aliases associated with Elasticsearch indices.
     *
     * @param array $params Optional parameters.
     *
     * @return array List of aliases.
     */
    public function getIndexAliases(array $params = []): array;

    /**
     * Update aliases for Elasticsearch indices.
     *
     * @param array $aliasActions Alias actions to perform.
     */
    public function updateAliases(array $aliasActions): array;

    /**
     * Perform a bulk operation in Elasticsearch.
     *
     * @param array $bulkParams Bulk operation parameters.
     *
     * @return array Bulk operation response.
     */
    public function bulk(array $bulkParams): array;

    /**
     * Perform text analysis in Elasticsearch.
     *
     * @param array $params Analysis parameters.
     *
     * @return array Analysis results.
     */
    public function analyze(array $params): array;

    /**
     * Retrieve statistics for an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     *
     * @throws NotFoundException If the index does not exist.
     *
     * @return array Index statistics.
     */
    public function indexStats(string $indexName): array;

    /**
     * Perform term vectors operation in Elasticsearch.
     *
     * @param array $params Term vectors parameters.
     *
     * @return array Term vectors response.
     */
    public function termvectors(array $params): array;

    /**
     * Perform multi term vectors operation in Elasticsearch.
     *
     * @param array $params Multi term vectors parameters.
     *
     * @return array Multi term vectors response.
     */
    public function mtermvectors(array $params): array;

    /**
     * Reindex data in Elasticsearch.
     *
     * @param array $params Reindexing parameters.
     *
     * @return array Reindexing response.
     */
    public function reindex(array $params): array;

    /**
     * Retrieve all documents from an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     * @param array $query Optional query parameters to filter documents.
     *
     * @throws NotFoundException If an error occurs while retrieving documents.
     *
     * @return array Array of documents found in the index.
     */
    public function getAllDocuments(string $indexName, array $query = []): array;

    /**
     * Retrieve a document from an Elasticsearch index by ID.
     *
     * @param string $indexName Name of the index.
     * @param string $id Document ID.
     *
     * @throws NotFoundException If an error occurs while retrieving the document.
     *
     * @return array|null Document data if found, null if not found.
     */
    public function getDocument(string $indexName, string $id): ?array;

    /**
     * Add a document to an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     * @param array $document Document data.
     * @param string|null $id Document ID.
     *
     * @return array Response from Elasticsearch.
     */
    public function addDocument(string $indexName, array $document, ?string $id): array;

    /**
     * Update a document in an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     * @param string $id Document ID.
     * @param array $document Document data.
     *
     * @throws NotFoundException If the document does not exist.
     *
     * @return array Response from Elasticsearch.
     */
    public function updateDocument(string $indexName, string $id, array $document): array;

    /**
     * Delete a document from an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     * @param string $id Document ID.
     *
     * @throws NotFoundException If the document does not exist.
     *
     * @return array Response from Elasticsearch.
     */
    public function deleteDocument(string $indexName, string $id): array;
}
