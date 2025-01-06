<?php

declare(strict_types=1);

namespace Maginium\Framework\Filesystem\Interfaces;

use Illuminate\Contracts\Filesystem\Filesystem as FilesystemInterface;

/**
 * Interface FactoryInterface.
 *
 * Defines the contract for managing filesystem implementations across different storage systems.
 * This includes retrieving specific filesystem instances, such as local, S3, or any custom disk configuration.
 * The interface allows flexible management of file storage and retrieval across various environments.
 */
interface FactoryInterface
{
    /**
     * Get a filesystem implementation by name.
     *
     * This method retrieves a filesystem instance based on the provided disk name.
     * The disk could represent a specific storage solution (e.g., local storage, S3, or a custom configuration).
     *
     * @param  string|null  $name Optional name of the filesystem disk to retrieve.
     *
     * @return FilesystemInterface The filesystem instance corresponding to the provided name or default.
     */
    public function disk(?string $name = null): FilesystemInterface;
}
