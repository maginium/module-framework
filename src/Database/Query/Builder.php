<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;
use Maginium\Framework\Database\Concerns\BuildsQueries;

/**
 * Extended Query Builder for Maginium Framework.
 *
 * This class extends Laravel's default query builder (`BaseBuilder`) and
 * provides additional pagination functionality through the `PaginationFactory` trait.
 */
class Builder extends BaseBuilder
{
    /**
     * Include PaginationFactory for extended pagination support.
     *
     * The PaginationFactory trait provides methods for creating custom paginators,
     * including length-aware, simple, and cursor-based paginators, enhancing
     * the default query builder capabilities.
     */
    use BuildsQueries;
}
