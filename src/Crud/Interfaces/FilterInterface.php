<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces;

use Closure;

/**
 * Interface for filters that can be applied to queries.
 *
 * This interface defines the structure for filters that can be dynamically
 * applied to database queries. Filters should implement the logic to apply
 * themselves to queries using the `apply()` method, and they should also
 * define an operator through the `operator()` method, which is used to identify
 * the filter when processing queries.
 */
interface FilterInterface
{
    /**
     * Retrieves the operator associated with the filter.
     *
     * This operator is used to identify the filter when applied.
     *
     * @return string|null The operator string, or null if no operator is defined.
     */
    public static function operator(): ?string;

    /**
     * Applies the filter logic to a query.
     *
     * This method defines how the filter will be applied to a query, typically
     * using a closure that modifies the query builder or query itself.
     *
     * @return Closure A closure that applies the filter to the query.
     */
    public function apply(): Closure;
}
