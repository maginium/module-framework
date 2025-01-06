<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Schema;

use Maginium\Framework\Database\DatabaseManager;
use Maginium\Framework\Database\Enums\Searcher;
use Maginium\Framework\Support\Facade;

/**
 * Class Schema.
 *
 * This facade class provides a convenient interface for interacting with the Elasticsearch schema builder.
 * It offers methods to manage indices, mappings, settings, and analyzers, as well as to perform various operations
 * on Elasticsearch schemas, such as creating, deleting, and modifying indices. The methods are statically accessible
 * via the facade, simplifying interaction with Elasticsearch schema components within the application.
 *
 * @method static Builder overridePrefix(string|null $value) Override the prefix for the index.
 * @method static array getIndex(string $index) Get the details of a specific index.
 * @method static array getIndices() Get a list of all indices.
 * @method static array getMappings(string $index) Get the mappings of a specific index.
 * @method static array getSettings(string $index) Get the settings of a specific index.
 * @method static array create(string $index, \Closure $callback) Create a new index with the provided callback.
 * @method static array createIfNotExists(string $index, \Closure $callback) Create an index if it does not already exist.
 * @method static Builder reIndex(string $from, string $to) Reindex data from one index to another.
 * @method static Builder rename(string $from, string $to) Rename an index (Work in Progress).
 * @method static Builder modify(string $index, \Closure $callback) Modify an existing index.
 * @method static bool delete(string $index) Delete an index.
 * @method static bool deleteIfExists(string $index) Delete an index if it exists.
 * @method static array setAnalyser(string $index, \Closure $callback) Set analyzers for a specific index.
 * @method static Builder createTemplate(string $name, \Closure $callback) Create an index template (Work in Progress).
 * @method static bool hasField(string $index, string $field) Check if a specific field exists in the index.
 * @method static bool hasFields(string $index, array $fields) Check if multiple fields exist in the index.
 * @method static bool hasIndex(string $index) Check if a specific index exists.
 * @method static bool dsl(string $method, array $parameters) Execute a DSL query.
 * @method static \Maginium\Framework\Elasticsearch\Connection getConnection() Get the current Elasticsearch connection instance.
 * @method static \Maginium\Framework\Elasticsearch\Schema\Builder setConnection(\Maginium\Framework\Elasticsearch\Connection $connection) Set the Elasticsearch connection instance.
 *
 * @see Builder
 */
class Schema extends Facade
{
    /**
     * Get the schema builder instance for a specific connection.
     *
     * This method allows you to access the schema builder for a specific connection by its name.
     *
     * @param  string|null  $name The name of the connection (optional).
     *
     * @return Builder The schema builder instance for the connection.
     */
    public static function on($name): Builder
    {
        return static::connection($name);
    }

    /**
     * Get a schema builder instance for a given connection name.
     *
     * This method retrieves the schema builder for a specific connection, or the default connection
     * if no name is provided. It is primarily used to get access to methods that modify or query Elasticsearch schemas.
     *
     * @param  string|null  $name The name of the connection.
     *
     * @return Builder The schema builder for the specified connection.
     */
    public static function connection($name): Builder
    {
        if ($name === null) {
            return static::getFacadeAccessor();
        }

        return static::resolve(DatabaseManager::class)->connection($name)->getSchemaBuilder();
    }

    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return static::resolve(DatabaseManager::class)->connection(Searcher::ELASTIC_SEARCH)->getSchemaBuilder();
    }

    /**
     * Handle dynamic method calls.
     *
     * This method is used to dynamically delegate method calls to the underlying schema builder instance.
     *
     * @param  string  $method The name of the method being called.
     * @param  array  $args The arguments passed to the method.
     *
     * @return mixed The result of the method call on the schema builder.
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeAccessor();

        return $instance->{$method}(...$args);
    }
}
