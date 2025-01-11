<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Illuminate\Support\Facades\DB;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * ContainsCaseSensitiveFilter.
 *
 * This filter strategy applies a case-sensitive "contains" filter to a database query.
 * It supports multiple database drivers, including MySQL, MariaDB, SQLite, PostgreSQL, and SQL Server.
 * If an unsupported driver is detected, a RuntimeException is thrown.
 */
class ContainsCaseSensitiveFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify case-sensitive "contains" filters in incoming requests.
     *
     * @var string
     */
    protected static string $operator = '$containsc';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include a case-sensitive "contains" condition
     * for the specified column and values. It dynamically adapts to the database driver in use.
     *
     * @throws RuntimeException if the database driver is unsupported.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Get the name of the current database driver
            $connection = DB::connection()->getDriverName();

            // Loop through all filter values and apply the condition for each
            foreach ($this->values as $value) {
                switch ($connection) {
                    case 'mariadb':
                    case 'mysql':
                        // For MySQL and MariaDB, use the `BINARY` keyword to enforce case sensitivity
                        $query->whereRaw(
                            "BINARY `{$this->column}` LIKE ?",
                            ["%{$value}%"],
                        );

                        break;

                    case 'sqlite':
                        // For SQLite, use the `COLLATE BINARY` clause to enforce case sensitivity
                        $query->whereRaw(
                            "`{$this->column}` COLLATE BINARY LIKE ?",
                            ["%{$value}%"],
                        );

                        break;

                    case 'pgsql':
                        // For PostgreSQL, the default `LIKE` is case-sensitive
                        $query->where(
                            $this->column,
                            'LIKE',
                            "%{$value}%",
                        );

                        break;

                    case 'sqlsrv':
                        // For SQL Server, use the `COLLATE` clause with a binary collation for case sensitivity
                        $query->whereRaw(
                            "`{$this->column}` COLLATE Latin1_General_BIN LIKE ?",
                            ["%{$value}%"],
                        );

                        break;

                    default:
                        // Throw an exception if the database driver is unsupported
                        throw RuntimeException::make("Unsupported database driver: {$connection}");
                }
            }
        };
    }
}
