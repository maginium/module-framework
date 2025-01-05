<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Schema;

use Maginium\Framework\Support\Fluent;

/**
 * Class AttributeBlueprint.
 *
 * Defines the schema and settings for an attribute, providing methods for configuring
 * column type, behavior, and properties in the database. Allows chaining for fluent
 * API usage.
 *
 * @method $this for(string $modelType) Set the attribute model type
 * @method $this searchable(bool $isEnabled) Set whether the attribute is searchable in the system.
 * @method $this filterable(bool $isEnabled) Indicates if the attribute can be used as a filter.
 * @method $this comparable(bool $isEnabled) Specifies if the attribute can be compared with others.
 * @method $this input(string $inputType) Defines the input type for the attribute (e.g., text, select, etc.).
 * @method $this note(string $note) A descriptive note or comment about the attribute.
 * @method $this label(string $label) Returns the label for the attribute, usually used in the UI.
 * @method $this backendModel(string $model) Specifies the backend model to use for this attribute.
 * @method $this system(bool $isSystem) Indicates whether the attribute is a system attribute (used internally only).
 * @method $this sortOrder(int $sortOrder) The order in which the attribute appears in the UI (e.g., in a form or grid).
 * @method $this type(string $type) Returns the data type of the attribute (e.g., varchar, int).
 * @method $this required(bool $isRequired) Indicates whether this attribute is required (can't be left blank).
 * @method $this visible(bool $isVisible) Returns whether the attribute is visible in the UI (e.g., in forms or grids).
 * @method $this userDefined(bool $userDefined) Specifies whether the attribute is user-defined or system-defined.
 * @method $this searchableInGrid(bool $isEnabled) Indicates whether the attribute can be searched in the admin grid.
 * @method $this usedInGrid(bool $isEnabled) States whether the attribute is used in the admin grid for display.
 * @method $this visibleInGrid(bool $isVisibleInGrid) Returns whether the attribute is visible in the admin grid.
 * @method $this filterableInGrid(bool $isEnabled) States if the attribute can be used as a filter in the admin grid.
 * @method $this source(string $source) Specifies the source model used to retrieve the attribute's options.
 * @method $this position(int $postion) Defines the position of the attribute in a form, grid, or UI element.
 * @method $this unique(bool $isUnique) Indicates if the attribute must have a unique value for each model.
 * @method $this default(string $default) The default value to be assigned to the attribute when not specified by the user.
 * @method $this visibleInAdvancedSearch(bool $isEnabled) Specifies whether the attribute is visible in advanced search forms.
 * @method $this wysiwygEnabled(bool $isEnabled) Indicates whether the attribute supports WYSIWYG (What You See Is What You Get) editing.
 * @method $this usedForPromoRules(bool $isEnabled) States whether the attribute can be used in promotional rules.
 * @method $this requiredInAdminStore(bool $isRequired) Specifies if the attribute is required when managing in the admin panel.
 * @method $this frontendInputRenderer(string $renderer) Returns the renderer for the frontend input field for this attribute.
 * @method $this global(string $scope) Indicates whether the attribute is global and should be shared across all stores.
 * @method $this visibleOnFront(bool $isVisible) Specifies whether the attribute is visible on the frontend of the store.
 * @method $this htmlAllowedOnFront(bool $isAllowed) States if HTML content is allowed for this attribute on the frontend.
 * @method $this usedForPriceRules(bool $isEnabled) Indicates whether the attribute can be used for price-related rules.
 * @method $this filterableInSearch(bool $isEnabled) Specifies whether the attribute is filterable when searching for products.
 * @method $this usedInProductListing(bool $isEnabled) States if the attribute is used for product listings.
 * @method $this usedForSortBy(bool $isEnabled) Specifies if the attribute is available as a sorting option in product listings.
 * @method $this applyTo(array $modelTypes) Defines the types of models (e.g., products, categories) this attribute applies to.
 * @method $this searchWeight(int $weight) Sets the weight or importance of this attribute in search ranking.
 * @method $this additionalData(array $data) Provides any additional data or metadata related to the attribute.
 * @method $this modelTypeId(int $modelTypeId) Returns the model type ID associated with this attribute.
 * @method $this attributeCode(string $attributeCode) Returns the unique code or identifier for the attribute.
 * @method $this attributeModel(string $model) Specifies the model used for managing this attribute.
 * @method $this backendTable(string $table) Returns the name of the backend table used for storing this attribute's data.
 * @method $this frontendModel(string $frontendModel) Returns the frontend model used to render the attribute.
 * @method $this frontendClass(string $class) Specifies the frontend class applied to the attribute in the UI.
 * @method $this inputFilter(string $filter) Returns the filter applied to the attribute input.
 * @method $this multilineCount(int $lines) Specifies the number of lines for a multiline input field (if applicable).
 * @method $this dataModel(string $dataModel) Returns the data model used for the attribute's storage and retrieval.
 * @method $this gridFilterConditionType(string $type) Specifies the type of condition used for filtering in the admin grid.
 * @method $this options(array $options) Define a static list of selectable options for the attribute.
 * @method $this searchable(bool $searchable = false) Enable search functionality for the attribute
 * @method $this filterable(bool $filterable = false) Set the attribute as filterable
 * @method $this comparable(bool $comparable = false) Enable comparability for the attribute
 * @method $this validateRules(array $validateRules = null) Define validation rules for the attribute
 * @method $this usedInForms(array $forms) Set the attribute as used in forms
 */
class AttributeDefinition extends Fluent
{
}
