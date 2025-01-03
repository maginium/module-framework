<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;

/**
 * Custom attribute to mark a class as strict in its behavior.
 *
 * The `Strict` attribute is used to annotate a class, indicating that the class should
 * enforce strict data validation, casting, or other operations. When this attribute is
 * applied to a class, it signifies that the class expects its data to be handled with
 * strict rules and does not tolerate exceptions or non-conformities.
 *
 * This can be used to enforce tighter control over how data is processed within the DTO,
 * improving consistency and avoiding errors.
 *
 * @example
 * #[Strict]
 * class MyDto {
 *     public string $name;
 *     public int $age;
 * }
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Strict
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    // This class is a marker attribute and does not require additional logic.
}
