<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Database\EntityManager as BaseEntityManager;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the EntityManager service.
 *
 * This facade provides easy access to the underlying EntityManager service,
 * allowing methods for loading, saving, checking, and deleting models.
 *
 * @method static self for(string|ModelInterface|AbstractModel $model) Specify the model to be queried.
 * @method static self by(string $identifierField) Set the identifier field for the model.
 * @method static self where(string $field, mixed $value, string $operator = '=') Add a condition to the query.
 * @method static self resetConditions() Reset all conditions for the query.
 * @method static object load(object $model, string $identifier, array $arguments = []) Load an model using its identifier and optional arguments.
 * @method static object save(object $model, array $arguments = []) Save an model with optional arguments.
 * @method static bool has(object $model) Check if an model exists in the EntityManager.
 * @method static bool delete(object $model, array $arguments = []) Delete an model with optional arguments.
 * @method static self setIdentifierField(string $identifierField) Sets the identifier field for the model, returning the facade instance for method chaining.
 * @method static self by(string $identifierField) Sets the identifier field for the model, returning the facade instance for method chaining.
 * @method static object execute() Execute the query and load the model. This method combines all set conditions, the current model, and other parameters to execute the query and retrieve the model data.
 *
 * @see MagentoEntityManager
 */
class EntityManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return BaseEntityManager::class;
    }
}
