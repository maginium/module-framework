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

    /**
     * Get the schema builder instance for the connection.
     *
     * This method returns a `SchemaBuilder` instance customized for the current
     * database connection. If no schema grammar is set, it uses the default grammar.
     *
     * @return BuilderInterface The schema builder instance for managing database schemas.
     */
    public function getSchemaBuilder(): BuilderInterface;

    /**
     * Set the schema builder instance for the connection.
     *
     * This method is responsible for creating and returning a customized
     * SchemaBuilder instance, passing the current connection to it.
     *
     * @param  string  $builderClass The class name of the builder to set.
     *
     * @return BuilderInterface The schema builder instance.
     */
    public function setSchemaBuilder(string $builderClass): BuilderInterface;
}
