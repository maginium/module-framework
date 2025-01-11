<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;
use Maginium\Framework\Crud\Filters\Resolve;

/**
 * OrFilter.
 *
 * This filter strategy applies an "OR" condition to the query.
 * It is used when multiple conditions need to be applied, and the records should match any of the conditions (i.e., logical OR).
 */
class OrFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify the "OR" condition in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$or';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query by applying an "OR" condition
     * on the specified column(s). The filter will loop through the values and apply each
     * condition using the "OR" logical operator.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Begin an "OR" condition grouping
            $query->where(function($query) {
                // Loop through each condition in the filter
                foreach ($this->values as $value) {
                    // Apply each condition using the "OR" logic
                    $query->orWhere(function($query) use ($value) {
                        // Loop through each key-value pair in the condition
                        foreach ($value as $key => $item) {
                            // Resolve and apply the filter logic for each key-value pair
                            $this->resolve->apply($query, $key, $item);
                        }
                    });
                }
            });
        };
    }
}
