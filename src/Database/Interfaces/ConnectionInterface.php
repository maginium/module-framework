<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces;

use Illuminate\Database\ConnectionInterface as BaseConnectionInterface;
use Maginium\Framework\Database\Query\Builder as QueryBuilder;

/**
 * Interface ConnectionInterface.
 */
interface ConnectionInterface extends BaseConnectionInterface
{
    /**
     * Begin a fluent query against a database table.
     *
     * @param  Closure|QueryBuilder|string  $table
     * @param  string|null  $as
     *
     * @return QueryBuilder
     */
    public function table($table, $as = null);
}
