<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Datasource;

use Closure;
use Maginium\Foundation\Abstracts\DataSource\DataSourceResolver;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facades\StoreManager;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Resolver class responsible for mapping document data using data sources.
 * This class identifies the appropriate data sources for a given entity type,
 * validates them, and processes them to map document data.
 */
class Resolver extends DataSourceResolver
{
    /**
     * Error message for processing data failures.
     *
     * @var string
     */
    private const ERROR_PROCESSING_DATA = 'An error occurred while processing %1 data. Please check the logs for more details.';

    /**
     * Constructor method.
     * Initializes the resolver with the provided data sources registry.
     *
     * @param Registry $registry The registry used to manage data sources.
     */
    public function __construct(Registry $registry)
    {
        parent::__construct($registry);
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
     * @param array $keys The attributes to convert to an array (optional).
     *
     * @throws LocalizedException If processing data fails or validation issues occur.
     *
     * @return array The modified data array including additional data from sources.
     */
    public function aroundToDataArray(ModelInterface $model, Closure $next, array $keys = []): array
    {
        try {
            // Calling the original toDataArray method.
            $result = $next($keys);

            // Creating a DataObject from the response data.
            $documentData = DataObject::make($result);

            // Resolve the model type based on the index identifier, entity type from the context, or a default value.
            $modelType = $this->resolveModelType($model);

            // Retrieve all data sources registered for the resolved model type.
            $dataSources = $this->registry->getDatasourcesForEntity($modelType);

            // If no data sources are available, return the original document data unchanged.
            if (Validator::isEmpty($dataSources)) {
                return $model->toArray();
            }

            // Get Store ID from the model, or fall back to the default store if unavailable.
            /** @var AbstractModel $model */
            $storeId = (int)$model->getStoreId() ?? (int)StoreManager::getStore()->getId();

            // Process the data sources concurrently and collect the results.
            $results = $this->processDataSources($dataSources, $documentData, (int)$storeId);

            // Merge all the results into a single array and return the final mapped data.
            return $results;
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
}
