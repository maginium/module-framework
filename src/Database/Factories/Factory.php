<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Factories;

use Faker\Generator;
use Illuminate\Database\Eloquent\Factories\Factory as BaseFactory;
use Maginium\Foundation\Enums\FileExtension;
use Maginium\Framework\Component\Module;
use Maginium\Framework\Database\Eloquent\Model;
use Maginium\Framework\Database\Helpers\Faker;
use Maginium\Framework\Database\Interfaces\HasMetadataInterface;
use Maginium\Framework\Database\Interfaces\HasSoftDeletesInterface;
use Maginium\Framework\Database\Interfaces\HasUserStampsInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Csv;
use Maginium\Framework\Support\Facades\Date;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;
use Maginium\Framework\Uuid\Interfaces\UuidInterface;
use Override;

/**
 * Abstract factory class for generating database model instances.
 *
 * This factory provides methods to instantiate and configure model instances
 * for use in database seeding and testing. It supports generic model creation
 * and additional modifiers for simulating various states.
 *
 * @template TModel of Model
 *
 * @property string $slugKey The key used for model slugs.
 */
abstract class Factory extends BaseFactory
{
    /**
     * Create a collection of models and persist them to the database.
     *
     * @param  (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed>  $attributes
     * @param  Model|null  $parent
     *
     * @return DataObject
     */
    #[Override]
    public function create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
    {
        // If attributes are provided, set them as state, then generate the data
        if (! Validator::isEmpty($attributes)) {
            return $this->state($attributes)->create([]);
        }

        // Generate a collection of attribute arrays without creating actual models
        $results = $this->make($attributes);

        // Return the raw attribute data collection
        return $results;
    }

    /**
     * Generate a collection of factory data without persisting it.
     *
     * This method generates an array of attribute arrays based on the factory definition,
     * applying any states or attributes provided. It returns the data as a collection of arrays,
     * but no actual models are created in the database.
     *
     * @param  (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed>  $attributes
     *         Attributes or a callable that returns an attribute array to set initial state for the generated data.
     *
     * @return DataObject<int, array<string, mixed>>
     *         Returns a collection of attribute arrays as a DataObject.
     */
    #[Override]
    public function make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
    {
        // Apply attributes as state if they are provided, then generate data
        if (! Validator::isEmpty($attributes)) {
            return $this->state($attributes)->make([]);
        }

        // Generate a single item if no count is specified
        if ($this->count === null) {
            return DataObject::make($this->getRawAttributes($parent));
        }

        // If count is specified, generate multiple items
        $results = DataObject::make(
            Arr::each(fn() => $this->getRawAttributes($parent), range(1, $this->count)),
        );

        // Return the collection of generated data arrays
        return $results;
    }

    /**
     * Indicate that the region should have user tracking.
     *
     * This method sets attributes related to user tracking when creating a region.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function withUserStamps(): self
    {
        // Format the date and time as "YYYY-MM-DD HH:mm:ss"
        return $this->state(fn(array $attributes) => [
            HasUserStampsInterface::CREATED_BY => Faker::randomDigitNotNull(),
            HasUserStampsInterface::UPDATED_BY => Faker::randomDigitNotNull(),
        ]);
    }

    /**
     * Indicate that the model should have timestamps.
     *
     * This method sets the `created_at` and `updated_at` attributes to the current timestamp.
     * The timestamps are formatted as `YYYY-MM-DD HH:mm:ss` using Carbon. This ensures that
     * the model has accurate `created_at` and `updated_at` fields during creation, which is
     * especially useful for database seeding or testing.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function withTimestamps(): static
    {
        // Create a new Carbon instance to get the current date and time.
        $currentDate = Date::now();

        // Modify the state to include current timestamps for `created_at` and `updated_at`.
        return $this->state(function(array $attributes) use ($currentDate): array {
            // Return an array containing the current timestamp values
            return [
                // Set the `created_at` column to the current timestamp in the format "Y-m-d H:i:s"
                $this->newModel()->getCreatedAtColumn() => $currentDate->format('Y-m-d H:i:s'),

                // Set the `updated_at` column to the current timestamp in the format "Y-m-d H:i:s"
                $this->newModel()->getUpdatedAtColumn() => $currentDate->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * Indicate that the region should have a UUID.
     *
     * This method sets the UUID attribute for the region.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function withUuid(): self
    {
        return $this->set(UuidInterface::UUID, Faker::uuid());
    }

    /**
     * Indicate that the region should have a slug.
     *
     * This method sets the slug attribute for the region entity.
     *
     * @param string|null $slug The slug to be set. If null, a random slug will be generated using Faker.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function withSlug(?string $slug = null): self
    {
        return $this->set($this->model::$slugKey, $slug ?? Faker::word());
    }

    /**
     * Indicate that the model should use soft deletes.
     *
     * This method sets the `deleted_at` attribute to the current timestamp and the `deleted_by`
     * user ID when soft delete is applied. Soft deletes mark records as deleted without removing
     * them from the database, which can be useful for preserving data for audit or recovery purposes.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function withSoftDeletes(): self
    {
        // Create a new Carbon instance to get the current date and time.
        $currentDate = Date::now();

        // Modify the state to include soft delete attributes: `deleted_at` and `deleted_by`.
        return $this->state(function(array $attributes) use ($currentDate): array {
            // Return an array containing the soft delete attributes
            return [
                // Set the `deleted_at` column to the current timestamp in the format "Y-m-d H:i:s"
                HasSoftDeletesInterface::DELETED_AT => $currentDate->format('Y-m-d H:i:s'),

                // Set the `deleted_by` column to a random non-null user ID (for example, using Faker for seeding)
                HasSoftDeletesInterface::DELETED_BY => Faker::randomDigitNotNull(),
            ];
        });
    }

    /**
     * Enable metadata inclusion for the seeder.
     *
     * This method enables the inclusion of metadata when seeding, by setting
     * the `metadata` attribute to a random JSON value generated via Faker.
     *
     * @return $this Returns the current instance for method chaining.
     */
    public function withMetadata(): self
    {
        // Modify the state to include the metadata attribute
        return $this->state(fn(array $attributes): array => [
            // Set the METADATA field to a random JSON value generated by Faker
            HasMetadataInterface::METADATA => Faker::randomJson(),
        ]);
    }

    /**
     * Get a new Faker instance to generate random data.
     *
     * @return Generator The Faker instance.
     */
    #[Override]
    protected function withFaker()
    {
        // Create a new Faker instance using the application's container
        return Container::get(Generator::class);
    }

    /**
     * Generate a random JSON string representing metadata.
     *
     * @param int|null $count The optional number of key-value pairs to include in the
     *                        generated JSON. Defaults to null, which uses the seeder's count.
     *
     * @return string Returns a JSON-encoded string containing random metadata.
     */
    protected function randomJson(?int $count = null): string
    {
        // Use the provided count or fall back to the seeder's count from the config
        $count ??= $this->count;

        // Generate a random number of key-value pairs between the provided count and count + 10
        $randomCount = rand($count, $count + 10);

        // Generate and return the random JSON string using the Faker helper
        return Faker::randomJson($randomCount);
    }

    /**
     * Load a fixture file based on the file extension (CSV, JSON, XML, etc.).
     *
     * This method handles loading different types of fixture files based on their extension and returns the processed data in an array format.
     *
     * @param string $fileName The path to the fixture file.
     *
     * @return array The processed data from the file, or an empty array if the file is invalid or doesn't exist.
     */
    protected function loadFile(string $fileName): array
    {
        // Obtain the module name from the namespace of the current class.
        $moduleName = Reflection::getNamespaceName(static::class, 2);

        // Retrieve the module's absolute path.
        $modulePath = Module::getPath($moduleName);

        // Combine the module path with the file name to get the full file path
        $filePath = $modulePath . DIRECTORY_SEPARATOR . $fileName;

        // Check if the fixture file exists before attempting to load it
        if (! Filesystem::exists($filePath)) {
            Log::error("Fixture file '{$filePath}' not found.");

            // Return an empty array if the file doesn't exist
            return [];
        }

        // Get the file extension
        $type = Filesystem::extension($fileName);

        // Determine how to process the file based on its type
        switch ($type) {
            case FileExtension::CSV:
                // Load and process CSV files
                return $this->loadCsvFile($filePath);

            case FileExtension::JSON:
                // Load and process JSON files
                return $this->loadJsonFile($filePath);

            case FileExtension::XML:
                // Load and process XML files
                return $this->loadXmlFile($filePath);

                // Additional file types can be added here (e.g., YAML, TXT)
            default:
                Log::error("Unsupported file type '{$type}' for '{$filePath}'.");

                // Return an empty array if the file type is unsupported
                return [];
        }
    }

    /**
     * Load and process a CSV file.
     *
     * Reads the CSV file, extracts the header, and associates each row with the corresponding column.
     * If the file is empty or has invalid data, it returns an empty array.
     *
     * @param string $fileName The path to the CSV file.
     *
     * @return array The processed CSV data, or an empty array if invalid.
     */
    private function loadCsvFile(string $fileName): array
    {
        // Read the CSV data using a CSV reader
        $content = Csv::getData($fileName);

        // Return early if no data is found
        if (empty($content)) {
            Log::error("No data found in CSV fixture '{$fileName}'.");

            return [];
        }

        // Extract the header row (first row) from the CSV data
        $header = Arr::shift($content);
        $data = [];

        // Process each row and associate it with the header columns
        foreach ($content as $row) {
            // Only proceed if the row has the same number of columns as the header
            if (Php::count($row) === Php::count($header)) {
                // Combine the header with the row data
                $rowData = Arr::combine($header, $row);

                // Add valid row data to the result array
                $data[] = $rowData;
            } else {
                Log::warning('Skipping row due to invalid column count.');
            }
        }

        return $data;
    }

    /**
     * Load and process a JSON file.
     *
     * Reads the JSON file, decodes it into an associative array, and checks for JSON parsing errors.
     * If the JSON data is invalid, it returns an empty array.
     *
     * @param string $fileName The path to the JSON file.
     *
     * @return array The processed JSON data, or an empty array if invalid.
     */
    private function loadJsonFile(string $fileName): array
    {
        // Read the JSON content from the file
        $content = Filesystem::get($fileName);

        // Return an empty array if file reading fails
        if ($content === false) {
            Log::error("Failed to read JSON file '{$fileName}'.");

            return [];
        }

        // Decode the JSON content into an associative array
        $data = Json::decode($content);

        // Check if JSON decoding failed
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Invalid JSON data in '{$fileName}'. Error: " . json_last_error_msg());

            return [];
        }

        return $data;
    }

    /**
     * Load and process an XML file.
     *
     * Loads the XML file, converts it to an array, and returns the processed data.
     * If the XML file is invalid or cannot be parsed, it returns an empty array.
     *
     * @param string $fileName The path to the XML file.
     *
     * @return array The processed XML data, or an empty array if invalid.
     */
    private function loadXmlFile(string $fileName): array
    {
        // Try loading the XML content from the file
        // Use @ to suppress potential errors
        $content = @simplexml_load_file($fileName);

        // Return early if XML parsing fails
        if ($content === false) {
            Log::error("Failed to parse XML file '{$fileName}'.");

            return [];
        }

        // Initialize an empty array to store the processed data
        $processedData = [];

        // Loop through each element in the XML content
        foreach ($content->children() as $element) {
            // Initialize an array for the current element
            $elementArray = [];

            // Loop through each child element of the current node
            foreach ($element as $key => $value) {
                // Convert SimpleXML element to string and store it as key-value pair
                $elementArray[$key] = (string)$value;
            }

            // Add the processed element to the result array
            $processedData[] = $elementArray;
        }

        return $processedData;
    }
}
