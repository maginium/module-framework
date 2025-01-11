<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Helpers;

use Illuminate\Support\Facades\Schema;
use Maginium\Framework\Database\Eloquent\Model;
use Maginium\Framework\Support\Reflection;

/**
 * Class Column.
 *
 * A helper class to manage columns in Eloquent models.
 */
class Column
{
    /**
     * Get all columns for a given table.
     *
     * This method uses Laravel's Schema facade to retrieve all the column names from the
     * specified database table.
     *
     * @param string $table The name of the database table.
     *
     * @return array An array of column names for the given table.
     */
    public static function getColumns(string $table): array
    {
        // Use Schema facade to get the column names of the given table
        return Schema::getColumnListing($table);
    }

    /**
     * Get the available columns for sorting in a model.
     *
     * This method checks if the model has a 'sortFields' property. If it does, it returns the
     * value of that property, which contains the available columns for sorting. If the property
     * is not set, the method falls back to getting all the columns of the model's corresponding table.
     *
     * @param Model $model The model instance.
     *
     * @return array An array of columns available for sorting.
     */
    public static function getAvailableSortColumns(Model $model): array
    {
        // Check if the 'sortFields' property exists on the model
        if (Reflection::hasProperty($model, 'sortFields')) {
            $rProperty = Reflection::getProperty($model, 'sortFields');
            // Make the property accessible, even if it is private or protected
            $rProperty->setAccessible(true);

            // Return the value of the 'sortFields' property (an array of sort columns)
            return $rProperty->getValue($model);
        }

        // If no 'sortFields' property exists, fall back to getting columns from the table
        return self::getColumns($model->getTable());
    }
}
