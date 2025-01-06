<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Facades;

use Illuminate\Support\Traits\Macroable;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Eav\Model\AttributeManagement;
use Magento\Eav\Model\AttributeRepository;
use Magento\Eav\Model\AttributeSetRepository;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ResourceConnection;
use Maginium\Framework\Database\Services\EavAttribute;
use Maginium\Framework\Support\Arr;

/**
 * ProductAttribute class for managing product-specific EAV attributes in Magento.
 *
 * This class extends the EavAttribute class to handle product attributes specifically.
 * It provides methods for creating, assigning, and managing attributes related to products,
 * as well as handling mass updates, and managing attribute sets and groups.
 */
class ProductAttribute extends EavAttribute
{
    use Macroable;

    /**
     * Entity type for product attributes.
     *
     * This constant defines the model type for product attributes in Magento's EAV system.
     */
    public const ENTITY_TYPE = ProductAttributeInterface::ENTITY_TYPE_CODE;

    /**
     * Represents the service responsible for performing product-related actions.
     * This includes operations such as updating product attributes or performing bulk updates.
     *
     * @var ProductAction
     */
    private ProductAction $productAction;

    /**
     * Constructor for the ProductAttribute class.
     *
     * Initializes the necessary dependencies for managing product attributes, including
     * configuration, EAV setup, product actions, attribute management, and attribute sets.
     *
     * @param Config $config The configuration object containing settings for the EAV system.
     * @param EavSetupFactory $eavSetupFactory The factory used for setting up EAV attributes.
     * @param ProductAction $productAction The action handler for performing operations on product attributes.
     * @param ResourceConnection $resourceConnection The resource connection to interact with the database.
     * @param AttributeManagement $attributeManagement The manager for handling attribute configurations.
     * @param AttributeRepository $attributeRepository The repository for handling attribute.
     * @param AttributeSetRepository $attributeSetRepository The repository for handling attribute sets.
     */
    public function __construct(
        Config $config,
        ProductAction $productAction,
        EavSetupFactory $eavSetupFactory,
        ResourceConnection $resourceConnection,
        AttributeManagement $attributeManagement,
        AttributeRepository $attributeRepository,
        AttributeSetRepository $attributeSetRepository,
    ) {
        // Call the parent constructor to initialize common dependencies
        parent::__construct(
            $config,
            $eavSetupFactory,
            $resourceConnection,
            $attributeManagement,
            $attributeRepository,
            $attributeSetRepository,
        );

        // Initialize specific dependencies for managing product attributes
        $this->productAction = $productAction;
    }

    /**
     * Create a dropdown-type attribute for products.
     *
     * This method allows for quick creation of a product attribute that has a dropdown
     * list of values. It sets the attribute type to 'select' and stores the available
     * options as 'values'. This is useful when creating attributes like "Color", "Size",
     * etc., which have a predefined set of options that customers can choose from.
     *
     * @param string $code The attribute code, which is a unique identifier for the attribute.
     * @param string $label The label for the attribute, which will be displayed in the UI.
     * @param array $values The list of values for the dropdown, e.g., ['Red', 'Blue', 'Green'].
     * @param array $config Optional additional configuration for the attribute, allowing customization
     *                      like setting whether it's required, filterable, etc.
     */
    public function createDropdown($code, $label, $values, $config = []): void
    {
        // Calls the 'create' method to create the attribute with the specified code and merged configurations
        // The default configurations are set for the dropdown (select) type, along with options like 'filterable' and 'required'
        $this->create(
            $code,
            Arr::merge([
                'label' => $label, // Set the label of the attribute
                'required' => false, // Attribute is not required by default
                'filterable' => 1, // Attribute is filterable (visible in product filters)
                'user_defined' => true, // The attribute is user-defined (not system-generated)
                'input' => 'select', // Set the input type to 'select', meaning it's a dropdown
                'option' => ['values' => $values], // The available dropdown options (values list)
                'type' => 'int', // The attribute will store integer values (ID of selected option)
                'source_model' => Table::class, // The source model for the options is a table class (the source of values)
            ], $config),  // Merge any additional configurations passed in $config
        );
    }

    /**
     * Mass update the attribute value for multiple products.
     *
     * This method allows updating the value of an attribute for multiple products
     * at once, using a provided list of model IDs (product IDs) and new attribute data.
     * It's typically used when you want to update a product's attribute for a large
     * number of products in one operation, for instance, changing the category for many products.
     *
     * @param array $modelIds The IDs of the products to update (e.g., [1, 2, 3, 4]).
     * @param array $data The attribute data to update, containing key-value pairs of attribute codes and their new values.
     *
     * @return void
     */
    public function massUpdate($modelIds, $data): void
    {
        // Use the product action model to perform the mass update of attributes for the provided products (model IDs)
        $this->productAction->updateAttributes(
            $data, // The new attribute data (e.g., ['color' => 'Red'])
            $modelIds, // List of product IDs whose attributes are being updated
            self::SCOPE_STORE, // Scope of the update, in this case, applies to the store
        );
    }
}
