<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud;

use AllowDynamicProperties;
use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;
use Magento\Eav\Model\Entity\Collection\AbstractCollection as EavAbstractCollection;
use Magento\Eav\Model\Entity\Collection\AbstractCollectionFactory as EavAbstractCollectionFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollectionFactory;
use Maginium\Framework\Crud\Abstracts\AbstractRepository;
use Maginium\Framework\Crud\Interfaces\Repositories\RepositoryInterface;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Support\Collection;

/**
 * Abstract base class for custom repositories managing models.
 *
 * This abstract class extends Magento's `ModelInterface` and incorporates additional traits and macros
 * to enhance functionality, such as global scopes, timestamps, UUIDs, and the ability to handle dynamic method calls.
 *
 * The class includes several features:
 * - **Conditionable**: Adds conditional logic for repository queries.
 * - **ForwardsCalls**: Facilitates forwarding method calls to another object.
 * - **Macroable**: Enables dynamic method calls via registered macros, both instance-level and static.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @mixin Collection
 */
#[AllowDynamicProperties] //@phpstan-ignore-line
abstract class Repository extends AbstractRepository implements RepositoryInterface
{
    // Adds conditional logic to the repository.
    use Conditionable;
    // Adding forward calls functionality
    use ForwardsCalls;
    // Adding macroable functionality
    use Macroable {
        // Allows dynamic method calls via registered macros.
        __call as macroCall;

        // Allows static dynamic method calls via registered macros.
        __callStatic as macroCallStatic;
    }

    /**
     * Collection factory to generate collections of models.
     *
     * @var AbstractCollectionFactory|EavAbstractCollectionFactory
     */
    protected $collectionFactory;

    /**
     * Model factory to create new model instances.
     *
     * @var ModelInterfaceFactory
     */
    protected $modelFactory;

    /**
     * Repository constructor.
     *
     * Initializes the repository with a model and collection factory.
     *
     * @param mixed $model The model model factory to create models.
     * @param mixed $collection The model collection factory to create collections.
     */
    public function __construct(
        $model,
        $collection,
    ) {
        // Set the model and collection factories for later use
        $this->modelFactory = $model;
        $this->collectionFactory = $collection;
    }

    /**
     * Get a collection of models in Magento format.
     *
     * This method creates a collection using the provided arguments, which can be filters, sort orders, etc.
     *
     * @param mixed ...$arguments The arguments to be passed to the collection constructor.
     *
     * @return DatabaseCollection|AbstractCollection|EavAbstractCollection The collection of models in Magento's format.
     */
    public function collection(mixed ...$arguments): DatabaseCollection|AbstractCollection|EavAbstractCollection
    {
        // Create and return a new collection based on the provided arguments in Magento's format
        return $this->collectionFactory->create($arguments);
    }

    /**
     * Get a collection of models in an Eloquent-like format.
     *
     * @param mixed ...$arguments The arguments to be passed to the collection constructor.
     *
     * @return Collection The collection of models in Illuminate's format.
     */
    public function newCollection(mixed ...$arguments): Collection
    {
        // Create and return a new collection of models in Illuminate's format
        return Collection::make($this->collectionFactory->create($arguments)->getData());
    }

    /**
     * Create a new model.
     *
     * This method creates a new model using the model factory.
     *
     * @param mixed ...$arguments The arguments to be passed to the model constructor.
     *
     * @return ModelInterface The newly created model.
     */
    public function factory(mixed ...$arguments): ModelInterface
    {
        // Create and return a new model instance
        return $this->modelFactory->create($arguments);
    }
}
