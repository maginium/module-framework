<?php

declare(strict_types=1);

namespace Maginium\Framework\Pagination;

use Illuminate\Pagination\Paginator as BasePaginator;
use Maginium\Framework\Pagination\Interfaces\PaginatorInterface;
use Maginium\Framework\Support\Facades\Container;

/**
 * Enhanced Paginator class for managing pagination.
 *
 * This class extends the base Paginator from Laravel and implements
 * the PaginatorInterface to ensure compatibility with custom pagination
 * logic. It also integrates with the framework's container to resolve dependencies.
 */
class Paginator extends BasePaginator implements PaginatorInterface
{
    /**
     * Create and return a new instance of the Paginator.
     *
     * This method uses the framework's container to resolve an instance of the paginator.
     * It allows passing additional arguments that can be used during the instantiation
     * process, enabling custom configurations or logic.
     *
     * @param  mixed  ...$arguments  Additional arguments to pass to the paginator's constructor.
     *
     * @return PaginatorInterface A new instance of the paginator.
     */
    public static function make(...$arguments): PaginatorInterface
    {
        // Resolve and return a new paginator instance using the container with the provided arguments
        return Container::make(PaginatorInterface::class, ...$arguments);
    }
}
