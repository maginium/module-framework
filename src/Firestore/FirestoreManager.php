<?php

declare(strict_types=1);

namespace Maginium\Framework\Firestore;

use Google\Cloud\Firestore\Client as Client;
use Google\Cloud\Firestore\CollectionReference;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Firestore\Interfaces\ClientInterface;
use Maginium\Framework\Firestore\Interfaces\FirestoreInterface;
use Maginium\Framework\Pagination\Constants\Paginator;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Str;

/**
 * Class FirestoreManager.
 *
 * Service class for interacting with Firestore.
 */
class FirestoreManager implements FirestoreInterface
{
    /**
     * @var Client The Firestore client instance.
     */
    protected Client $client;

    /**
     * Constructor.
     *
     * Initializes the Firestore client and sets up the logger.
     *
     * @param ClientInterface $client The Firestore client interface.
     */
    public function __construct(
        ClientInterface $client,
    ) {
        // Initialize Firestore client instance
        /** @var ClientInterface $client */
        $this->client = $client->getClient();

        // Set Logger class name for logging purposes
        Log::setClassName(static::class);
    }

    /**
     * Read documents from Firestore.
     *
     * This method retrieves documents from the specified Firestore collections.
     *
     * @param array $collections Array of collection names.
     * @param array $documentIds Array of document IDs corresponding to each collection.
     *
     * @throws LocalizedException If an error occurs while reading the documents.
     *
     * @return array Array of document data, indexed by collection/document IDs.
     */
    public function find(array $collections, array $documentIds): array
    {
        // Initialize an empty array to store results
        $result = [];

        try {
            // Iterate through each collection provided
            foreach ($collections as $key => $collection) {
                // Get the document ID corresponding to the current collection
                $documentId = $documentIds[$key];

                // Get a reference to the document in Firestore
                $docRef = $this->getCollectionReference($collection)->document($documentId);

                // Retrieve a snapshot of the document
                $snapshot = $docRef->snapshot();

                // Check if the document exists in Firestore
                if ($snapshot->exists()) {
                    // If document exists, store its data in the result array
                    $result[$collection][$documentId] = $snapshot->data();
                } else {
                    // If document doesn't exist, store null in the result array
                    $result[$collection][$documentId] = null;
                }
            }
        } catch (Exception $e) {
            // If any exception occurs during the process, catch it
            // and throw a localized exception with a descriptive error message.
            throw LocalizedException::make(
                __('Error reading documents from Firestore: %1', [$e->getMessage()]),
                $e,
            );
        }

        // Return the array containing document data indexed by collection/document IDs
        return $result;
    }

    /**
     * Read a document from Firestore.
     *
     * @param string $collection The name of the collection.
     * @param string $documentId The ID of the document to read.
     *
     * @return array|null The document data, or null if the document does not exist.
     */
    public function findOne(string $collection, string $documentId): ?array
    {
        try {
            // Get a reference to the document and retrieve its snapshot
            $docRef = $this->getCollectionReference($collection)->document($documentId);
            $snapshot = $docRef->snapshot();

            // Check if the document exists and return its data
            if ($snapshot->exists()) {
                return $snapshot->data();
            }

            return null;
        } catch (Exception $e) {
            // Throw a localized exception with an error message
            throw LocalizedException::make(
                __('Error reading document from Firestore: %1', [$e->getMessage()]),
                $e,
            );
        }
    }

    /**
     * Search documents in Firestore with filters and sorting.
     *
     * This method retrieves documents from the specified Firestore collection
     * based on the provided filters and sorts.
     *
     * @param string $collection The name of the collection to search.
     * @param array $filters Associative array of filters (field => value).
     * @param array $sorts Associative array of sorting options (field => direction).
     * @param int|null $limit Optional. Maximum number of documents to retrieve. Default is null (no limit).
     *
     * @throws LocalizedException If an error occurs while searching documents.
     *
     * @return array Array of document data matching the search criteria.
     */
    public function where(string $collection, array $filters = [], array $sorts = [], ?int $limit = Paginator::DEFAULT_PER_PAGE): array
    {
        try {
            // Start building the Firestore query
            $query = $this->client->collection($collection);

            // Apply filters to the query
            foreach ($filters as $field => $value) {
                $query = $query->where($field, '=', $value);
            }

            // Apply sorting to the query
            foreach ($sorts as $field => $direction) {
                // $direction should be 'asc' or 'desc'
                $query = $query->orderBy($field, $direction);
            }

            // Apply limit to the query if provided
            if ($limit !== null) {
                $query = $query->limit($limit);
            }

            // Execute the query and retrieve documents
            $documents = $query->documents();
            $result = [];

            // Iterate through the documents and collect their data
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $result[] = $document->data();
                }
            }

            return $result;
        } catch (Exception $e) {
            // If any exception occurs during the process, catch it
            // and throw a localized exception with a descriptive error message.
            throw LocalizedException::make(
                __('Error searching documents in Firestore: %1', [$e->getMessage()]),
                $e,
            );
        }
    }

    /**
     * Create documents in Firestore.
     *
     * This method creates new documents in the specified Firestore collection.
     *
     * @param array $collections Array of collection names.
     * @param array $documentIds Array of document IDs corresponding to each collection.
     * @param array $data Array of data to store in the documents. Each element corresponds to a document.
     *
     * @throws LocalizedException If an error occurs while creating the documents.
     */
    public function create(array $collections, array $documentIds, array $data): void
    {
        try {
            // Iterate through each collection provided
            foreach ($collections as $key => $collection) {
                // Get the document ID corresponding to the current collection
                $documentId = $documentIds[$key];

                // Get a reference to the document in Firestore
                $docRef = $this->getCollectionReference($collection)->document($documentId);

                // Set the data for the document
                $docRef->set($data[$key]);
            }
        } catch (Exception $e) {
            // If any exception occurs during the process, catch it
            // and throw a localized exception with a descriptive error message.
            throw LocalizedException::make(
                __('Error creating documents in Firestore: %1', [$e->getMessage()]),
                $e,
            );
        }
    }

    /**
     * Update documents in Firestore.
     *
     * This method updates existing documents in the specified Firestore collections.
     * If the document does not exist, it creates a new one.
     *
     * @param array $collections Array of collection names.
     * @param array $documentIds Array of document IDs corresponding to each collection.
     * @param array $data Array of data to update in the documents. Each element corresponds to a document.
     *
     * @throws LocalizedException If an error occurs while updating the documents.
     */
    public function update(array $collections, array $documentIds, array $data): void
    {
        try {
            // Iterate through each collection provided
            foreach ($collections as $key => $collection) {
                // Get the document ID corresponding to the current collection
                $documentId = $documentIds[$key];

                // Get a reference to the document in Firestore
                $docRef = $this->getCollectionReference($collection)->document($documentId);

                // Check if the document exists
                $snapshot = $docRef->snapshot();

                if ($snapshot->exists()) {
                    // Document exists, perform update
                    $updateData = [];

                    foreach ($data[$key] as $field => $value) {
                        $updateData[] = ['path' => $field, 'value' => $value];
                    }
                    $docRef->update($updateData);
                } else {
                    // Document does not exist, create a new document
                    $docRef->set($data[$key]);
                }
            }
        } catch (Exception $e) {
            // If any exception occurs during the process, catch it
            // and throw a localized exception with a descriptive error message.
            throw LocalizedException::make(
                __('Error updating documents in Firestore: %1', [$e->getMessage()]),
                $e,
            );
        }
    }

    /**
     * Delete documents from Firestore.
     *
     * This method deletes documents from the specified Firestore collections.
     *
     * @param array $collections Array of collection names.
     * @param array $documentIds Array of document IDs corresponding to each collection.
     *
     * @throws LocalizedException If an error occurs while deleting the documents.
     */
    public function delete(array $collections, array $documentIds): void
    {
        try {
            // Iterate through each collection provided
            foreach ($collections as $key => $collection) {
                // Get the document ID corresponding to the current collection
                $documentId = $documentIds[$key];

                // Get a reference to the document in Firestore
                $docRef = $this->getCollectionReference($collection)->document($documentId);

                // Delete the document from Firestore
                $docRef->delete();
            }
        } catch (Exception $e) {
            // If any exception occurs during the process, catch it
            // and throw a localized exception with a descriptive error message.
            throw LocalizedException::make(
                __('Error deleting documents from Firestore: %1', [$e->getMessage()]),
                $e,
            );
        }
    }

    /**
     * Create a document in Firestore.
     *
     * This method creates a new document in the specified Firestore collection.
     *
     * @param string $collection The name of the collection.
     * @param string $documentId The ID of the document to create.
     * @param array $data The data to store in the document.
     *
     * @throws LocalizedException If an error occurs while creating the document.
     */
    public function createOne($collection, $documentId, $data): void
    {
        try {
            // Get a reference to the document and set its data
            $docRef = $this->getCollectionReference($collection)->document($documentId);
            $docRef->set($data);
        } catch (Exception $e) {
            // Throw a localized exception with an error message
            throw LocalizedException::make(
                __('Error creating document in Firestore: %1', [$e->getMessage()]),
                $e,
            );
        }
    }

    /**
     * Update a document in Firestore.
     *
     * This method updates an existing document in the specified Firestore collection.
     * If the document does not exist, it creates a new one.
     *
     * @param string $collection The name of the collection.
     * @param string $documentId The ID of the document to update.
     * @param array $data The data to update in the document.
     *
     * @throws LocalizedException If an error occurs while updating the document.
     */
    public function updateOne(string $collection, string $documentId, array $data): void
    {
        try {
            // Get a reference to the document in Firestore
            $docRef = $this->getCollectionReference($collection)->document($documentId);

            // Check if the document exists
            $snapshot = $docRef->snapshot();

            if ($snapshot->exists()) {
                // Document exists, perform update
                $updateData = [];

                foreach ($data as $key => $value) {
                    $updateData[] = ['path' => $key, 'value' => $value];
                }
                $docRef->update($updateData);
            } else {
                // Document does not exist, create a new document
                $docRef->set($data);
            }
        } catch (Exception $e) {
            // If any exception occurs during the process, catch it
            // and throw a localized exception with a descriptive error message.
            throw LocalizedException::make(
                __('Error updating document in Firestore: %1', [$e->getMessage()]),
                $e,
            );
        }
    }

    /**
     * Delete a document from Firestore.
     *
     * This method deletes a document from the specified Firestore collection.
     *
     * @param string $collection The name of the collection.
     * @param string $documentId The ID of the document to delete.
     *
     * @throws LocalizedException If an error occurs while deleting the document.
     */
    public function deleteOne($collection, $documentId): void
    {
        try {
            // Get a reference to the document and delete it
            $docRef = $this->getCollectionReference($collection)->document($documentId);
            $docRef->delete();
        } catch (Exception $e) {
            // Throw a localized exception with an error message
            throw LocalizedException::make(
                __('Error deleting document from Firestore: %1', [$e->getMessage()]),
                $e,
            );
        }
    }

    /**
     * Get all documents from Firestore collections.
     *
     * This method retrieves all documents from the specified Firestore collections.
     *
     * @param array $collections Array of collection names.
     *
     * @throws LocalizedException If an error occurs while retrieving the documents.
     *
     * @return array Array of document data, indexed by collection name.
     */
    public function all(array $collections): array
    {
        // Initialize an empty array to store the result
        $result = [];

        try {
            foreach ($collections as $collection) {
                // Get a reference to the collection in Firestore and retrieve all documents
                $documents = $this->getCollectionReference($collection)->documents();

                // Initialize an empty array to store document data
                $data = [];

                // Iterate through each document in the collection
                foreach ($documents as $document) {
                    if ($document->exists()) {
                        // If the document exists, store its data indexed by document ID
                        $data[$document->id()] = $document->data();
                    }
                }

                // Store the collected data in the result array indexed by collection name
                $result[$collection] = $data;
            }
        } catch (Exception $e) {
            // If any exception occurs during the process, catch it
            // and throw a localized exception with a descriptive error message.
            throw LocalizedException::make(
                __('Error retrieving documents from Firestore collections: %1', [$e->getMessage()]),
                $e,
            );
        }

        // Return the array containing all document data indexed by collection name
        return $result;
    }

    /**
     * Get all documents from a Firestore collection.
     *
     * This method retrieves all documents from the specified Firestore collection.
     *
     * @param string $collection The name of the collection.
     *
     * @throws LocalizedException If an error occurs while retrieving the documents.
     *
     * @return array The data of all documents in the collection.
     */
    public function allFrom($collection): array
    {
        try {
            // Get all documents in the collection
            $documents = $this->getCollectionReference($collection)->documents();
            $data = [];

            // Iterate through the documents and collect their data
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data[] = $document->data();
                }
            }

            return $data;
        } catch (Exception $e) {
            // Throw a localized exception with an error message
            throw LocalizedException::make(
                __('Error retrieving documents from Firestore collection: %1', [$e->getMessage()]),
                $e,
            );
        }
    }

    /**
     * Resolve the Firestore collection reference path.
     *
     * This method resolves the dot-notated path to a Firestore collection reference,
     * navigating through nested collections if necessary.
     *
     * @param string $path The dot-notated path of the collection.
     *
     * @return CollectionReference The resolved collection reference.
     */
    protected function getCollectionReference(string $path)
    {
        // Check if the path already contains slashes
        if (! Str::contains($path, SP)) {
            // Replace dots with slashes to navigate through nested collections
            $path = Str::replace('.', SP, $path);
        }

        // Return the collection reference
        return $this->client->collection($path);
    }
}
