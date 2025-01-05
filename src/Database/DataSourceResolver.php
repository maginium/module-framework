<?php

declare(strict_types=1);

namespace Maginium\Framework\Database;

use Closure;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Database\Interfaces\DataSourceInterface;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Interceptor class for resolving data from multiple sources.
 *
 * This class modifies the behavior of the `Customer` model to inject additional data
 * from various sources (e.g., sales, shipping, etc.) into the response payload.
 */
abstract class DataSourceResolver
{
    /**
     * Error message for processing data failures.
     *
     * @var string
     */
    private const ERROR_PROCESSING_DATA = 'An error occurred while processing %1 data. Please check the logs for more details.';

    /**
     * Array of data sources to resolve.
     *
     * @var array
     */
    protected array $dataSources;

    /**
     * Constructor.
     *
     * @param array $dataSources Array of data source objects to process.
     */
    public function __construct(array $dataSources = [])
    {
        // Initializing the dataSources property with the passed array.
        $this->dataSources = $dataSources;
    }

    /**
     * Modifies the behavior of Model's toDataArray method to include additional data from sources.
     *
     * @template TModel of ModelInterface
     *
     * @param TModel $model The subject instance of Model. This parameter is expected
     *                       to implement ModelInterface, and it represents the model
     *                       from which data will be processed and merged with other sources.
     * @param Closure $next The original method to call (toDataArray) after processing.
     * @param array $arrAttributes The attributes to convert to an array (optional).
     *
     * @throws LocalizedException If processing data fails or validation issues occur.
     *
     * @return array The modified data array including additional data from sources.
     */
    public function aroundToDataArray(ModelInterface $model, Closure $next, array $arrAttributes = []): array
    {
        try {
            // Calling the original toDataArray method.
            $originalResponse = $next($arrAttributes);

            // Validating the response data.
            $this->validate($originalResponse);

            // Creating a DataObject from the response data.
            $response = $this->make($originalResponse);

            // Sorting data sources based on their defined sort order.
            $this->sortDataSources();

            // Iterating over each data source to process it.
            foreach ($this->dataSources as $dataSource) {
                // Adding data from the source into the response.
                $this->processDataSource($dataSource, $model, $response);
            }

            // Returning the final response data as an array.
            return $response->toArray();
        } catch (LocalizedException $e) {
            // Rethrowing any localized exceptions.
            throw $e;
        } catch (Exception $e) {
            // Catching general exceptions, throwing a localized exception with a custom message.
            throw LocalizedException::make(
                __(self::ERROR_PROCESSING_DATA, Str::lower(Str::headline($sourceName ?? 'Unknown'))),
                $e,
            );
        }
    }

    /**
     * Validates the structure of the API response, ensuring it is both non-empty and an array.
     *
     * @param array $response The API response to validate.
     *
     * @throws LocalizedException If the response is empty or not an array.
     */
    private function validate(array $response): void
    {
        // Checking if the response is empty or not an array, and throwing an exception if it is invalid.
        if (empty($response) || ! Validator::isArray($response)) {
            throw LocalizedException::make(__('Invalid or empty response received.'));
        }
    }

    /**
     * Extracts the main data from the API response based on predefined keys.
     *
     * @param array $response The API response array to search within.
     *
     * @return DataObject|null Returns a DataObject containing the response data.
     */
    private function make(array $response): ?DataObject
    {
        // Creating and returning a DataObject from the response.
        return DataObject::make($response);
    }

    /**
     * Sorts the data sources based on their sort order.
     */
    private function sortDataSources(): void
    {
        // Sort the data sources in ascending order based on their `sortOrder` property.
        usort($this->dataSources, function(DataSourceInterface $a, DataSourceInterface $b) {
            $aSortOrder = $this->getSortOrder($a);
            $bSortOrder = $this->getSortOrder($b);

            return $aSortOrder <=> $bSortOrder;
        });
    }

    /**
     * Processes a single data source and adds its data to the response.
     *
     * @param mixed $dataSource The data source to process.
     * @param ModelInterface $model The model instance.
     * @param DataObject $response The response object to modify.
     *
     * @throws LocalizedException If the data source does not implement the required interface.
     */
    private function processDataSource($dataSource, ModelInterface $model, DataObject $response): void
    {
        // Retrieves the data key from the data source.
        $sourceName = $this->getSourceName($dataSource);

        // Checking if the data source implements the required DataSourceInterface.
        if (! Reflection::implements($dataSource, DataSourceInterface::class)) {
            // Throwing a localized exception if the data source does not implement the interface.
            throw LocalizedException::make(
                __('Data source %1 does not implement %2.', $sourceName, DataSourceInterface::class),
            );
        }

        // Getting data from the data source by calling its addData method.
        /** @var DataSourceInterface $dataSource */
        $dataFromSource = $dataSource->addData($model);

        // If the data source provides any data, add it to the response.
        // Fetch the key using the getDataKey method.
        $dataKey = $this->getDataKey($dataSource);

        // Use the fetched key to set the data in the response.
        $response->setData($dataKey, $dataFromSource);
    }

    /**
     * Retrieves the data key from the data source.
     *
     * This method checks if the data source object has a `key` property.
     * If the property exists, it returns it. If not, it falls back to the
     * class name (in lowercase) of the data source as the key.
     *
     * @param DataSourceInterface $dataSource The data source object.
     *
     * @return string The key from the data source or a derived key based on the class name.
     */
    private function getDataKey(DataSourceInterface $dataSource): string
    {
        // Check if the data source has a 'key' property. If not, use the class name as the key.
        if (Reflection::propertyExists($dataSource, 'key') && ! Validator::isEmpty($dataSource->key)) {
            return $dataSource->key;
        }

        // If the 'key' doesn't exist or is empty, fall back to the class name.
        return Str::lower($this->getSourceName($dataSource));
    }

    /**
     * Retrieves the base name of the data source class.
     *
     * This method uses PHP's `get_class_basename` function to get the class name
     * of the data source without the namespace.
     *
     * @param DataSourceInterface $dataSource The data source object.
     *
     * @return string The base name of the class (e.g., "SalesOrderData").
     */
    private function getSourceName(DataSourceInterface $dataSource): string
    {
        // Get the class name without the namespace.
        $className = Reflection::getClassBasename($dataSource::class);

        // Return the base name of the class (e.g., "SalesOrderData").
        return $className;
    }

    /**
     * Retrieves the sort order of a data source, or a default value if not available.
     *
     * @param DataSourceInterface $dataSource
     *
     * @return int
     */
    private function getSortOrder(DataSourceInterface $dataSource): int
    {
        // Default sort order if the property is not set.
        $sortOrder = 999;

        if (property_exists($dataSource, 'sortOrder')) {
            $sortOrder = $dataSource->sortOrder;
        }

        return $sortOrder;
    }
}
