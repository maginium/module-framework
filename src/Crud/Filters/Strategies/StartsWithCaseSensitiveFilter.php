<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Illuminate\Support\Facades\DB;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * StartsWithCaseSensitiveFilter.
 *
 * This filter strategy applies a case-sensitive "starts with" condition to the query.
 * It checks whether the column values begin with a specified prefix, considering case sensitivity.
 */
class StartsWithCaseSensitiveFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify the "starts with" condition with case sensitivity
     * in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$startsWithc';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query by applying a case-sensitive
     * "starts with" condition on the specified column(s). The filter will loop through the values
     * and apply each condition, considering case sensitivity for the respective database connection.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Get the current database connection driver name
            $connection = DB::connection()->getDriverName();

            // Loop through each value in the filter
            foreach ($this->values as $value) {
                // Apply the "starts with" condition for each database connection type
                switch ($connection) {
                    case 'mariadb':
                    case 'mysql':
                        // For MySQL and MariaDB, apply a case-sensitive "starts with" condition
                        $query->whereRaw("BINARY `{$this->column}` like ?", [$value . '%']);

                        break;

                    case 'sqlite':
                        // For SQLite, apply a case-sensitive "starts with" condition
                        $query->whereRaw("`{$this->column}` COLLATE BINARY like ?", [$value . '%']);

                        break;

                    case 'pgsql':
                        // For PostgreSQL, apply the "starts with" condition
                        $query->where($this->column, 'LIKE', $value . '%');

                        break;

                    case 'sqlsrv':
                        // For SQL Server, apply a case-sensitive "starts with" condition
                        $query->whereRaw("`{$this->column}` COLLATE Latin1_General_BIN LIKE ?", [$value . '%']);

                        break;

                    default:
                        // Throw an exception if the database driver is unsupported
                        throw RuntimeException::make("Unsupported database driver: {$connection}");
                }
            }
        };
    }
}
