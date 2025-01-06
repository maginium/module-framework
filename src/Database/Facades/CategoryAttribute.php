<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Facades;

use Illuminate\Support\Traits\Macroable;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Catalog\Model\Category;
use Magento\Eav\Model\AttributeManagement;
use Magento\Eav\Model\AttributeRepository;
use Magento\Eav\Model\AttributeSetRepository;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ResourceConnection;
use Maginium\Framework\Database\Services\EavAttribute;

/**
 * CategoryAttribute class for managing category attributes.
 *
 * This class extends the EavAttribute class and provides methods for managing
 * category-specific attributes, including mass updating attribute values across multiple categories.
 */
class CategoryAttribute extends EavAttribute
{
    use Macroable;

    /**
     * The model type code for category attributes.
     */
    public const ENTITY_TYPE = CategoryAttributeInterface::ENTITY_TYPE_CODE;

    /**
     * The category repository for fetching and saving category models.
     *
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * Constructor for the CategoryAttribute class.
     *
     * Initializes the CategoryAttribute class with the necessary dependencies for managing
     * category-specific attributes and interacting with the category repository.
     *
     * @param Config $config The configuration object containing settings for the EAV system.
     * @param EavSetupFactory $eavSetupFactory The factory used for setting up EAV attributes.
     * @param ResourceConnection $resourceConnection The resource connection to interact with the database.
     * @param AttributeManagement $attributeManagement The manager for handling attribute configurations.
     * @param AttributeSetRepository $attributeSetRepository The repository for handling attribute sets.
     * @param AttributeRepository $attributeRepository The repository for handling attribute.
     * @param CategoryRepositoryInterface $categoryRepository The repository used to manage category models.
     */
    public function __construct(
        Config $config,
        EavSetupFactory $eavSetupFactory,
        ResourceConnection $resourceConnection,
        AttributeManagement $attributeManagement,
        AttributeSetRepository $attributeSetRepository,
        AttributeRepository $attributeRepository,
        CategoryRepositoryInterface $categoryRepository,
    ) {
        // Call the parent constructor to initialize shared dependencies
        parent::__construct(
            $config,
            $eavSetupFactory,
            $resourceConnection,
            $attributeManagement,
            $attributeRepository,
            $attributeSetRepository,
        );

        // Initialize the category repository for interacting with category models
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Mass update attribute values for multiple categories.
     *
     * This method allows updating the specified attribute values for multiple categories
     * at once by passing in an array of category IDs and corresponding data.
     * It iterates through each category ID, loads the category, adds the data, and saves the category.
     *
     * @todo Find a faster method for this operation, potentially similar to how product updates are handled.
     *
     * @param array $modelIds The list of category IDs to update.
     * @param array $data The data to update the categories with (e.g., attribute values).
     *
     * @return void
     */
    public function massUpdate(array $modelIds, array $data): void
    {
        // Iterate over each category ID to update its attribute values
        foreach ($modelIds as $categoryId) {
            /** @var Category $category */
            $category = $this->categoryRepository->get($categoryId);

            // Add the new data to the category
            $category->addData($data);

            // Save the updated category
            $this->categoryRepository->save($category);
        }
    }
}
