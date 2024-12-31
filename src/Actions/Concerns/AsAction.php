<?php

declare(strict_types=1);

namespace Maginium\Framework\Actions\Concerns;

/**
 * Trait AsAction.
 *
 * The `AsAction` trait combines the functionality of the `AsObject` and `AsCommand` traits.
 * By using `AsAction`, any class gains access to the methods and properties defined in both traits.
 *
 * The `AsObject` trait provides functionality for managing object behavior, while the `AsCommand`
 * trait defines properties and methods related to command handling, such as command signature,
 * name, description, and help.
 *
 * This trait allows a class to be treated both as an object and as a command, streamlining the
 * process of defining actions that can be executed as commands, while maintaining object-like behavior.
 *
 * @see AsObject
 * @see AsCommand
 */
trait AsAction
{
    // Include functionality from the AsController trait, which helps in managing controller-based behavior.
    use AsController;
    // Include functionality from the AsObject trait, which helps in managing object-based behavior.
    use AsObject;
}
