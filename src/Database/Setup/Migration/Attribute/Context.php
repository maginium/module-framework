<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Setup\Migration\Attribute;

use Illuminate\Support\Traits\Macroable;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\State;
use Magento\Framework\Setup\ModuleDataSetupInterface as ModuleDataSetup;
use Magento\Framework\Setup\Patch\PatchHistory;
use Magento\Framework\Setup\SchemaSetupInterface;
use Maginium\Framework\Database\Facades\AdminConfig;
use Maginium\Framework\Database\Facades\CategoryAttribute;
use Maginium\Framework\Database\Facades\CustomerAttribute;
use Maginium\Framework\Database\Facades\ProductAttribute;
use Maginium\Framework\Database\Setup\Migration\Context as BaseContext;

/**
 * Context class for managing EAV (Entity-Attribute-Value) attributes in Magento.
 *
 * This class is responsible for providing the necessary dependencies required
 * to manage and interact with EAV attributes. It includes services for EAV configurations,
 * attribute setup, database connection, and specific attribute handlers for products,
 * categories, and customers.
 */
class Context extends BaseContext
{
    use Macroable;

    /**
     * Handler for managing product-specific EAV attributes.
     *
     * @var ProductAttribute
     */
    private ProductAttribute $productAttribute;

    /**
     * Handler for managing category-specific EAV attributes.
     *
     * @var CategoryAttribute
     */
    private CategoryAttribute $categoryAttribute;

    /**
     * Handler for managing customer-specific EAV attributes.
     *
     * @var CustomerAttribute
     */
    private CustomerAttribute $customerAttribute;

    /**
     * Constructor to initialize dependencies for patching operations.
     *
     * This constructor injects dependencies required for managing database setup,
     * EAV configurations, and patch history. It ensures the necessary services
     * are available for modifying model attributes and maintaining a stable Magento setup.
     *
     * @param State $state The current application state, used to retrieve store and configuration information.
     * @param EavConfig $config Configuration manager for EAV attributes, enabling modifications for models.
     * @param AdminConfig $adminConfig Manages admin-specific configurations for Magento.
     * @param PatchHistory $patchHistory Tracks the history of applied patches to prevent duplicate executions.
     * @param ModuleDataSetup $moduleDataSetup Provides methods for interacting with database setup operations.
     * @param SchemaSetupInterface $schemaSetup Interface for schema setup operations in the database.
     * @param ProductAttribute $productAttribute Handler for product-specific EAV attribute management.
     * @param CategoryAttribute $categoryAttribute Handler for category-specific EAV attribute management.
     * @param CustomerAttribute $customerAttribute Handler for customer-specific EAV attribute management.
     */
    public function __construct(
        State $state,
        EavConfig $config,
        AdminConfig $adminConfig,
        PatchHistory $patchHistory,
        ModuleDataSetup $moduleDataSetup,
        SchemaSetupInterface $schemaSetup,
        ProductAttribute $productAttribute,
        CategoryAttribute $categoryAttribute,
        CustomerAttribute $customerAttribute,
    ) {
        // Call the parent constructor to initialize inherited dependencies.
        parent::__construct($state, $config, $adminConfig, $patchHistory, $moduleDataSetup, $schemaSetup);

        // Assign dependencies for managing EAV attributes.
        $this->productAttribute = $productAttribute;
        $this->categoryAttribute = $categoryAttribute;
        $this->customerAttribute = $customerAttribute;
    }

    /**
     * Get the product attribute handler.
     *
     * This method returns the `ProductAttribute` service, which is responsible
     * for managing EAV attributes related to products.
     *
     * @return ProductAttribute The product attribute handler instance.
     */
    public function getProductAttribute(): ProductAttribute
    {
        return $this->productAttribute;
    }

    /**
     * Get the category attribute handler.
     *
     * Provides access to the `CategoryAttribute` service, which manages EAV attributes
     * specific to categories.
     *
     * @return CategoryAttribute The category attribute handler instance.
     */
    public function getCategoryAttribute(): CategoryAttribute
    {
        return $this->categoryAttribute;
    }

    /**
     * Get the customer attribute handler.
     *
     * Returns the `CustomerAttribute` service, which is used to manage customer-specific
     * EAV attributes in Magento.
     *
     * @return CustomerAttribute The customer attribute handler instance.
     */
    public function getCustomerAttribute(): CustomerAttribute
    {
        return $this->customerAttribute;
    }
}
