<?php

declare(strict_types=1);

namespace Maginium\Framework\Firestore\Facades;

use Maginium\Framework\Firestore\Interfaces\FirestoreInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Firestore service.
 *
 * This facade provides a simplified interface for interacting with the Firestore service.
 *
 * @method static array find(array $collections, array $documentIds) Retrieve documents from Firestore.
 * @method static array|null findOne(string $collection, string $documentId) Retrieve a single document from Firestore.
 * @method static array where(string $collection, array $filters = [], array $sorts = [], ?int $limit = null) Search documents with filters and sorting.
 * @method static void create(array $collections, array $documentIds, array $data) Create documents in Firestore.
 * @method static void update(array $collections, array $documentIds, array $data) Update documents in Firestore.
 * @method static void delete(array $collections, array $documentIds) Delete documents from Firestore.
 * @method static void createOne(string $collection, string $documentId, array $data) Create a single document in Firestore.
 * @method static void updateOne(string $collection, string $documentId, array $data) Update a single document in Firestore.
 * @method static void deleteOne(string $collection, string $documentId) Delete a single document from Firestore.
 * @method static array all(array $collections) Retrieve all documents from Firestore collections.
 * @method static array allFrom(string $collection) Retrieve all documents from a Firestore collection.
 *
 * @see FirestoreInterface
 */
class Firestore extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return FirestoreInterface::class;
    }
}
