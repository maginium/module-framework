<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Services;

use Illuminate\Support\Traits\Macroable;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\BlockRepository;
use Magento\Cms\Model\Blocks;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\PageRepository;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as BlockCollectionFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Maginium\Foundation\Exceptions\LocalizedException;
use Throwable;

/**
 * Abstract CMS class that provides methods for managing CMS content such as Blocks and Pages.
 *
 * This class provides CRUD operations for CMS Blocks and Pages through the use of
 * Magento's factory and repository classes. It includes methods to create, update,
 * retrieve, and delete CMS content, while also checking if the content exists.
 */
abstract class Cms
{
    use Macroable;

    /**
     * Constant representing the field identifier used in EAV (Entity-Attribute-Value) operations.
     * This is the key used to uniquely identify models or attributes.
     */
    public const FIELD_IDENTIFIER = 'identifier';

    /**
     * Constant representing the field store ID used in EAV operations.
     * This is the key that links an model or attribute to a specific store view in Magento.
     */
    public const FIELD_STORE_ID = 'store_id';

    /**
     * Constant representing an error message template when content cannot be found by identifier.
     * The error message includes a placeholder for the identifier, which is substituted during runtime.
     */
    public const ERROR_CONTENT_NOT_FOUND = 'Unable to find content with the identifier "%1".';

    /**
     * The collection factory used for retrieving CMS content collections (Blocks/Pages).
     *
     * @var BlockCollectionFactory|PageCollectionFactory
     */
    protected BlockCollectionFactory|PageCollectionFactory $collectionFactory;

    /**
     * The factory used for creating CMS content models (Blocks/Pages).
     *
     * @var BlockFactory|PageFactory
     */
    protected BlockFactory|PageFactory $factory;

    /**
     * The repository used for saving, retrieving, and deleting CMS content models (Blocks/Pages).
     *
     * @var BlockRepository|PageRepository
     */
    protected BlockRepository|PageRepository $repository;

    /**
     * Cms constructor.
     *
     * Initializes the collection factory, factory, and repository for handling CMS content.
     * This constructor allows dependency injection of the necessary services for CMS operations.
     *
     * @param BlockFactory|PageFactory $factory The factory for creating CMS content models.
     * @param BlockRepository|PageRepository $repository The repository for saving and retrieving CMS content.
     * @param BlockCollectionFactory|PageCollectionFactory $collectionFactory The collection factory for CMS content.
     */
    // phpcs:ignore
    public function __construct(
        BlockFactory|PageFactory $factory,
        BlockRepository|PageRepository $repository,
        BlockCollectionFactory|PageCollectionFactory $collectionFactory,
    ) {
        $this->factory = $factory;
        $this->repository = $repository;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Retrieves CMS content (Block or Page) by its identifier.
     *
     * This method will find the CMS content model by its identifier and return it.
     * If the content is not found, a LocalizedException will be thrown.
     *
     * @param string $identifier The identifier of the CMS content.
     * @param int|null $storeId The store ID to filter the content by (optional).
     *
     * @throws LocalizedException If the CMS content with the given identifier is not found.
     *
     * @return BlockInterface|PageInterface The CMS content model.
     */
    public function get(string $identifier, $storeId = null): BlockInterface|PageInterface
    {
        // Retrieve the ID of the CMS content model based on the identifier
        $id = $this->findId($identifier, $storeId);

        // Fetch and return the model by its ID
        return $this->repository->getById((string)$id);
    }

    /**
     * Creates a new CMS content (Block or Page) and saves it to the database.
     *
     * This method creates a new CMS content model and saves it to the database through
     * the corresponding CMS repository.
     *
     * @param string $identifier The identifier for the new CMS content.
     * @param array $data The data to set on the CMS content.
     * @param int|null $storeId The store ID to associate with the CMS content (optional).
     *
     * @return BlockInterface|PageInterface The created CMS content model.
     */
    public function create($identifier, $data, $storeId = null): BlockInterface|PageInterface
    {
        // Create a new CMS content model (Block or Page) using the factory.
        // The factory is responsible for creating an instance of the CMS content model.
        $model = $this->factory->create();

        // Set the unique identifier for the new CMS content model.
        // This identifier is typically used to reference the content programmatically.
        $model->setIdentifier($identifier);

        // Populate the CMS content model with the provided data.
        // The `addData` method sets multiple properties on the model at once.
        $model->addData($data);

        // Optionally, set the store ID for the CMS content.
        // If a store ID is provided, the content will be associated with that specific store.
        $model->setStoreId($storeId);

        // Save the model through the repository and return the saved model.
        // The repository handles the actual saving process to the database.
        return $this->repository->save($model);
    }

    /**
     * Updates the CMS content based on the given identifier.
     *
     * This method updates an existing CMS content model with new data. It will retrieve
     * the existing model by its identifier, apply the new data, and save it.
     *
     * @param string $identifier The identifier for the CMS content to update.
     * @param array $data The data to update the CMS content with.
     * @param int|null $storeId The store ID to filter the content by (optional).
     *
     * @return BlockInterface|PageInterface The updated CMS content model.
     */
    public function update($identifier, $data, $storeId = null): BlockInterface|PageInterface
    {
        // Retrieve the existing CMS content by identifier
        /** @var Block|Page $model */
        $model = $this->get($identifier, $storeId);

        // Add new data to the model
        $model->addData($data);

        // Save and return the updated model
        return $this->repository->save($model);
    }

    /**
     * Deletes CMS content by its identifier.
     *
     * This method deletes a CMS content model (Block or Page) from the database
     * using its identifier. It first retrieves the model ID and then deletes it.
     *
     * @param string $identifier The identifier for the CMS content to delete.
     * @param int|null $storeId The store ID to filter the content by (optional).
     *
     * @return bool Returns true if the content was deleted successfully, false otherwise.
     */
    public function delete($identifier, $storeId = null): bool
    {
        // Retrieve the ID of the CMS content model to delete
        $id = $this->findId($identifier, $storeId);

        // Delete the content using the repository and return the result
        return $this->repository->deleteById((string)$id);
    }

    /**
     * Creates CMS content only if it does not already exist.
     *
     * This method checks if the CMS content already exists. If it does, it retrieves
     * the existing content. If it does not exist, it creates and saves a new content model.
     *
     * @param string $identifier The identifier for the new CMS content.
     * @param array $data The data to set on the CMS content.
     * @param int|null $storeId The store ID to associate with the CMS content (optional).
     *
     * @return BlockInterface|PageInterface The existing or newly created CMS content model.
     */
    public function safeCreate($identifier, $data, $storeId = null): BlockInterface|PageInterface
    {
        // Check if the CMS content already exists
        if ($this->exists($identifier, $storeId)) {
            // Return the existing content if it exists
            return $this->get($identifier, $storeId);
        }

        // Create and save new content if it doesn't exist
        return $this->create($identifier, $data, $storeId);
    }

    /**
     * Updates CMS content only if it exists.
     *
     * This method checks if the CMS content exists. If it does, it updates the content.
     * If it doesn't exist, it returns null.
     *
     * @param string $identifier The identifier for the CMS content to update.
     * @param array $data The data to update the CMS content with.
     * @param int|null $storeId The store ID to filter the content by (optional).
     *
     * @return BlockInterface|PageInterface|null The updated CMS content model, or null if the content does not exist.
     */
    public function safeUpdate($identifier, $data, $storeId = null): BlockInterface|PageInterface|null
    {
        // Check if the CMS content exists before updating
        if (! $this->exists($identifier, $storeId)) {
            // Return null if the content does not exist
            return null;
        }

        // Update and return the existing content
        return $this->update($identifier, $data, $storeId);
    }

    /**
     * Checks if CMS content with the given identifier exists.
     *
     * This method attempts to find the CMS content model by its identifier and store ID.
     * If an exception is thrown, the content does not exist.
     *
     * @param string $identifier The identifier for the CMS content.
     * @param int|null $storeId The store ID to filter the content by (optional).
     *
     * @return bool Returns true if the content exists, false otherwise.
     */
    public function exists($identifier, $storeId = null): bool
    {
        try {
            // Try to find the content by identifier and store ID
            $this->findId($identifier, $storeId);
        } catch (Throwable $th) {
            // Return false if content is not found
            return false;
        }

        // Return true if content exists
        return true;
    }

    /**
     * Finds the CMS content ID based on the given identifier and store ID.
     *
     * This method retrieves the CMS content ID by filtering the collection with the identifier
     * and store ID (if provided). If no content is found, it throws a LocalizedException.
     *
     * @param string $identifier The identifier for the CMS content.
     * @param int|null $storeId The store ID to filter the content by (optional).
     *
     * @throws LocalizedException If the CMS content with the given identifier is not found.
     *
     * @return int The ID of the CMS content model.
     */
    protected function findId($identifier, $storeId = null): int
    {
        // Retrieve the collection of CMS content models (Blocks or Pages)
        $collection = $this->collectionFactory->create();

        // Filter by identifier
        $collection->addFieldToFilter(self::FIELD_IDENTIFIER, $identifier);

        if ($storeId !== null) {
            // Filter by store ID if provided
            $collection->addFieldToFilter(self::FIELD_STORE_ID, $storeId);
        }

        // Check if any content matches the filters
        if ($collection->getSize() === 0) {
            throw LocalizedException::make(__(
                self::ERROR_CONTENT_NOT_FOUND,
                $identifier,
            ));
        }

        // Return the first matching content ID
        return (int)$collection->getFirstItem()->getId();
    }
}
