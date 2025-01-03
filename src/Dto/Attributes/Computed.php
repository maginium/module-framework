<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;

/**
 * The `Computed` attribute is used to mark properties of a Data Transfer Object (DTO)
 * that are computed values. These are typically values that are calculated dynamically
 * and should not be directly set during instantiation or passed in data.
 *
 * This attribute can be applied to properties within DTO classes to signal that they
 * represent computed values, which may be derived from other properties or data.
 *
 * @example
 * #[Computed]
 * public string $fullName;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Computed
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * Computed constructor. This attribute does not require parameters.
     *
     * You can attach this attribute to any public property of a DTO class that is
     * computed and should not be manually set or passed during object instantiation.
     * The computed value will be calculated based on other logic or values.
     */
    public function __construct()
    {
        // No parameters required for this attribute.
    }
}
