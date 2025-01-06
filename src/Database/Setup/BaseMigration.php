<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Database\Interfaces\RevertablePatchInterface;
use Maginium\Framework\Database\Model;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Reflection;
use Zend_Db_Exception;
use Zend_Db_Statement_Interface;

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
 * @mixin RevertablePatchInterface
 */
abstract class BaseMigration implements PatchRevertableInterface, SchemaPatchInterface
{
    /**
     * The name of the database table that this migration will operate on.
     *
     * @var string
     */
    protected static ?string $tableName = null;

    /**
     * The area code for the patch, can be overridden in child classes.
     *
     * This constant is used to specify the area code when the patch is applied.
     * The area code helps Magento determine the context (e.g., frontend, admin) under which the patch should run.
     * If not defined, no area code will be set, and Magento will use the default behavior.
     *
     * @var string|null The area code for the patch, or null if not set.
     */
    protected const AREA_CODE = null;

    /**
     * The model that this factory corresponds to.
     *
     * @var class-string<TModel>
     */
    protected $model;

    /**
     * Context object containing necessary services for patch execution.
     *
     * @var Migration\Context
     */
    protected $context;

    /**
     * Migration constructor.
     *
     * This constructor accepts the context object which contains necessary services
     * like the module data setup, patch history, and other dependencies required to apply or revert the patch.
     *
     * @param Migration\Context $context Context object containing dependencies like module setup, patch history, etc.
     */
    public function __construct(
        Migration\Context $context,
    ) {
        // Initialize context object with the given context
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     *
     * Returns the list of dependencies that this patch has.
     * Can be overridden in child classes to specify other patches that should be applied first.
     *
     * @return array Array of class names that this patch depends on.
     */
    public static function getDependencies(): array
    {
        // No dependencies by default
        return [];
    }

    /**
     * Executes an SQL query on the database.
     *
     * This method provides a shorthand to execute SQL queries by accessing
     * the database connection used within the setup process. It ensures that
     * SQL queries are run efficiently during patch execution, optionally
     * binding parameters to the query.
     *
     * @param string $sql The SQL query string to be executed.
     * @param array $bind Optional array of values to bind to the SQL query.
     *
     * @return Zend_Db_Statement_Interface The statement object returned from the query execution.
     */
    protected function query(string $sql, array $bind = []): Zend_Db_Statement_Interface
    {
        // Ensure that the database connection is available
        $connection = $this->context->getModuleDataSetup()->getConnection();

        // Execute the query with optional bindings and return the statement
        return $connection->query($sql, $bind);
    }

    /**
     * {@inheritdoc}
     *
     * Returns the list of aliases for the patch.
     * If there are multiple aliases under which the patch could be identified, return them here.
     *
     * @return array Array of alias names for this patch.
     */
    public function getAliases(): array
    {
        // Return an empty array by default as no aliases are defined
        return [];
    }

    /**
     * Run code inside patch
     * If code fails, patch must be reverted, in case when we are speaking about schema - then under revert
     * means run PatchInterface::revert().
     *
     * If we speak about data, under revert means: $transaction->rollback()
     *
     * @return $this
     */
    abstract public function apply(): void;

    /**
     * Rollback all changes, done by this patch.
     *
     * @return void
     */
    abstract public function revert(): void;

    /**
     * Retrieve the schema setup instance.
     *
     * Provides access to the SchemaSetupInterface for general setup operations during module installation or upgrades.
     *
     * @return SchemaSetupInterface The schema setup instance.
     */
    public function getSetup(): SchemaSetupInterface
    {
        return $this->context->getSchemaSetup();
    }

    /**
     * Start the setup process.
     *
     * Initiates the setup process by invoking the startSetup method on the schema setup instance.
     *
     * @return void
     */
    public function startSetup(): void
    {
        $this->getSetup()->startSetup();
    }

    /**
     * End the setup process.
     *
     * Finalizes the setup process by invoking the endSetup method on the schema setup instance.
     *
     * @return void
     */
    public function endSetup(): void
    {
        $this->getSetup()->endSetup();
    }

    /**
     * Check if a table exists in the database.
     *
     * This method checks whether the specified table exists in the database.
     * It uses the `tableExists` method from the database connection to perform the check.
     *
     * @param string $table The name of the table to check.
     *
     * @return bool Returns true if the table exists, false otherwise.
     */
    protected function tableExists(string $table): bool
    {
        try {
            // Check if the table exists using the connection's `tableExists` method
            return $this->getConnection()->isTableExists($table);
        } catch (Exception $e) {
            // Log the error if the check fails
            Log::error(__('Error checking existence of table "%1": %2', [$table, $e->getMessage()])->render());

            return false;
        }
    }

    /**
     * Shorthand method to get the database connection.
     *
     * This method provides a convenient way to access the database connection
     * used for running SQL queries during patch execution.
     *
     * @return AdapterInterface The database connection object.
     */
    protected function getConnection(): AdapterInterface
    {
        // Return the database connection from the setup instance
        return $this->getSetup()->getConnection();
    }

    /**
     * Retrieve the table name.
     *
     * @return string|null The fully prefixed table name if provided, or `null` if no table name is defined.
     */
    protected function getTable(): ?string
    {
        return static::$tableName;
    }

    /**
     * Get the full table name with prefix.
     *
     * This method ensures the table name is correctly prefixed as per Magento's table naming conventions.
     * It uses the `ModuleDataSetupInterface` to get the full table name with the appropriate prefix.
     *
     * @param string|null $rawName The raw (unprefixed) table name.
     *
     * @return string The fully qualified table name, including prefix.
     */
    protected function getTableName(?string $rawName = null): string
    {
        // Get constant values using reflection
        $modelName = $this->getModelConstant('ENTITY');
        $tableName = $this->getModelConstant('TABLE_NAME');

        // Determine the base table name (unprefixed)
        $tableName = $this->model::$table ?? $modelName ?? $tableName ?? $rawName;

        // Return the fully qualified table name with the appropriate prefix
        return $this->context->getModuleDataSetup()->getTable($tableName);
    }

    /**
     * Truncates the specified database table.
     *
     * This method uses the `TRUNCATE TABLE` SQL query to remove all records from the table.
     * It is useful when you want to reset the table before running a patch or migration.
     *
     * @param string $rawTableName The raw table name to truncate (without prefix).
     *
     * @return void
     */
    protected function truncate(string $rawTableName): void
    {
        // Get the full table name with prefix
        $tableName = $this->getTableName($rawTableName);

        try {
            // Begin the setup process (transaction)
            $this->getConnection()->startSetup();

            // Execute the TRUNCATE query to remove all records from the table
            $this->getConnection()->query('TRUNCATE TABLE ' . $tableName);

            // Log successful truncation
            Log::info(__('Table "%1" truncated successfully.', [$tableName])->render());
        } catch (Exception $e) {
            // Log the error if the truncation fails
            Log::error(__('Error truncating table "%1": %2', [$tableName, $e->getMessage()])->render());
        } finally {
            // End the setup process (commit transaction)
            $this->getConnection()->endSetup();
        }
    }

    /**
     * Get a constant value by name from the model.
     *
     * This method retrieves the value of a specified constant defined in the model class.
     *
     * @param string $constant The name of the constant to retrieve.
     *
     * @return mixed|null The value of the constant, or null if not found.
     */
    protected function getModelConstant(string $constant): mixed
    {
        return Reflection::getConstant($this->model, $constant);
    }

    /**
     * Get a property value by name from the model.
     *
     * This method retrieves the value of a specified property from the model class.
     *
     * @param string $property The name of the property to retrieve.
     *
     * @return mixed|null The value of the property, or null if not found.
     */
    protected function getModelProperty(string $property): mixed
    {
        return Reflection::getProperty($this->model, $property);
    }

    /**
     * Workaround for Magento core bug.
     *
     * This method checks if a patch has already been applied by looking for its alias in the patch history.
     * If an alias exists in the patch history, it returns the applied alias; otherwise, it returns false.
     *
     * @see https://github.com/magento/magento2/issues/31396
     *
     * @return string|false The applied alias name or false if no alias is found.
     */
    protected function hasAlreadyAppliedAlias()
    {
        // Check if any alias of this patch has already been applied
        foreach ($this->getAliases() as $alias) {
            // Look for the alias in the patch history and return it if found
            if ($this->context->getPatchHistory()->isApplied($alias)) {
                return $alias;
            }
        }

        // Return false if no alias is found
        return false;
    }

    /**
     * Convert table charset and collation, and modify specified columns to use the desired charset and data type.
     *
     * @param string $tableName The name of the table to modify.
     * @param array $columns An associative array where the keys are column names and the values are their data types.
     * @param string $charset The desired character set (default: 'utf8mb4').
     * @param string $collation The desired collation (default: 'utf8mb4_unicode_ci').
     *
     * @throws Zend_Db_Exception If there is an error executing any of the queries.
     */
    protected function updateTableCharsetAndColumns(string $tableName, array $columns, string $charset = 'utf8mb4', string $collation = 'utf8mb4_unicode_ci'): void
    {
        // Convert the entire table to the desired charset and collation
        $this->query(
            sprintf(
                'ALTER TABLE %s CONVERT TO CHARACTER SET %s COLLATE %s;',
                $tableName,
                $charset,
                $collation,
            ),
        );

        // Loop through the columns array and modify each column's data type and charset
        foreach ($columns as $columnName => $columnType) {
            // Modify each column with the specified data type and charset
            $this->query(
                sprintf(
                    'ALTER TABLE %s MODIFY %s %s CHARSET %s;',
                    $tableName,
                    $columnName,
                    $columnType,
                    $charset,
                ),
            );
        }
    }

    /**
     * Handles setting up the area code if required, with error logging.
     *
     * @param string $areaCode The area code to set (e.g., admin, frontend).
     */
    protected function initializeAreaCode(string $areaCode): void
    {
        try {
            $this->context->getState()->setAreaCode($areaCode);
        } catch (Exception $e) {
            Log::warning(
                __('Failed to set area code "%1": %2', [$areaCode, $e->getMessage()]),
            );
            // Proceed even if setting the area code fails
        }
    }
}
