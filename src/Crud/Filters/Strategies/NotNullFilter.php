<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * NotNullFilter.
 *
 * This filter strategy applies a "not null" condition to the query.
 * It filters records where the specified column does not have a null value.
 */
class NotNullFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify the "not null" condition in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$notNull';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include a "not null" condition.
     * The filter ensures that the column value is not null.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Apply the "not null" condition for the column
            $query->whereNotNull($this->column);
        };
    }
}
