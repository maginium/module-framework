<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces\Data;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\AbstractModel;
use Maginium\Foundation\Interfaces\DataObjectInterface;
use Maginium\Framework\Database\Interfaces\HasTimestampsInterface;
use Maginium\Framework\Database\Interfaces\IdentifiableInterface;
use Maginium\Framework\Database\Interfaces\SearchableInterface;
use Maginium\Framework\Dto\DataTransferObject;
use Maginium\Framework\Elasticsearch\Eloquent\Model as ElasticModel;

/**
 * Interface ModelInterface.
 *
 * This interface defines the contract for entities.
 */
interface ModelInterface extends DataObjectInterface, HasTimestampsInterface, IdentifiableInterface, SearchableInterface
{
    /**
     * Create a new instance of the model and optionally populate it with the provided data.
     *
     * This is a factory-style method that allows you to instantiate the model and populate it
     * with data. It returns the newly created instance, which can then be used as needed.
     *
     * @param array $attributes An optional array of attributes to initialize the model with.
     *                          These attributes can be set on the instance upon creation.
     *
     * @return ModelInterface The newly created instance of the model, populated with the provided attributes.
     */
    public static function make(array $attributes = []): self;

    /**
     * Retrieve the instance of the base model associated with this class.
     *
     * This method resolves the factory class associated with the base model,
     * invokes its `create` method, and passes the given arguments.
     *
     * @param array $args Arguments to pass to the factory's create method.
     *
     * @return AbstractModel|AbstractExtensibleModel|null The created instance of the base model or null if not set.
     */
    public function getBaseModel(array $args = []): mixed;

    /**
     * Retrieve the class name of the Elastic model associated with this instance.
     *
     * @return ElasticModel|null The class name of the Elastic model or null if not set.
     */
    public function getElasticModel(): ?ElasticModel;

    /**
     * Retrieve the class name of the Data Transfer Object (DTO) associated with this instance.
     *
     * @return DataTransferObject|null The class name of the DTO or null if not set.
     */
    public function getDtoClass(): ?DataTransferObject;

    /**
     * Get the event object type for the model's events.
     *
     * This method returns the event object type associated with the model's events.
     * The event object is used when dispatching or handling model events.
     *
     * @return string|null The event object type.
     */
    public function getEventObject(): ?string;

    /**
     * Returns eventPrefix.
     *
     * This method simply returns the event prefix for this model, which is typically
     * used to differentiate events for different types of models or entities.
     *
     * @return string|null The event prefix for the model.
     */
    public function getEventPrefix(): ?string;

    /**
     * Get the instance as an array.
     *
     * This method converts the model's instance into an array representation, optionally including specific keys.
     * It allows easy manipulation or output of model data as an array.
     *
     * @param array $keys Optional array of keys to include in the resulting array.
     *                    Defaults to all columns (`*`).
     *
     * @return array The model's data as an array.
     */
    public function toDataArray(array $keys = ['*']): array;
}
