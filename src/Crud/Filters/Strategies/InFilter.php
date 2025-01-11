<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * InFilter.
 *
 * This filter strategy applies an "IN" condition to a query.
 * It filters records where the column value is one of the values in the specified list.
 */
class InFilter extends Filter
{
    /**
     * Operator to detect in the query parameters.
     *
     * This operator is used to identify the "IN" condition in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$in';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include an "IN" condition.
     * The filter ensures that the column value is one of the values in the provided list.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Apply the "IN" condition to the column using the provided values
            $query->whereIn($this->column, $this->values);
        };
    }
}
