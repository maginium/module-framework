<?php

declare(strict_types=1);

namespace Maginium\Framework\Database;

use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Connection;
use Illuminate\Database\Grammar;
use Illuminate\Database\PDO\MySqlDriver;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as SchemaGrammar;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Database\Schema\MySqlSchemaState;
use Maginium\Framework\Support\Facades\Container;
use PDO;

/**
 * Class MySqlConnection.
 *
 * Custom MySQL database connection class extending the base Laravel connection.
 * Provides additional functionality for schema management, query execution, and
 * specific MySQL features like MariaDB detection and binary value escaping.
 */
class MySqlConnection extends Connection
{
    /**
     * The last inserted ID generated by the server.
     *
     * @var string|int|null
     */
    protected string|int|null $lastInsertId = null;

    /**
     * Executes an insert statement against the database.
     *
     * @param string $query The SQL query string.
     * @param array $bindings The bindings for the query placeholders.
     * @param string|null $sequence The sequence name for retrieving the last insert ID.
     *
     * @return bool Returns true if the operation was successful, false otherwise.
     */
    public function insert($query, $bindings = [], $sequence = null): bool
    {
        // Run the query and handle the execution logic in a closure.
        return $this->run($query, $bindings, function(string $query, array $bindings) use ($sequence) {
            // Check if the database is in pretend mode (used for testing or dry runs).
            if ($this->pretending()) {
                return true; // Return true to simulate successful execution.
            }

            // Prepare the SQL query for execution.
            $statement = $this->getPdo()->prepare($query);

            // Bind the provided bindings to the query placeholders.
            $this->bindValues($statement, $this->prepareBindings($bindings));

            // Mark the database records as modified.
            $this->recordsHaveBeenModified();

            // Execute the prepared statement.
            $result = $statement->execute();

            // Retrieve and store the last inserted ID, if applicable.
            $this->lastInsertId = $this->getPdo()->lastInsertId($sequence);

            // Return the result of the execution (true or false).
            return $result;
        });
    }

    /**
     * Retrieves the last inserted ID for the connection.
     *
     * @return string|int|null The last inserted ID or null if not set.
     */
    public function getLastInsertId(): string|int|null
    {
        return $this->lastInsertId;
    }

    /**
     * Checks if the connected database is a MariaDB instance.
     *
     * @return bool True if the database is MariaDB, false otherwise.
     */
    public function isMaria(): bool
    {
        // Use PDO to get the server version and check if "MariaDB" is in the string.
        return str_contains($this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION), 'MariaDB');
    }

    /**
     * Retrieves a schema builder instance for the connection.
     *
     * @return MySqlBuilder The schema builder instance.
     */
    public function getSchemaBuilder(): MySqlBuilder
    {
        // Ensure that the default schema grammar is used if not already set.
        if ($this->schemaGrammar === null) {
            $this->useDefaultSchemaGrammar();
        }

        // Use the container to resolve an instance of MySqlBuilder with the connection injected.
        return Container::make(MySqlBuilder::class, [
            'connection' => $this,
        ]);
    }

    /**
     * Retrieves the schema state for the connection.
     *
     * @param Filesystem|null $files An optional filesystem instance.
     * @param callable|null $processFactory An optional process factory callback.
     *
     * @return MySqlSchemaState The schema state instance.
     */
    public function getSchemaState(?Filesystem $files = null, ?callable $processFactory = null): MySqlSchemaState
    {
        // Use the container to resolve an instance of MySqlSchemaState with dependencies injected.
        return Container::make(MySqlSchemaState::class, [
            'files' => $files,
            'connection' => $this,
            'processFactory' => $processFactory,
        ]);
    }

    /**
     * Escapes a binary value for safe SQL embedding.
     *
     * @param string $value The binary value to escape.
     *
     * @return string The escaped binary value.
     */
    protected function escapeBinary($value): string
    {
        // Convert the binary value to a hexadecimal representation.
        $hex = bin2hex($value);

        // Return the value in a format safe for SQL embedding (e.g., x'hex_value').
        return "x'{$hex}'";
    }

    /**
     * Determines if the exception was caused by a unique constraint violation.
     *
     * @param Exception $exception The exception to check.
     *
     * @return bool True if caused by a unique constraint violation, false otherwise.
     */
    protected function isUniqueConstraintError(Exception $exception): bool
    {
        // Use a regular expression to match the MySQL error code for unique constraint violations (1062).
        return (bool)preg_match('#Integrity constraint violation: 1062#i', $exception->getMessage());
    }

    /**
     * Retrieves the default query grammar instance.
     *
     * @return MySqlGrammar The default query grammar.
     */
    protected function getDefaultQueryGrammar(): Grammar
    {
        // Use the container to resolve an instance of MySqlGrammar.
        $grammar = Container::make(MySqlGrammar::class);

        // Set the connection on the grammar instance.
        $grammar->setConnection($this);

        // Apply the table prefix to the grammar and return it.
        return $this->withTablePrefix($grammar);
    }

    /**
     * Retrieves the default schema grammar instance.
     *
     * @return SchemaGrammar The default schema grammar.
     */
    protected function getDefaultSchemaGrammar(): Grammar
    {
        // Use the container to resolve an instance of SchemaGrammar.
        $grammar = Container::make(SchemaGrammar::class);

        // Set the connection on the grammar instance.
        $grammar->setConnection($this);

        // Apply the table prefix to the grammar and return it.
        return $this->withTablePrefix($grammar);
    }

    /**
     * Retrieves the default post processor instance.
     *
     * @return MySqlProcessor The default query post processor.
     */
    protected function getDefaultPostProcessor(): MySqlProcessor
    {
        // Use the container to resolve an instance of MySqlProcessor and return it.
        return Container::make(MySqlProcessor::class);
    }

    /**
     * Retrieves the Doctrine DBAL driver.
     *
     * @return MySqlDriver The Doctrine MySQL driver instance.
     */
    protected function getDoctrineDriver(): MySqlDriver
    {
        // Use the container to resolve an instance of MySqlDriver and return it.
        return Container::make(MySqlDriver::class);
    }
}
