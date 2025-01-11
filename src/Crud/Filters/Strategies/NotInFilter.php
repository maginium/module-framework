<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * NotInFilter.
 *
 * This filter strategy applies a "not in" condition to the query.
 * It filters records where the column value is not within the specified list of values.
 */
class NotInFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify the "not in" condition in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$notIn';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include a "not in" condition.
     * The filter ensures that the column value is not in the specified list of values.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Apply the "not in" condition for the column with the provided values
            $query->whereNotIn($this->column, $this->values);
        };
    }
}
