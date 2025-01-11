<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * EndsWithFilter.
 *
 * This filter strategy applies an "ENDS WITH" condition to a query with case-insensitive matching.
 * It filters records where a column value ends with the specified substring.
 */
class EndsWithFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify "ENDS WITH" conditions in incoming filter requests,
     * with case-insensitive matching.
     *
     * @var string
     */
    protected static string $operator = '$endsWith';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include "ENDS WITH" conditions.
     * It performs a case-insensitive match using the `LIKE` operator for various databases.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Loop through each value in the filter's values and apply the condition
            foreach ($this->values as $value) {
                // Apply the "ENDS WITH" condition to the specified column using a LIKE query
                // It matches records where the column ends with the specified value
                $query->where($this->column, 'like', '%' . $value);
            }
        };
    }
}
