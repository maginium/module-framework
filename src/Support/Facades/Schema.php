<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Closure;
use Maginium\Framework\Database\Capsule\Manager as Capsule;
use Maginium\Framework\Database\Connection;
use Maginium\Framework\Database\Schema\Builder;
use Maginium\Framework\Support\Facade;

/**
 * @method static void defaultStringLength(int $length)
 * @method static void defaultMorphKeyType(string $type)
 * @method static void morphUsingUuids()
 * @method static void morphUsingUlids()
 * @method static void useNativeSchemaOperationsIfPossible(bool $value = true)
 * @method static bool createDatabase(string $name)
 * @method static bool dropDatabaseIfExists(string $name)
 * @method static bool hasTable(string $table)
 * @method static bool hasView(string $view)
 * @method static array getTables()
 * @method static array getTableListing()
 * @method static array getViews()
 * @method static array getTypes()
 * @method static bool hasColumn(string $table, string $column)
 * @method static bool hasColumns(string $table, array $columns)
 * @method static void whenTableHasColumn(string $table, string $column, \Closure $callback)
 * @method static void whenTableDoesntHaveColumn(string $table, string $column, \Closure $callback)
 * @method static string getColumnType(string $table, string $column, bool $fullDefinition = false)
 * @method static array getColumnListing(string $table)
 * @method static array getColumns(string $table)
 * @method static array getIndexes(string $table)
 * @method static array getIndexListing(string $table)
 * @method static bool hasIndex(string $table, string|array $index, string|null $type = null)
 * @method static array getForeignKeys(string $table)
 * @method static void table(string $table, \Closure $callback)
 * @method static void create(string $table, \Closure $callback)
 * @method static void drop(string $table)
 * @method static void dropIfExists(string $table)
 * @method static void dropColumns(string $table, string|array $columns)
 * @method static void dropAllTables()
 * @method static void dropAllViews()
 * @method static void dropAllTypes()
 * @method static void rename(string $from, string $to)
 * @method static bool enableForeignKeyConstraints()
 * @method static bool disableForeignKeyConstraints()
 * @method static mixed withoutForeignKeyConstraints(\Closure $callback)
 * @method static \Maginium\Framework\Database\Connection getConnection()
 * @method static \Maginium\Framework\Database\Schema\Builder setConnection(\Maginium\Framework\Database\Connection $connection)
 * @method static void blueprintResolver(\Closure $resolver)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see Builder
 */
class Schema extends Facade
{
    /**
     * Indicates if the resolved facade should be cached.
     *
     * Determines whether the resolved facade instance is cached for
     * repeated access, improving performance in some cases.
     *
     * @var bool
     */
    protected static $cached = false;

    /**
     * Get a schema builder instance for a connection.
     *
     * This method retrieves the schema builder associated with a specific
     * database connection by its name. If no name is provided, the default
     * connection is used.
     *
     * @param  string|null  $name  The name of the connection (optional).
     *
     * @return Builder The schema builder instance.
     */
    public static function connection($name): Builder
    {
        /** @var Connection $connection */
        $connection = Capsule::connection($name);

        return $connection->getSchemaBuilder();
    }

    /**
     * Get the default schema builder instance.
     *
     * This method retrieves the schema builder from the default
     * connection provided by the Capsule instance.
     *
     * @return Builder The schema builder instance.
     */
    public static function get()
    {
        return Capsule::schema();
    }

    /**
     * Create a new table in the schema.
     *
     * This method uses the schema builder to define the structure of a new table.
     * The provided callback is executed to define the table's columns and constraints.
     *
     * @param  string  $table    The name of the table to create.
     * @param  Closure  $callback  A callback defining the table's structure.
     *
     * @return void
     */
    public static function create(string $table, Closure $callback)
    {
        return static::get()->create($table, $callback);
    }

    /**
     * Get the registered name of the component.
     *
     * This method provides the key used to access the service bound
     * in the container. It should return a string identifier for the
     * schema service.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return '';
    }
}
