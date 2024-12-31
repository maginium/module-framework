<?php

declare(strict_types=1);

namespace Maginium\Framework\Actions;

use Maginium\Framework\Actions\Concerns\AsAction;

/**
 * Class Action.
 *
 * This is a base class representing an action within the framework.
 * It utilizes the `AsAction` trait to inherit common behavior and methods
 * that are typically used by actions. The `AsAction` trait may provide
 * functionality for working with actions, such as making them invokable,
 * handling command signatures, and other action-specific methods.
 */
class Action
{
    // Use the AsAction trait to add common action-related methods to the class.
    // Using the AsAction trait to add common action functionality
    use AsAction;
}
