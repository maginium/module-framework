<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Actions;

use AllowDynamicProperties;
use Maginium\Foundation\Enums\HttpStatusCode;
use Maginium\Foundation\Exceptions\CouldNotDeleteException;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Foundation\Exceptions\NotFoundException;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Crud\Interfaces\DeleteInterface;
use Maginium\Framework\Crud\Interfaces\Services\ServiceInterface;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
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
     * @var ServiceInterface
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
        $this->modelName = $service->getRepository()->getEntityName();
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
            $model = $this->service->deleteById($id);

            // Filter the columns from the deleted model's data
            $filteredEntityData = $this->applyColumnFilter($model->toDataArray());

            // Prepare the response with the payload, status code, success message, and meta information
            $response = $this->response()
                ->setPayload($filteredEntityData) // Set the payload
                ->setStatusCode(HttpStatusCode::OK) // Set HTTP status code to 200 (OK)
                ->setMessage(__('%1 deleted deleted successfully.', $this->modelName)); // Set a success message with the model name

            // Return the formatted result as an associative array
            return $response->toArray();
        } catch (NotFoundException|CouldNotDeleteException|LocalizedException $e) {
            // Propagate service exceptions as-is
            throw $e;
        } catch (Exception $e) {
            // Wrap unexpected exceptions in a domain-specific exception
            throw LocalizedException::make(
                __('An unexpected error occurred while deleting a %1. %2.', Str::plural($this->modelName), $e->getMessage()),
                $e,
                HttpStatusCode::INTERNAL_SERVER_ERROR,
            );
        }
    }
}
