<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Setup\Migration;

use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Database\Interfaces\RevertablePatchInterface;
use Maginium\Framework\Database\Model;
use Maginium\Framework\Database\Setup\BaseMigration;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Schema;
use Maginium\Framework\Support\Reflection;

/**
 * Migration Class.
 *
 * This abstract class is used as a base class for data patchers that modify database tables during
 * module setup. It implements both `SchemaPatchInterface` and `PatchRevertableInterface`, allowing
 * patches to be applied and reverted. It provides helper methods to manage database connections,
 * execute migrations, and handle migration rollback.
 *
 * @template TModel of Model
 *
 * @method void applyIndexes() Apply indexes to the specified table schema.
 * @method void down() Revert the changes made by the `down` method.
 *
 * @mixin RevertablePatchInterface
 */
abstract class Migration extends BaseMigration implements PatchRevertableInterface, SchemaPatchInterface
{
    /**
     * Run code inside patch
     * If code fails, patch must be reverted, in case when we are speaking about schema - then under revert
     * means run PatchInterface::revert().
     *
     * If we speak about data, under revert means: $transaction->rollback()
     *
     * @return $this
     */
    final public function apply(): void
    {
        // Check if the patch was already applied under a different alias to avoid reapplying
        if ($appliedAlias = $this->hasAlreadyAppliedAlias()) {
            // Log the fact that the patch was skipped because it was already applied under the old alias
            Log::info(
                __('Patch "%1" skipped. Already applied with alias "%2".', [static::class, $appliedAlias])->render(),
            );

            // Skip further execution if the patch is already applied
            return;
        }

        try {
            // Start database setup process (begin a transaction)
            $this->startSetup();

            // Attempt to set the area code if defined
            if ($areaCode = static::AREA_CODE) {
                $this->initializeAreaCode($areaCode);
            }

            // Determine the target for the migration and log the start of the process
            $tableOrClass = $this->getTable() ?? Reflection::getClassBasename(static::class);

            // Log the start of the migration
            ConsoleOutput::info("🔨 Starting migration for: '{$tableOrClass}' ...", false);

            // Call migration logic defined in child classes
            $this->up();
        } catch (Exception $e) {
            // Log any error that occurs during the patch application
            Log::error(
                __('Error applying patch "%1": %2', [static::class, $e->getMessage()]),
            );
        } finally {
            // Ensure the database transaction is properly committed
            $this->endSetup();
        }
    }

    /**
     * Rollback all changes, done by this patch.
     *
     * @return void
     */
    final public function revert(): void
    {
        // Start setup process (begin database transaction)
        $this->getConnection()->startSetup();

        // Revert the patch's changes (if any custom rollback logic is implemented)
        if (Reflection::methodExists($this, 'down')) {
            // Log the start of the rollback
            ConsoleOutput::info("🗑️ Starting rollback for table: '{$this->getTable()}' ...", false);

            $this->down();

            // Log the completion of the rollback
            ConsoleOutput::success("Rollback completed: '{$this->getTable()}'  table dropped successfully!");
        }

        // End setup process (commit database transaction)
        $this->getConnection()->endSetup();
    }

    /**
     * Alter the database schema.
     *table This method must be implemented in the subclass to define the columns
     * and their properties for the database table.
     *
     *
     * @return void
     */
    abstract public function up(): void;
}
