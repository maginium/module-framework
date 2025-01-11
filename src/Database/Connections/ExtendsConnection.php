<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Connections;

use Maginium\Framework\Database\Query\Builder as QueryBuilder;

/**
 * Trait ExtendsConnection.
 *
 * Provides enhancements to a database connection by replacing the query builder,
 * customizing logging events, and extending connection-specific event firing.
 *
 * This trait is intended to be used with a connection class that extends the
 * `Illuminate\Database\Connection` class.
 */
trait ExtendsConnection
{
    /**
     * Overrides the query builder for the connection.
     *
     * This method replaces the default query builder with a custom `QueryBuilder`
     * implementation, providing additional functionality specific to the application.
     *
     * @return QueryBuilder The custom query builder instance.
     */
    public function query()
    {
        // Return a new instance of the custom query builder
        return new QueryBuilder(
            $this, // The current connection instance
            $this->getQueryGrammar(), // The query grammar used for building SQL queries
            $this->getPostProcessor(), // The post-processor for processing query results
        );
    }

    /**
     * Logs a query to the connection's query log.
     *
     * This method dispatches a custom query logging event (`illuminate.query`)
     * with query details and then calls the parent `logQuery` method to log the query.
     *
     * @param string $query The SQL query string being executed.
     * @param array $bindings The array of bound parameters for the query.
     * @param float|null $time The execution time of the query in milliseconds (optional).
     *
     * @return void
     */
    public function logQuery($query, $bindings, $time = null)
    {
        // Check if the events dispatcher is available
        if (isset($this->events)) {
            // Dispatch a custom query logging event with query details
            $this->events->dispatch(
                'illuminate.query', // Event name
                [$query, $bindings, $time, $this->getName()], // Event payload
            );
        }

        // Call the parent method to perform the actual query logging
        parent::logQuery($query, $bindings, $time);
    }

    /**
     * Fires a connection-specific event.
     *
     * This method dispatches a custom connection event (e.g., `connection.{name}.{event}`)
     * and then calls the parent `fireConnectionEvent` method to handle the default behavior.
     *
     * @param string $event The name of the connection event (e.g., 'beginTransaction', 'commit').
     *
     * @return void
     */
    protected function fireConnectionEvent($event)
    {
        // Check if the events dispatcher is available
        if (isset($this->events)) {
            // Dispatch a custom connection-specific event
            $this->events->dispatch(
                'connection.' . $this->getName() . '.' . $event, // Event name
                $this, // The current connection instance as the event payload
            );
        }

        // Call the parent method to handle the default event behavior
        parent::fireConnectionEvent($event);
    }
}
