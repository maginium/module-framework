<?php

declare(strict_types=1);

use Maginium\Framework\Component\Module;

/*
 * Registers a module with the given namespace and file system path.
 *
 * This code registers the specified module by its namespace and the absolute path
 * to its directory using the `Module::register` method.
 * The module is identified by its fully-qualified namespace (e.g., 'Maginium_Framework')
 * and the path where the module's files are located (in this case, the current directory).
 *
 * Example usage:
 *   Module::register('Maginium_Framework', __DIR__);
 *
 * @param string $namespace The fully-qualified namespace of the module (e.g., 'Maginium_Framework').
 * @param string $path The absolute file system path to the module's directory.
 */
Module::register(
    'Maginium_Framework', // The fully-qualified namespace of the module
    __DIR__, // The absolute file system path to the module's directory
);
