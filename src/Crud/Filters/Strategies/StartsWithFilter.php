<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * StartsWithFilter.
 *
 * This filter strategy applies a "starts with" condition to the query.
 * It checks whether the column values begin with a specified prefix.
 */
class StartsWithFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify the "starts with" condition
     * in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$startsWith';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query by applying
     * the "starts with" condition on the specified column(s).
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Loop through each value in the filter and apply the "starts with" condition
            foreach ($this->values as $value) {
                // Apply the "starts with" condition using the 'like' operator
                $query->where($this->column, 'like', $value . '%');
            }
        };
    }
}
