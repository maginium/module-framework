<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Illuminate\Support\Facades\DB;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * NotContainsSensitiveFilter.
 *
 * This filter strategy applies a case-sensitive "not contains" condition to a query.
 * It filters records where the column value does not contain the specified string in a case-sensitive manner.
 */
class NotContainsSensitiveFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify the "not contains" case-sensitive condition in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$notContainsc';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include a "not contains" case-sensitive condition.
     * The filter ensures that the column value does not contain the specified string in a case-sensitive manner.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Get the database connection driver name to handle each database type accordingly
            $connection = DB::connection()->getDriverName();

            // Loop through each value in $this->values and apply the "not contains" condition
            foreach ($this->values as $value) {
                switch ($connection) {
                    case 'mariadb':
                    case 'mysql':
                        // For MySQL/MariaDB, apply a case-sensitive "not like" query
                        $query->whereRaw("BINARY `{$this->column}` not like ?", ["%{$value}%"]);

                        break;

                    case 'sqlite':
                        // For SQLite, apply a case-sensitive "not like" query using COLLATE BINARY
                        $query->whereRaw("`{$this->column}` COLLATE BINARY not like ?", ["%{$value}%"]);

                        break;

                    case 'pgsql':
                        // For PostgreSQL, apply a case-sensitive "not like" query
                        $query->whereNot($this->column, 'LIKE', "%{$value}%");

                        break;

                    case 'sqlsrv':
                        // For SQL Server, apply a case-sensitive "not like" query using COLLATE Latin1_General_BIN
                        $query->whereRaw("`{$this->column}` COLLATE Latin1_General_BIN not LIKE ?", ["%{$value}%"]);

                        break;

                    default:
                        // Throw an exception for unsupported database drivers
                        throw RuntimeException::make("Unsupported database driver: {$connection}");
                }
            }
        };
    }
}
