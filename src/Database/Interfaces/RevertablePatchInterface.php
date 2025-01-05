<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces;

/**
 * Interface RevertablePatchInterface.
 *
 * This interface defines a contract for classes that support reverting changes
 * made during a migration or setup process. Classes implementing this interface
 * must provide a `down` method to undo their actions, typically by dropping
 * tables, removing indexes, or deleting data.
 */
interface RevertablePatchInterface
{
    /**
     * Revert the changes made by the `up` method.
     *
     * This method should define the logic to undo database schema changes
     * or other modifications made during the `up` operation.
     *
     * @return void
     */
    public function down(): void;
}
