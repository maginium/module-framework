<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Illuminate\Support\Facades\DB;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * EqualCaseSensitiveFilter.
 *
 * This filter strategy applies a case-sensitive "EQUAL" condition to a query.
 * It filters records where a column value exactly matches the specified value, with case sensitivity.
 */
class EqualCaseSensitiveFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify "EQUAL CASE SENSITIVE" conditions in incoming filter requests.
     * It ensures that the value matches exactly, considering case sensitivity.
     *
     * @var string
     */
    protected static string $operator = '$eqc';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include "EQUAL" conditions with case-sensitivity.
     * The filter will use different SQL syntax depending on the database connection type.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Get the current database connection type
            $connection = DB::connection()->getDriverName();

            // Loop through each value in the filter's values and apply the condition
            foreach ($this->values as $value) {
                // Switch based on the database type to apply the case-sensitive equality check
                switch ($connection) {
                    // For MariaDB and MySQL, apply BINARY comparison to ensure case-sensitivity
                    case 'mariadb':
                    case 'mysql':
                        $query->whereRaw("BINARY `{$this->column}`= ?", [$value]);

                        break;

                        // For SQLite, apply COLLATE BINARY to ensure case-sensitive equality
                    case 'sqlite':
                        $query->whereRaw("`{$this->column}` COLLATE BINARY = ?", [$value]);

                        break;

                        // For PostgreSQL, apply a standard equality check (case-sensitive by default)
                    case 'pgsql':
                        $query->where($this->column, $value);

                        break;

                        // For SQL Server, use COLLATE Latin1_General_BIN for case-sensitive comparison
                    case 'sqlsrv':
                        $query->whereRaw("`{$this->column}` COLLATE Latin1_General_BIN = ?", $value);

                        break;

                        // Throw an exception if the database connection is unsupported
                    default:
                        throw RuntimeException::make("Unsupported database driver: {$connection}");
                }
            }
        };
    }
}
