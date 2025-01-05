<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces;

use Closure;
use InvalidArgumentException;
use LogicException;
use Maginium\Framework\Database\Connection;

/**
 * Interface BuilderInterface.
 *
 * This interface defines the methods for schema building operations in the database.
 * It includes methods for creating, modifying, and dropping tables, databases, and columns,
 * as well as checking for the existence of tables, columns, and views. It also supports
 * managing foreign key constraints, and provides methods for working with schema blueprints.
 */
interface BuilderInterface
{
    /**
     * Set the default string length for migrations.
     *
     * @param  int  $length
     *
     * @return void
     */
    public static function defaultStringLength($length);

    /**
     * Set the default morph key type for migrations.
     *
     * @param  string  $type
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    public static function defaultMorphKeyType(string $type);

    /**
     * Set the default morph key type for migrations to UUIDs.
     *
     * @return void
     */
    public static function morphUsingUuids();

    /**
     * Set the default morph key type for migrations to ULIDs.
     *
     * @return void
     */
    public static function morphUsingUlids();

    /**
     * Attempt to use native schema operations for dropping, renaming, and modifying columns, even if Doctrine DBAL is installed.
     *
     * @param  bool  $value
     *
     * @return void
     */
    public static function useNativeSchemaOperationsIfPossible(bool $value = true);

    /**
     * Create a new table on the schema.
     *
     * @param  string  $table
     * @param  Closure  $callback
     *
     * @return void
     */
    public function create(string $table, Closure $callback);

    /**
     * Create a database in the schema.
     *
     * @param  string  $name
     *
     * @throws LogicException
     *
     * @return bool
     */
    public function createDatabase(string $name);

    /**
     * Drop a database from the schema if the database exists.
     *
     * @param  string  $name
     *
     * @throws LogicException
     *
     * @return bool
     */
    public function dropDatabaseIfExists(string $name);

    /**
     * Determine if the given table exists.
     *
     * @param  string  $table
     *
     * @return bool
     */
    public function hasTable(string $table);

    /**
     * Determine if the given view exists.
     *
     * @param  string  $view
     *
     * @return bool
     */
    public function hasView(string $view);

    /**
     * Get the tables that belong to the database.
     *
     * @return array
     */
    public function getTables();

    /**
     * Get the names of the tables that belong to the database.
     *
     * @return array
     */
    public function getTableListing();

    /**
     * Get the views that belong to the database.
     *
     * @return array
     */
    public function getViews();

    /**
     * Get the user-defined types that belong to the database.
     *
     * @return array
     */
    public function getTypes();

    /**
     * Get all of the table names for the database.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @throws LogicException
     *
     * @return array
     */
    public function getAllTables();

    /**
     * Determine if the given table has a given column.
     *
     * @param  string  $table
     * @param  string  $column
     *
     * @return bool
     */
    public function hasColumn(string $table, string $column);

    /**
     * Determine if the given table has given columns.
     *
     * @param  string  $table
     * @param  array  $columns
     *
     * @return bool
     */
    public function hasColumns(string $table, array $columns);

    /**
     * Execute a table builder callback if the given table has a given column.
     *
     * @param  string  $table
     * @param  string  $column
     * @param  Closure  $callback
     *
     * @return void
     */
    public function whenTableHasColumn(string $table, string $column, Closure $callback);

    /**
     * Execute a table builder callback if the given table doesn't have a given column.
     *
     * @param  string  $table
     * @param  string  $column
     * @param  Closure  $callback
     *
     * @return void
     */
    public function whenTableDoesntHaveColumn(string $table, string $column, Closure $callback);

    /**
     * Get the data type for the given column name.
     *
     * @param  string  $table
     * @param  string  $column
     * @param  bool  $fullDefinition
     *
     * @return string
     */
    public function getColumnType(string $table, string $column, bool $fullDefinition = false);

    /**
     * Get the column listing for a given table.
     *
     * @param  string  $table
     *
     * @return array
     */
    public function getColumnListing(string $table);

    /**
     * Get the columns for a given table.
     *
     * @param  string  $table
     *
     * @return array
     */
    public function getColumns(string $table);

    /**
     * Get the indexes for a given table.
     *
     * @param  string  $table
     *
     * @return array
     */
    public function getIndexes(string $table);

    /**
     * Get the names of the indexes for a given table.
     *
     * @param  string  $table
     *
     * @return array
     */
    public function getIndexListing(string $table);

    /**
     * Determine if the given table has a given index.
     *
     * @param  string  $table
     * @param  string|array  $index
     * @param  string|null  $type
     *
     * @return bool
     */
    public function hasIndex(string $table, $index, ?string $type = null);

    /**
     * Get the foreign keys for a given table.
     *
     * @param  string  $table
     *
     * @return array
     */
    public function getForeignKeys(string $table);

    /**
     * Modify a table on the schema.
     *
     * @param  string  $table
     * @param  Closure  $callback
     *
     * @return void
     */
    public function table(string $table, Closure $callback);

    /**
     * Drop a table from the schema.
     *
     * @param  string  $table
     *
     * @return void
     */
    public function drop(string $table);

    /**
     * Drop a table from the schema if it exists.
     *
     * @param  string  $table
     *
     * @return void
     */
    public function dropIfExists(string $table);

    /**
     * Drop columns from a table schema.
     *
     * @param  string  $table
     * @param  string|array  $columns
     *
     * @return void
     */
    public function dropColumns(string $table, $columns);

    /**
     * Drop all tables from the database.
     *
     * @throws LogicException
     *
     * @return void
     */
    public function dropAllTables();

    /**
     * Drop all views from the database.
     *
     * @throws LogicException
     *
     * @return void
     */
    public function dropAllViews();

    /**
     * Drop all types from the database.
     *
     * @throws LogicException
     *
     * @return void
     */
    public function dropAllTypes();

    /**
     * Rename a table on the schema.
     *
     * @param  string  $from
     * @param  string  $to
     *
     * @return void
     */
    public function rename(string $from, string $to);

    /**
     * Enable foreign key constraints.
     *
     * @return bool
     */
    public function enableForeignKeyConstraints();

    /**
     * Disable foreign key constraints.
     *
     * @return bool
     */
    public function disableForeignKeyConstraints();

    /**
     * Disable foreign key constraints during the execution of a callback.
     *
     * @param  Closure  $callback
     *
     * @return mixed
     */
    public function withoutForeignKeyConstraints(Closure $callback);

    /**
     * Get the database connection instance.
     *
     * @return Connection
     */
    public function getConnection();

    /**
     * Set the database connection instance.
     *
     * @param  Connection  $connection
     *
     * @return $this
     */
    public function setConnection(Connection $connection);

    /**
     * Set the Schema Blueprint resolver callback.
     *
     * @param  Closure  $resolver
     *
     * @return void
     */
    public function blueprintResolver(Closure $resolver);
}
