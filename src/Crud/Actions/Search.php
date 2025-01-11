<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Actions;

use AllowDynamicProperties;
use Maginium\Foundation\Enums\HttpStatusCode;
use Maginium\Foundation\Enums\SortOrder;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Foundation\Exceptions\NoSuchEntityException;
use Maginium\Foundation\Exceptions\NotFoundException;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Crud\Constants\Criteria;
use Maginium\Framework\Crud\Interfaces\SearchInterface;
use Maginium\Framework\Crud\Interfaces\Services\ServiceInterface;
use Maginium\Framework\Database\Enums\ComparisonOperator;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Elasticsearch\Interfaces\Services\ServiceInterface as ElasticServiceInterface;
use Maginium\Framework\Pagination\Constants\Paginator as PaginatorConstants;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Str;

/**
 * Class Search.
 *
 * Abstract service class for managing models.
 * This class handles CRUD operations and responses related to models.
 */
#[AllowDynamicProperties] //@phpstan-ignore-line
class Search implements SearchInterface
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * @var ModelInterface
     */
    protected ModelInterface $modelFactory;

    /**
     * @var ServiceInterface|ElasticServiceInterface
     */
    protected $service;

    /**
     * The name of the model.
     *
     * @var string
     */
    protected string $modelName;

    /**
     * Search constructor.
     * Initializes the service and sets the logger and model name.
     *
     * @param ServiceInterface $service The Entity service instance.
     */
    public function __construct(
        ServiceInterface $service,
    ) {
        // Assign the service instance to class property
        $this->service = $service;

        // Set the class name for logging purposes
        Log::setClassName(static::class);

        // Set model name (can be dynamically set in subclasses)
        $this->modelName = $service->getEntityName();
    }

    /**
     * Search for models based on a search term with optional filters, sorting, and pagination.
     *
     * @param string $searchTerm The term to search for.
     * @param int $page The page number for pagination (defaults to the first page).
     * @param int $perPage The number of items per page (defaults to 10).
     * @param array $filters Additional filters for refining the search.
     * @param array $sorts The sorting order ('ASC' or 'DESC').
     *
     * @throws NotFoundException If the model with the given search term does not exist in the service.
     * @throws LocalizedException If no models are found or if an error occurs during the search process.
     *
     * @return array The search results with metadata and model data.
     */
    public function handle(
        string $searchTerm,
        int $page = PaginatorConstants::DEFAULT_PAGE,
        int $perPage = PaginatorConstants::DEFAULT_PER_PAGE,
        array $filters = [],
        array $sorts = [],
    ): array {
        try {
            // Validate pagination parameters to ensure the page and perPage values are within allowed ranges
            $this->assertValidPagination($page, $perPage);

            // Prepare search criteria, which include the search term, filters, sorts, and pagination details
            $criteria = $this->prepareCriteria($searchTerm, $filters, $sorts, $page, $perPage);

            // Fetch search results from the service based on the prepared criteria
            $searchResults = $this->service->query($searchTerm)->filter($filters)->sortBy($sorts);

            // Using Collection to map each search result models to an array representation
            $models = collect($searchResults->all())->map(function($model) {
                // Convert each model to an array (assuming each model has a toArray method)
                return $this->applyColumnFilter($model->toDataArray());
            });

            // Handle the case where no models are found, throwing a localized exception with an appropriate message
            if ($models->isEmpty()) {
                throw LocalizedException::make(
                    __('No %1 were found matching the search term "%2". Please try using different search criteria.', Str::plural($this->modelName), $searchTerm),
                    null,
                    HttpStatusCode::NOT_FOUND,
                );
            }

            // Prepare the response with the payload, status code, success message, and meta information
            $response = $this->response()
                ->setPayload($models->all()) // Set the payload
                ->setStatusCode(HttpStatusCode::OK) // Set HTTP status code to 200 (OK)
                ->setMessage(__('Successfully retrieved %1 search results.', Str::plural($this->modelName))) // Set a success message with the model name
                ->setMeta($searchResults->meta()); // Set meta information (e.g., total count, pagination info)

            // Return the formatted result as an associative array
            return $response->toArray();
        } catch (NoSuchEntityException|NotFoundException|LocalizedException $e) {
            // Propagate service exceptions as-is
            throw $e;
        } catch (Exception $e) {
            // Catch any general exceptions and rethrow a localized exception with a generic error message
            throw LocalizedException::make(
                __('An unexpected error occurred while searching for %1. Please try again later.', Str::plural($this->modelName)),
                $e,
                HttpStatusCode::INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Prepare the search criteria including pagination, filters, and sorting.
     *
     * @param string $searchTerm The search term to search for.
     * @param array $filters The filters to apply to the search.
     * @param array $sorts The sorting order for the search results.
     * @param int $page The current page number for pagination.
     * @param int $perPage The number of items per page.
     *
     * @return array The prepared criteria for searching.
     */
    private function prepareCriteria(
        string $searchTerm,
        array $filters,
        array $sorts,
        int $page,
        int $perPage,
    ): array {
        // Set default sorting if no sorting options are provided
        $sorts = $this->setDefaultSorts($sorts);

        // Process the provided filters to ensure they are properly structured
        $enhancedFilters = $this->processFilters($searchTerm, $filters);

        // Return the full search criteria as an associative array
        return [
            Criteria::KEY_PAGE => $page, // Current page number
            Criteria::KEY_PER_PAGE => $perPage, // Pagination limit (number of items per page)
            Criteria::KEY_FILTERS => $enhancedFilters, // Filters to refine the search
            Criteria::KEY_SORTS => $sorts, // Sorting options for the search results
        ];
    }

    /**
     * Set default sorting if no sorting is provided.
     *
     * @param array $sorts The sorting order.
     *
     * @return array The default or provided sorting order.
     */
    private function setDefaultSorts(array $sorts): array
    {
        // If no sorting is provided, use 'id' in ascending order as the default sort
        if (empty($sorts)) {
            return [Criteria::DEFAULT_KEY => SortOrder::ASC];
        }

        // Return the sorting order provided by the user
        return $sorts;
    }

    /**
     * Process and enhance filters to be applied in the search.
     *
     * @param string $searchTerm The search term to include in the filters.
     * @param array $filters The provided filters.
     *
     * @return array The enhanced filters for the search query.
     */
    private function processFilters(string $searchTerm, array $filters): array
    {
        $finalFilters = [];

        // Always include the search term as a filter if provided (search term is treated as a 'like' condition)
        if (! empty($searchTerm)) {
            $finalFilters[] = [
                Criteria::KEY_VALUE => $searchTerm, // The value to filter on (the search term)
                Criteria::KEY_CONDITION => ComparisonOperator::LIKE, // Condition is 'LIKE' for partial matching
            ];
        }

        // Add custom filters if provided by the user
        foreach ($filters as $filter) {
            // Ensure each filter has the required keys: field, value, and condition
            if (isset($filter[Criteria::KEY_FIELD], $filter[Criteria::KEY_VALUE], $filter[Criteria::KEY_CONDITION])) {
                // Add the filter to the final filter array
                $finalFilters[] = [
                    Criteria::KEY_FIELD => $filter[Criteria::KEY_FIELD], // The field to filter on
                    Criteria::KEY_VALUE => $filter[Criteria::KEY_VALUE], // The value to match for this filter
                    Criteria::KEY_CONDITION => $filter[Criteria::KEY_CONDITION], // The condition for the filter (e.g., 'EQUALS', 'LIKE')
                ];
            }
        }

        // Return the enhanced filters that can be used in the search query
        return $finalFilters;
    }
}
