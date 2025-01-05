<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces\Data;

/**
 * Interface defining the blueprint for attribute constants in a database context.
 *
 * This interface provides constants representing different properties of an attribute,
 * such as whether it is searchable, filterable, comparable, or its input type. These constants
 * serve as key identifiers that help in managing and manipulating attributes within a database schema.
 *
 * Each constant corresponds to a specific characteristic of an attribute, such as whether the attribute
 * can be used for search, whether it can be filtered, and how it is represented in the frontend or backend.
 */
interface AttributeBlueprintInterface
{
    /**
     * Constant representing the context in which the attribute is applied.
     */
    public const FOR = 'for';

    /**
     * Constant representing whether the attribute is used in forms.
     */
    public const USED_IN_FORMS = 'usedInForms';

    /**
     * Constant representing whether the attribute is searchable.
     */
    public const IS_SEARCHABLE = 'searchable';

    /**
     * Constant representing whether the attribute is filterable.
     */
    public const IS_FILTERABLE = 'filterable';

    /**
     * Constant representing whether the attribute is comparable.
     */
    public const IS_COMPARABLE = 'comparable';

    /**
     * Constant representing the input type of the attribute.
     */
    public const INPUT = 'input';

    /**
     * Constant representing the note for the attribute.
     */
    public const NOTE = 'note';

    /**
     * Constant representing the label of the attribute.
     */
    public const LABEL = 'label';

    /**
     * Constant representing the backend model for the attribute.
     */
    public const BACKEND = 'backendModel';

    /**
     * Constant representing whether the attribute is a system attribute.
     */
    public const SYSTEM = 'system';

    /**
     * Constant representing the sort order of the attribute.
     */
    public const SORT_ORDER = 'sortOrder';

    /**
     * Constant representing the type of the attribute.
     */
    public const TYPE = 'type';

    /**
     * Constant representing whether the attribute is required.
     */
    public const REQUIRED = 'required';

    /**
     * Constant representing whether the attribute is visible.
     */
    public const VISIBLE = 'visible';

    /**
     * Constant representing whether the attribute is user-defined.
     */
    public const USER_DEFINED = 'userDefined';

    /**
     * Constant representing whether the attribute is searchable in the grid.
     */
    public const IS_SEARCHABLE_IN_GRID = 'searchableInGrid';

    /**
     * Constant representing whether the attribute is used in the grid.
     */
    public const IS_USED_IN_GRID = 'usedInGrid';

    /**
     * Constant representing whether the attribute is visible in the grid.
     */
    public const IS_VISIBLE_IN_GRID = 'visibleInGrid';

    /**
     * Constant representing whether the attribute is filterable in the grid.
     */
    public const IS_FILTERABLE_IN_GRID = 'filterableInGrid';

    /**
     * Constant representing the source of the attribute.
     */
    public const SOURCE = 'source';

    /**
     * Constant representing the position of the attribute.
     */
    public const POSITION = 'position';

    /**
     * Constant representing the validation rules for the attribute.
     */
    public const VALIDATE_RULES = 'validateRules';

    /**
     * Constant representing whether the attribute is unique.
     */
    public const UNIQUE = 'unique';

    /**
     * Constant representing the options for the attribute.
     */
    public const OPTIONS = 'options';

    /**
     * Constant representing the default value of the attribute.
     */
    public const DEFAULT = 'default';

    /**
     * Constant representing whether the attribute is visible in advanced search.
     */
    public const IS_VISIBLE_IN_ADVANCED_SEARCH = 'visibleInAdvancedSearch';

    /**
     * Constant representing whether WYSIWYG (What You See Is What You Get) is enabled for the attribute.
     */
    public const IS_WYSIWYG_ENABLED = 'wysiwygEnabled';

    /**
     * Constant representing whether the attribute is used for promo rules.
     */
    public const IS_USED_FOR_PROMO_RULES = 'usedForPromoRules';

    /**
     * Constant representing whether the attribute is required in the admin store.
     */
    public const IS_REQUIRED_IN_ADMIN_STORE = 'requiredInAdminStore';

    /**
     * Constant representing the frontend input renderer for the attribute.
     */
    public const FRONTEND_INPUT_RENDERER = 'frontendInputRenderer';

    /**
     * Constant representing whether the attribute is global.
     */
    public const IS_GLOBAL = 'global';

    /**
     * Constant representing whether the attribute is visible on the frontend.
     */
    public const IS_VISIBLE_ON_FRONT = 'visibleOnFront';

    /**
     * Constant representing whether HTML is allowed on the frontend for the attribute.
     */
    public const IS_HTML_ALLOWED_ON_FRONT = 'htmlAllowedOnFront';

    /**
     * Constant representing whether the attribute is used for price rules.
     */
    public const IS_USED_FOR_PRICE_RULES = 'usedForPriceRules';

    /**
     * Constant representing whether the attribute is filterable in search.
     */
    public const IS_FILTERABLE_IN_SEARCH = 'filterableInSearch';

    /**
     * Constant representing whether the attribute is used in product listings.
     */
    public const USED_IN_PRODUCT_LISTING = 'usedInProductListing';

    /**
     * Constant representing whether the attribute is used for sorting by.
     */
    public const USED_FOR_SORT_BY = 'usedForSortBy';

    /**
     * Constant representing the models the attribute applies to.
     */
    public const APPLY_TO = 'applyTo';

    /**
     * Constant representing the search weight of the attribute.
     */
    public const SEARCH_WEIGHT = 'searchWeight';

    /**
     * Constant representing additional data for the attribute.
     */
    public const ADDITIONAL_DATA = 'additionalData';

    /**
     * Constant representing the entity type ID for the attribute.
     */
    public const ENTITY_TYPE_ID = 'entityTypeId';

    /**
     * Constant representing the attribute code.
     */
    public const ATTRIBUTE_CODE = 'attributeCode';

    /**
     * Constant representing the attribute model for the attribute.
     */
    public const ATTRIBUTE_MODEL = 'attributeModel';

    /**
     * Constant representing the backend table for the attribute.
     */
    public const BACKEND_TABLE = 'backendTable';

    /**
     * Constant representing the frontend model for the attribute.
     */
    public const FRONTEND_MODEL = 'frontendModel';

    /**
     * Constant representing the frontend class for the attribute.
     */
    public const FRONTEND_CLASS = 'frontendClass';

    /**
     * Constant representing the input filter for the attribute.
     */
    public const INPUT_FILTER = 'inputFilter';

    /**
     * Constant representing the number of lines for multiline attributes.
     */
    public const MULTILINE_COUNT = 'multilineCount';

    /**
     * Constant representing the data model for the attribute.
     */
    public const DATA_MODEL = 'dataModel';

    /**
     * Constant representing the grid filter condition type for the attribute.
     */
    public const GRID_FILTER_CONDITION_TYPE = 'gridFilterConditionType';

    /**
     * Constant representing the raw value for whether the attribute is searchable.
     */
    public const RAW_IS_SEARCHABLE = 'is_searchable';

    /**
     * Constant representing the raw value for whether the attribute is filterable.
     */
    public const RAW_IS_FILTERABLE = 'is_filterable';

    /**
     * Constant representing the raw value for whether the attribute is comparable.
     */
    public const RAW_IS_COMPARABLE = 'is_comparable';

    /**
     * Constant representing the raw value for the input type of the attribute.
     */
    public const RAW_INPUT = 'input';

    /**
     * Constant representing the raw value for the note associated with the attribute.
     */
    public const RAW_NOTE = 'note';

    /**
     * Constant representing the raw value for the label of the attribute.
     */
    public const RAW_LABEL = 'label';

    /**
     * Constant representing the raw value for the backend model associated with the attribute.
     */
    public const RAW_BACKEND = 'backend';

    /**
     * Constant representing the raw value for whether the attribute is a system attribute.
     */
    public const RAW_SYSTEM = 'system';

    /**
     * Constant representing the raw value for the sort order of the attribute.
     */
    public const RAW_SORT_ORDER = 'sort_order';

    /**
     * Constant representing the raw value for the type of the attribute.
     */
    public const RAW_TYPE = 'type';

    /**
     * Constant representing the raw value for whether the attribute is required.
     */
    public const RAW_REQUIRED = 'required';

    /**
     * Constant representing the raw value for whether the attribute is visible.
     */
    public const RAW_VISIBLE = 'visible';

    /**
     * Constant representing the raw value for whether the attribute is user-defined.
     */
    public const RAW_USER_DEFINED = 'user_defined';

    /**
     * Constant representing the raw value for whether the attribute is searchable in the grid.
     */
    public const RAW_IS_SEARCHABLE_IN_GRID = 'is_searchable_in_grid';

    /**
     * Constant representing the raw value for whether the attribute is used in the grid.
     */
    public const RAW_IS_USED_IN_GRID = 'is_used_in_grid';

    /**
     * Constant representing the raw value for whether the attribute is visible in the grid.
     */
    public const RAW_IS_VISIBLE_IN_GRID = 'is_visible_in_grid';

    /**
     * Constant representing the raw value for whether the attribute is filterable in the grid.
     */
    public const RAW_IS_FILTERABLE_IN_GRID = 'is_filterable_in_grid';

    /**
     * Constant representing the raw value for the source of the attribute.
     */
    public const RAW_SOURCE = 'source';

    /**
     * Constant representing the raw value for the position of the attribute.
     */
    public const RAW_POSITION = 'position';

    /**
     * Constant representing the raw value for the validation rules of the attribute.
     */
    public const RAW_VALIDATE_RULES = 'validate_rules';

    /**
     * Constant representing the raw value for whether the attribute is unique.
     */
    public const RAW_UNIQUE = 'unique';

    /**
     * Constant representing the raw value for the options associated with the attribute.
     */
    public const RAW_OPTIONS = 'options';

    /**
     * Constant representing the raw value for the default value of the attribute.
     */
    public const RAW_DEFAULT = 'default_value';

    /**
     * Constant representing the raw value for whether the attribute is visible in advanced search.
     */
    public const RAW_IS_VISIBLE_IN_ADVANCED_SEARCH = 'is_visible_in_advanced_search';

    /**
     * Constant representing the raw value for whether WYSIWYG (What You See Is What You Get) is enabled for the attribute.
     */
    public const RAW_IS_WYSIWYG_ENABLED = 'is_wysiwyg_enabled';

    /**
     * Constant representing the raw value for whether the attribute is used for promo rules.
     */
    public const RAW_IS_USED_FOR_PROMO_RULES = 'is_used_for_promo_rules';

    /**
     * Constant representing the raw value for whether the attribute is required in the admin store.
     */
    public const RAW_IS_REQUIRED_IN_ADMIN_STORE = 'is_required_in_admin_store';

    /**
     * Constant representing the raw value for the frontend input renderer associated with the attribute.
     */
    public const RAW_FRONTEND_INPUT_RENDERER = 'frontend_input_renderer';

    /**
     * Constant representing the raw value for whether the attribute is global.
     */
    public const RAW_IS_GLOBAL = 'is_global';

    /**
     * Constant representing the raw value for whether the attribute is visible on the frontend.
     */
    public const RAW_IS_VISIBLE_ON_FRONT = 'is_visible_on_front';

    /**
     * Constant representing the raw value for whether HTML is allowed on the frontend for the attribute.
     */
    public const RAW_IS_HTML_ALLOWED_ON_FRONT = 'is_html_allowed_on_front';

    /**
     * Constant representing the raw value for whether the attribute is used for price rules.
     */
    public const RAW_IS_USED_FOR_PRICE_RULES = 'is_used_for_price_rules';

    /**
     * Constant representing the raw value for whether the attribute is filterable in search.
     */
    public const RAW_IS_FILTERABLE_IN_SEARCH = 'is_filterable_in_search';

    /**
     * Constant representing the raw value for whether the attribute is used in product listings.
     */
    public const RAW_USED_IN_PRODUCT_LISTING = 'used_in_product_listing';

    /**
     * Constant representing the raw value for whether the attribute is used for sorting by.
     */
    public const RAW_USED_FOR_SORT_BY = 'used_for_sort_by';

    /**
     * Constant representing the raw value for the models the attribute applies to.
     */
    public const RAW_APPLY_TO = 'apply_to';

    /**
     * Constant representing the raw value for the search weight of the attribute.
     */
    public const RAW_SEARCH_WEIGHT = 'search_weight';

    /**
     * Constant representing the raw value for additional data associated with the attribute.
     */
    public const RAW_ADDITIONAL_DATA = 'additional_data';

    /**
     * Constant representing the raw value for the model type ID of the attribute.
     */
    public const RAW_ENTITY_TYPE_ID = 'model_type_id';

    /**
     * Constant representing the raw value for the attribute code of the attribute.
     */
    public const RAW_ATTRIBUTE_CODE = 'attribute_code';

    /**
     * Constant representing the raw value for the attribute model associated with the attribute.
     */
    public const RAW_ATTRIBUTE_MODEL = 'attribute_model';

    /**
     * Constant representing the raw value for the backend table associated with the attribute.
     */
    public const RAW_BACKEND_TABLE = 'backend_table';

    /**
     * Constant representing the raw value for the frontend model associated with the attribute.
     */
    public const RAW_FRONTEND_MODEL = 'frontend_model';

    /**
     * Constant representing the raw value for the frontend class associated with the attribute.
     */
    public const RAW_FRONTEND_CLASS = 'frontend_class';

    /**
     * Constant representing the raw value for the input filter associated with the attribute.
     */
    public const RAW_INPUT_FILTER = 'input_filter';

    /**
     * Constant representing the raw value for the number of lines for multiline attributes.
     */
    public const RAW_MULTILINE_COUNT = 'multiline_count';

    /**
     * Constant representing the raw value for the data model associated with the attribute.
     */
    public const RAW_DATA_MODEL = 'data_model';

    /**
     * Constant representing the raw value for the grid filter condition type associated with the attribute.
     */
    public const RAW_GRID_FILTER_CONDITION_TYPE = 'grid_filter_condition_type';

    /**
     * Constant representing whether the attribute is used in forms.
     */
    public const RAW_USED_IN_FORMS = 'used_in_forms';
}
