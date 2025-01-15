<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Maginium\Framework\Filesystem\Interfaces\FilesystemInterface;
use Maginium\Framework\Support\Facade;

/**
 * Filesystem.
 *
 * Provides a facade for the Filesystem class.
 *
 * @method static bool exists(string $path) Check if a file or directory exists.
 * @method static void delete(string|array $paths) Delete a file or files.
 * @method static bool copy(string $from, string $to) Copy a file.
 * @method static bool move(string $from, string $to) Move a file.
 * @method static string get(string $path) Get the contents of a file.
 * @method static int|bool put(string $path, string $contents, string $mode = 'w+', bool $lock = false) Write the contents to a file.
 * @method static string|false getVisibility(string $path) Get the visibility of a file or directory.
 * @method static void putVisibility(string $path, string $visibility) Set the visibility of a file or directory.
 * @method static bool deleteDirectory(string $directory, bool $preserve = false) Delete a directory and its contents.
 * @method static bool makeDirectory(string $path) Create a directory.
 * @method static bool isDirectory(string $directory) Check if a directory exists.
 * @method static bool isFile(string $file) Check if a file exists.
 * @method static array files(string $directory,string $pattern = '*.php',array $excludedDirectories = [],?array $excludedFiles = []) Get all files in a directory, optionally recursively.
 * @method static array directories(string $directory, bool $recursive = false) Get all directories in a directory, optionally recursively.
 * @method static string path(string $path) Get the path to a file or directory.
 * @method static mixed mimeType(string $path) Get the MIME type of a file.
 * @method static string extension(string $path) Get the file extension.
 * @method static string type(string $path) Get the file type.
 * @method static string basename(string $path) Get the file basename.
 * @method static string dirname(string $path) Get the directory name.
 * @method static bool isReadable(string $path) Check if a file is readable.
 * @method static bool isWritable(string $path) Check if a file is writable.
 * @method static bool isExecutable(string $path) Check if a file is executable.
 * @method static bool chmod(string $path, int $mode) Change the permissions of a file or directory.
 * @method static bool touch(string $path, int $time = null, int $atime = null) Set the access time and modification time of a file.
 * @method static bool moveFile(string $path, string $target) Move a file.
 * @method static bool copyFile(string $path, string $target) Copy a file.
 * @method static bool deleteFile(string $path) Delete a file.
 * @method static string anyname(string $path) Extracts the path and filename without its extension.
 * @method static bool isDirectoryEmpty(string $directory) Determines if the given directory is empty.
 * @method static string sizeToString(int $bytes) Converts a file size in bytes to a human-readable format.
 * @method static string localToPublic(string $path) Converts a local file path to a public file path.
 * @method static bool isLocalPath(string $path, bool $realpath = true) Checks if the specified path is within the application's base path.
 * @method static string fromClass(mixed $className) Finds the file path of a given class.
 * @method static string|bool existsInsensitive(string $path) Checks if a file exists with case insensitivity.
 * @method static string normalizePath(string $path) Returns a normalized version of the supplied path.
 * @method static string nicePath(string $path) Removes the base path from a local path and returns a user-friendly relative path.
 * @method static string symbolizePath(string $path, mixed $default = false) Converts a path using path symbols, or returns the original path if no symbol is used.
 * @method static bool|string isPathSymbol(string $path) Checks if a path uses a known path symbol.
 * @method static string getSafe(string $path, float $limitKbs = 1) Reads the first portion of file contents up to a specified limit.
 * @method static void chmodRecursive(string $path, int $fileMask = null, int $directoryMask = null) Recursively modifies file and folder permissions.
 * @method static string|null getFilePermissions() Returns the default file permission mask.
 * @method static string|null getFolderPermissions() Returns the default folder permission mask.
 * @method static bool fileNameMatch(string|array $fileName, string $pattern) Matches a filename against a pattern using wildcard characters.
 * @method static int lastModifiedRecursive(string $path) Recursively finds the most recently modified file in a directory.
 * @method static string|null searchDirectory(string $file, string $directory, string $rootDir = '') Searches for a file within a directory and returns its relative path.
 * @method static string|false realPath(string $path) Converts a relative path to an absolute path.
 * @method static SplFileInfo[] scanDirectory(string $directory, string $name = '*.php', array $excludedDirectories = [], ?array $excludedFiles = []) Retrieves an array of files from a directory with optional filtering and sorting.
 * @method static ReadInterface getDirectoryRead(string $directoryCode, string $driverCode = DriverPool::FILE) Create an instance of directory with read permissions.
 * @method static ReadInterface getDirectoryReadByPath(string $path, string $driverCode = DriverPool::FILE) Create an instance of directory with read permissions by path.
 * @method static WriteInterface getDirectoryWrite(string $directoryCode, string $driverCode = DriverPool::FILE) Create an instance of directory with write permissions.
 * @method static string getUri(string $code) Retrieve uri for given code.
 * @method static void append($path, $data) Append data to a file.
 *
 * @see FilesystemInterface
 */
class Filesystem extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return FilesystemInterface::class;
    }
}
