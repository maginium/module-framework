<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Schema;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Grammars\Grammar;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Database\Enums\AttributeType;
use Maginium\Framework\Database\Facades\CategoryAttribute;
use Maginium\Framework\Database\Facades\CustomerAttribute;
use Maginium\Framework\Database\Facades\ProductAttribute;
use Maginium\Framework\Database\Interfaces\Data\AttributeBlueprintInterface;
use Maginium\Framework\Database\Services\EavAttribute;
use Maginium\Framework\Database\Setup\Migration\Attribute\Context;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Fluent;
use Maginium\Framework\Support\Str;
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
     * Execute the blueprint against the database.
     *
     * @param Grammar $grammar
     * @param ConnectionInterface $connection
     *
     * @return void
     */
    #[Override]
    public function build(ConnectionInterface $connection, ?Grammar $grammar): void
    {
        // Loop through each attribute and process it
        foreach ($this->getAttributes() as $attribute) {
            // Map the attribute to an array of EAV-compatible data
            $attributeData = $this->map($attribute);

            // Retrieve the model type from the attribute's 'for' field
            $modelType = $attribute->get(AttributeBlueprintInterface::FOR);

            // Retrieve the appropriate EAV handler for the model type
            /** @var EavAttribute $eavHandler */
            $eavHandler = $this->getEntityEavHandler($modelType);

            // Ensure that a valid EAV handler is found
            $this->validateEntityType($eavHandler, $modelType);

            // Process the attribute: add to EAV system, assign to the attribute set
            $this->processAttribute($eavHandler, $attribute, $attributeData);

            // Handle localized options for the attribute
            $this->processAttributeOptions($attribute, $eavHandler);

            // Update additional data such as 'used_in_forms' and save the attribute
            $this->updateAttributeUsedInForms($eavHandler, $attribute);
        }
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
        $defaultValue = $attribute->get(AttributeBlueprintInterface::DEFAULT);

        // Validate if options are available and valid
        if (Validator::isEmpty($options) && ! Validator::isArray($options)) {
            // Optionally log invalid options or throw an exception based on your error handling strategy
            return;
        }

        // Call the handler to add options to the EAV system
        $eavHandler->addOptions($this->getAttribute(), $options, $defaultValue);
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
            $attribute->get(AttributeBlueprintInterface::FOR),
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

        // Retrieve the current EAV attribute based on its code
        // This allows the method to work with the existing attribute data in the system
        $eavAttribute = $eavHandler->get($this->getAttribute(), $attribute->get(AttributeBlueprintInterface::FOR));

        // Update the 'used_in_forms' field of the EAV attribute data with the value from the attribute
        $eavAttribute->setData(AttributeBlueprintInterface::RAW_USED_IN_FORMS, value: $usedInForms);

        // Save the updated EAV attribute to persist the changes made to the 'used_in_forms' value
        // This ensures that the attribute is properly configured and displayed in the appropriate forms
        $eavAttribute->save();
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

    /**
     * Map the attribute data to an EAV-compatible format.
     *
     * This method converts an attribute definition into an array that conforms to the EAV attribute setup
     * format required by the database schema. It includes various attribute properties like visibility,
     * filtering, and frontend/backend configurations.
     *
     * @param AttributeDefinition $attribute The attribute definition to map.
     *
     * @return array The mapped attribute data in EAV format.
     */
    private function map(AttributeDefinition $attribute): array
    {
        // dd($attribute);
        // Retrieve the validation rules associated with the attribute, if any.
        $validationRule = $attribute->get(AttributeBlueprintInterface::VALIDATE_RULES, null);

        // Get attribute for property
        $for = $attribute->get(AttributeBlueprintInterface::FOR, null);

        // Get label and note for the attribute, constructing the note if not explicitly set.
        $label = $attribute->get(AttributeBlueprintInterface::LABEL);
        $note = $attribute->get(
            AttributeBlueprintInterface::NOTE,
            Str::format('%1 %2 Attribute', $label, Str::capital($for)),
        );

        // Return the attribute data in an EAV-compatible array format.
        return [
            // Flags and visibility settings
            AttributeBlueprintInterface::RAW_DEFAULT => $attribute->get(AttributeBlueprintInterface::DEFAULT, null), // Whether the attribute has default value
            AttributeBlueprintInterface::RAW_REQUIRED => $attribute->get(AttributeBlueprintInterface::REQUIRED, false), // Whether the attribute is required
            AttributeBlueprintInterface::RAW_SYSTEM => $attribute->get(AttributeBlueprintInterface::SYSTEM, false), // Whether the attribute is a system attribute
            AttributeBlueprintInterface::RAW_IS_GLOBAL => $attribute->get(AttributeBlueprintInterface::IS_GLOBAL, false), // Flag indicating if the attribute is global
            AttributeBlueprintInterface::RAW_VISIBLE => $attribute->get(AttributeBlueprintInterface::VISIBLE, true), // Whether the attribute is visible in forms or not
            AttributeBlueprintInterface::RAW_USER_DEFINED => $attribute->get(AttributeBlueprintInterface::USER_DEFINED, false), // Whether the attribute is user defined
            AttributeBlueprintInterface::RAW_IS_USED_IN_GRID => $attribute->get(AttributeBlueprintInterface::IS_USED_IN_GRID, null), // Whether the attribute is used in grid
            AttributeBlueprintInterface::RAW_IS_COMPARABLE => $attribute->get(AttributeBlueprintInterface::IS_COMPARABLE, false), // Flag indicating if the attribute is comparable
            AttributeBlueprintInterface::RAW_USED_FOR_SORT_BY => $attribute->get(AttributeBlueprintInterface::USED_FOR_SORT_BY, false), // Whether the attribute is used for sorting
            AttributeBlueprintInterface::RAW_IS_WYSIWYG_ENABLED => $attribute->get(AttributeBlueprintInterface::IS_WYSIWYG_ENABLED, false), // Whether the attribute supports WYSIWYG editor
            AttributeBlueprintInterface::RAW_IS_VISIBLE_IN_GRID => $attribute->get(AttributeBlueprintInterface::IS_VISIBLE_IN_GRID, false), // Whether the attribute is visible on the frontend
            AttributeBlueprintInterface::RAW_IS_VISIBLE_ON_FRONT => $attribute->get(AttributeBlueprintInterface::IS_VISIBLE_ON_FRONT, false), // Whether the attribute is visible on the frontend
            AttributeBlueprintInterface::RAW_IS_USED_FOR_PRICE_RULES => $attribute->get(AttributeBlueprintInterface::IS_USED_FOR_PRICE_RULES, false), // Whether the attribute is used in price rules
            AttributeBlueprintInterface::RAW_IS_USED_FOR_PROMO_RULES => $attribute->get(AttributeBlueprintInterface::IS_USED_FOR_PROMO_RULES, false), // Whether the attribute is used in promo rules
            AttributeBlueprintInterface::RAW_IS_FILTERABLE_IN_SEARCH => $attribute->get(AttributeBlueprintInterface::IS_FILTERABLE_IN_SEARCH, false), // Whether the attribute is filterable in search
            AttributeBlueprintInterface::RAW_USED_IN_PRODUCT_LISTING => $attribute->get(AttributeBlueprintInterface::USED_IN_PRODUCT_LISTING, false), // Whether the attribute is used in product listings
            AttributeBlueprintInterface::RAW_IS_REQUIRED_IN_ADMIN_STORE => $attribute->get(AttributeBlueprintInterface::IS_REQUIRED_IN_ADMIN_STORE, false), // Whether the attribute is required in the admin store
            AttributeBlueprintInterface::RAW_IS_HTML_ALLOWED_ON_FRONT => $attribute->get(AttributeBlueprintInterface::IS_HTML_ALLOWED_ON_FRONT, false), // Whether HTML is allowed on the frontend for the attribute
            AttributeBlueprintInterface::RAW_IS_VISIBLE_IN_ADVANCED_SEARCH => $attribute->get(AttributeBlueprintInterface::IS_VISIBLE_IN_ADVANCED_SEARCH, false), // Whether the attribute is visible in advanced search
            $for === CustomerAttribute::ENTITY_TYPE ? AttributeBlueprintInterface::RAW_IS_SEARCHABLE_IN_GRID : AttributeBlueprintInterface::RAW_IS_SEARCHABLE => $attribute->get(AttributeBlueprintInterface::IS_SEARCHABLE, false), // Flag indicating if the attribute is searchable
            $for === CustomerAttribute::ENTITY_TYPE ? AttributeBlueprintInterface::RAW_IS_FILTERABLE_IN_GRID : AttributeBlueprintInterface::RAW_IS_FILTERABLE => $attribute->get(AttributeBlueprintInterface::IS_FILTERABLE, false), // Flag indicating if the attribute is filterable

            // Input and frontend-related settings
            AttributeBlueprintInterface::RAW_INPUT => $attribute->get(AttributeBlueprintInterface::INPUT, null), // Type of frontend input (e.g., text, select)
            AttributeBlueprintInterface::RAW_FRONTEND_CLASS => $attribute->get(AttributeBlueprintInterface::FRONTEND_CLASS, null), // CSS class for the frontend input
            AttributeBlueprintInterface::RAW_FRONTEND_MODEL => $attribute->get(AttributeBlueprintInterface::FRONTEND_MODEL, null), // The frontend model for the attribute
            AttributeBlueprintInterface::RAW_INPUT_FILTER => $attribute->get(AttributeBlueprintInterface::INPUT_FILTER, null), // Input filter (validation or sanitization rules)
            AttributeBlueprintInterface::RAW_MULTILINE_COUNT => $attribute->get(AttributeBlueprintInterface::MULTILINE_COUNT, null), // Number of lines for multiline input fields
            AttributeBlueprintInterface::RAW_FRONTEND_INPUT_RENDERER => $attribute->get(AttributeBlueprintInterface::FRONTEND_INPUT_RENDERER, null), // Frontend input renderer for the attribute

            // Backend and system-related settings
            AttributeBlueprintInterface::RAW_TYPE => $attribute->get(AttributeBlueprintInterface::TYPE, null), // Type of backend storage for the attribute
            AttributeBlueprintInterface::RAW_BACKEND => $attribute->get(AttributeBlueprintInterface::BACKEND, default: null), // The backend model for this attribute
            AttributeBlueprintInterface::RAW_BACKEND_TABLE => $attribute->get(AttributeBlueprintInterface::BACKEND_TABLE, null), // Database table for backend storage
            AttributeBlueprintInterface::RAW_DATA_MODEL => $attribute->get(AttributeBlueprintInterface::DATA_MODEL, null), // Data model associated with the attribute
            AttributeBlueprintInterface::RAW_ATTRIBUTE_MODEL => $attribute->get(AttributeBlueprintInterface::ATTRIBUTE_MODEL, null), // The model class used for this attribute

            // Sorting, positioning, and ordering settings
            AttributeBlueprintInterface::RAW_SORT_ORDER => $attribute->get(AttributeBlueprintInterface::SORT_ORDER, 0), // Order in which the attribute is sorted
            AttributeBlueprintInterface::RAW_SEARCH_WEIGHT => $attribute->get(AttributeBlueprintInterface::SEARCH_WEIGHT, null), // Search weight for the attribute
            AttributeBlueprintInterface::RAW_POSITION => $attribute->get(AttributeBlueprintInterface::POSITION, 0), // Position of the attribute (for display order)

            // Options and validation
            AttributeBlueprintInterface::RAW_OPTIONS => ['values' => $attribute->get(AttributeBlueprintInterface::OPTIONS, default: [])], // Attribute options
            AttributeBlueprintInterface::RAW_UNIQUE => $attribute->get(AttributeBlueprintInterface::UNIQUE, false), // Whether the attribute value must be unique
            AttributeBlueprintInterface::RAW_VALIDATE_RULES => $validationRule ? $this->validateRules($validationRule) : null, // Serialized validation rules for the attribute

            // Additional data and identifiers
            AttributeBlueprintInterface::RAW_NOTE => $note, // Any additional note for the attribute
            AttributeBlueprintInterface::RAW_ATTRIBUTE_CODE => $this->getAttribute(), // Attribute code (unique identifier)
            AttributeBlueprintInterface::RAW_LABEL => $attribute->get(AttributeBlueprintInterface::LABEL, null), // Label for the attribute on the frontend
            AttributeBlueprintInterface::RAW_APPLY_TO => $attribute->get(AttributeBlueprintInterface::APPLY_TO, null), // Product types to which the attribute applies
            AttributeBlueprintInterface::RAW_ENTITY_TYPE_ID => $attribute->get(AttributeBlueprintInterface::ENTITY_TYPE_ID, null), // Entity type ID for the attribute
            AttributeBlueprintInterface::RAW_SOURCE => $attribute->get(AttributeBlueprintInterface::SOURCE, null), // Source model for the attribute (used for options or values)
            AttributeBlueprintInterface::RAW_ADDITIONAL_DATA => $attribute->get(AttributeBlueprintInterface::ADDITIONAL_DATA, null), // Any additional data associated with the attribute
            AttributeBlueprintInterface::RAW_GRID_FILTER_CONDITION_TYPE => $attribute->get(AttributeBlueprintInterface::GRID_FILTER_CONDITION_TYPE, null), // Filter condition type for the attribute in grids
        ];
    }

    /**
     * Set validation rules for the attribute.
     *
     * This method processes validation rules provided as either:
     * - An array of strings (e.g., `['min:10', 'max:255']`), or
     * - An associative array (e.g., `['max_text_length' => 255]`).
     * If the input is neither an array nor a string, an exception will be thrown.
     *
     * The rules are ultimately stored as a JSON string.
     *
     * @param array|string $validationRule Validation rules array or JSON string.
     *
     * @throws InvalidArgumentException If the validationRule is not an array or a string.
     *
     * @return string The validation rule JSON string.
     */
    private function validateRules(array|string $validationRule): string
    {
        // If the validation rules are an array, process them.
        if (Validator::isArray($validationRule)) {
            // Check if it's an associative array or an array of strings.
            $isAssociative = array_keys($validationRule) !== range(0, count($validationRule) - 1);

            $validationRule = $isAssociative
                ? $validationRule // Use the associative array directly.
                : array_reduce($validationRule, function($carry, $item) {
                    // Split each rule string into key-value format and add to the array.
                    [$key, $value] = explode(':', $item);
                    $carry[$key] = (int)$value; // Ensure the value is an integer.

                    return $carry;
                }, []);

            // Serialize the rules into JSON format.
            return Json::encode($validationRule);
        }

        // If it's already a string, assume it's a valid JSON or validation rule.
        if (Validator::isString($validationRule)) {
            return $validationRule;
        }

        // Throw an exception if the input is neither an array nor a string.
        throw new InvalidArgumentException('Validation rules must be an array or a JSON string.');
    }
}
