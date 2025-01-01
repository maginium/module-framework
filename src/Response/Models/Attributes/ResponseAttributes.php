<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Models\Attributes;

use Maginium\Framework\Response\Models\Attributes\Response\GetterAttributes;
use Maginium\Framework\Response\Models\Attributes\Response\SetterAttributes;

/**
 * Trait ResponseAttributes.
 *
 * This trait defines the attributes for the Response model, providing methods
 * for getting and setting various attributes associated with the Response.
 */
trait ResponseAttributes
{
    // Trait that provides response getter methods for attributes
    use GetterAttributes;
    // Trait that provides response setter methods for attributes
    use SetterAttributes;
}
