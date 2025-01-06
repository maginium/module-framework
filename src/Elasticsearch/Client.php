<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch;

use Elasticsearch\Client as ESClient;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Elasticsearch\Interfaces\ClientInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Uuid;

/**
 * Class Client.
 *
 * Provides methods to interact with Elasticsearch using the official Elasticsearch client.
 */
class Client implements ClientInterface
{
    /**
     * @var ESClient|null
     */
    protected $client = null;

    /**
     * @var ClientBuilder
     */
    protected $clientBuilder;

    /**
     * ESClient constructor.
     *
     * @param ClientBuilder $clientBuilder ES client builder.
     */
    public function __construct(
        ClientBuilder $clientBuilder,
    ) {
        $this->clientBuilder = $clientBuilder;

        if ($this->client === null) {
            $this->client = $this->clientBuilder->build();
        }
    }

    /**
     * Perform a search operation in Elasticsearch.
     *
     * @param array $params Search parameters.
     *
     * @return array Search results.
     */
    public function search(array $params): array
    {
        return $this->client->search($params);
    }

    /**
     * Gets the Elasticsearch client instance.
     *
     * This method returns the Elasticsearch client, building it if necessary.
     *
     * @return ESClient|null The Elasticsearch client instance.
     */
    public function getClient(): ?ESClient
    {
        return $this->client;
    }

    /**
     * Retrieve information about the Elasticsearch cluster.
     *
     * @return array Elasticsearch cluster information.
     */
    public function info(): array
    {
        return $this->client->info();
    }

    /**
     * Ping the Elasticsearch cluster to check if it is reachable.
     *
     * @return bool True if the cluster is reachable, false otherwise.
     */
    public function ping(): bool
    {
        return $this->client->ping();
    }

    /**
     * Retrieve all indexes in Elasticsearch.
     *
     * @return array List of index names.
     */
    public function getIndexes(): array
    {
        return $this->client->cat()->indices();
    }

    /**
     * Retrieve details about a specific index in Elasticsearch.
     *
     * @param string $indexName Name of the index.
     *
     * @throws Missing404Exception If the index does not exist.
     *
     * @return array Index details.
     */
    public function getIndex(string $indexName): array
    {
        return $this->client->indices()->get(['index' => $indexName]);
    }

    /**
     * Create an Elasticsearch index with specified settings.
     *
     * @param string $indexName Name of the index to create.
     * @param array $indexSettings Index settings.
     */
    public function createIndex(string $indexName, array $indexSettings): array
    {
        return $this->client->indices()->create(['index' => $indexName, 'body' => $indexSettings]);
    }

    /**
     * Clean an Elasticsearch index.
     *
     * @param string $indexName Name of the index to clean.
     */
    public function cleanIndex(string $indexName): array
    {
        return $this->client->indices()->flush(['index' => $indexName]);
    }

    /**
     * Delete an Elasticsearch index.
     *
     * @param string $indexName Name of the index to delete.
     */
    public function deleteIndex(string $indexName): array
    {
        return $this->client->indices()->delete(['index' => $indexName]);
    }

    /**
     * Check if an index exists in Elasticsearch.
     *
     * @param string $indexName Name of the index to check.
     *
     * @return bool True if the index exists, false otherwise.
     */
    public function indexExists(string $indexName): bool
    {
        return $this->client->indices()->exists(['index' => $indexName]);
    }

    /**
     * Set settings for an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     * @param array $indexSettings Index settings to set.
     */
    public function putIndexSettings(string $indexName, array $indexSettings): array
    {
        return $this->client->indices()->putSettings(['index' => $indexName, 'body' => $indexSettings]);
    }

    /**
     * Set mapping for an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     * @param array $mapping Mapping settings.
     */
    public function putMapping(string $indexName, array $mapping): array
    {
        return $this->client->indices()->putMapping(['index' => $indexName, 'body' => $mapping]);
    }

    /**
     * Retrieve mapping for an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     *
     * @return array Mapping information.
     */
    public function getMapping(string $indexName): array
    {
        return $this->client->indices()->getMapping(['index' => $indexName]);
    }

    /**
     * Retrieve settings for an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     *
     * @return array Index settings.
     */
    public function getSettings(string $indexName): array
    {
        return $this->client->indices()->getSettings(['index' => $indexName]);
    }

    /**
     * Perform a force merge on an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     */
    public function forceMerge(string $indexName): array
    {
        return $this->client->indices()->forceMerge(['index' => $indexName]);
    }

    /**
     * Refresh an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     */
    public function refreshIndex(string $indexName): array
    {
        return $this->client->indices()->refresh(['index' => $indexName]);
    }

    /**
     * Retrieve all indices associated with a given alias.
     *
     * @param string $indexAlias Alias name.
     *
     * @return array List of index names.
     */
    public function getIndicesNameByAlias(string $indexAlias): array
    {
        $indices = [];

        try {
            $indices = $this->client->indices()->getMapping(['index' => $indexAlias]);
        } catch (Missing404Exception $e) {
            // Handle 404 error (index not found)
        }

        return Arr::keys($indices);
    }

    /**
     * Retrieve aliases associated with Elasticsearch indices.
     *
     * @param array $params Optional parameters.
     *
     * @return array List of aliases.
     */
    public function getIndexAliases(array $params = []): array
    {
        return $this->client->indices()->getAlias($params);
    }

    /**
     * Update aliases for Elasticsearch indices.
     *
     * @param array $aliasActions Alias actions to perform.
     */
    public function updateAliases(array $aliasActions): array
    {
        return $this->client->indices()->updateAliases(['body' => ['actions' => $aliasActions]]);
    }

    /**
     * Perform a bulk operation in Elasticsearch.
     *
     * @param array $bulkParams Bulk operation parameters.
     *
     * @return array Bulk operation response.
     */
    public function bulk(array $bulkParams): array
    {
        return $this->client->bulk($bulkParams);
    }

    /**
     * Perform text analysis in Elasticsearch.
     *
     * @param array $params Analysis parameters.
     *
     * @return array Analysis results.
     */
    public function analyze(array $params): array
    {
        return $this->client->indices()->analyze($params);
    }

    /**
     * Retrieve statistics for an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     *
     * @throws Missing404Exception If the index does not exist.
     *
     * @return array Index statistics.
     */
    public function indexStats(string $indexName): array
    {
        try {
            return $this->client->indices()->stats(['index' => $indexName]);
        } catch (Exception $e) {
            throw new Missing404Exception($e->getMessage());
        }
    }

    /**
     * Perform term vectors operation in Elasticsearch.
     *
     * @param array $params Term vectors parameters.
     *
     * @return array Term vectors response.
     */
    public function termvectors(array $params): array
    {
        return $this->client->termvectors($params);
    }

    /**
     * Perform multi term vectors operation in Elasticsearch.
     *
     * @param array $params Multi term vectors parameters.
     *
     * @return array Multi term vectors response.
     */
    public function mtermvectors(array $params): array
    {
        return $this->client->mtermvectors($params);
    }

    /**
     * Reindex data in Elasticsearch.
     *
     * @param array $params Reindexing parameters.
     *
     * @return array Reindexing response.
     */
    public function reindex(array $params): array
    {
        return $this->client->reindex($params);
    }

    /**
     * Retrieve all documents from an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     * @param array $query Optional query parameters to filter documents.
     *
     * @throws Missing404Exception If an error occurs while retrieving documents.
     *
     * @return array Array of documents found in the index.
     */
    public function getAllDocuments(string $indexName, array $query = []): array
    {
        try {
            $params = [
                'index' => $indexName,
                'body' => [
                    'query' => $query,
                ],
            ];

            $response = $this->client->search($params);

            $documents = [];

            foreach ($response['hits']['hits'] as $hit) {
                $documents[] = $hit['_source'];
            }

            return $documents;
        } catch (Exception $e) {
            throw Exception::make('Error retrieving documents: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a document from an Elasticsearch index by ID.
     *
     * @param string $indexName Name of the index.
     * @param string $id Document ID.
     *
     * @throws Missing404Exception If an error occurs while retrieving the document.
     *
     * @return array|null Document data if found, null if not found.
     */
    public function getDocument(string $indexName, string $id): ?array
    {
        try {
            $response = $this->client->get([
                'index' => $indexName,
                'id' => $id,
            ]);

            return $response['_source'] ?? null;
        } catch (Missing404Exception $e) {
            return null; // Return null if document is not found
        } catch (Exception $e) {
            throw Exception::make('Error retrieving document: ' . $e->getMessage());
        }
    }

    /**
     * Add a document to an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     * @param array $document Document data.
     * @param string|null $id Document ID.
     *
     * @return array Response from Elasticsearch.
     */
    public function addDocument(string $indexName, array $document, ?string $id = null): array
    {
        return $this->client->index([
            'index' => $indexName,
            'id' => $id ?? Uuid::generate(),
            'body' => $document,
        ]);
    }

    /**
     * Update a document in an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     * @param string $id Document ID.
     * @param array $document Document data.
     *
     * @throws Missing404Exception If the document does not exist.
     *
     * @return array Response from Elasticsearch.
     */
    public function updateDocument(string $indexName, string $id, array $document): array
    {
        try {
            return $this->client->update([
                'index' => $indexName,
                'id' => $id,
                'body' => ['doc' => $document],
            ]);
        } catch (Missing404Exception $e) {
            throw $e; // Rethrow the exception to handle document not found case
        }
    }

    /**
     * Delete a document from an Elasticsearch index.
     *
     * @param string $indexName Name of the index.
     * @param string $id Document ID.
     *
     * @throws Missing404Exception If the document does not exist.
     *
     * @return array Response from Elasticsearch.
     */
    public function deleteDocument(string $indexName, string $id): array
    {
        try {
            return $this->client->delete([
                'index' => $indexName,
                'id' => $id,
            ]);
        } catch (Missing404Exception $e) {
            throw $e; // Rethrow the exception to handle document not found case
        }
    }

    /**
     * Dynamically handles method calls to the Redis client.
     *
     * Delegates method calls to the Redis client if the method is not defined in the manager.
     *
     * @param  string $method The name of the method being called.
     * @param  array $parameters The parameters passed to the method.
     *
     * @return mixed The result of the method call on the Redis client.
     */
    public function __call(string $method, array $parameters)
    {
        // Call the method on the Redis client instance
        return $this->client->{$method}(...$parameters);
    }
}
