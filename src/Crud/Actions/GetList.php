<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Actions;

use AllowDynamicProperties;
use Maginium\Foundation\Enums\HttpStatusCodes;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Foundation\Exceptions\NoSuchEntityException;
use Maginium\Foundation\Exceptions\NotFoundException;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Crud\Interfaces\GetListInterface;
use Maginium\Framework\Crud\Interfaces\Services\ServiceInterface;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Elasticsearch\Interfaces\Services\ServiceInterface as ElasticServiceInterface;
use Maginium\Framework\Pagination\Constants\Paginator as PaginatorConstants;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Str;

/**
 * Class GetList.
 *
 * Abstract service class for managing models.
 * This class handles CRUD operations and responses related to models.
 */
#[AllowDynamicProperties] //@phpstan-ignore-line
class GetList implements GetListInterface
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
     * GetList constructor.
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
     * Retrieve a paginated list of models.
     *
     * @throws NotFoundException If no models exist in the service.
     * @throws LocalizedException If an error occurs during the retrieval process.
     *
     * @return array The paginated list of models, including metadata and data.
     */
    public function handle(): array
    {
        try {
            // Retrieve the 'data' array that will be inserted or updated.
            $page = (int)$this->query(PaginatorConstants::PAGE, PaginatorConstants::DEFAULT_PAGE);

            // Retrieve the 'update' array, which contains the fields to update if a matching record is found.
            $defaultPerPage = $this->service->getRepository()->getModel()->getPerPage() ?? PaginatorConstants::DEFAULT_PER_PAGE;
            $perPage = (int)$this->query(PaginatorConstants::PER_PAGE, $defaultPerPage);

            // Validate pagination parameters
            $this->assertValidPagination($page, $perPage);

            // Fetch paginated list of models from the service
            $models = $this->service->paginate($perPage, $this->getColumns(), $page);

            // Throw exception if no models were found
            if ($models->isEmpty()) {
                throw LocalizedException::make(
                    __('No %1 records found. Please verify the request and try again.', Str::plural($this->modelName)),
                    null,
                    HttpStatusCodes::NOT_FOUND,
                );
            }

            // Prepare the response with the payload, status code, success message, and meta information
            $response = $this->response()
                ->setPayload(data: $models->all()) // Set the payload
                ->setStatusCode(HttpStatusCodes::OK) // Set HTTP status code to 200 (OK)
                ->setMessage(__('Successfully retrieved the %1 list.', Str::plural($this->modelName))) // Set a success message with the model name
                ->setMeta($models->meta()); // Set meta information (e.g., total count, pagination info)

            // Return the formatted result as an associative array
            return $response->toArray();
        } catch (NoSuchEntityException|NotFoundException|LocalizedException $e) {
            // Propagate service exceptions as-is
            throw $e;
        } catch (Exception $e) {
            // Catch any general exceptions and rethrow a localized exception with a generic error message
            throw LocalizedException::make(
                __('An unexpected error occurred while retrieving the %1 list. Please contact support if the problem persists.', Str::plural($this->modelName)),
                $e,
                HttpStatusCodes::INTERNAL_SERVER_ERROR,
            );
        }
    }
}
