<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Helpers;

use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Database\Facades\CustomerAttribute;
use Maginium\Framework\Database\Interfaces\Data\AttributeBlueprintInterface;
use Maginium\Framework\Database\Schema\AttributeDefinition;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Validator;

/**
 * AttributeMapper class is responsible for mapping attribute definitions into EAV-compatible formats.
 */
class AttributeMapper
{
    /**
     * Map the attribute data to an EAV-compatible format.
     *
     * This method converts an attribute definition into an array that conforms to the EAV attribute setup
     * format required by the database schema. It includes various attribute properties like visibility,
     * filtering, and frontend/backend configurations.
     *
     * @param string $code The attribute code.
     * @param AttributeDefinition $attribute The attribute definition to map.
     *
     * @return array The mapped attribute data in EAV format.
     */
    public static function map(string $code, AttributeDefinition $attribute): array
    {
        // Retrieve the validation rules associated with the attribute, if any.
        $validationRule = $attribute->get(AttributeBlueprintInterface::VALIDATE_RULES, null);

        // Get attribute for property
        $for = $attribute->get(AttributeBlueprintInterface::FOR, null);

        // Return the attribute data in an EAV-compatible array format.
        return [
            // Flags and visibility settings
            AttributeBlueprintInterface::RAW_SYSTEM => $attribute->get(AttributeBlueprintInterface::SYSTEM, false),
            AttributeBlueprintInterface::RAW_VISIBLE => $attribute->get(AttributeBlueprintInterface::VISIBLE, true),
            AttributeBlueprintInterface::RAW_REQUIRED => $attribute->get(AttributeBlueprintInterface::REQUIRED, false),
            AttributeBlueprintInterface::RAW_IS_GLOBAL => $attribute->get(AttributeBlueprintInterface::IS_GLOBAL, false),
            AttributeBlueprintInterface::RAW_USER_DEFINED => $attribute->get(AttributeBlueprintInterface::USER_DEFINED, false),
            AttributeBlueprintInterface::RAW_DEFAULT_VALUE => $attribute->get(AttributeBlueprintInterface::DEFAULT_VALUE, null),
            AttributeBlueprintInterface::RAW_IS_COMPARABLE => $attribute->get(AttributeBlueprintInterface::IS_COMPARABLE, false),
            AttributeBlueprintInterface::RAW_IS_USED_IN_GRID => $attribute->get(AttributeBlueprintInterface::IS_USED_IN_GRID, null),
            AttributeBlueprintInterface::RAW_USED_FOR_SORT_BY => $attribute->get(AttributeBlueprintInterface::USED_FOR_SORT_BY, false),
            AttributeBlueprintInterface::RAW_IS_WYSIWYG_ENABLED => $attribute->get(AttributeBlueprintInterface::IS_WYSIWYG_ENABLED, false),
            AttributeBlueprintInterface::RAW_IS_VISIBLE_IN_GRID => $attribute->get(AttributeBlueprintInterface::IS_VISIBLE_IN_GRID, false),
            AttributeBlueprintInterface::RAW_IS_VISIBLE_ON_FRONT => $attribute->get(AttributeBlueprintInterface::IS_VISIBLE_ON_FRONT, false),
            AttributeBlueprintInterface::RAW_IS_USED_FOR_PRICE_RULES => $attribute->get(AttributeBlueprintInterface::IS_USED_FOR_PRICE_RULES, false),
            AttributeBlueprintInterface::RAW_IS_USED_FOR_PROMO_RULES => $attribute->get(AttributeBlueprintInterface::IS_USED_FOR_PROMO_RULES, false),
            AttributeBlueprintInterface::RAW_IS_FILTERABLE_IN_SEARCH => $attribute->get(AttributeBlueprintInterface::IS_FILTERABLE_IN_SEARCH, false),
            AttributeBlueprintInterface::RAW_USED_IN_PRODUCT_LISTING => $attribute->get(AttributeBlueprintInterface::USED_IN_PRODUCT_LISTING, false),
            AttributeBlueprintInterface::RAW_IS_HTML_ALLOWED_ON_FRONT => $attribute->get(AttributeBlueprintInterface::IS_HTML_ALLOWED_ON_FRONT, false),
            AttributeBlueprintInterface::RAW_IS_REQUIRED_IN_ADMIN_STORE => $attribute->get(AttributeBlueprintInterface::IS_REQUIRED_IN_ADMIN_STORE, false),
            AttributeBlueprintInterface::RAW_IS_VISIBLE_IN_ADVANCED_SEARCH => $attribute->get(AttributeBlueprintInterface::IS_VISIBLE_IN_ADVANCED_SEARCH, false),
            $for === CustomerAttribute::ENTITY_TYPE ? AttributeBlueprintInterface::RAW_IS_SEARCHABLE_IN_GRID : AttributeBlueprintInterface::RAW_IS_SEARCHABLE => $attribute->get(AttributeBlueprintInterface::IS_SEARCHABLE, false),
            $for === CustomerAttribute::ENTITY_TYPE ? AttributeBlueprintInterface::RAW_IS_FILTERABLE_IN_GRID : AttributeBlueprintInterface::RAW_IS_FILTERABLE => $attribute->get(AttributeBlueprintInterface::IS_FILTERABLE, false),

            // Input and frontend-related settings
            AttributeBlueprintInterface::RAW_INPUT => $attribute->get(AttributeBlueprintInterface::INPUT, null),
            AttributeBlueprintInterface::RAW_INPUT_FILTER => $attribute->get(AttributeBlueprintInterface::INPUT_FILTER, null),
            AttributeBlueprintInterface::RAW_FRONTEND_CLASS => $attribute->get(AttributeBlueprintInterface::FRONTEND_CLASS, null),
            AttributeBlueprintInterface::RAW_FRONTEND_MODEL => $attribute->get(AttributeBlueprintInterface::FRONTEND_MODEL, null),
            AttributeBlueprintInterface::RAW_MULTILINE_COUNT => $attribute->get(AttributeBlueprintInterface::MULTILINE_COUNT, null),
            AttributeBlueprintInterface::RAW_FRONTEND_INPUT_RENDERER => $attribute->get(AttributeBlueprintInterface::FRONTEND_INPUT_RENDERER, null),

            // Backend and system-related settings
            AttributeBlueprintInterface::RAW_TYPE => $attribute->get(AttributeBlueprintInterface::TYPE, null),
            AttributeBlueprintInterface::RAW_BACKEND => $attribute->get(AttributeBlueprintInterface::BACKEND, null),
            AttributeBlueprintInterface::RAW_DATA_MODEL => $attribute->get(AttributeBlueprintInterface::DATA_MODEL, null),
            AttributeBlueprintInterface::RAW_BACKEND_TABLE => $attribute->get(AttributeBlueprintInterface::BACKEND_TABLE, null),
            AttributeBlueprintInterface::RAW_ATTRIBUTE_MODEL => $attribute->get(AttributeBlueprintInterface::ATTRIBUTE_MODEL, null),

            // Sorting, positioning, and ordering settings
            AttributeBlueprintInterface::RAW_POSITION => $attribute->get(AttributeBlueprintInterface::POSITION, 0),
            AttributeBlueprintInterface::RAW_SORT_ORDER => $attribute->get(AttributeBlueprintInterface::SORT_ORDER, 0),
            AttributeBlueprintInterface::RAW_SEARCH_WEIGHT => $attribute->get(AttributeBlueprintInterface::SEARCH_WEIGHT, null),

            // Options and validation
            AttributeBlueprintInterface::RAW_VALIDATE_RULES => $validationRule ? static::validateRules($validationRule) : null,
            AttributeBlueprintInterface::RAW_UNIQUE => $attribute->get(AttributeBlueprintInterface::UNIQUE, false),
            AttributeBlueprintInterface::RAW_OPTIONS => ['values' => $attribute->get(AttributeBlueprintInterface::OPTIONS, [])],

            // Additional data and identifiers
            AttributeBlueprintInterface::RAW_ATTRIBUTE_CODE => $code,
            AttributeBlueprintInterface::RAW_NOTE => $attribute->get(AttributeBlueprintInterface::NOTE, null),
            AttributeBlueprintInterface::RAW_LABEL => $attribute->get(AttributeBlueprintInterface::LABEL, null),
            AttributeBlueprintInterface::RAW_SOURCE => $attribute->get(AttributeBlueprintInterface::SOURCE, null),
            AttributeBlueprintInterface::RAW_APPLY_TO => $attribute->get(AttributeBlueprintInterface::APPLY_TO, null),
            AttributeBlueprintInterface::RAW_ENTITY_TYPE_ID => $attribute->get(AttributeBlueprintInterface::ENTITY_TYPE_ID, null),
            AttributeBlueprintInterface::RAW_ADDITIONAL_DATA => $attribute->get(AttributeBlueprintInterface::ADDITIONAL_DATA, null),
            AttributeBlueprintInterface::RAW_GRID_FILTER_CONDITION_TYPE => $attribute->get(AttributeBlueprintInterface::GRID_FILTER_CONDITION_TYPE, null),
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
    private static function validateRules(array|string $validationRule): string
    {
        // Check if the $validationRule is an array.
        if (Validator::isArray($validationRule)) {
            // Check if the array is associative (i.e., has keys) or indexed (i.e., numeric keys).
            $isAssociative = Arr::keys($validationRule) !== range(0, count($validationRule) - 1);

            // If the array is associative, leave it as is; otherwise, process it as an array of strings.
            $validationRule = $isAssociative
                ? $validationRule // If associative, leave it as it is.
                : Arr::reduce($validationRule, function($carry, $item) {
                    // Split each string rule by ':' (e.g., 'min:10' becomes ['min', '10']).
                    [$key, $value] = explode(':', $item);
                    // Assign the value as an integer to the associative array ($carry).
                    $carry[$key] = (int)$value;

                    // Return the modified associative array for further processing.
                    return $carry;
                }, []);

            // Convert the array of validation rules to JSON format and return it.
            return Json::encode($validationRule);
        }

        // If the $validationRule is already a string, return it directly.
        if (Validator::isString($validationRule)) {
            return $validationRule;
        }

        // If the $validationRule is neither an array nor a string, throw an InvalidArgumentException.
        throw InvalidArgumentException::make('Validation rules must be an array or a JSON string.');
    }
}
