<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Connections;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Connection as BaseConnection;
use Magento\Framework\Event\ManagerInterface;
use Maginium\Framework\Database\Interfaces\BuilderInterface;
use Maginium\Framework\Database\Interfaces\ConnectionInterface;
use Maginium\Framework\Database\Query\Builder as QueryBuilder;
use Maginium\Framework\Database\Schema\Builder as SchemaBuilder;
use Maginium\Framework\Support\Facades\Container;
use Override;

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
     * @var BuilderInterface|null
     */
    protected ?BuilderInterface $schemaBuilder = null;

    /**
     * Get a new query builder instance.
     *
     * @return QueryBuilder
     */
    #[Override]
    public function query(): QueryBuilder
    {
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
    #[Override]
    public function table($table, $as = null): mixed
    {
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
    #[Override]
    public function getSchemaBuilder(): BuilderInterface
    {
        // If the schemaBuilder property is null, set it to SchemaBuilder::class
        if ($this->schemaBuilder === null) {
            $this->setSchemaBuilder(SchemaBuilder::class);
        }

        return $this->schemaBuilder;
    }

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
    public function setSchemaBuilder(string $builderClass): BuilderInterface
    {
        // Resolve and store the builder in the schemaBuilder property
        $this->schemaBuilder = Container::make($builderClass, [
            'connection' => $this, // Pass the current connection to the schema builder
        ]);

        return $this->schemaBuilder;
    }

    /**
     * Indicates whether native alter operations will be used when dropping, renaming, or modifying columns, even if Doctrine DBAL is installed.
     *
     * @return bool
     */
    #[Override]
    public function usingNativeSchemaOperations(): bool
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
    #[Override]
    public function setEventDispatcher(mixed $events): static
    {
        $this->events = $events;

        return $this;
    }
}
