<?php

declare(strict_types=1);

namespace Maginium\Framework\Pagination\Interfaces;

use Illuminate\Contracts\Pagination\Paginator;

/**
 * Interface Paginator.
 *
 * Defines constants and methods for handling pagination within the application.
 * Provides structure for paginated data, including navigation properties
 * such as current page, next page, and other pagination metadata.
 *
 * Implementers of this interface will use these constants to standardize
 * array keys, ensuring consistent access to pagination attributes across
 * different parts of the application.
 */
interface PaginatorInterface extends Paginator
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
    public static function make(...$arguments): self;
}
