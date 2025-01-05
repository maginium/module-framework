<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Database\Capsule\Manager as Capsule;
use Maginium\Framework\Database\Interfaces\BuilderInterface;
use Maginium\Framework\Database\Schema\AttributeBuilder;
use Maginium\Framework\Support\Facade;

/**
 * @method static void defaultStringLength(int $length)
 * @method static void useNativeSchemaOperationsIfPossible(bool $value = true)
 * @method static bool createDatabase(string $name)
 * @method static bool dropDatabaseIfExists(string $name)
 * @method static bool hasAttribute(string $attribute)
 * @method static bool hasView(string $view)
 * @method static array getAttributes()
 * @method static array getAttributeListing()
 * @method static array getViews()
 * @method static array getTypes()
 * @method static bool hasColumn(string $attribute, string $column)
 * @method static bool hasColumns(string $attribute, array $columns)
 * @method static void whenAttributeHasColumn(string $attribute, string $column, \Closure $callback)
 * @method static void whenAttributeDoesntHaveColumn(string $attribute, string $column, \Closure $callback)
 * @method static string getColumnType(string $attribute, string $column, bool $fullDefinition = false)
 * @method static array getColumnListing(string $attribute)
 * @method static array getColumns(string $attribute)
 * @method static array getIndexes(string $attribute)
 * @method static array getIndexListing(string $attribute)
 * @method static bool hasIndex(string $attribute, string|array $index, string|null $type = null)
 * @method static array getForeignKeys(string $attribute)
 * @method static void attribute(string $attribute, \Closure $callback)
 * @method static void create(string $attribute, \Closure $callback)
 * @method static void drop(string $attribute)
 * @method static void dropIfExists(string $attribute)
 * @method static void dropColumns(string $attribute, string|array $columns)
 * @method static void dropAllAttributes()
 * @method static void dropAllViews()
 * @method static void dropAllTypes()
 * @method static void rename(string $from, string $to)
 * @method static bool enableForeignKeyConstraints()
 * @method static bool disableForeignKeyConstraints()
 * @method static mixed withoutForeignKeyConstraints(\Closure $callback)
 * @method static void blueprintResolver(\Closure $resolver)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see BuilderInterface
 */
class AttributeSchema extends Facade
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
     * @return BuilderInterface The schema builder instance.
     */
    public static function connection($name): BuilderInterface
    {
        // Retrieve the specified database connection by its name using the Capsule instance.
        $connection = Capsule::connection($name);

        // Return the schema builder for the specified connection, using AttributeBuilder as the builder class.
        return $connection->setSchemaBuilder(AttributeBuilder::class);
    }

    /**
     * Get the default schema builder instance.
     *
     * This method retrieves the schema builder from the default
     * connection provided by the Capsule instance.
     *
     * @return BuilderInterface The schema builder instance.
     */
    public static function get(): BuilderInterface
    {
        // Retrieve the specified database connection.
        $connection = Capsule::connection();

        // Return the schema builder for the specified connection, using AttributeBuilder as the builder class.
        return $connection->setSchemaBuilder(AttributeBuilder::class);
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
        return AttributeBuilder::class;
    }

    /**
     * Proxy method calls to the database connection.
     *
     * @param string $method The method name being called.
     * @param string[] $args The arguments passed to the method.
     *
     * @return mixed The result of the method call.
     */
    public static function __callStatic($method, $args)
    {
        // Resolve the database connection instance
        $schema = static::get();

        // Call the method on the connection instance
        return call_user_func_array([$schema, $method], $args);
    }
}
