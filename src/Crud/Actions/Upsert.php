<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Actions;

use AllowDynamicProperties;
use Maginium\Foundation\Enums\HttpStatusCode;
use Maginium\Foundation\Exceptions\CouldNotSaveException;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Foundation\Exceptions\NotFoundException;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Crud\Interfaces\Services\ServiceInterface;
use Maginium\Framework\Crud\Interfaces\UpsertInterface;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Str;

/**
 * Class Upsert.
 *
 * Abstract service class for managing models.
 * This class handles CRUD operations and responses related to models.
 */
#[AllowDynamicProperties] //@phpstan-ignore-line
class Upsert implements UpsertInterface
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
     * Upsert constructor.
     * Initializes the service and sets the logger and model name.
     *
     * @param mixed $service The Entity service instance.
     */
    public function __construct(
        $service,
    ) {
        // Assign the service instance to class property
        $this->service = $service;

        // Set the class name for logging purposes
        Log::setClassName(static::class);

        // Set model name (can be dynamically set in subclasses)
        $this->modelName = $service->getRepository()->getEntityName();
    }

    /**
     * Handles the upsert operation for an model by its unique identifier.
     *
     * This method processes the input data from the request, performs the upsert operation through the service,
     * and returns a response with the upserted model's data. It ensures that known exceptions are propagated,
     * while unexpected ones are wrapped in a domain-specific exception to provide better error context.
     *
     * @throws NotFoundException If the model with the given ID cannot be found in the service.
     * @throws CouldNotSaveException If there is an issue saving the model to the service.
     * @throws LocalizedException For unexpected errors during the upsert process.
     *
     * @return array An associative array containing the upserted model's data, HTTP status, and a success message.
     */
    public function handle(): array
    {
        try {
            // Retrieve the 'data' array that will be inserted or updated.
            $data = $this->input(static::DATA, []);

            // Retrieve the 'update' array, which contains the fields to update if a matching record is found.
            $update = $this->input(static::UPDATE, []);

            // Retrieve the 'unique_by' array which contains the columns that will be used to identify unique records
            // for performing the upsert operation.
            $uniqueBy = $this->input(static::UNIQUE_BY, []);

            // Perform the upsert operation via the service.
            // The service's upsert method will either update existing records based on the unique identifier
            // or insert new records if no match is found.
            $upsertedModels = $this->service->upsert($data, $uniqueBy, $update);

            // Using Collection to map each upserted model to an array representation
            $models = collect($upsertedModels)->map(function($model) {
                // Convert each model to an array (assuming each model has a toArray method)
                return $this->applyColumnFilter($model->toDataArray());
            });

            // Throw exception if no models were found
            if ($models->isEmpty()) {
                throw LocalizedException::make(
                    __('No %1 records upserted. Please verify the request and try again.', Str::plural($this->modelName)),
                    null,
                    HttpStatusCode::NOT_FOUND,
                );
            }

            // Prepare the response with the payload, status code, success message, and meta information
            $response = $this->response()
                ->setPayload(data: $models->all()) // Set the payload
                ->setStatusCode(HttpStatusCode::OK) // Set HTTP status code to 200 (OK)
                ->setMessage(__('%1 model upserted successfully.', $this->modelName)); // Set a success message with the model name

            // Return the formatted result as an associative array
            return $response->toArray();
        } catch (NotFoundException|CouldNotSaveException|LocalizedException $e) {
            // Propagate service exceptions as-is
            throw $e;
        } catch (Exception $e) {
            // Wrap unexpected exceptions in a domain-specific exception
            throw LocalizedException::make(
                __('An unexpected error occurred while upserting a %1. %2.', Str::plural($this->modelName), $e->getMessage()),
                $e,
                HttpStatusCode::INTERNAL_SERVER_ERROR,
            );
        }
    }
}
