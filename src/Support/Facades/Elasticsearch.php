<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Elasticsearch\Interfaces\ElasticsearchInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Elasticsearch service.
 *
 * This class acts as a simplified interface to access the ElasticsearchInterface.
 * By extending AbstractFacade, it inherits basic functionality for service access.
 *
 * @method static array getIndexes()
 *     Retrieves a list of all indexes.
 * @method static Indexes|array getIndex(string $indexName)
 *     Retrieves information about a specific index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index.
 * @method static array createIndex(string $indexName, $primaryKey = null)
 *     Creates a new Elasticsearch index with optional primary key configuration.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index to create.
 *     - $primaryKey: Optional primary key configuration for the index.
 * @method static array updateIndex(string $indexName, array $options = [])
 *     Updates settings for an existing Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index to update.
 *     - $options: Optional settings to update for the index.
 * @method static Indexes|array cleanIndex(string $indexName)
 *     Cleans (deletes and recreates) an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index to clean.
 * @method static mixed deleteIndex(string $indexName)
 *     Deletes an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index to delete.
 * @method static array search(array $searchParams, string $indexName, ?string $query = null, array $options = [])
 *     Performs a search query on an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index to search.
 *     - $query: Optional query string for the search.
 *     - $searchParams: Additional search parameters.
 *     - $options: Additional options for the search.
 * @method static array indexDocument(string $indexName, array $document)
 *     Indexes a single document into an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index.
 *     - $document: The document data to index.
 * @method static array indexDocuments(string $indexName, array $documents)
 *     Indexes multiple documents into an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index.
 *     - $documents: An array of documents to index.
 * @method static Indexes|null getDocument(string $indexName, int $documentId)
 *     Retrieves a specific document from an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index.
 *     - $documentId: The ID of the document to retrieve.
 * @method static mixed|null getAllDocuments(string $indexName, ?DocumentsQuery $options = null)
 *     Retrieves all documents from an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index.
 *     - $options: Optional query parameters for filtering.
 * @method static array updateDocument(string $indexName, int $documentId, array $data)
 *     Updates a document in an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index.
 *     - $documentId: The ID of the document to update.
 *     - $data: The updated data for the document.
 * @method static array deleteDocument(string $indexName, int $documentId)
 *     Deletes a specific document from an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index.
 *     - $documentId: The ID of the document to delete.
 * @method static array deleteDocuments(string $indexName, int $documentIds)
 *     Deletes multiple documents from an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index.
 *     - $documentIds: An array of document IDs to delete.
 * @method static ?array deleteAllDocuments(string $indexName)
 *     Deletes all documents from an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index.
 * @method static array getIndexSettings(string $indexName)
 *     Retrieves settings for an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index.
 * @method static array updateIndexSettings(string $indexName, array $settings)
 *     Updates settings for an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index.
 *     - $settings: The settings to update for the index.
 * @method static array getIndexStats(string $indexName)
 *     Retrieves statistics for an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index.
 * @method static array updateDocuments(string $indexName, array $documents, int $timeout = 30)
 *     Updates multiple documents in an Elasticsearch index.
 *     Parameters:
 *     - $indexName: The name of the Elasticsearch index.
 *     - $documents: An array of documents to update.
 *     - $timeout: Optional timeout for the operation (default is 30 seconds).
 *
 * @see ElasticsearchInterface
 */
class Elasticsearch extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return ElasticsearchInterface::class;
    }
}
