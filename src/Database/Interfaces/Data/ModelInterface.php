<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces\Data;

use Illuminate\Database\Eloquent\MassAssignmentException;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\AbstractModel;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Foundation\Interfaces\DataObjectInterface;
use Maginium\Framework\Database\Eloquent\Builder;
use Maginium\Framework\Database\Interfaces\HasTimestampsInterface;
use Maginium\Framework\Database\Interfaces\IdentifiableInterface;
use Maginium\Framework\Database\Interfaces\SearchableInterface;
use Maginium\Framework\Dto\DataTransferObject;
use Maginium\Framework\Elasticsearch\Eloquent\Docs\ModelDocs;
use Maginium\Framework\Elasticsearch\Eloquent\Model as ElasticModel;

/**
 * Interface ModelInterface.
 *
 * This interface defines the contract for entities.
 *
 * @mixin ModelDocs
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
     * @throws RuntimeException If the factory class is not found or cannot be resolved.
     *
     * @return AbstractModel|AbstractExtensibleModel|null The created instance of the base model or null if not set.
     */
    public static function toBase(array $args = []): AbstractModel|AbstractExtensibleModel|null;

    /**
     * Retrieve the instance of the base model associated with this class.
     *
     * @throws RuntimeException If the factory class is not found or cannot be resolved.
     *
     * @return string|null The created instance of the base model or null if not set.
     */
    public static function getBaseModel(): ?string;

    /**
     * Begin querying the model.
     *
     * @return Builder
     */
    public static function query(): Builder;

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

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function getKeyName(): string;

    /**
     * Set the connection associated with the model.
     *
     * @param  string|null  $name
     *
     * @return ModelInterface
     */
    public function setConnection($name): self;

    /**
     * Get a new instance of the query builder.
     *
     * @return Builder
     */
    public function newQuery(): Builder;

    /**
     * Set the table (index) associated with the model.
     *
     * This method allows explicitly setting the Elasticsearch index name. In Elasticsearch, the term `index` is used to represent
     * the equivalent of a database table in relational models, so the `table` property is unset to avoid confusion.
     *
     * @param string $index The name of the Elasticsearch index.
     *
     * @return ModelInterface The current instance of the model.
     */
    public function setTable($index): self;

    /**
     * Merge new casts with existing casts on the model.
     *
     * @param  array  $casts
     *
     * @return ModelInterface
     */
    public function mergeCasts($casts): self;

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array  $attributes
     *
     * @throws MassAssignmentException ModelInterfacen
     *
     * @return ModelInterface
     */
    public function fill(array $attributes): self;

    /**
     * Get the table (index) name for the model.
     *
     * This method overrides the standard Eloquent method to return the Elasticsearch index name instead of
     * the traditional database table name.
     *
     * @return string The Elasticsearch index name.
     */
    public function getTable(): string;
}
