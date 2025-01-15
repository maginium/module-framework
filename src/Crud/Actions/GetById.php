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
use Maginium\Framework\Crud\Interfaces\GetByIdInterface;
use Maginium\Framework\Crud\Interfaces\Services\ServiceInterface;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Elasticsearch\Eloquent\Model;
use Maginium\Framework\Elasticsearch\Interfaces\Services\ServiceInterface as ElasticServiceInterface;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Validator;

/**
 * Class GetById.
 *
 * Abstract service class for managing models.
 * This class handles CRUD operations and responses related to models.
 */
#[AllowDynamicProperties] //@phpstan-ignore-line
class GetById implements GetByIdInterface
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
     * GetById constructor.
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
     * Retrieve model by ID.
     *
     * @param int $id The ID of the model to retrieve.
     *
     * @throws NotFoundException If the model with the given ID does not exist in the service.
     * @throws LocalizedException If an error occurs during the retrieval process.
     *
     * @return array The retrieved model data.
     */
    public function handle(int $id): array
    {
        try {
            // Use the service to get the model by its unique ID
            /** @var Model $model */
            $model = $this->service->find($id);

            // Check if the model does not exist (empty result)
            if (Validator::isEmpty($model)) {
                // No results found, throw a localized exception with a custom message
                throw LocalizedException::make(
                    __('No %1 model found with ID: %2.', $this->modelName, $id),
                    null,
                    HttpStatusCodes::NOT_FOUND,
                );
            }

            // Filter the columns from the model's data
            $filteredEntityData = $model->only($this->getColumns());

            // Prepare the response with the payload, status code, success message, and meta information
            $response = $this->response()
                ->setPayload($filteredEntityData) // Set the payload
                ->setStatusCode(HttpStatusCodes::OK) // Set HTTP status code to 200 (OK)
                ->setMessage(__('%1 model retrieved successfully.', $this->modelName)); // Set a success message with the model name

            // Return the formatted result as an associative array
            return $response->toArray();
        } catch (NoSuchEntityException|NotFoundException|LocalizedException $e) {
            // Propagate service exceptions as-is
            throw $e;
        } catch (Exception $e) {
            // Catch any general exceptions and rethrow a localized exception with a generic error message
            throw LocalizedException::make(
                __('An error occurred while retrieving the %1 model by ID: %2.', $this->modelName, $id),
                $e,
                HttpStatusCodes::INTERNAL_SERVER_ERROR,
            );
        }
    }
}
