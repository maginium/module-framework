<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Concerns;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Maginium\Framework\Database\Traits\Searchable;

/**
 * Abstract class representing a custom model in the application.
 *
 * This class extends Magento's `AbstractModel` and incorporates various traits and macros
 * to extend functionality such as global query scopes, timestamps, UUID handling, and more.
 * It serves as a foundational class for custom model models that need additional features
 * beyond the default Magento model.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @property string $slugKey The key used for model slugs, typically used for URL slugs.
 * @property string $resourceModel The fully qualified class name of the resource model associated with the model.
 * @property string $table The table associated with the model.
 * @property string $primaryKey The name of the primary key field for the model.
 * @property string $keyType The "type" of the primary key ID.
 * @property string $eventPrefix The event prefix used when firing model-related events.
 * @property string $eventObject The event object type for the model's events.
 * @property string $dtoClass The Data Transfer Object (DTO) class associated with the model.
 */
trait HasEnhancedModel
{
    // Trait for adding conditional logic support to the model.
    use Conditionable;
    // Trait for adding unique ID functionality to the model.
    // use HasUniqueIds;
    // Macroable trait that allows dynamic method calls via registered macros.
    use Macroable {
        __call as macroCall;
        __callStatic as macroCallStatic;
    }

    // Trait for adding search functionality to the model.
    use Searchable;
}
