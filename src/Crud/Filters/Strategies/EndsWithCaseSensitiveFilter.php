<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Illuminate\Support\Facades\DB;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * EndsWithCaseSensitiveFilter.
 *
 * This filter strategy applies an "ENDS WITH" condition to a query with case-sensitive matching.
 * It filters records where a column value ends with the specified substring.
 * The implementation differs based on the database driver being used.
 */
class EndsWithCaseSensitiveFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify "ENDS WITH" conditions in incoming filter requests,
     * with case-sensitive matching.
     *
     * @var string
     */
    protected static string $operator = '$endsWithc';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include "ENDS WITH" conditions
     * with case-sensitive matching.
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
                    case 'mariadb':
                    case 'mysql':
                        // Use a binary `LIKE` clause for MySQL and MariaDB to ensure case-sensitive matching
                        $query->whereRaw("BINARY `{$this->column}` LIKE ?", '%' . $value);

                        break;

                    case 'sqlite':
                        // Use a binary collation `LIKE` clause for SQLite to enforce case sensitivity
                        $query->whereRaw("`{$this->column}` COLLATE BINARY LIKE ?", '%' . $value);

                        break;

                    case 'pgsql':
                        // Use a case-sensitive `LIKE` clause for PostgreSQL
                        $query->where($this->column, 'LIKE', '%' . $value);

                        break;

                    case 'sqlsrv':
                        // Use a binary collation `LIKE` clause for SQL Server for case-sensitive matching
                        $query->whereRaw("`{$this->column}` COLLATE Latin1_General_BIN LIKE ?", '%' . $value);

                        break;

                    default:
                        // Throw an exception if the database driver is unsupported
                        throw RuntimeException::make("Unsupported database driver: {$connection}");
                }
            }
        };
    }
}
