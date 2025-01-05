<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Schema;

use Closure;
use Illuminate\Database\Schema\Builder as BaseBuilder;
use Maginium\Framework\Database\Connection;
use Maginium\Framework\Database\Interfaces\BuilderInterface;
use Maginium\Framework\Support\Facades\Container;

/**
 * Class Builder.
 *
 * Extends the functionality of the base schema builder, allowing for enhanced
 * schema creation and modification. This class introduces additional logic for
 * resolving blueprints, including prefix handling for table names.
 */
class Builder extends BaseBuilder implements BuilderInterface
{
    /**
     * Create a new database Schema manager.
     *
     * @param Connection $connection
     *
     * @return void
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    /**
     * Create a new blueprint instance for the table schema.
     *
     * This method determines the appropriate blueprint creation logic, including
     * optional prefix resolution for table indexes. If a custom resolver is provided,
     * it will be used; otherwise, the blueprint is created via the container.
     *
     * @param  string  $table  The table name for which the blueprint is created.
     * @param  Closure|null  $callback  The schema manipulation logic.
     *
     * @return Blueprint  The blueprint instance for schema modifications.
     */
    protected function createBlueprint($table, ?Closure $callback = null): Blueprint
    {
        // Determine the prefix to be applied to table indexes if required.
        $prefixIndexes = (bool)$this->connection->getConfig('prefix_indexes');
        $prefix = $prefixIndexes ? (string)$this->connection->getConfig('prefix') : '';

        // Use a custom resolver if available; otherwise, resolve via the container.
        if ($this->resolver !== null) {
            return call_user_func($this->resolver, $table, $callback, $prefix);
        }

        // Resolve and return the blueprint instance using dependency injection.
        return Container::make(Blueprint::class, [
            'table' => $table,
            'callback' => $callback,
            'prefix' => $prefix,
        ]);
    }
}
