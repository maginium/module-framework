<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Sorts\Strategies;

use Maginium\Framework\Crud\Sorts\AbstractSort;
use Maginium\Framework\Database\Eloquent\Builder;

/**
 * Class DefaultSort.
 *
 * A default sorting strategy for applying sorting to queries based on a column.
 *
 * This class is used to sort results based on a specified column in the model's table,
 * using the direction provided (ascending or descending). This is the simplest sorting
 * strategy without involving complex relationships.
 */
class DefaultSort extends AbstractSort
{
    /**
     * Apply default sorting to the query.
     *
     * This method directly applies sorting to the query using the specified column
     * and direction. It does not involve any relationships or subqueries,
     * making it the most straightforward sorting strategy.
     *
     * @return Builder The modified query builder with applied sorting.
     */
    public function apply(): Builder
    {
        // Apply sorting directly on the model's table using the specified column and direction.
        return $this->query->orderBy($this->column, $this->direction);
    }
}
