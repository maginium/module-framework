<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Schema;

use Closure;
use Maginium\Framework\Support\Facades\Container;
use Override;

/**
 * AttributeBuilder is responsible for creating new attributes on the schema.
 * It interacts with the schema builder to set up the required blueprint
 * and applies any additional configuration via a callback.
 */
class AttributeBuilder extends Builder
{
    /**
     * Create a new command set with a Closure.
     *
     * This method is responsible for creating a new blueprint with the
     * given attribute and callback. It injects the necessary context
     * to the blueprint for further operations.
     *
     * @param  string  $attribute The name of the attribute.
     * @param  Closure|null  $callback The callback to apply additional configurations.
     *
     * @return AttributeBlueprint Returns the created blueprint instance.
     */
    #[Override]
    protected function createBlueprint($attribute, ?Closure $callback = null): AttributeBlueprint
    {
        // If a resolver is set, use it to create the blueprint.
        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $attribute, $callback);
        }

        // Otherwise, create the blueprint using the default factory.
        return Container::make(AttributeBlueprint::class, compact('attribute', 'callback'));
    }
}
