<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Helpers;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Support\Facades\MageDB;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Reflection;

/**
 * Class Model.
 *
 * This helper class provides methods for retrieving model class names,
 * checking if a class uses a specific trait, and getting all traits used by a class and its parent classes.
 */
class Model
{
    /**
     * Get the primary key for a given table.
     *
     * @param string $tableName The name of the table.
     *
     * @return string|null The primary key column name, or null if not found.
     */
    public static function getPrimaryKeyForTable(string $tableName): ?string
    {
        // Describe the table to retrieve column information
        $columns = MageDB::describeTable($tableName);

        $primaryKey = null;

        // Iterate through columns to find the primary key
        foreach ($columns as $columnName => $columnInfo) {
            if (isset($columnInfo['PRIMARY'])) {
                // Found the primary key column
                $primaryKey = $columnName;

                break;
            }
        }

        return $primaryKey;
    }

    /**
     * Get the name of the resource model.
     *
     * @param string $class Fully qualified class name.
     *
     * @return string|null Resource model name.
     */
    public static function getResourceModel(string $class): ?string
    {
        try {
            // Extract the namespace and class name from the fully qualified class name
            $namespace = Reflection::getNamespaceName($class, 2);
            $className = Reflection::getClassBasename($class);

            // Build the resource model class name by replacing 'Models' with 'ResourceModel' and appending the class name
            $resourceModelClassName = sprintf('%s\\Models\\ResourceModel\\%s', $namespace, $className);

            // Check if the resource model class exists
            if (Php::isClassExists($resourceModelClassName)) {
                return $resourceModelClassName;
            }

            // Fallback: Check for 'Models\ResourceModel\Index' if the class does not exist
            $defaultResourceModelClassName = sprintf('%s\\Models\\ResourceModel\\Index', $namespace);

            // Check if the fallback class exists
            if (Php::isClassExists($defaultResourceModelClassName)) {
                return $defaultResourceModelClassName;
            }

            return null;
        } catch (Exception $e) {
            // Catch any unexpected errors and rethrow as InvalidArgumentException
            throw InvalidArgumentException::make(__('Class %1 does not exist.', $class),  $e);
        }
    }
}
