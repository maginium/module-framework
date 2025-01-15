<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Traits;

use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Validator;

trait getColumns
{
    /**
     * Fetches the columns of the model's associated database table.
     *
     * This method retrieves the column names from the database using the
     * schema builder, and it stores the columns in a cache to avoid
     * querying the database multiple times.
     *
     * @return array The list of column names.
     */
    private function getTableColumns(): array
    {
        // Cache the column names after the first fetch to optimize repeated calls
        // This ensures that the column names are only fetched once from the database.
        $this->columns ??=
            $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());

        // Convert column names to their qualified form, in case they need to be
        // referenced with table aliases in join queries or subqueries.
        // The `collect` method is used to create a collection that can be mapped.
        $qualifiedColumns = collect($this->columns)
            // `map` method applies the `qualifyColumn` method to each column name
            // making it qualified (e.g., `table.column` format) for joins or subqueries
            ->map(fn($column) => $this->qualifyColumn($column))
            // Convert the collection back to an array
            ->toArray();

        // Merge the original columns and the qualified columns to return both.
        // This ensures that the method returns the full set of columns,
        // both unqualified and qualified, for later use.
        return Arr::merge($this->columns, $qualifiedColumns);
    }

    /**
     * Retrieves the real name of a field from the provided list of fields.
     *
     * This method checks if the field exists in the provided array and returns
     * the field if found, or the original field name otherwise.
     *
     * @param array $fields The list of fields to check against.
     * @param string $field The field name to resolve.
     *
     * @return string The real field name.
     */
    private function realName(array $fields, string $field): string
    {
        // Search for the field in the list of available fields
        // `Arr::search` returns the key if found or false if not found.
        $real = Arr::search($field, $fields, true);

        // If the field is found, `Arr::search` returns the field name as key
        // If not found, `Arr::search` returns false, so return the original field name
        return Validator::isInt($real) ? $field : $real;
    }
}
