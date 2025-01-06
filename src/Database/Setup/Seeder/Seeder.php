<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Setup\Seeder;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Database\Model;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Seeder Class.
 *
 * This abstract class is used as a base class for data patchers that modify database tables during
 * module setup. It implements both `DataPatchInterface`, allowing patches to be applied.
 * It provides helper methods to manage database connections and execute seeders.
 *
 * @template TModel of Model
 */
abstract class Seeder implements DataPatchInterface
{
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
     * The number of sample records to create.
     *
     * @var int Number of records to create in the database.
     */
    protected int $count = 10;

    /**
     * The model that this factory corresponds to.
     *
     * @var class-string<TModel>
     */
    protected string $model;

    /**
     * The model name.
     *
     * @var string
     */
    protected string $modelName;

    /**
     * Context object containing necessary services for patch execution.
     *
     * @var Context
     */
    private Context $context;

    /**
     * Seeder constructor.
     *
     * This constructor accepts the context object which contains necessary services
     * like the module data setup, patch history, and other dependencies required to apply or revert the patch.
     *
     * @param Context $context Context object containing dependencies like module setup, patch history, etc.
     */
    public function __construct(
        Context $context,
    ) {
        // Initialize context object with the given context
        $this->context = $context;

        // Get the dynamic model name for logging (capitalized and formatted)
        $this->modelName = Str::lower(Str::headline(value: Reflection::getClassBasename($this->model)));
    }

    /**
     * Returns the list of dependencies that this patch has.
     *
     * This method can be overridden in child classes to specify any patches
     * that should be applied before the current patch. By default, it returns
     * an empty array indicating that there are no dependencies.
     *
     * @return array An array of fully qualified class names that this patch depends on.
     *                  If no dependencies exist, the method will return an empty array.
     */
    public static function getDependencies(): array
    {
        // No dependencies by default
        return [];
    }

    /**
     * Applies the patch to the database.
     *
     * This method is responsible for executing the patch's changes to the database.
     * It first checks if the patch has already been applied under a different alias to avoid reapplying it.
     * If necessary, it sets the area code for the operation (e.g., admin, frontend) and then invokes the
     * `execute()` method to apply the migration logic. The method ensures the database transaction is properly handled.
     *
     * @return void
     */
    final public function apply(): void
    {
        // Check if the patch was already applied under a different alias
        if ($appliedAlias = $this->hasAlreadyAppliedAlias()) {
            // Log that the patch was skipped because it was already applied under the old alias
            Log::info(
                __('Patch "%1" skipped. Already applied with alias "%2".', [static::class, $appliedAlias])->render(),
            );

            // Skip execution if already applied
            return;
        }

        // Start setup process (begin database transaction)
        $this->getConnection()->startSetup();

        // Attempt to set the area code if defined
        if ($areaCode = static::AREA_CODE) {
            $this->initializeAreaCode($areaCode);
        }

        // Execute the patch's migration logic
        $this->seed();

        // End setup process (commit database transaction)
        $this->getConnection()->endSetup();
    }

    /**
     * Retrieves the list of aliases for the patch.
     *
     * This method returns an array of aliases under which the patch could be identified.
     * If the patch has multiple aliases, they should be returned in the array. By default,
     * it returns an empty array indicating that no aliases are defined.
     *
     * @return array An array of alias names for the patch.
     */
    public function getAliases(): array
    {
        // Return an empty array by default as no aliases are defined
        return [];
    }

    /**
     * Inserts data into the database.
     *
     * This method is responsible for inserting data into the specified table.
     * It handles the database insertion and logs success or failure.
     * If the insertion fails, it will roll back the transaction to maintain data consistency.
     *
     * @param DataObject|Collection $data The data to be inserted into the database.
     *
     * @return void
     */
    protected function insertData(DataObject|Collection $data): void
    {
        // Get the table name (using the prefixed table name if needed)
        $tableName = $this->getTableName();

        // Ensure the table exists before proceeding with the insert
        if (! $this->tableExists($tableName)) {
            // Log an error message if the table does not exist
            Log::error(__('Table "%1" does not exist. Data insertion aborted.', [$tableName])->render());

            // Exit early as the table doesn't exist
            return;
        }

        // Ensure there is data to insert
        if ($this->isDataEmpty($data)) {
            // Log a warning message if no data is provided for insertion
            Log::warning(__('No data provided for insertion into table "%1".', [$tableName])->render());

            return;
        }

        // Start the database transaction to ensure rollback on failure
        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            // Normalize data to an array if it's a DataObject or Collection
            $data = $this->normalizeData($data);

            // Insert the provided data into the table
            $connection->insertOnDuplicate($tableName, $data);

            // Log details of seeded record
            $this->processLogging($data);

            // Commit the transaction if insertion is successful
            $connection->commit();

            // Log a success message
            Log::info(__('Data inserted successfully into table "%1".', [$tableName])->render());
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            $connection->rollBack();

            // Log the error with the exception details
            Log::error(__('Error inserting data into table "%1": %2', [$tableName, $e->getMessage()])->render());
        }
    }

    /**
     * Check if a table exists in the database.
     *
     * This method checks whether the specified table exists in the database.
     * It uses the `tableExists` method from the database connection to perform the check.
     *
     * @param string $tableName The name of the table to check.
     *
     * @return bool Returns true if the table exists, false otherwise.
     */
    protected function tableExists(string $tableName): bool
    {
        try {
            // Check if the table exists using the connection's `tableExists` method
            return $this->getConnection()->isTableExists($tableName);
        } catch (Exception $e) {
            // Log the error if the check fails
            Log::error(__('Error checking existence of table "%1": %2', [$tableName, $e->getMessage()])->render());

            return false;
        }
    }

    /**
     * Abstract method for the migration logic.
     *
     * This method should be implemented in child classes to define the actual database migration logic.
     * It is invoked when the patch is applied.
     *
     * @return void
     */
    abstract protected function seed(): void;

    /**
     * Log a single record (to be implemented by child classes).
     *
     * This method is meant to be overridden by child classes to handle logging logic
     * that depends on the structure of the data. For example, different fields might
     * need to be logged based on the data model.
     *
     * @param DataObject $record The data record to log.
     * @param string $modelName The name of the model.
     *
     * @return void
     */
    protected function log(DataObject $record, string $modelName): void
    {
        // Default logging behavior (can be customized or overridden by child classes)
        ConsoleOutput::success("🌱 Seeded {$modelName}: {$record->getName()} (ID: {$record->getId()})", false);
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
        return $this->context->getModuleDataSetup()->getConnection();
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
     * Truncates the specified database table.
     *
     * This method uses the `TRUNCATE TABLE` SQL query to remove all records from the table.
     * It is useful when you want to reset the table before running a patch or seeder.
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
     * Log details of seeded records to the console.
     *
     * This method is responsible for outputting the details of each seeded record
     * to the console for confirmation and debugging purposes. It can be customized in child
     * classes to implement any additional logic (such as rollback logic) if needed.
     *
     * @param array $data The collection of seeded records.
     *
     * @return void
     */
    private function processLogging(array $data): void
    {
        // Convert the data array to data object
        $data = DataObject::make($data);

        // Use the modelName directly from the instance
        $modelName = $this->modelName;

        // Iterate through each record and log its details
        $data->each(function(DataObject|Collection $record) use ($modelName): void {
            // Call the abstract or customizable method in the child class
            $this->log($record, $modelName);
        });
    }

    /**
     * Checks if the provided data is empty.
     *
     * This method is used to check if the data passed to the insertData method is empty.
     * It considers different types of data: arrays, DataObjects, and Collections.
     *
     * @param mixed $data The data to be checked.
     *
     * @return bool Returns true if the data is empty, false otherwise.
     */
    private function isDataEmpty(mixed $data): bool
    {
        // Check if the data is an array
        // If it's an array, we simply check if it's empty.
        if (Validator::isArray($data)) {
            return Validator::isEmpty($data);
        }

        // Check if the data is a DataObject or Collection
        // Both DataObject and Collection have an isEmpty method to determine if they contain any items.
        if ($data instanceof DataObject || $data instanceof Collection) {
            return $data->isEmpty();
        }

        // Return true for any other type of data that doesn't match the above conditions
        return true;
    }

    /**
     * Normalizes the data into an array if it's a DataObject or Collection.
     *
     * This method converts a DataObject or Collection into an array so that we can always work with an array
     * when performing the database insert. This ensures consistency, as the database insertion logic always
     * expects an array.
     *
     * @param array|DataObject|Collection $data The data to normalize.
     *
     * @return array The normalized data in array form.
     */
    private function normalizeData(array|DataObject|Collection $data): array
    {
        // Check if the data is an instance of DataObject or Collection
        // If it is, we convert it into an array using the toArray method.
        if ($data instanceof DataObject || $data instanceof Collection) {
            $data = $data->toArray();
        }

        // If the data is an array and has index 0, return the first element
        if (Validator::isArray($data) && isset($data[0])) {
            // Return the first item in the array if it exists
            return $data[0];
        }

        // If no index 0, return the data as is (can be an associative array)
        return $data;
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
    private function hasAlreadyAppliedAlias()
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
     * Handles setting up the area code if required, with error logging.
     *
     * @param string $areaCode The area code to set (e.g., admin, frontend).
     */
    private function initializeAreaCode(string $areaCode): void
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
