<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Database\Schema\AttributeBuilder;
use Maginium\Framework\Support\Facade;

/**
 * @method static void defaultStringLength(int $length)
 * @method static void useNativeSchemaOperationsIfPossible(bool $value = true)
 * @method static bool createDatabase(string $name)
 * @method static bool dropDatabaseIfExists(string $name)
 * @method static bool hasTable(string $attribute)
 * @method static bool hasView(string $view)
 * @method static array getTables()
 * @method static array getTableListing()
 * @method static array getViews()
 * @method static array getTypes()
 * @method static bool hasColumn(string $attribute, string $column)
 * @method static bool hasColumns(string $attribute, array $columns)
 * @method static void whenTableHasColumn(string $attribute, string $column, \Closure $callback)
 * @method static void whenTableDoesntHaveColumn(string $attribute, string $column, \Closure $callback)
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
 * @method static void dropAllTables()
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
 * @see Builder
 */
class AttributeSchema extends Facade
{
    /**
     * Indicates if the resolved facade should be cached.
     *
     * @var bool
     */
    protected static $cached = false;

    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return AttributeBuilder::class;
    }
}
