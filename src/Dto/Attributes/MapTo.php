<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;

/**
 * Custom attribute to specify a target name or index for mapping.
 *
 * The `MapTo` attribute is used to annotate a class or property, indicating the
 * target name or index to which the property should be mapped. This can be useful
 * when transforming DTO properties or mapping data to a specific field in another structure
 * like arrays or external data sources.
 *
 * @example
 * #[MapTo('target_field')]
 * public string $sourceField;
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class MapTo
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * The name or index of the target property to map to.
     *
     * This value indicates the field or index that the data should be mapped to.
     * It could be a string (e.g., 'target_field') or an integer (e.g., 0 for array indices),
     * depending on the mapping requirements.
     *
     * @var string
     */
    public function __construct(
        public string $name,  // The target name or index
    ) {
    }
}
