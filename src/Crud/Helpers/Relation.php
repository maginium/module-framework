<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Helpers;

use Maginium\Framework\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionException;

/**
 * Class Relation.
 *
 * A helper class to manage relations in Eloquent models.
 */
class Relation
{
    /**
     * Retrieve all the relationships defined in a given model.
     *
     * This method inspects the methods of the provided model (either a Model instance
     * or the class name as a string) and filters out the ones that return an instance
     * of an Eloquent relationship (i.e., any class within the `Illuminate\Database\Eloquent\Relations` namespace).
     *
     * @param Model|string $model The model class or an instance of the model.
     *
     * @throws ReflectionException If there is an issue with reflection on the given model.
     *
     * @return array A list of relationship method names defined in the model.
     */
    public static function getRelations(Model|string $model): array
    {
        // Create a ReflectionClass instance for the given model (either class name or instance)
        $methods = (new ReflectionClass($model))->getMethods();

        // Use Laravel's Collection methods to filter and map the methods
        return collect($methods)
            // Filter out methods that return a type containing 'Illuminate\Database\Eloquent\Relations'
            ->filter(
                fn($method) => ! empty($method->getReturnType()) &&
                    str_contains(
                        $method->getReturnType(),
                        'Illuminate\Database\Eloquent\Relations',
                    ),
            )
            // Map the filtered methods to their names
            ->map(fn($method) => $method->name)
            // Return the relationship method names as a plain array
            ->values()->all();
    }
}
