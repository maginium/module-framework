<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Schema;
use Maginium\Framework\Support\Reflection;

/**
 * Trait HasIndexes.
 *
 * This trait provides reusable methods for managing indexes on database tables.
 * It is intended to be used in classes that work with database schema management.
 *
 * @method void applyIndexes(Blueprint $table) Apply indexes to the specified table schema.
 * @method void addIndex(Blueprint $table, string|array $columns, ?string $name = null) Add a new index to the table.
 * @method void dropIndex(Blueprint $table, string $name) Remove an existing index from the table.
 *
 * @property string $tableName
 */
trait HasIndexes
{
    /**
     * Apply indexes to the specified table schema.
     *
     * This method can be called to apply indexes defined in the subclass
     * that implements the Indexable interface.
     *
     * @return void
     */
    public function applyIndexes(): void
    {
        if (Reflection::methodExists($this, 'indexes')) {
            // Create the main table with the specified schema and indexes
            Schema::create(static::$tableName, function(Blueprint $table): void {
                // Log the index creation process
                ConsoleOutput::info('⚙️ Creating indexes for table: "' . static::$tableName . '"...', false);

                // Apply the indexes to the table
                $this->indexes($table);
            });
        }
    }

    /**
     * Adds a new index to the table.
     *
     * @param string|array $columns The column(s) to include in the index.
     * @param string|null $name The name of the index (optional).
     *
     * @return void
     */
    public function addIndex($columns, ?string $name = null): void
    {
        // Add the specified index to the table
        $this->modifyIndex($columns, $name, 'index');
    }

    /**
     * Removes an existing index from the table.
     *
     * @param string $name The name of the index to remove.
     *
     * @return void
     */
    public function dropIndex(string $name): void
    {
        // Drop the specified index from the table
        $this->modifyIndex($name, null, 'dropIndex');
    }

    /**
     * Modifies the index of the table by either adding or removing it.
     *
     * @param string|array $columns The column(s) for the index.
     * @param string|null $name The name of the index (optional).
     * @param string $action The action to perform: 'index' for adding and 'dropIndex' for removing.
     *
     * @return void
     */
    private function modifyIndex($columns, ?string $name, string $action): void
    {
        // Create the main table with the specified schema and indexes
        Schema::create(static::$tableName, function(Blueprint $table) use ($columns, $name, $action): void {
            // Modify the index based on the specified action
            if ($action === 'index') {
                // Add the specified index to the table
                $table->index($columns, $name);
            } elseif ($action === 'dropIndex' && $name) {
                // Drop the specified index from the table
                $table->dropIndex($name);
            }
        });
    }
}
