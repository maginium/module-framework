<?php

declare(strict_types=1);

namespace Maginium\Framework\Database;

use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Connection as BaseConnection;
use Magento\Framework\Event\ManagerInterface;
use Maginium\Framework\Database\Interfaces\ConnectionInterface;
use Maginium\Framework\Database\Query\Builder as QueryBuilder;
use Maginium\Framework\Database\Schema\Builder as SchemaBuilder;
use Maginium\Framework\Support\Facades\Container;

/**
 * Class Connection.
 *
 * Extends the base database connection to provide a custom schema builder instance.
 * This class ensures compatibility with Laravel's database features while allowing
 * for additional customization of schema operations.
 */
class Connection extends BaseConnection implements ConnectionInterface
{
    /**
     * Get a new query builder instance.
     *
     * @return QueryBuilder
     */
    public function query(): QueryBuilder
    {
        dd('Asdas');

        return Container::make(QueryBuilder::class, [
            'connection' => $this,
            'grammar' => $this->getQueryGrammar(),
            'processor' => $this->getPostProcessor(),
        ]);
    }

    /**
     * Begin a fluent query against a database table.
     *
     * @param  Closure|QueryBuilder|Expression|string  $table
     * @param  string|null  $as
     *
     * @return QueryBuilder
     */
    public function table($table, $as = null): QueryBuilder
    {
        dd($this->query());

        return $this->query()->from($table, $as);
    }

    /**
     * Get the schema builder instance for the connection.
     *
     * This method returns a `SchemaBuilder` instance customized for the current
     * database connection. If no schema grammar is set, it uses the default grammar.
     *
     * @return SchemaBuilder The schema builder instance for managing database schemas.
     */
    public function getSchemaBuilder(): SchemaBuilder
    {
        // Ensure the connection has a schema grammar; use the default if not set.
        if ($this->schemaGrammar === null) {
            $this->useDefaultSchemaGrammar();
        }

        // Resolve and return the custom SchemaBuilder instance from the container.
        return Container::make(SchemaBuilder::class, [
            'connection' => $this,
        ]);
    }

    /**
     * Indicates whether native alter operations will be used when dropping, renaming, or modifying columns, even if Doctrine DBAL is installed.
     *
     * @return bool
     */
    public function usingNativeSchemaOperations()
    {
        return ! $this->isDoctrineAvailable() || SchemaBuilder::$alwaysUsesNativeSchemaOperationsIfPossible;
    }

    /**
     * Set the event dispatcher instance on the connection.
     *
     * @param  ManagerInterface $events
     *
     * @return $this
     */
    public function setEventDispatcher(mixed $events): static
    {
        $this->events = $events;

        return $this;
    }
}
