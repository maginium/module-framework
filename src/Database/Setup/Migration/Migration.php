<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Setup\Migration;

use Illuminate\Database\Schema\Blueprint;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Database\Concerns\HasIndexes;
use Maginium\Framework\Database\Enums\MetadataType;
use Maginium\Framework\Database\Interfaces\HasSoftDeletesInterface;
use Maginium\Framework\Database\Interfaces\HasUserStampsInterface;
use Maginium\Framework\Database\Interfaces\RevertablePatchInterface;
use Maginium\Framework\Database\Model;
use Maginium\Framework\Database\Setup\BaseMigration;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Schema;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;
use Maginium\Framework\Uuid\Interfaces\UuidInterface;

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
 * @mixin HasIndexes
 * @mixin RevertablePatchInterface
 */
abstract class Migration extends BaseMigration implements PatchRevertableInterface, SchemaPatchInterface
{
    /**
     * The name of the database table that this migration will operate on.
     *
     * @var string
     */
    protected static string $tableName = '';

    /**
     * Indicates whether soft deletes are enabled for the table.
     *
     * @var bool
     */
    protected static bool $hasSoftDeletes = false;

    /**
     * Indicates whether user tracking is enabled for the table.
     *
     * @var bool
     */
    protected static bool $hasUserStamps = false;

    /**
     * Indicates whether timestamp tracking is enabled for the table.
     *
     * @var bool
     */
    protected static bool $hasTimeStamps = false;

    /**
     * The type of metadata storage (single or separate).
     *
     * @var string|null
     */
    protected static ?string $metable = null;

    /**
     * Indicates whether the table uses UUIDs as primary keys.
     *
     * @var bool
     */
    protected static bool $hasUUID = false;

    /**
     * Indicates whether the table has a slug column.
     * Possible values: 'handler', 'slug', or null if not applicable.
     *
     * @var ?string
     */
    protected static ?string $slugable = null;

    /**
     * The cascade option for foreign key constraints.
     */
    protected const CASCADE = 'cascade';

    /**
     * @var string The key used in metadata storage.
     */
    protected const KEY = 'key';

    /**
     * @var string The value used in metadata storage.
     */
    protected const VALUE = 'value';

    /**
     * @var string The prefix for metadata table names.
     */
    protected const METADATA_TABLE_PREFIX = 'metadata';

    /**
     * @var string The key for storing metadata.
     */
    protected const META_DATA = 'meta_data';

    /**
     * @var string The identifier for the record associated with the metadata.
     */
    protected const RECORD_ID = 'record_id';

    /**
     * {@inheritdoc}
     *
     * Applies the patch to the database. This method is called to execute the patch's changes.
     * It checks if the patch has already been applied (to avoid reapplying), sets the area code if needed,
     * applies migration logic, and ensures that database transactions are properly managed.
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

        // Start database setup process (begin a transaction)
        try {
            $this->startSetup();

            // Set the area code if defined in the class constant
            if ($areaCode = static::AREA_CODE) {
                try {
                    // Attempt to set the area code for the operation (e.g., admin, frontend)
                    $this->context->getState()->setAreaCode($areaCode);
                } catch (Exception $e) {
                    // Log error if setting area code fails (e.g., if already set)
                    Log::warning(
                        __('Failed to set area code "%1": %2', [$areaCode, $e->getMessage()]),
                    );
                    // In case of failure, proceed with the operation
                }
            }

            // Check if the table name is not empty before attempting migration
            if (! Validator::isEmpty(static::$tableName)) {
                // Apply migration logic
                $this->migrate();
            } else {
                $this->up();
            }
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
     * {@inheritdoc}
     *
     * Reverts the patch. This method is called to undo the changes made by the patch.
     * It invokes the `rollback()` method to allow for custom undo logic.
     */
    final public function revert(): void
    {
        // Start setup process (begin database transaction)
        $this->getConnection()->startSetup();

        // Revert the patch's changes (if any custom rollback logic is implemented)
        if (Reflection::methodExists($this, 'down')) {
            // Log the start of the rollback
            ConsoleOutput::info('🗑️ Starting rollback for table: "' . static::$tableName . '"...', false);

            $this->down();

            // Log the completion of the rollback
            ConsoleOutput::success('Rollback completed: "' . static::$tableName . '" table dropped successfully!');
        }

        // End setup process (commit database transaction)
        $this->getConnection()->endSetup();
    }

    /**
     * Create the database schema.
     *
     * This method must be implemented in the subclass to define the columns
     * and their properties for the database table.
     *
     *
     * @return void
     */
    abstract public function up(): void;

    /**
     * Adds a UUID column to the database table.
     *
     * This method adds a UUID field that serves as the primary key and
     * logs the addition of the column to the console.
     *
     * @return void
     */
    protected function addUUID(): void
    {
        // Create the main table with the specified schema and indexes
        Schema::create(static::$tableName, function(Blueprint $table): void {
            // Log the addition of the UUID column
            ConsoleOutput::info('🆔 Adding UUID column to "' . static::$tableName . '"...', false);

            // UUID column (primary key)
            $table->uuid(UuidInterface::UUID)->primary()->comment('Unique identifier for the record');
        });
    }

    /**
     * Adds a slug column to the database table.
     *
     * This method adds a slug field for URL-friendly identification of the record
     * and logs the addition of the column to the console.
     *
     * @return void
     */
    protected function addSlug(): void
    {
        // Create the main table with the specified schema and indexes
        Schema::create(static::$tableName, function(Blueprint $table): void {
            // Log the addition of the slug column
            ConsoleOutput::info('🔖 Adding slug column to "' . static::$tableName . '"...', false);

            // Slug column (unique)
            $table->string(static::$slugable)->unique()->nullable()->comment('URL-friendly identifier for the record');
        });
    }

    /**
     * Adds user timestamps to the database table.
     *
     * This method adds fields for tracking the user who created, updated,
     * and deleted the record. It logs each addition step to the console.
     *
     * @return void
     */
    protected function addUserTimestamps(): void
    {
        // Create the main table with the specified schema and indexes
        Schema::create(static::$tableName, function(Blueprint $table): void {
            // Log the addition of user timestamps
            ConsoleOutput::info('🕒 Adding user timestamps to "' . static::$tableName . '"...', false);

            // User who created the record (nullable)
            $table->bigInteger(HasUserStampsInterface::CREATED_BY)->unsigned()->nullable()->comment('User who created the record');

            // User who last updated the record (nullable)
            $table->bigInteger(HasUserStampsInterface::UPDATED_BY)->unsigned()->nullable()->comment('User who last updated the record');

            if (static::$hasSoftDeletes) {
                // User who deleted the record (nullable)
                $table->bigInteger(HasSoftDeletesInterface::DELETED_BY)->unsigned()->nullable()->comment('User who deleted the record');
            }
        });
    }

    /**
     * Adds standard timestamps to the database table.
     *
     * This method adds fields for created_at and updated_at to track when
     * records are created and last updated. It logs each addition step to
     * the console.
     *
     * @return void
     */
    protected function addTimestamps(): void
    {
        // Create the main table with the specified schema and indexes
        Schema::create(static::$tableName, function(Blueprint $table): void {
            // Log the addition of standard timestamps
            ConsoleOutput::info('⏳ Adding standard timestamps to "' . static::$tableName . '"...', false);

            // Creation timestamp
            $table->timestamp(Model::getCreatedAtKey())->useCurrent()->nullable()->comment('Creation timestamp');

            // Last update timestamp (nullable)
            $table->timestamp(Model::getUpdatedAtKey())->nullable()->comment('Last update timestamp');
        });
    }

    /**
     * Adds soft delete functionality to the database table.
     *
     * This method adds a soft delete timestamp field to the table, allowing
     * for soft deletion of records. It logs the action to the console.
     *
     * @return void
     */
    protected function addSoftDeletes(): void
    {
        // Create the main table with the specified schema and indexes
        Schema::create(static::$tableName, function(Blueprint $table): void {
            // Log enabling soft deletes
            ConsoleOutput::info('🗑️ Enabling soft deletes for "' . static::$tableName . '"...', false);

            // Soft delete timestamp
            $table->softDeletes()->unique()->comment('Soft delete timestamp');
        });
    }

    /**
     * Adds a metadata field to the database table.
     *
     * This method adds a JSON column for storing additional metadata for
     * the record. It logs the action to the console.
     *
     * @return void
     */
    protected function addMetadata(): void
    {
        // Create the main table with the specified schema and indexes
        Schema::create(static::$tableName, function(Blueprint $table): void {
            // Log the addition of the metadata field
            ConsoleOutput::info('🗄️ Adding metadata field to "' . static::$tableName . '"...', false);

            // Metadata column (JSON)
            $table->json(static::META_DATA)->nullable()->comment('Additional metadata for the record');
        });
    }

    /**
     * Creates a separate metadata table linked to the main table.
     *
     * This method defines the structure of the metadata table, which
     * holds additional information related to the records in the main table.
     * It logs the action to the console once the table is created.
     *
     * @return void
     */
    protected function createMetadataTable(): void
    {
        // Define the name of the metadata table
        $metadataTableName = static::$tableName . static::METADATA_TABLE_PREFIX;

        // Create the metadata table
        Schema::create($metadataTableName, function(Blueprint $table): void {
            // Primary key
            $table->id();

            // Foreign key constraint
            $table->foreignId(static::RECORD_ID)->constrained(static::$tableName)->onDelete('cascade');

            // Metadata key
            $table->string(static::KEY)->comment('Key of the metadata entry');

            // Metadata value
            $table->text(static::VALUE)->nullable()->comment('Value of the metadata entry');

            // Add timestamps for the metadata table
            $this->addTimestamps();
        });

        // Log the creation of the metadata table
        ConsoleOutput::success('Metadata table "' . static::$tableName . '_metadata" created successfully! 🎉');
    }

    /**
     * {@inheritdoc}
     *
     * Reverts the patch. This method is called to undo the changes made by the patch.
     * It invokes the `rollback()` method to allow for custom undo logic.
     */
    private function migrate(): void
    {
        // Log the start of the migration
        ConsoleOutput::info('🔨 Starting migration for table: "' . static::$tableName . '"...', false);

        // Call the method to define the schema for the table
        $this->up();

        // Add UUID if enabled
        if (static::$hasUUID) {
            $this->addUUID();
        }

        // Add Slug if enabled
        if (static::$slugable) {
            $this->addSlug();
        }

        // Add user timestamps if enabled
        if (static::$hasUserStamps) {
            $this->addUserTimestamps();
        }

        // Add standard timestamps
        if (static::$hasTimeStamps) {
            $this->addTimestamps();
        }

        // Add soft deletes if enabled
        if (static::$hasSoftDeletes) {
            $this->addSoftDeletes();
        }

        // Add metadata field if enabled
        if (static::$metable === MetadataType::INLINE) {
            $this->addMetadata();
        } elseif (static::$metable === MetadataType::EXTERNAL) {
            $this->createMetadataTable();
        }

        // Call the method to create indexes for the table
        if (Reflection::methodExists($this, 'applyIndexes')) {
            $this->applyIndexes();

            // Log the completion of index creation
            ConsoleOutput::success('Indexes created successfully for table: "' . static::$tableName . '".');
        }

        // Optionally, apply custom table schema and log the action
        if (Reflection::methodExists($this, 'schema')) {
            $this->schema();
            ConsoleOutput::success('Custom table schema applied to: "' . static::$tableName . '" successfully!');
        }

        // Log the completion of the migration
        ConsoleOutput::success('Migration completed: "' . static::$tableName . '" table created successfully!');
    }
}
