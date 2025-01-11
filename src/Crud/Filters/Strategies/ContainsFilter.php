<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Illuminate\Support\Facades\DB;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * ContainsFilter.
 *
 * This filter strategy applies a "CONTAINS" condition to a query.
 * It is used to filter records where a column value contains a specified substring.
 * The implementation varies based on the database driver being used.
 */
class ContainsFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify "CONTAINS" conditions in incoming filter requests.
     *
     * @var string
     */
    protected static string $operator = '$contains';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include "CONTAINS" conditions.
     * The filter logic is implemented differently for various database drivers.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Retrieve the name of the database driver being used
            $connection = DB::connection()->getDriverName();

            // Apply the appropriate filtering logic based on the database driver
            foreach ($this->values as $value) {
                switch ($connection) {
                    case 'sqlite':
                    case 'mariadb':
                    case 'mysql':
                        // Use a standard SQL `LIKE` clause for MySQL, MariaDB, and SQLite
                        $query->whereRaw("`{$this->column}` LIKE ?", ["%{$value}%"]);

                        break;

                    case 'pgsql':
                        // Use an `ILIKE` clause for PostgreSQL to enable case-insensitive matching
                        $query->where($this->column, 'ILIKE', "%{$value}%");

                        break;

                    case 'sqlsrv':
                        // Use a collation-sensitive `LIKE` clause for SQL Server
                        $query->whereRaw("`{$this->column}` COLLATE Latin1_General_CI_AS LIKE ?", ["%{$value}%"]);

                        break;

                    default:
                        // Throw an exception if the database driver is unsupported
                        throw RuntimeException::make("Unsupported database driver: {$connection}");
                }
            }
        };
    }
}
