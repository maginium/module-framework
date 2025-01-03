<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;

/**
 * Custom attribute to specify a source name or index for mapping.
 *
 * The `MapFrom` attribute is used to annotate a class or property, indicating the
 * source name or index from which the property should be mapped. This is typically
 * used when mapping data from one structure to another, such as transforming DTO properties
 * or mapping data from arrays or external data sources.
 *
 * @example
 * #[MapFrom('source_field')]
 * public string $destinationField;
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class MapFrom
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * The name or index of the source property to map from.
     *
     * This value can either be a string (e.g., 'source_field') or an integer (e.g., 0 for array indices),
     * indicating the field or index that the data should be mapped from.
     *
     * @var string|int
     */
    public function __construct(
        public string|int $name,  // The source name or index
    ) {
    }
}
