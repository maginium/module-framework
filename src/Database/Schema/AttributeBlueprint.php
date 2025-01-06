<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Schema;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Grammars\Grammar;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Database\Enums\AttributeType;
use Maginium\Framework\Database\Facades\CategoryAttribute;
use Maginium\Framework\Database\Facades\CustomerAttribute;
use Maginium\Framework\Database\Facades\ProductAttribute;
use Maginium\Framework\Database\Helpers\AttributeMapper;
use Maginium\Framework\Database\Interfaces\Data\AttributeBlueprintInterface;
use Maginium\Framework\Database\Services\EavAttribute;
use Maginium\Framework\Database\Setup\Migration\Attribute\Context;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\DB;
use Maginium\Framework\Support\Fluent;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;
use Override;

/**
 * AttributeBlueprint represents a blueprint for an EAV (Entity-Attribute-Value) attribute.
 * It provides methods to configure and create attributes within the database schema,
 * and interacts with the context to perform necessary operations for different model types
 * such as Product, Category, or Customer.
 */
class AttributeBlueprint extends Blueprint
{
    /**
     * The name of the attribute being created.
     *
     * @var string
     */
    protected string $attribute;

    /**
     * The attribute object.
     *
     * @var AbstractAttribute
     */
    protected AbstractAttribute $attributeObject;

    /**
     * A callback that can be applied for additional configurations to the attribute.
     *
     * @var Closure|null
     */
    protected ?Closure $callback;

    /**
     * The context instance for interacting with the EAV system.
     * This context holds services and dependencies needed to manage and manipulate EAV attributes.
     *
     * @var Context
     */
    protected Context $context;

    /**
     * The context instance for interacting with the EAV system.
     * This context holds services and dependencies needed to manage and manipulate EAV attributes.
     *
     * @var EavAttribute|null
     */
    protected ?EavAttribute $eavHandler;

    /**
     * AttributeBlueprint constructor.
     *
     * Sets up the blueprint with necessary parameters:
     * - $attribute: The name of the attribute.
     * - $context: The context object that holds the necessary EAV services.
     * - $callback: An optional callback function for additional attribute configurations.
     *
     * @param Context $context The context object that holds the EAV services.
     * @param string $attribute The name of the attribute.
     * @param Closure|null $callback An optional callback for additional configurations.
     */
    public function __construct(string $attribute, ?Context $context, ?Closure $callback = null)
    {
        $this->context = $context;
        $this->attribute = $attribute;

        parent::__construct($attribute, $callback);
    }

    /**
     * Execute the blueprint against the database.
     *
     * @param ConnectionInterface $connection
     * @param Grammar|null $grammar
     *
     * @return void
     */
    #[Override]
    public function build(ConnectionInterface $connection, ?Grammar $grammar): void
    {
        $moduleDataSetup = Container::resolve(ModuleDataSetupInterface::class);

        /** @var CustomerSetup $customerSetup */
        $customerSetup = Container::make(CustomerSetupFactory::class)->create(['setup' => $moduleDataSetup]);

        // Loop through each attribute and process it
        foreach ($this->getAttributes() as $attribute) {
            // Map the attribute to an array of EAV-compatible data
            $attributeData = AttributeMapper::map($this->getAttribute(), $attribute);

            // Retrieve the model type from the attribute's 'for' field
            $modelType = $attribute->get(AttributeBlueprintInterface::FOR);

            // Retrieve the appropriate EAV handler for the model type
            $this->eavHandler = $this->getEavHandler($modelType);

            // Ensure that a valid EAV handler is found
            $this->validateEntityType($modelType);

            // Process the attribute: add to EAV system, assign to the attribute set
            $this->createAttribute($attributeData, $modelType);

            // Set the default value for the attribute
            $this->setDefaultValue($attribute);

            // Handle localized options for the attribute
            $this->addAttributeOptions($attribute);

            // Update additional data such as 'used_in_forms' and save the attribute
            if (Reflection::methodExists($this->eavHandler, '_getFormAttributeTable')) {
                $this->setUsedInForms($attribute);
            }
        }
    }

    /**
     * Create a new asStatic type attribute for the schema.
     *
     * @return AttributeDefinition The created attribute definition for a asStatic type.
     */
    public function asStatic(): AttributeDefinition
    {
        return $this->addAttribute(AttributeType::STATIC);
    }

    /**
     * Create a new VARCHAR type attribute for the schema.
     *
     * @return AttributeDefinition The created attribute definition for a VARCHAR type.
     */
    public function asVarchar(): AttributeDefinition
    {
        return $this->addAttribute(AttributeType::VARCHAR);
    }

    /**
     * Create a new INT type attribute for the schema.
     *
     * @return AttributeDefinition The created attribute definition for an INT type.
     */
    public function asInteger(): AttributeDefinition
    {
        return $this->addAttribute(AttributeType::INT);
    }

    /**
     * Create a new DECIMAL type attribute for the schema.
     *
     * @return AttributeDefinition The created attribute definition for a DECIMAL type.
     */
    public function asDecimal(): AttributeDefinition
    {
        return $this->addAttribute(AttributeType::DECIMAL);
    }

    /**
     * Create a new TEXT type attribute for the schema.
     *
     * @return AttributeDefinition The created attribute definition for a TEXT type.
     */
    public function asText(): AttributeDefinition
    {
        return $this->addAttribute(AttributeType::TEXT);
    }

    /**
     * Create a new DATETIME type attribute for the schema.
     *
     * @return AttributeDefinition The created attribute definition for a DATETIME type.
     */
    public function asDatetime(): AttributeDefinition
    {
        return $this->addAttribute(AttributeType::DATETIME);
    }

    /**
     * Get the name of the attribute defined in this blueprint.
     *
     * @return string The name of the attribute.
     */
    public function getAttribute(): string
    {
        return $this->attribute;
    }

    /**
     * Get all attributes defined on this blueprint.
     *
     * @return AttributeDefinition[] The list of attribute definitions.
     */
    public function getAttributes(): array
    {
        return $this->columns;
    }

    /**
     * Add a new attribute of the specified type to the blueprint.
     *
     * @param string $type The type of the attribute (e.g., VARCHAR, INT, etc.).
     * @param array $parameters Optional additional parameters for the attribute.
     *
     * @return AttributeDefinition The created attribute definition.
     */
    public function addAttribute($type, array $parameters = []): AttributeDefinition
    {
        return $this->addAttributeDefinition(new AttributeDefinition(
            Arr::merge(compact('type'), $parameters),
        ));
    }

    /**
     * Add an attribute definition to the blueprint.
     *
     * This method adds the given attribute definition to the list of attributes for this blueprint.
     * If the blueprint is being executed (i.e., not being created), it will also add the definition to the command list.
     *
     * @param AttributeDefinition $definition The attribute definition to be added.
     *
     * @return AttributeDefinition The added attribute definition.
     */
    protected function addAttributeDefinition($definition): AttributeDefinition
    {
        $this->columns[] = $definition;

        // If we are not currently in the creation phase, add this definition to the list of commands.
        if (! $this->creating()) {
            $this->commands[] = $definition;
        }

        // If there is an 'after' attribute, set it as the 'after' dependency for the new definition.
        if ($this->after) {
            $definition->after($this->after);
            $this->after = $definition->name;
        }

        return $definition;
    }

    /**
     * Validate that the model type has a corresponding EAV handler.
     *
     * This method checks if the model type passed has a valid handler that can process the attribute.
     * If no handler is found for the model type, an InvalidArgumentException is thrown.
     *
     * @param string $modelType The model type (e.g., Product, Category, Customer) to be validated.
     *
     * @throws InvalidArgumentException If no handler is found for the model type.
     *
     * @return void
     */
    private function validateEntityType(string $modelType)
    {
        // Get the model type from the handler
        $handlerEntityType = $this->eavHandler->getConfig()->getEntityType($modelType);

        // Match the model type from the handler with the given model type
        if ($handlerEntityType->getEntityTypeCode() !== $modelType) {
            throw InvalidArgumentException::make(__('Entity type mismatch: expected %1, got %2', $modelType, $handlerEntityType));
        }
    }

    /**
     * Process and add the attribute to the EAV system.
     *
     * @param array $attributeData The EAV-compatible data mapped from the attribute.
     * @param string $modelType The model type (e.g., Product, Category, Customer) to be validated.
     *
     * @throws InvalidArgumentException if the EavAttribute handler is null.
     *
     * @return void
     */
    private function createAttribute(array $attributeData, string $modelType): void
    {
        // Check if the EAV handler is null, and throw an exception if it is
        if ($this->eavHandler === null) {
            throw InvalidArgumentException::make('The EavAttribute handler cannot be null.');
        }

        // Add the attribute to the EAV system
        $this->eavHandler->create(
            $this->getAttribute(),
            $attributeData,
        );

        // Save changes to the database
        $this->eavHandler->flush();

        // Assign the attribute to the correct attribute set
        $this->eavHandler->assignToAttributeSet($this->getAttribute());
    }

    /**
     * Set the default value for the attribute.
     *
     * @param Fluent $attribute The attribute handler containing the default value.
     *
     * @return void
     */
    private function setDefaultValue(Fluent $attribute): void
    {
        // Retrieve the default value for the attribute from the handler.
        $defaultValue = $attribute->get(AttributeBlueprintInterface::DEFAULT_VALUE);

        // Check if the default value is empty or not defined.
        if (Validator::isEmpty($defaultValue)) {
            // If no default value is provided, exit early.
            return;
        }

        // Update the attribute with the raw default value in the EAV system.
        $this->eavHandler->update($this->getAttribute(), [AttributeBlueprintInterface::RAW_DEFAULT_VALUE => $defaultValue]);
    }

    /**
     * Update the 'used_in_forms' data and save the EAV attribute.
     *
     * This method updates the `customer_form_attribute` table by associating
     * the given attribute with the specified forms. It first retrieves the
     * `attribute_id` for the given `attribute_code` and then performs a bulk
     * insert for all forms provided in the `used_in_forms` field.
     *
     * @param Fluent $attribute The attribute containing metadata, including forms to associate.
     *
     * @throws InvalidArgumentException if no forms are provided or if the attribute ID is not found.
     *
     * @return void
     */
    private function setUsedInForms(Fluent $attribute): void
    {
        // Retrieve the list of forms the attribute should be used in.
        $forms = $attribute->get(AttributeBlueprintInterface::USED_IN_FORMS);

        // Exit early if no forms are specified.
        if (empty($forms)) {
            return;
        }

        // Retrieve the attribute ID from the 'eav_attribute' table using the attribute code.
        $attributeId = DB::table(EavAttribute::TABLE_EAV_ATTRIBUTE)
            ->where(EavAttribute::ATTRIBUTE_CODE, operator: $this->getAttribute())
            ->value(EavAttribute::ATTRIBUTE_ID);

        // Validate that the attribute ID was successfully retrieved.
        if (! $attributeId) {
            throw new InvalidArgumentException("Attribute ID not found for attribute code: {$this->getAttribute()}.");
        }

        // Prepare the data for bulk insertion into the 'customer_form_attribute' table.
        $data = [];

        foreach ($forms as $formCode) {
            $data[] = [
                EavAttribute::FORM_CODE => $formCode,          // The form in which the attribute should be used.
                EavAttribute::ATTRIBUTE_ID => (int)$attributeId, // The ID of the attribute from the EAV system.
            ];
        }

        // Perform a bulk insert into the 'customer_form_attribute' table.
        if (! empty($data)) {
            DB::table($this->eavHandler->_getFormAttributeTable())->insert($data);
        }
    }

    /**
     * Build and persist localized options for attributes.
     *
     * @param Fluent $attribute The attribute handler containing the options.
     *
     * @return void
     */
    private function addAttributeOptions(Fluent $attribute): void
    {
        // Retrieve the options array from the attribute handler.
        $options = $attribute->get(AttributeBlueprintInterface::OPTIONS);

        // Validate that options are not empty and are of type array.
        if (Validator::isEmpty($options) || ! Validator::isArray($options)) {
            // If options are invalid or not present, exit early.
            return;
        }

        // Retrieve the default value for the attribute from the handler.
        $defaultValue = $attribute->get(AttributeBlueprintInterface::DEFAULT_VALUE);

        // Add the provided options to the attribute in the EAV system.
        // This also sets the default value if provided.
        $this->eavHandler->addOptions($this->getAttribute(), $options, $defaultValue);
    }

    /**
     * Retrieve the appropriate EAV handler based on the model type.
     *
     * This method maps the model type string (e.g., Product, Category, Customer) to the appropriate EAV handler
     * for adding attributes to that model. If no handler is found, it returns null.
     *
     * @param string $modelType The model type (e.g., Product, Category, Customer).
     *
     * @return EavAttribute|null The EAV handler for the model type, or null if not supported.
     */
    private function getEavHandler(string $modelType): ?EavAttribute
    {
        // Match the model type to the corresponding handler
        return match ($modelType) {
            ProductAttribute::ENTITY_TYPE => $this->context->getProductAttribute(),
            CategoryAttribute::ENTITY_TYPE => $this->context->getCategoryAttribute(),
            CustomerAttribute::ENTITY_TYPE => $this->context->getCustomerAttribute(),
            default => null, // Return null for unsupported model types
        };
    }
}
