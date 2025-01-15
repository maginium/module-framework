<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Actions;

use AllowDynamicProperties;
use Maginium\Foundation\Enums\HttpStatusCodes;
use Maginium\Foundation\Exceptions\CouldNotDeleteException;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Foundation\Exceptions\NoSuchEntityException;
use Maginium\Foundation\Exceptions\NotFoundException;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Crud\Interfaces\DeleteInterface;
use Maginium\Framework\Crud\Interfaces\Services\ServiceInterface;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Elasticsearch\Eloquent\Model;
use Maginium\Framework\Elasticsearch\Interfaces\Services\ServiceInterface as ElasticServiceInterface;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Str;

/**
 * Class Service.
 *
 * Abstract service class for managing models.
 * This class handles CRUD operations and responses related to models.
 */
#[AllowDynamicProperties] //@phpstan-ignore-line
class DeleteById implements DeleteInterface
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
     * DeleteById constructor.
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
     * Handles the delete of an model by its unique identifier.
     *
     * This method retrieves input data from the request, deletes the model through the service,
     * and constructs a response with the deleted model's data. It ensures consistent exception
     * handling by propagating known exceptions and wrapping unexpected ones in a domain-specific exception.
     *
     * @param int $id The unique identifier of the model to be deleted.
     *
     * @throws NotFoundException If the model with the given ID does not exist in the service.
     * @throws CouldNotDeleteException If the model could not be saved due to an error.
     * @throws LocalizedException For unexpected errors during the delete process.
     *
     * @return array An associative array containing the deleted model's data, HTTP status, and message.
     */
    public function handle(int $id): array
    {
        try {
            // Proceed with deleting the model
            /** @var Model $model */
            $model = $this->service->delete($id);

            // Filter the columns from the model's data
            $filteredEntityData = $model->only($this->getColumns());

            // Prepare the response with the payload, status code, success message, and meta information
            $response = $this->response()
                ->setPayload($filteredEntityData) // Set the payload
                ->setStatusCode(HttpStatusCodes::OK) // Set HTTP status code to 200 (OK)
                ->setMessage(__('%1 deleted deleted successfully.', $this->modelName)); // Set a success message with the model name

            // Return the formatted result as an associative array
            return $response->toArray();
        } catch (NoSuchEntityException|NotFoundException|LocalizedException $e) {
            // Propagate service exceptions as-is
            throw $e;
        } catch (Exception $e) {
            // Wrap unexpected exceptions in a domain-specific exception
            throw LocalizedException::make(
                __('An unexpected error occurred while deleting a %1. %2.', Str::plural($this->modelName), $e->getMessage()),
                $e,
                HttpStatusCodes::INTERNAL_SERVER_ERROR,
            );
        }
    }
}
