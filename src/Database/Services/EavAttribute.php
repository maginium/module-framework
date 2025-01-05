<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Services;

use Illuminate\Support\Traits\Macroable;
use Magento\Eav\Model\AttributeManagement;
use Magento\Eav\Model\AttributeSetRepository;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Group as AttributeGroup;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Foundation\Exceptions\NoSuchEntityException;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\AppState;
use Maginium\Framework\Support\Facades\Config as ConfigFacade;
use Maginium\Framework\Support\Facades\StoreManager;
use Maginium\Framework\Support\Facades\Translator;
use Maginium\Framework\Support\Validator;
use Maginium\Store\Helpers\Data as DataHelper;

/**
 * Abstract class for managing EAV (Entity-Attribute-Value) attributes.
 *
 * This class provides basic functionality to create, update, and check the existence of
 * EAV attributes. It is intended to be extended by concrete classes that will define
 * specific model types.
 */
abstract class EavAttribute implements ScopedAttributeInterface
{
    use Macroable;

    /**
     * The model type constant must be defined in child classes.
     *
     * @var string
     */
    public const ENTITY_TYPE = 'OVERRIDE THIS IS CHILD CLASSES';

    /**
     * EAV Table Name for model type.
     *
     * @var string
     */
    public const TABLE_NAME_EAV_ENTITY_TYPE = 'eav_model_type';

    /**
     * Column name for model type code.
     *
     * @var string
     */
    public const ENTITY_TYPE_CODE = 'model_type_code';

    /**
     * Column name for model type ID.
     *
     * @var string
     */
    public const ENTITY_TYPE_ID = 'model_type_id';

    /**
     * Key for attribute set ID in queries and data.
     *
     * This constant represents the key 'attribute_set_id' used when referencing the attribute set ID.
     */
    public const ATTRIBUTE_SET_ID = 'attribute_set_id';

    /**
     * Key for group ID in queries and data.
     *
     * This constant represents the key 'group_id' used when referencing the attribute group ID.
     */
    public const GROUP_ID = 'group_id';

    /**
     * Key for sort order in queries and data.
     *
     * This constant represents the key 'sort_order' used when referencing the sort order.
     */
    public const SORT_ORDER = 'sort_order';

    /**
     * Table name for the EAV attribute set.
     *
     * This constant holds the table name 'eav_attribute_set' used in queries related to attribute sets.
     */
    public const TABLE_EAV_ATTRIBUTE_SET = 'eav_attribute_set';

    /**
     * Table name for the EAV attribute group.
     *
     * This constant holds the table name 'eav_attribute_group' used in queries related to attribute groups.
     */
    public const TABLE_EAV_ATTRIBUTE_GROUP = 'eav_attribute_group';

    /**
     * Table name for the EAV model attribute.
     *
     * This constant holds the table name 'eav_model_attribute' used for model attribute relationships.
     */
    public const TABLE_EAV_ENTITY_ATTRIBUTE = 'eav_model_attribute';

    /**
     * Table name for the EAV attribute.
     *
     * This constant holds the table name 'eav_attribute' used for managing attributes in the EAV system.
     */
    public const TABLE_EAV_ATTRIBUTE = 'eav_attribute';

    /**
     * Column name for attribute group ID in database queries.
     *
     * This constant represents the column name 'attribute_group_id' used to reference the attribute group ID.
     */
    public const ATTRIBUTE_GROUP_ID = 'attribute_group_id';

    /**
     * Column name for attribute ID in database queries.
     *
     * This constant represents the column name 'attribute_id' used to reference the attribute ID.
     */
    public const ATTRIBUTE_ID = 'attribute_id';

    /**
     * Column name for attribute group name in database queries.
     *
     * This constant represents the column name 'attribute_group_name' used to reference the attribute group name.
     */
    public const ATTRIBUTE_GROUP_NAME = 'attribute_group_name';

    /**
     * Column name for attribute code in database queries.
     *
     * This constant represents the column name 'attribute_code' used to reference the attribute code.
     */
    public const ATTRIBUTE_CODE = 'attribute_code';

    /**
     * The EAV configuration object.
     *
     * @var EavConfig
     */
    protected EavConfig $config;

    /**
     * The factory to create EavSetup instances.
     *
     * @var EavSetupFactory
     */
    protected EavSetupFactory $eavSetupFactory;

    /**
     * The resource connection for database operations.
     *
     * @var ResourceConnection
     */
    protected ResourceConnection $resourceConnection;

    /**
     * Represents the service that handles operations related to managing product attributes.
     * This class is responsible for creating, updating, and deleting product attributes.
     *
     * @var AttributeManagement
     */
    private AttributeManagement $attributeManagement;

    /**
     * Represents the repository responsible for managing attribute sets.
     * This class is used to retrieve and persist attribute sets for products.
     *
     * @var AttributeSetRepository
     */
    private AttributeSetRepository $attributeSetRepository;

    /**
     * Constructor for the EavAttribute class.
     *
     * Initializes the necessary dependencies for the EavAttribute class.
     *
     * @param EavConfig $config The configuration object containing settings for the EAV system.
     * @param EavSetupFactory $eavSetupFactory The factory used for setting up EAV attributes.
     * @param ResourceConnection $resourceConnection The resource connection to interact with the database.
     * @param AttributeManagement $attributeManagement The manager for handling attribute configurations.
     * @param AttributeSetRepository $attributeSetRepository The repository for handling attribute sets.
     */
    public function __construct(
        EavConfig $config,
        EavSetupFactory $eavSetupFactory,
        ResourceConnection $resourceConnection,
        AttributeManagement $attributeManagement,
        AttributeSetRepository $attributeSetRepository,
    ) {
        $this->config = $config;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->resourceConnection = $resourceConnection;
        $this->attributeManagement = $attributeManagement;
        $this->attributeSetRepository = $attributeSetRepository;
    }

    /**
     * Retrieve an attribute by its code and check if it exists.
     *
     * This method retrieves the attribute based on the provided code and returns the
     * corresponding attribute object if it exists. It checks for the presence of the
     * attribute by its code in the defined model type and returns the attribute if found.
     *
     * @param string $code The attribute code to retrieve.
     * @param string $modelType The model type to which the attribute belongs (optional, defaults to self::ENTITY_TYPE).
     *
     * @return AbstractAttribute|null The attribute object if it exists, null if it does not exist.
     */
    public function get(string $code, string $modelType = self::ENTITY_TYPE): ?AbstractAttribute
    {
        // Retrieve the attribute from the EAV configuration by its code
        $attribute = $this->config->getAttribute($modelType, $code);

        // Return the attribute if it exists, otherwise null
        return $attribute ?: null;
    }

    /**
     * Create a new attribute in the system.
     *
     * This method utilizes the EavSetup to add a new attribute to the model type defined
     * in the child class's ENTITY_TYPE constant.
     *
     * @param string $code The attribute code.
     * @param array $data The data to define the attribute.
     * @param string $modelType The model type to which the attribute belongs (optional, defaults to self::ENTITY_TYPE).p
     *
     * @return EavSetup The EavSetup instance used for the operation.
     */
    public function create($code, $data, string $modelType = self::ENTITY_TYPE): EavSetup
    {
        return $this->getEavSetup()->addAttribute(
            $modelType, // Entity type defined in the child class
            $code, // Attribute code
            $data, // Attribute data (configuration)
        );
    }

    /**
     * Update an existing EAV attribute.
     *
     * This method updates the configuration of an existing EAV attribute using the provided
     * attribute code and an array of updated attribute data. It interacts with the EAV setup
     * instance to apply the changes.
     *
     * @param string $code The attribute code to be updated.
     * @param array $data The updated data for the attribute (e.g., label, type, etc.).
     * @param string $modelType The model type to which the attribute belongs (optional, defaults to self::ENTITY_TYPE).
     *
     * @return EavSetup The EavSetup instance used for updating the attribute.
     */
    public function update($code, $data, string $modelType = self::ENTITY_TYPE): EavSetup
    {
        return $this->getEavSetup()->updateAttribute(
            $modelType, // Entity type defined in the child class
            $code, // Attribute code
            $data, // Updated attribute data
        );
    }

    /**
     * Add options for an attribute with localization.
     *
     * This method updates the attribute options for a given attribute code,
     * handling the default label and localized labels for each store view.
     * It retrieves the attribute, processes the labels for each store, and prepares
     * the options for storage in the EAV setup. The method ensures that the default label
     * is translated if necessary, and localized labels are provided for all store views.
     *
     * @param string $code The attribute code.
     * @param array $options Default options for the attribute, where the key is
     *                       the option ID and the value is the default label.
     * @param string $defaultValue The default value to be used when translation is not available.
     *
     * @throws LocalizedException If a localization issue occurs during translation.
     * @throws InvalidArgumentException If the attribute does not exist for the given code.
     *
     * @return void
     */
    public function addOptions(string $code, array $options, string $defaultValue): void
    {
        // Step 1: Set the application state to "frontend" to ensure the appropriate
        // context is set for the operation (this might affect localization and other settings).
        if (! AppState::isAreaSet()) {
            AppState::setFrontend();
        }

        // Step 2: Retrieve the attribute instance by code
        $attribute = $this->get($code);
        $attributeOptions = $attribute->getSource()->getAllOptions();

        if ($attributeOptions) {
            // dd($attributeOptions);
        }

        // Step 3: Check if the attribute exists
        if (! $attribute) {
            // If the attribute doesn't exist, throw an exception
            throw InvalidArgumentException::make(__("Attribute with code '%1' does not exist.", $code));
        }

        // Step 4: Retrieve the attribute ID and all available stores
        $attributeId = $attribute->getAttributeId();

        // Retrieves all store views
        $stores = StoreManager::getStores();

        // Step 5: Prepare a list of store IDs to use later for localization
        $storeIds = Arr::each(fn($store) => $store->getId(), $stores);

        // Step 6: Initialize the structure for storing the prepared options
        $preparedOptions = [
            'attribute_id' => $attributeId, // Attribute ID
            'value' => [], // Contains the option values for each store
            'order' => [], // Contains the order for each option
        ];

        // Step 7: Process each option
        foreach ($options as $optionId => $defaultLabel) {
            // Step 7a: Use Translator to translate the default label if necessary
            $translatedDefault = Translator::translate($defaultValue) ?: $defaultValue;

            // Step 7b: Ensure the default value is set for store ID 0 (default store)
            $preparedOptions['value'][$optionId][0] = $translatedDefault;

            // Step 8: Prepare localized values for each store view
            foreach ($storeIds as $storeId) {
                // Get the locale code for the current store
                $localeCode = ConfigFacade::getString(DataHelper::XML_PATH_LOCALE_CONFIG, $storeId);

                // Set the translator to the correct locale
                Translator::setLocale($localeCode);

                // Load the relevant translations
                Translator::loadTranslations();

                // Step 8a: Retrieve the localized label from the options or use the default
                $localizedLabel = Translator::translate($optionId);

                // Step 8b: Store the localized label for the current option and store
                $preparedOptions['value'][$optionId][$storeId] = $localizedLabel;
            }

            // Step 9: Set the sort order for this option (order index is based on the option ID)

            // Example: Default order is the option ID + 1
            $preparedOptions['order'][$optionId] = (int)$optionId + 1;
        }

        // Step 10: Pass the prepared options structure to the EAV setup for storage
        $this->getEavSetup()->addAttributeOption($preparedOptions);
    }

    /**
     * Check if a given attribute exists.
     *
     * This method checks if an attribute with the specified code exists in the system
     * under the defined model type.
     *
     * @param string $code The attribute code to check for existence.
     * @param string $modelType The model type to which the attribute belongs (optional, defaults to self::ENTITY_TYPE).

     *
     * @return bool True if the attribute exists, false otherwise.
     */
    public function exists($code, string $modelType = self::ENTITY_TYPE): bool
    {
        // Checks if the attribute exists by fetching its ID from the EAV config
        return (bool)$this->config->getAttribute($modelType, $code)->getId();
    }

    /**
     * Assign an attribute to an attribute set and group.
     *
     * This method assigns a given product attribute to an attribute set and group.
     * It handles setting the sort order and checks if any values are provided
     * for the 'after' parameter to define the placement of the attribute.
     *
     * @param string $attributeCode The code of the attribute to assign.
     * @param int|string|AttributeSet $attributeSet The attribute set to assign the attribute to.
     * @param int|string|AttributeGroup $group The group within the attribute set to assign the attribute to.
     * @param string $after The attribute after which the new attribute will be placed.
     */
    public function assignToAttributeSet($attributeCode, $attributeSet = null, $group = null, $after = null): void
    {
        if (Validator::isArray($attributeSet)) {
            $this->assignToAttributeSetLegacy($attributeCode, $attributeSet);
        }

        // Resolve the attribute set and group IDs
        $attributeSetId = $this->resolveAttributeSetId($attributeSet);
        $attributeGroupId = $this->resolveGroupId($group, $attributeSet);
        $sortOrder = $after ? $this->getSortOrder($after, $attributeSet) + 1 : 999;

        // Assign the attribute to the set and group
        $this->attributeManagement->assign(
            static::ENTITY_TYPE,
            $attributeSetId,
            $attributeGroupId,
            $attributeCode,
            $sortOrder,
        );
    }

    /**
     * Unassign a given attribute from an attribute set.
     *
     * This method removes an attribute from a given attribute set, optionally
     * using the default set if none is specified.
     *
     * @param string $attributeCode The code of the attribute to unassign.
     * @param int $attributeSetId The ID of the attribute set to remove the attribute from.
     * @param string $modelType The model type to which the attribute belongs (optional, defaults to self::ENTITY_TYPE).
     */
    public function unassignFromAttributeSet($attributeCode, $attributeSetId = null, string $modelType = self::ENTITY_TYPE): void
    {
        // If no attribute set ID is provided, use the default set ID
        $attributeSetId = $attributeSetId ?: $this->getEavSetup()
            ->getDefaultAttributeSetId($modelType);

        // Unassign the attribute from the set
        $this->attributeManagement->unassign((string)$attributeSetId, $attributeCode);
    }

    /**
     * Retrieve the EAV configuration instance.
     *
     * This method returns the EavConfig object, which provides access to the database configuration
     * and allows performing database operations, such as retrieving EAV attribute configurations.
     *
     * @return EavConfig The EAV configuration instance used for database operations.
     */
    public function getConfig(): EavConfig
    {
        return $this->config;
    }

    /**
     * Retrieve a fresh instance of EavSetup.
     *
     * This method returns a fresh instance of the EavSetup factory, which is used to
     * create or update EAV attributes.
     *
     * @return EavSetup The EavSetup instance.
     */
    protected function getEavSetup(): EavSetup
    {
        return $this->eavSetupFactory->create();
    }

    /**
     * Database facade for quick operations.
     *
     * This method returns the database connection, which can be used for direct database
     * operations, such as selecting data or executing queries.
     *
     * @return AdapterInterface The database connection instance.
     */
    protected function getConnection(): AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }

    /**
     * Retrieve the full table name, including the database prefix if applicable.
     *
     * This method returns the full table name by adding the prefix and handling any other
     * necessary adjustments to retrieve the correct table name from the database.
     *
     * @param string $rawTableName The raw table name without prefix.
     *
     * @return string The full table name, including any prefix if applicable.
     */
    protected function getTableName($rawTableName): string
    {
        return $this->resourceConnection->getTableName($rawTableName);
    }

    /**
     * Retrieve the model type ID for the given model type code.
     *
     * This method fetches the model type ID from the `eav_model_type` table using the
     * model type code defined in the child class.
     *
     * @return int The model type ID.
     */
    protected function getEntityTypeId(): int
    {
        // Retrieve the full table name with prefix
        $tableName = $this->getTableName(static::TABLE_NAME_EAV_ENTITY_TYPE);

        // Create the select query
        $select = $this->getConnection()->select();

        // Select the model_type_id
        $select->from($tableName, static::ENTITY_TYPE_ID);

        // Filter by model type code
        $select->where(static::ENTITY_TYPE_CODE . '=?', static::ENTITY_TYPE);

        // Execute the query and fetch the model type ID
        $modelTypeId = $this->getConnection()->fetchOne($select);

        // Return the fetched model type ID as an integer
        return (int)$modelTypeId;
    }

    /**
     * Legacy method for assigning an attribute to an attribute set.
     *
     * This method is kept for retrocompatibility and handles assigning attributes
     * using older methods. It is used only when the attribute set is provided as an array.
     * It assigns the attribute to the attribute set, group, and specifies the sort order.
     *
     * @param string $attributeCode The code of the attribute to assign.
     * @param array $options The options for assigning the attribute, such as 'attribute_set_id', 'group_id', and 'sort_order'.
     */
    private function assignToAttributeSetLegacy($attributeCode, $options = []): void
    {
        // Retrieve the attribute set ID from the options or use the default attribute set ID.
        $attributeSetId = (int)($options[static::ATTRIBUTE_SET_ID] ?? $this->getEavSetup()->getDefaultAttributeSetId(static::ENTITY_TYPE));

        // Retrieve the attribute group ID from options or use the default group ID.
        $attributeGroupId = $options[static::GROUP_ID] ?? $this->getDefaultGroupId($attributeSetId);

        // Set the sort order of the attribute. Default is 999 if not provided.
        $sortOrder = $options[static::SORT_ORDER] ?? 999;

        // Assign the attribute using the legacy method from the attribute management service.
        $this->attributeManagement->assign(
            static::ENTITY_TYPE,   // Entity type for the attribute assignment.
            $attributeSetId,     // Attribute set ID to assign the attribute to.
            $attributeGroupId,   // Group ID within the attribute set.
            $attributeCode,      // The code of the attribute to assign.
            $sortOrder,           // Sort order of the attribute within the group.
        );
    }

    /**
     * Resolve the ID of a given attribute set.
     *
     * This method resolves the ID of the attribute set based on the provided name,
     * set object, or ID. If the attribute set cannot be found, it throws an exception.
     *
     * @param int|string|AttributeSet $attributeSet The attribute set to resolve (ID, name, or object).
     *
     * @throws NoSuchEntityException If the attribute set does not exist.
     *
     * @return int The ID of the attribute set.
     */
    private function resolveAttributeSetId($attributeSet = null): int
    {
        // If attribute set is not provided, return the default attribute set ID.
        if ($attributeSet === null) {
            return $this->getDefaultAttributeSetId();
        }

        // If attribute set is an object, return its ID.
        if (is_object($attributeSet)) {
            return (int)$attributeSet->getId();
        }

        // If attribute set is a name (string), resolve its ID from the database.
        if (! Validator::isInt($attributeSet)) {
            $select = $this->getConnection()->select()
                ->from($this->getTableName(rawTableName: static::TABLE_EAV_ATTRIBUTE_SET), static::ATTRIBUTE_SET_ID)
                ->where(static::ATTRIBUTE_SET_ID, $attributeSet)
                ->where(static::ENTITY_TYPE_ID, $this->getEntityTypeId());
            $attributeSetId = $this->getConnection()->fetchOne($select);

            // Throw exception if the attribute set ID cannot be found.
            if (Validator::isEmpty($attributeSetId)) {
                NoSuchEntityException::make(__("Attribute Set with name {$attributeSet} not found"));
            }

            return (int)$attributeSetId;
        }

        // If attribute set is already an integer (ID), return it directly.
        return $attributeSet;
    }

    /**
     * Resolve the ID of a given attribute group.
     *
     * This method resolves the group ID within a specific attribute set, either
     * from an object or string name, or by resolving it from the database.
     *
     * @param int|string|AttributeGroup $group The attribute group to resolve (ID, name, or object).
     * @param int|string|AttributeSet $attributeSet The attribute set where the group belongs (ID, name, or object).
     *
     * @throws NoSuchEntityException If the group does not exist.
     *
     * @return int The ID of the attribute group.
     */
    private function resolveGroupId($group, $attributeSet = null): int
    {
        // If group is not provided, return the default group ID.
        if ($group === null) {
            return $this->getDefaultGroupId($attributeSet);
        }

        // If group is an object, return its ID.
        if (is_object($group)) {
            return (int)$group->getId();
        }

        // If group is a name (string), resolve its ID from the database.
        if (! Validator::isInt($group)) {
            return (int)$this->getGroupIdByName($attributeSet, $group);
        }

        // If group is already an integer (ID), return it directly.
        return $group;
    }

    /**
     * Get the ID of a group by name.
     *
     * This method retrieves the group ID from the database by matching the group
     * name within the provided attribute set.
     *
     * @param string|AttributeSet $attributeSet The attribute set to search within (ID, name, or object).
     * @param string $groupName The name of the attribute group.
     *
     * @throws NoSuchEntityException If the group does not exist.
     *
     * @return int The group ID.
     */
    private function getGroupIdByName($attributeSet, $groupName): int
    {
        // If attribute set is provided as a name, resolve it to ID.
        if (Validator::isString($attributeSet)) {
            $attributeSet = $this->resolveAttributeSetId($attributeSet);
        }

        // Query the database to retrieve the group ID by attribute set and group name.
        $select = $this->getConnection()->select()
            ->from($this->getTableName(static::TABLE_EAV_ATTRIBUTE_GROUP), static::ATTRIBUTE_GROUP_ID)
            ->where(static::ATTRIBUTE_SET_ID, $attributeSet)
            ->where(static::ATTRIBUTE_GROUP_NAME, $groupName);

        // Fetch the group ID.
        $groupId = $this->getConnection()->fetchOne($select);

        // Throw exception if the group ID cannot be found.
        if (! $groupId) {
            NoSuchEntityException::make(__('Group %1 not found in attribute set %2', $groupName, $attributeSet));
        }

        return (int)$groupId;
    }

    /**
     * Retrieve default product attribute set ID.
     *
     * This method returns the default attribute set ID associated with the model type.
     *
     * @return int The default attribute set ID.
     */
    private function getDefaultAttributeSetId(): int
    {
        // Fetch and return the default attribute set ID using the EAV setup service.
        return (int)$this->getEavSetup()->getDefaultAttributeSetId(static::ENTITY_TYPE);
    }

    /**
     * Retrieve default group ID for a given attribute set.
     *
     * This method returns the default group ID associated with the given attribute set.
     *
     * @param int|string|AttributeSet $attributeSet The attribute set to resolve (ID, name, or object).
     *
     * @return int The default group ID for the attribute set.
     */
    private function getDefaultGroupId($attributeSet = null): int
    {
        // Resolve the attribute set ID if necessary.
        $attributeSetId = $this->resolveAttributeSetId($attributeSet);

        // Retrieve the attribute set object and fetch the default group ID.
        /** @var AttributeSet $attributeSet */
        $attributeSet = $this->attributeSetRepository->get($attributeSetId);

        return (int)$attributeSet->getDefaultGroupId();
    }

    /**
     * Retrieve the current sort position of the given attribute.
     *
     * This method fetches the current sort order of the specified attribute in the provided attribute set.
     *
     * @param string $attributeCode The code of the attribute.
     * @param string|int|AttributeSet $attributeSet The attribute set where the attribute belongs (ID, name, or object).
     *
     * @return int The sort order of the attribute within the group, or 999 if not set.
     */
    private function getSortOrder($attributeCode, $attributeSet = null)
    {
        // Resolve the model type ID and attribute set ID.
        $modelTypeId = $this->getEntityTypeId();
        $attributeSetId = $this->resolveAttributeSetId($attributeSet);

        // Retrieve the attribute ID based on the attribute code.
        $attributeIdSelect = $this->getConnection()->select()
            ->from($this->getTableName(static::TABLE_EAV_ATTRIBUTE), static::ATTRIBUTE_ID)
            ->where(static::ATTRIBUTE_CODE, $attributeCode);

        // Query the database for the sort order of the attribute.
        $select = $this->getConnection()->select()
            ->from($this->getTableName(static::TABLE_EAV_ENTITY_ATTRIBUTE), static::SORT_ORDER)
            ->where(static::ENTITY_TYPE_ID, $modelTypeId)
            ->where(static::ATTRIBUTE_SET_ID, $attributeSetId)
            ->where(static::ATTRIBUTE_ID, $attributeIdSelect);

        // Return the sort order or 999 if no value is found.
        return (int)$this->getConnection()->fetchOne($select) ?: 999;
    }
}
