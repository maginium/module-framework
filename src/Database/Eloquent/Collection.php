<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Eloquent;

use Illuminate\Database\Eloquent\Collection as BaseCollection;

/**
 * Class Collection.
 *
 * This class extends the base Laravel Eloquent Collection and allows for enhanced
 * functionality specific to this framework, such as custom query builders and scope management.
 * It ensures seamless integration with the architecture while providing flexibility for complex data handling.
 */
class Collection extends BaseCollection
{
}
