<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Eloquent;

use AllowDynamicProperties;
use Maginium\Foundation\Exceptions\BadMethodCallException;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Crud\Interfaces\Services\ServiceInterface;
use Maginium\Framework\Crud\Service as BaseService;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Reflection;

/**
 * Class Service.
 *
 * A generic service class for managing CRUD operations for models.
 * This class interacts with the repository layer to perform operations such as
 * retrieving, saving, deleting, and creating models.
 */
#[AllowDynamicProperties]
class Service extends BaseService implements ServiceInterface
{
    /**
     * Handle dynamic calls to the instance, including static ones, by delegating the call to the model.
     *
     * @param string $method The name of the method being called.
     * @param array $parameters The arguments passed to the method.
     *
     * @throws RuntimeException If the method does not exist on the model.
     *
     * @return mixed The result of the method call on the model.
     */
    private function handleDynamicCall($method, $parameters)
    {
        // Check if the method exists on the current class itself
        if (Reflection::methodExists(static::class, $method)) {
            return call_user_func([static::class, $method], ...$parameters);
        }

        // Delegate the call to the model's method if it exists
        $model = $this->getRepository()->getModel();

        if (method_exists($model, $method)) {
            return $model->{$method}(...$parameters);
        }

        // Throw an exception if the method doesn't exist on the model
        throw BadMethodCallException::make("Method {$method} not found on the model.");
    }

    /**
     * Handle dynamic method calls on an instance of the class.
     * This method allows calling dynamic methods on an instance, including scope methods
     * (e.g., scopeActive) and standard methods. It checks if a scope method exists and, if so,
     * calls the scope method. Otherwise, it delegates to the model's method.
     *
     * @param string $method The method name being called.
     * @param array  $parameters The parameters to pass to the method.
     *
     * @return mixed The result of the dynamic method call.
     */
    public function __call($method, $parameters): mixed
    {
        return $this->handleDynamicCall($method, $parameters);
    }

    /**
     * Handle dynamic static method calls.
     * This method allows for calling methods on this class statically, and it passes parameters to the method
     * being called dynamically. It instantiates the class and then calls the specified method with the given parameters.
     *
     * @param string $method The method name being called.
     * @param array  $parameters The parameters to pass to the method.
     *
     * @return mixed The result of the dynamic method call.
     */
    public static function __callStatic($method, $parameters): mixed
    {
        // Static method calls require a different context; use `static::` to call the instance method
        $instance = Container::resolve(static::class);

        return $instance->handleDynamicCall($method, $parameters);
    }
}
