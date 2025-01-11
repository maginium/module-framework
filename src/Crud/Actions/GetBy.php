<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Actions;

use AllowDynamicProperties;
use Maginium\Foundation\Enums\HttpStatusCode;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Foundation\Exceptions\NotFoundException;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Crud\Constants\Criteria;
use Maginium\Framework\Crud\Interfaces\GetByInterface;
use Maginium\Framework\Crud\Interfaces\Services\ServiceInterface;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Elasticsearch\Eloquent\Model;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Validator;

/**
 * Class GetBy.
 *
 * Abstract service class for managing models.
 * This class handles CRUD operations and responses related to models.
 */
#[AllowDynamicProperties] //@phpstan-ignore-line
class GetBy implements GetByInterface
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
     *  The class name of the Elastic model.
     *
     * @var class-string<Model>
     */
    protected string $elasticModel;

    /**
     * GetBy constructor.
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

        // Fetch the Elasticsearch model associated with the entity.
        $this->elasticModel = $service->getRepository()->factory()->getElasticModel();
    }

    /**
     * Retrieve an model by a given attribute.
     *
     * @param mixed $value The attribute of the model to retrieve.
     * @param string $attribute The key to use for the attribute (default: 'id').
     *
     * @throws NotFoundException If the model with the given attribute does not exist in the service.
     * @throws LocalizedException If an error occurs during the retrieval process.
     *
     * @return array The retrieved model data.
     */
    public function handle($value, $attribute = Criteria::DEFAULT_KEY): array
    {
        try {
            // Use the service to get the model by attribute
            $model = $this->elasticModel::where($attribute, $value);

            if (Validator::isEmpty($model)) {
                // No results found, set custom status code and message, then throw exception
                throw LocalizedException::make(
                    __('No %1 found with the given %2: "%3". Please verify the provided attribute.', $this->modelName, $attribute, $value),
                    null,
                    HttpStatusCode::NOT_FOUND,
                );
            }

            // Filter the columns from the deleted model's data
            $filteredEntityData = $model->only($this->getColumns());

            // Prepare the response with the payload, status code, success message, and meta information
            $response = $this->response()
                ->setPayload($filteredEntityData) // Set the payload
                ->setStatusCode(HttpStatusCode::OK) // Set HTTP status code to 200 (OK)
                ->setMessage(__('Successfully retrieved %1 data by %2.', $this->modelName, $attribute)); // Set a success message with the model name

            // Return the formatted result as an associative array
            return $response->toArray();
        } catch (NotFoundException|LocalizedException $e) {
            // Propagate service exceptions as-is
            throw $e;
        } catch (Exception $e) {
            // Catch any general exceptions and rethrow a localized exception with a generic error message
            throw LocalizedException::make(
                __('An error occurred while retrieving the %1 by %2: "%3". Please try again later.', $this->modelName, $attribute, $value),
                $e,
                HttpStatusCode::INTERNAL_SERVER_ERROR,
            );
        }
    }
}