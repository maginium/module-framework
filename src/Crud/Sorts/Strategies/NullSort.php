<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Sorts\Strategies;

use Illuminate\Support\Facades\DB;
use Maginium\Framework\Crud\Sorts\AbstractSort;
use Maginium\Framework\Database\Eloquent\Builder;

/**
 * Class NullSort.
 *
 * A strategy class for applying sorting with special handling for null values.
 *
 * This class sorts query results based on whether a column contains null values. The sorting behavior
 * varies based on the database driver. Specifically, for PostgreSQL (pgsql), null values are placed last.
 * For other database drivers, the query is ordered with null values first.
 */
class NullSort extends AbstractSort
{
    /**
     * Apply sorting to the query with special handling for null values.
     *
     * This method checks the database driver in use and adjusts the ordering of null values accordingly.
     * - For PostgreSQL, null values are placed last.
     * - For other database drivers, null values are placed first, and the rest are sorted as normal.
     *
     * @return Builder The modified query builder with applied sorting.
     */
    public function apply(): Builder
    {
        // Determine the database driver being used.
        return match (DB::getDriverName()) {
            // If the database driver is PostgreSQL, sort null values last.
            'pgsql' => $this->query->orderByRaw("{$this->column} {$this->direction} nulls last"),

            // For all other database drivers, sort null values first and apply normal sorting afterwards.
            default => $this->query->orderByRaw("{$this->column} is null") // First order by whether the value is null.
                ->orderByRaw("{$this->column} {$this->direction}"), // Then apply the normal sorting (asc/desc).
        };
    }
}
