<?php

declare(strict_types=1);

namespace Maginium\Framework\Firestore\Interfaces;

use Maginium\Foundation\Exceptions\LocalizedException;

/**
 * Interface FirestoreServiceInterface.
 *
 * Interface for Firestore service operations.
 */
interface FirestoreInterface
{
    /**
     * The event name for creating data in Firestore.
     *
     * This event is triggered when a new document or record is created in Firestore.
     * It is typically used for initializing data and adding it to the Firestore database.
     */
    public const FIRESTORE_CREATE_EVENT = 'firestore_create_event';

    /**
     * The event name for updating data in Firestore.
     *
     * This event is triggered when an existing document or record is updated in Firestore.
     * It is used for modifying data that already exists in the database.
     */
    public const FIRESTORE_UPDATE_EVENT = 'firestore_update_event';

    /**
     * The event name for deleting data from Firestore.
     *
     * This event is triggered when a document or record is deleted from Firestore.
     * It is used to remove data from the database when it is no longer needed.
     */
    public const FIRESTORE_DELETE_EVENT = 'firestore_delete_event';

    /**
     * Retrieve documents from Firestore.
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
    public function find(array $collections, array $documentIds): array;

    /**
     * Retrieve a single document from Firestore.
     *
     * This method retrieves a document from the specified Firestore collection.
     *
     * @param string $collection The name of the collection.
     * @param string $documentId The ID of the document to retrieve.
     *
     * @throws LocalizedException If an error occurs while reading the document.
     *
     * @return array|null The document data, or null if the document does not exist.
     */
    public function findOne(string $collection, string $documentId): ?array;

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
    public function where(string $collection, array $filters = [], array $sorts = [], ?int $limit = null): array;

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
    public function create(array $collections, array $documentIds, array $data): void;

    /**
     * Update documents in Firestore.
     *
     * This method updates existing documents in the specified Firestore collections.
     *
     * @param array $collections Array of collection names.
     * @param array $documentIds Array of document IDs corresponding to each collection.
     * @param array $data Array of data to update in the documents. Each element corresponds to a document.
     *
     * @throws LocalizedException If an error occurs while updating the documents.
     */
    public function update(array $collections, array $documentIds, array $data): void;

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
    public function delete(array $collections, array $documentIds): void;

    /**
     * Create a single document in Firestore.
     *
     * This method creates a new document in the specified Firestore collection.
     *
     * @param string $collection The name of the collection.
     * @param string $documentId The ID of the document to create.
     * @param array $data The data to store in the document.
     *
     * @throws LocalizedException If an error occurs while creating the document.
     */
    public function createOne(string $collection, string $documentId, array $data): void;

    /**
     * Update a single document in Firestore.
     *
     * This method updates an existing document in the specified Firestore collection.
     *
     * @param string $collection The name of the collection.
     * @param string $documentId The ID of the document to update.
     * @param array $data The data to update in the document.
     *
     * @throws LocalizedException If an error occurs while updating the document.
     */
    public function updateOne(string $collection, string $documentId, array $data): void;

    /**
     * Delete a single document from Firestore.
     *
     * This method deletes a document from the specified Firestore collection.
     *
     * @param string $collection The name of the collection.
     * @param string $documentId The ID of the document to delete.
     *
     * @throws LocalizedException If an error occurs while deleting the document.
     */
    public function deleteOne(string $collection, string $documentId): void;

    /**
     * Retrieve all documents from Firestore collections.
     *
     * This method retrieves all documents from the specified Firestore collections.
     *
     * @param array $collections Array of collection names.
     *
     * @throws LocalizedException If an error occurs while retrieving the documents.
     *
     * @return array Array of document data, indexed by collection name.
     */
    public function all(array $collections): array;

    /**
     * Retrieve all documents from a Firestore collection.
     *
     * This method retrieves all documents from the specified Firestore collection.
     *
     * @param string $collection The name of the collection.
     *
     * @throws LocalizedException If an error occurs while retrieving the documents.
     *
     * @return array The data of all documents in the collection.
     */
    public function allFrom(string $collection): array;
}
