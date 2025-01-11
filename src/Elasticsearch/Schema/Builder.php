<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Schema;

use Closure;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Elasticsearch\Connection;
use Maginium\Framework\Elasticsearch\DSL\Results;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Validator;

/**
 * Class Builder.
 *
 * This class provides a builder pattern for managing Elasticsearch index operations.
 * It includes methods for creating, modifying, deleting, and querying indices,
 * along with handling custom analyzers, templates, and field mappings.
 */
class Builder
{
    /**
     * @var Connection The Elasticsearch connection instance
     */
    protected Connection $connection;

    /**
     * @var IndexBlueprintFactory The factory for creating index blueprints
     */
    protected IndexBlueprintFactory $indexBlueprintFactory;

    /**
     * @var AnalyzerBlueprintFactory The factory for creating analyzer blueprints
     */
    protected AnalyzerBlueprintFactory $analyzerBlueprintFactory;

    /**
     * Builder constructor.
     *
     * Initializes the builder with an Elasticsearch connection, index blueprint factory,
     * and analyzer blueprint factory.
     *
     * @param Connection $connection The Elasticsearch connection instance
     * @param IndexBlueprintFactory $indexBlueprintFactory The factory for creating index blueprints
     * @param AnalyzerBlueprintFactory $analyzerBlueprintFactory The factory for creating analyzer blueprints
     */
    public function __construct(
        Connection $connection,
        IndexBlueprintFactory $indexBlueprintFactory,
        AnalyzerBlueprintFactory $analyzerBlueprintFactory,
    ) {
        $this->connection = $connection;
        $this->indexBlueprintFactory = $indexBlueprintFactory;
        $this->analyzerBlueprintFactory = $analyzerBlueprintFactory;
    }

    /**
     * Create a new Elasticsearch index.
     *
     * This method creates an index using a provided callback to modify the index's blueprint.
     *
     * @param string  $index    The name of the index to create
     * @param Closure $callback A closure that modifies the index blueprint
     *
     * @return array The details of the created index
     */
    public function create($index, Closure $callback): array
    {
        $blueprint = $this->indexBlueprintFactory->create(['index' => $index]);

        // Apply the callback to modify the blueprint
        tap($blueprint, function($blueprint) use ($callback) {
            $callback($blueprint);
        });

        // Perform the builder operation
        $this->builder('buildIndexCreate', blueprint: $blueprint);

        return $this->getIndex($index);
    }

    /**
     * Delete an existing Elasticsearch index.
     *
     * @param string $index The name of the index to delete
     *
     * @return bool Whether the index was successfully deleted
     */
    public function delete($index): bool
    {
        $this->connection->setIndex($index);

        return $this->connection->indexDelete();
    }

    /**
     * Delete an existing Elasticsearch index.
     *
     * @param string $index The name of the index to delete
     *
     * @return bool Whether the index was successfully deleted
     */
    public function deleteIfExists($index): bool
    {
        if ($this->hasIndex($index)) {
            $this->connection->setIndex($index);

            return $this->connection->indexDelete();
        }

        return false;
    }

    /**
     * Override the index prefix.
     *
     * Sets a custom prefix for the index.
     *
     * @param string $value The prefix value
     *
     * @return self The builder instance for method chaining
     */
    public function overridePrefix($value): self
    {
        $this->connection->setIndexPrefix($value);

        return $this;
    }

    /**
     * Get the settings for a specific index.
     *
     * @param string $index The index name
     *
     * @return array The settings of the index
     */
    public function getSettings($index): array
    {
        $this->connection->setIndex($index);

        return $this->connection->indexSettings($this->connection->getIndex());
    }

    /**
     * Get the details of an index.
     *
     * Retrieves the details of an index if it exists.
     *
     * @param string $index The name of the index
     *
     * @return array The details of the index, or an empty array if it doesn't exist
     */
    public function getIndex($index): array
    {
        if ($this->hasIndex($index)) {
            $this->connection->setIndex($index);

            return $this->connection->getIndices(false);
        }

        return [];
    }

    /**
     * Check if an index exists.
     *
     * @param string $index The name of the index
     *
     * @return bool Whether the index exists
     */
    public function hasIndex($index): bool
    {
        $index = $this->connection->setIndex($index);

        return $this->connection->indexExists($index);
    }

    /**
     * Get all indices.
     *
     * @return array A list of all indices
     */
    public function getIndices(): array
    {
        return $this->connection->getIndices(false);
    }

    /**
     * Create an index if it doesn't exist.
     *
     * Attempts to create the index if it doesn't already exist, otherwise returns the existing index.
     *
     * @param string  $index    The name of the index
     * @param Closure $callback A closure that modifies the index blueprint
     *
     * @return array The details of the index
     */
    public function createIfNotExists(string $index, Closure $callback): array
    {
        if ($this->hasIndex($index)) {
            return $this->getIndex($index);
        }

        // Use the factory to create the index blueprint
        $blueprint = $this->indexBlueprintFactory->create(['index' => $index]);

        // Apply the callback to the blueprint
        tap($blueprint, function($blueprint) use ($callback) {
            $callback($blueprint);
        });

        // Perform the builder operation
        $this->builder('buildIndexCreate', blueprint: $blueprint);

        return $this->getIndex($index);
    }

    /**
     * Reindex data from one index to another.
     *
     * @param string $from The source index
     * @param string $to   The destination index
     *
     * @return Results The result of the reindex operation
     */
    public function reIndex(string $from, string $to): Results
    {
        return $this->connection->reIndex($from, $to);
    }

    /**
     * Modify an existing index.
     *
     * Uses a callback to modify the index blueprint and apply changes to the index.
     *
     * @param string  $index    The name of the index
     * @param Closure $callback A closure that modifies the index blueprint
     *
     * @return array The details of the modified index
     */
    public function modify(string $index, Closure $callback): array
    {
        // Use the factory to create the analyzer blueprint
        /** @var IndexBlueprint $blueprint */
        $blueprint = $this->analyzerBlueprintFactory->create(['index' => $index]);

        // Apply the callback to the blueprint
        tap($blueprint, function($blueprint) use ($callback) {
            $callback($blueprint);
        });

        // Perform the builder operation
        $this->builder('buildIndexModify', $blueprint);

        return $this->getIndex($index);
    }

    /**
     * Create an index template.
     *
     * @todo Implement template creation functionality
     *
     * @param string $name The template name
     * @param Closure $callback A closure that defines the template settings
     */
    public function createTemplate($name, Closure $callback)
    {
        //TODO
    }

    /**
     * Set the analyzer settings for an index.
     *
     * @param string  $index    The index name
     * @param Closure $callback A closure that modifies the analyzer settings
     *
     * @return array The details of the index after analyzer settings have been applied
     */
    public function setAnalyser(string $index, Closure $callback): array
    {
        // Use the factory to create the analyzer blueprint
        $blueprint = $this->analyzerBlueprintFactory->create(['index' => $index]);

        // Apply the callback to customize the blueprint
        tap($blueprint, function($blueprint) use ($callback) {
            $callback($blueprint);
        });

        // Perform the builder operation with the modified blueprint
        $this->analyzerBuilder('buildIndexAnalyzerSettings',  $blueprint);

        return $this->getIndex($index);
    }

    /**
     * Check if a field exists in an index.
     *
     * @param string $index The index name
     * @param string $field The field name
     *
     * @return bool Whether the field exists in the index
     */
    public function hasField($index, $field): bool
    {
        $index = $this->connection->setIndex($index);

        try {
            $mappings = $this->getMappings($index);
            $props = $mappings[$index]['mappings']['properties'];
            $props = $this->_flattenFields($props);
            $fileList = $this->_sanitizeFlatFields($props);

            if (in_array($field, $fileList)) {
                return true;
            }
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * Get the mappings of an index.
     *
     * @param string $index The index name
     *
     * @return array The mappings of the index
     */
    public function getMappings($index): array
    {
        $this->connection->setIndex($index);

        return $this->connection->indexMappings($this->connection->getIndex());
    }

    /**
     * Get the mapping of a specific field in an index.
     *
     * @param string $index The index name
     * @param string|array $field The field or fields to retrieve the mapping for
     * @param bool $raw Whether to return the raw mapping data
     *
     * @return array The field mapping
     */
    public function getFieldMapping(string $index, string|array $field, bool $raw = false): array
    {
        $this->connection->setIndex($index);

        return $this->connection->fieldMapping($this->connection->getIndex(), $field, $raw);
    }

    /**
     * Check if multiple fields exist in an index.
     *
     * @param string $index The index name
     * @param array $fields The list of fields to check
     *
     * @return bool Whether all fields exist
     */
    public function hasFields($index, array $fields): bool
    {
        $index = $this->connection->setIndex($index);

        try {
            $mappings = $this->getMappings($index);
            $props = $mappings[$index]['mappings']['properties'];
            $props = $this->_flattenFields($props);
            $fileList = $this->_sanitizeFlatFields($props);
            $allFound = true;

            foreach ($fields as $field) {
                if (! in_array($field, $fileList)) {
                    $allFound = false;
                }
            }

            return $allFound;
        } catch (Exception $e) {
            return false;
        }
    }

    //----------------------------------------------------------------------
    /**
     * Run a DSL query on the Elasticsearch indices.
     *
     * @param string $method The DSL method to execute
     * @param mixed $params The parameters for the DSL query
     *
     * @return Results The results of the DSL query
     */
    public function dsl($method, $params): Results
    {
        return $this->connection->indicesDsl($method, $params);
    }

    /**
     * Flatten a nested array.
     *
     * Flattens a multi-dimensional array into a single-dimensional array with dot notation keys.
     *
     * @param array $array The array to flatten
     * @param string $prefix The prefix to add to the keys (optional)
     *
     * @return array The flattened array
     */
    public function flatten($array, $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (Validator::isArray($value)) {
                $result = $result + $this->flatten($value, $prefix . $key . '.');
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }

    /**
     * Check if a table exists.
     *
     * @param string $table The table name
     *
     * @return array The details of the table (or index) if it exists
     */
    public function hasTable($table): array
    {
        return $this->getIndex($table);
    }

    /**
     * Call the builder function for index creation or modification.
     *
     * @param string $builder The builder method to call
     * @param IndexBlueprint $blueprint The blueprint to modify
     */
    protected function builder($builder, IndexBlueprint $blueprint): void
    {
        $blueprint->{$builder}($this->connection);
    }

    /**
     * Call the analyzer builder function for setting analyzers.
     *
     * @param string $builder The builder method to call
     * @param AnalyzerBlueprint $blueprint The blueprint to modify
     */
    protected function analyzerBuilder($builder, AnalyzerBlueprint $blueprint): void
    {
        $blueprint->{$builder}($this->connection);
    }

    /**
     * Flatten fields from an array into dot notation.
     *
     * This method recursively flattens a multidimensional array into a single level
     * array where each key is represented in dot notation, making it easier to access.
     *
     * @param array $array The array to flatten. Can be multidimensional.
     * @param string $prefix The prefix for the keys. It's used to append to the keys
     *                       when traversing nested arrays (default is an empty string).
     *
     * @return array The flattened fields in dot notation.
     */
    private function _flattenFields(array $array, string $prefix = ''): array
    {
        // Initialize an empty array to hold the flattened results.
        $result = [];

        // Iterate through each key-value pair in the array
        foreach ($array as $key => $value) {
            // If the value is an array, recursively flatten it
            if (Validator::isArray($value)) {
                $result = $result + $this->_flattenFields($value, $prefix . $key . '.');
            } else {
                // If it's a scalar value, assign it to the result with the proper key in dot notation
                $result[$prefix . $key] = $value;
            }
        }

        // Return the flattened array
        return $result;
    }

    /**
     * Sanitize flattened fields by removing unwanted parts of the key.
     *
     * This method processes the flattened field names (dot notation) and simplifies
     * them by removing parts like 'properties' that might appear in nested structures.
     * It ensures that only the relevant part of the key remains for each field.
     *
     * @param array $flatFields The flattened fields, where the keys are in dot notation.
     *
     * @return array A sanitized list of fields with simplified names.
     */
    private function _sanitizeFlatFields(array $flatFields): array
    {
        // Initialize an empty array to store the sanitized fields
        $fields = [];

        // If there are flattened fields, process them
        if ($flatFields) {
            foreach ($flatFields as $flatField => $value) {
                // Split the dot notation key into parts
                $parts = explode('.', $flatField);

                // Start with the first part of the key
                $field = $parts[0];

                // Walk through the parts and adjust the field names if needed
                Arr::walk($parts, function($v, $k) use (&$field, $parts) {
                    // If we encounter the 'properties' part, we append the next part to the field name
                    if ($v === 'properties') {
                        $field .= '.' . $parts[$k + 1];
                    }
                });

                // Add the sanitized field to the result array
                $fields[] = $field;
            }
        }

        // Return the sanitized fields
        return $fields;
    }
}
