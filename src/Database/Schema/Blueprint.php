<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Schema;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Maginium\Framework\Database\Interfaces\HasSoftDeletesInterface;
use Maginium\Framework\Database\Interfaces\HasUserStampsInterface;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Uuid\Interfaces\UuidInterface;

/**
 * Custom Blueprint class that extends the base Blueprint class.
 *
 * This class can be used to define additional custom methods for table creation
 * or schema manipulation in the migration system. For now, it acts as a direct
 * extension of Laravel's Blueprint, but can be extended with custom functionality
 * specific to the application's needs.
 */
class Blueprint extends BaseBlueprint
{
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
     * Adds a UUID column to the database table.
     *
     * This method adds a UUID field that serves as the primary key and
     * logs the addition of the column to the console.
     *
     * @return void
     */
    public function addUUID(): void
    {
        // Alter the main table with the specified schema and indexes
        // Log the addition of the UUID column
        ConsoleOutput::info('🆔 Adding UUID column to "' . $this->getTable() . '"...', false);

        // UUID column (primary key)
        $this->uuid(UuidInterface::UUID)->primary()->comment('Unique identifier for the record');
    }

    /**
     * Adds a slug column to the database table.
     *
     * This method adds a slug field for URL-friendly identification of the record
     * and logs the addition of the column to the console.
     *
     * @return void
     */
    public function addSlug(?string $name = null): void
    {
        // Alter the main table with the specified schema and indexes
        // Log the addition of the slug column
        ConsoleOutput::info("🔖 Adding {$name} column to '{$this->getTable()}'...", false);

        // Slug column (unique)
        $this->string($name)->unique()->nullable()->comment('URL-friendly identifier for the record');
    }

    /**
     * Adds user timestamps to the database table.
     *
     * This method adds fields for tracking the user who created, updated,
     * and deleted the record. It logs each addition step to the console.
     *
     * @return void
     */
    public function addUserTimestamps(): void
    {
        // Alter the main table with the specified schema and indexes
        // Log the addition of user timestamps
        ConsoleOutput::info('🕒 Adding user timestamps to "' . $this->getTable() . '"...', false);

        // User who created the record (nullable)
        $this->bigInteger(HasUserStampsInterface::CREATED_BY)->unsigned()->nullable()->comment('User who created the record');

        // User who last updated the record (nullable)
        $this->bigInteger(HasUserStampsInterface::UPDATED_BY)->unsigned()->nullable()->comment('User who last updated the record');

        if (static::$hasSoftDeletes) {
            // User who deleted the record (nullable)
            $this->bigInteger(HasSoftDeletesInterface::DELETED_BY)->unsigned()->nullable()->comment('User who deleted the record');
        }
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
    public function addTimestamps(): void
    {
        // Alter the main table with the specified schema and indexes
        // Log the addition of standard timestamps
        ConsoleOutput::info('⏳ Adding standard timestamps to "' . $this->getTable() . '"...', false);

        // Creation timestamp
        $this->timestamp(Model::getCreatedAtKey())->useCurrent()->nullable()->comment('Creation timestamp');

        // Last update timestamp (nullable)
        $this->timestamp(Model::getUpdatedAtKey())->nullable()->comment('Last update timestamp');
    }

    /**
     * Adds soft delete functionality to the database table.
     *
     * This method adds a soft delete timestamp field to the table, allowing
     * for soft deletion of records. It logs the action to the console.
     *
     * @return void
     */
    public function addSoftDeletes(): void
    {
        // Alter the main table with the specified schema and indexes
        // Log enabling soft deletes
        ConsoleOutput::info('🗑️ Enabling soft deletes for "' . $this->getTable() . '"...', false);

        // Soft delete timestamp
        $this->softDeletes()->unique()->comment('Soft delete timestamp');
    }

    /**
     * Adds a metadata field to the database table.
     *
     * This method adds a JSON column for storing additional metadata for
     * the record. It logs the action to the console.
     *
     * @return void
     */
    public function addMetadata(): void
    {
        // Alter the main table with the specified schema and indexes
        // Log the addition of the metadata field
        ConsoleOutput::info('🗄️ Adding metadata field to "' . $this->getTable() . '"...', false);

        // Metadata column (JSON)
        $this->json(static::META_DATA)->nullable()->comment('Additional metadata for the record');
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
    public function createMetadataTable(): void
    {
        // Define the name of the metadata table
        $metadataTableName = $this->getTable() . static::METADATA_TABLE_PREFIX;

        // Alter the metadata table
        // Primary key
        $this->id();

        // Foreign key constraint
        $this->foreignId(static::RECORD_ID)->constrained($this->getTable())->onDelete('cascade');

        // Metadata key
        $this->string(static::KEY)->comment('Key of the metadata entry');

        // Metadata value
        $this->text(static::VALUE)->nullable()->comment('Value of the metadata entry');

        // Add timestamps for the metadata table
        $this->addTimestamps();

        // Log the creation of the metadata table
        ConsoleOutput::success('Metadata table "' . $this->getTable() . '_metadata" created successfully! 🎉');
    }
}
