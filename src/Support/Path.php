<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Maginium\Foundation\Enums\FileExtension;

/**
 * Class Path
 * A utility class for handling and manipulating file paths.
 */
class Path
{
    /**
     * Join multiple paths together to create a single path.
     *
     * This function behaves similarly to JavaScript's path.join, ensuring that the resulting path is correctly formatted.
     * If one of the paths is a file with an extension, it directly concatenates it with the previous path.
     *
     * @param string ...$paths One or more paths to join.
     *
     * @return string The joined path.
     */
    public static function join(string ...$paths): string
    {
        // Initialize an empty result path.
        $result = '';

        foreach ($paths as $path) {
            // Check if the path represents a file extension
            $extension = FileExtension::getKey($path);

            if ($extension) {
                // Directly concatenate if a file extension is found.
                $result .= $path;
            } else {
                // Otherwise, append the path using the separator.
                $result = rtrim($result, SP) . SP . Str::ltrim($path, SP);
            }
        }

        return $result;
    }

    /**
     * Normalize a given path by resolving '.' and '..' segments.
     *
     * @param string $path The path to normalize.
     *
     * @return string The normalized path.
     */
    public static function normalize(string $path): string
    {
        // Resolve any .. or . segments in the path
        return realpath($path) ?: $path;
    }

    /**
     * Check if a given path is an absolute path.
     *
     * @param string $path The path to check.
     *
     * @return bool True if the path is absolute, false otherwise.
     */
    public static function isAbsolute(string $path): bool
    {
        // Check if the path is absolute based on the platform
        return $path[0] === SP || preg_match('/^[a-zA-Z]:/', $path);
    }
}
