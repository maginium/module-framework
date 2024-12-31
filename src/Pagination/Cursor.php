<?php

declare(strict_types=1);

namespace Maginium\Framework\Pagination;

use Illuminate\Pagination\Cursor as BaseCursor;
use Maginium\Framework\Pagination\Interfaces\CursorInterface;
use Maginium\Framework\Support\Facades\Container;

/**
 * Enhanced Cursor class for managing pagination.
 *
 * This class extends the base Cursor from Laravel and implements
 * the CursorInterface to ensure compatibility with custom pagination
 * logic. It also integrates with the framework's container to resolve dependencies.
 */
class Cursor extends BaseCursor implements CursorInterface
{
    /**
     * Create and return a new instance of the Cursor.
     *
     * This method uses the framework's container to resolve an instance of the cursor.
     * It allows passing additional arguments that can be used during the instantiation
     * process, enabling custom configurations or logic.
     *
     * @param  mixed  ...$arguments  Additional arguments to pass to the cursor's constructor.
     *
     * @return CursorInterface A new instance of the cursor.
     */
    public static function make(...$arguments): CursorInterface
    {
        // Resolve and return a new cursor instance using the container with the provided arguments
        return Container::make(CursorInterface::class, ...$arguments);
    }
}
