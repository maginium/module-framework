<?php

declare(strict_types=1);

namespace Maginium\Framework\Database;

use Illuminate\Support\Traits\Conditionable;
use Maginium\Framework\Support\DataObject;

/**
 * Abstract class for custom models.
 *
 * This class extends the `DataObject` and integrates key functionalities
 * like conditional logic, extendable methods, and static method handling.
 * It introduces features such as global scopes, timestamps, UUIDs,
 * and other custom extensions.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @property string $slugKey The key used for model slugs.
 */
abstract class ObjectModel extends DataObject
{
    // Adds conditional logic to the model.
    use Conditionable;
}
