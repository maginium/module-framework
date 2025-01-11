<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * NullFilter.
 *
 * This filter strategy applies a "null" condition to the query.
 * It filters records where the specified column has a null value.
 */
class NullFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify the "null" condition in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$null';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include a "null" condition.
     * The filter ensures that the column value is null.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Apply the "null" condition for the column
            $query->whereNull($this->column);
        };
    }
}
