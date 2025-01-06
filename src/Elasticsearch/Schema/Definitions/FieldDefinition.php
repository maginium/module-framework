<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Schema\Definitions;

use Maginium\Framework\Support\Fluent;

/**
 * Class FieldDefinition.
 *
 * This class represents the definition of a field within an Elasticsearch schema.
 * It extends the Fluent class, allowing for dynamic method chaining to configure various
 * settings related to a field, such as analyzer, indexing options, and field properties.
 * The class provides a fluent interface for defining and modifying field attributes.
 *
 * @method $this analyzer(string|array $value) Specifies the analyzer(s) to be used for the field.
 * @method $this copyTo(string $field) Copies the field's value to another field.
 * @method $this coerce(bool $value) Enables or disables automatic type coercion for the field.
 * @method $this docValues(bool $value) Specifies whether doc values are enabled for the field.
 * @method $this norms(bool $value) Specifies whether norms are enabled for the field.
 * @method $this index(bool $value) Enables or disables indexing of the field.
 * @method $this nullValue(mixed $value) Defines the null value for the field.
 * @method $this addFieldMap(string $type) Adds a field mapping with a specified type.
 * @method $this ignoreAbove(int $value) Specifies the length limit for indexed fields (used in text fields).
 * @method $this indexOptions(int $value) Defines the indexing options for the field (used for text fields).
 *
 * @removed $this addType(string $indexName = null) Removed method for adding type to the field.
 * @removed $this format(string $value) Removed method for specifying a format for the field.
 * @removed $this path(string $expression = null) Removed method for defining a path for the field.
 */
class FieldDefinition extends Fluent
{
    // The class itself relies on the dynamic methods provided by the Fluent class,
    // hence no additional properties or methods are necessary here.
}
