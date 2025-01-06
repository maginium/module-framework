<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Schema;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Grammars\Grammar;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
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
use Maginium\Framework\Support\Fluent;
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
        // Loop through each attribute and process it
        foreach ($this->getAttributes() as $attribute) {
            // Map the attribute to an array of EAV-compatible data
            $attributeData = AttributeMapper::map($this->getAttribute(), $attribute);

            // Retrieve the model type from the attribute's 'for' field
            $modelType = $attribute->get(AttributeBlueprintInterface::FOR);

            // Retrieve the appropriate EAV handler for the model type
            /** @var EavAttribute $eavHandler */
            $eavHandler = $this->getEntityEavHandler($modelType);

            // Ensure that a valid EAV handler is found
            $this->validateEntityType($eavHandler, $modelType);

            // Process the attribute: add to EAV system, assign to the attribute set
            $this->processAttribute($eavHandler, $attribute, $attributeData);

            // Retrieve the current EAV attribute based on its code
            $this->attributeObject = $eavHandler->get($this->getAttribute());

            // Update additional data such as 'used_in_forms' and save the attribute
            $this->updateAttributeUsedInForms($eavHandler, $attribute);

            // Handle localized options for the attribute
            $this->processAttributeOptions($attribute, $eavHandler);
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
     * @param EavAttribute|null $eavHandler The handler responsible for managing attributes for the given model type.
     * @param string $modelType The model type (e.g., Product, Category, Customer) to be validated.
     *
     * @throws InvalidArgumentException If no handler is found for the model type.
     *
     * @return void
     */
    private function validateEntityType(EavAttribute $eavHandler, string $modelType)
    {
        // Get the model type from the handler
        $handlerEntityType = $eavHandler->getConfig()->getEntityType($modelType);

        // Match the model type from the handler with the given model type
        if ($handlerEntityType->getEntityTypeCode() !== $modelType) {
            throw InvalidArgumentException::make(__('Entity type mismatch: expected %1, got %2', $modelType, $handlerEntityType));
        }
    }

    /**
     * Process and add the attribute to the EAV system.
     *
     * This method is responsible for creating the attribute in the EAV system and assigning it to
     * the correct attribute set for the given model type. The attribute is then ready to be used
     * within the model.
     *
     * @param EavAttribute $eavHandler The handler responsible for managing the model's attributes.
     * @param Fluent $attribute The attribute to be processed.
     * @param array $attributeData The EAV-compatible data mapped from the attribute.
     *
     * @throws InvalidArgumentException if the EavAttribute handler is null.
     *
     * @return void
     */
    private function processAttribute(EavAttribute $eavHandler, Fluent $attribute, array $attributeData)
    {
        // Check if the EAV handler is null, and throw an exception if it is
        if ($eavHandler === null) {
            throw InvalidArgumentException::make('The EavAttribute handler cannot be null.');
        }

        // Add the attribute to the EAV system using the mapped data
        $eavHandler->create(
            $this->getAttribute(), // Attribute code (used as the identifier)
            $attributeData, // Mapped data for the attribute, e.g., type, labels, options, etc.
        );

        // Assign the attribute to the correct attribute set for the model type
        $eavHandler->assignToAttributeSet($this->getAttribute());
    }

    /**
     * Update the 'used_in_forms' data and save the EAV attribute.
     *
     * This method updates additional data for the attribute (in this case, the 'used_in_forms' value),
     * which defines which forms this attribute will appear on. After updating the attribute's data,
     * the attribute is saved to persist the changes.
     *
     * @param EavAttribute $eavHandler The handler responsible for managing the model's attributes.
     * @param Fluent $attribute The attribute to be updated.
     *
     * @return void
     */
    private function updateAttributeUsedInForms(EavAttribute $eavHandler, Fluent $attribute)
    {
        // Retrieve the 'usedInForms' value from the attribute, which defines which forms the attribute should be shown in
        $usedInForms = $attribute->get(AttributeBlueprintInterface::USED_IN_FORMS);

        // If 'usedInForms' is not defined or empty, exit early without making changes
        if (! $usedInForms) {
            return;
        }

        // Update the 'used_in_forms' field of the EAV attribute data with the value from the attribute
        $this->attributeObject->setData(AttributeBlueprintInterface::RAW_USED_IN_FORMS, $usedInForms);

        // Save the updated EAV attribute to persist the changes made to the 'used_in_forms' value
        // This ensures that the attribute is properly configured and displayed in the appropriate forms
        $this->attributeObject->save();
    }

    /**
     * Build and persist localized options for attributes.
     *
     * This method processes each attribute with localized options, prepares them for EAV compatibility,
     * and ensures they are persisted correctly in the database.
     *
     * @param Fluent $attribute The attribute handler containing the options and localized options.
     * @param EavAttribute $eavHandler The handler responsible for managing attributes for the given model type.
     *
     * @return void
     */
    private function processAttributeOptions(Fluent $attribute, EavAttribute $eavHandler): void
    {
        // Retrieve options and default value from the attribute
        $options = $attribute->get(AttributeBlueprintInterface::OPTIONS);
        $defaultValue = $attribute->get(AttributeBlueprintInterface::DEFAULT_VALUE);

        // If the attribute doesn't have a default value, assign the default value from the Fluent attribute object.
        if (! $this->attributeObject->getDefaultValue()) {
            $this->attributeObject->setDefaultValue($defaultValue);

            $this->attributeObject->save();
        }

        // Validate if options are available and valid
        if (Validator::isEmpty($options) && ! Validator::isArray($options)) {
            // Optionally log invalid options or throw an exception based on your error handling strategy
            return;
        }

        // Call the handler to add options to the EAV system
        $eavHandler->addOptions($this->getAttribute(), $options, $defaultValue);
    }

    /**
     * Retrieve the appropriate EAV handler based on the model type.
     *
     * This method maps the model type string (e.g., Product, Category, Customer) to the appropriate EAV handler
     * for adding attributes to that model. If no handler is found, it returns null.
     *
     * @param string $modelType The model type (e.g., Product, Category, Customer).
     *
     * @return object|null The EAV handler for the model type, or null if not supported.
     */
    private function getEntityEavHandler(string $modelType)
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
