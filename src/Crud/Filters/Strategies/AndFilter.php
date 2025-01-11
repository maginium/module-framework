<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;
use Maginium\Framework\Crud\Filters\Resolve;

/**
 * AndFilter.
 *
 * This filter strategy applies an "AND" condition to a query.
 * It processes multiple nested conditions and applies them as a group using closures.
 * Each condition is resolved dynamically through the Resolve class.
 */
class AndFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify "AND" conditions in incoming filter requests.
     *
     * @var string
     */
    protected static string $operator = '$and';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include "AND" conditions.
     * Each group of conditions is wrapped in a nested `where` clause for logical grouping.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Iterate over the filter values to process each "AND" group
            foreach ($this->values as $value) {
                // Add a nested where clause for each group of conditions
                $query->where(function($query) use ($value) {
                    // Process each key-value pair within the group
                    foreach ($value as $key => $item) {
                        // Resolve and apply the condition using the Resolve class
                        $this->resolve->apply($query, $key, $item);
                    }
                });
            }
        };
    }
}
