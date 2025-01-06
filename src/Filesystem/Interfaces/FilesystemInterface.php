<?php

declare(strict_types=1);

namespace Maginium\Framework\Filesystem\Interfaces;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\DriverPool;
use Maginium\Foundation\Exceptions\RuntimeException;
use SplFileInfo;

/**
 * Class FilesystemManager.
 *
 * This class manages the underlying filesystem connections and provides a flexible API
 * for interacting with the filesystem. It includes features for:
 * - Setting file and folder permissions.
 * - Managing filesystem connections.
 * - Applying custom macros through the Macroable trait.
 *
 * @property string|null $filePermissions  The default file permission mask in octal format (e.g., "0755").
 * @property string|null $folderPermissions  The default folder permission mask in octal format (e.g., "0755").
 * @property array $pathSymbols  A mapping of path symbols to their prefixes.
 * @property array|null $symlinkRootCache  Cache of symlinked root directories.
 */
interface FilesystemInterface
{
    /**
     * Get the contents of a file.
     *
     * This method retrieves the contents of a file located at the specified path.
     *
     * @param  string  $path  The path to the file.
     * @param  bool  $lock  If true, a shared lock will be used while reading the file.
     *
     * @throws FileNotFoundException  If the file does not exist.
     *
     * @return string  Returns the contents of the file as a string.
     */
    public function get($path, $lock = false);

    /**
     * Delete the file or directory at the specified path.
     *
     * This method deletes the file or directory located at the given path.
     *
     * @param  string  $path  The path to the file or directory.
     *
     * @return bool  Returns true if the file or directory was deleted successfully, false otherwise.
     */
    public function delete($path);

    /**
     * Determine if a file or directory exists.
     *
     * This method checks if the file or directory at the specified path exists.
     *
     * @param  string  $path  The path to the file or directory.
     *
     * @return bool  Returns true if the file or directory exists, false otherwise.
     */
    public function exists($path);

    /**
     * Determine if a file or directory is missing.
     *
     * This method checks if the file or directory at the specified path does not exist.
     *
     * @param  string  $path  The path to the file or directory.
     *
     * @return bool  Returns true if the file or directory is missing, false otherwise.
     */
    public function missing($path);

    /**
     * Get contents of a file with shared access.
     *
     * This method retrieves the contents of a file while allowing other processes to read from it.
     *
     * @param  string  $path  The path to the file.
     *
     * @return string  Returns the contents of the file as a string.
     */
    public function sharedGet($path);

    /**
     * Get the returned value of a file.
     *
     * This method requires a file and returns the value it produces.
     *
     * @param  string  $path  The path to the file.
     * @param  array  $data  An optional array of data to extract into the file's scope.
     *
     * @throws FileNotFoundException  If the file does not exist.
     *
     * @return mixed  Returns the value produced by the required file.
     */
    public function getRequire($path, array $data = []);

    /**
     * Require the given file once.
     *
     * This method requires a file, ensuring it is included only once during execution.
     *
     * @param  string  $path  The path to the file.
     * @param  array  $data  An optional array of data to extract into the file's scope.
     *
     * @throws FileNotFoundException  If the file does not exist.
     *
     * @return mixed  Returns the value produced by the required file.
     */
    public function requireOnce($path, array $data = []);

    /**
     * Get the contents of a file one line at a time.
     *
     * This method provides a generator that yields the lines of the file one by one.
     *
     * @param  string  $path  The path to the file.
     *
     * @throws FileNotFoundException  If the file does not exist.
     *
     * @return LazyCollection  A collection of lines from the file.
     */
    public function lines($path);

    /**
     * Get the hash of the file at the given path.
     *
     * This method calculates the hash of a file using the specified algorithm.
     *
     * @param  string  $path  The path to the file.
     * @param  string  $algorithm  The hashing algorithm to use (default is 'md5').
     *
     * @return string  Returns the hash of the file as a string.
     */
    public function hash($path, $algorithm = 'md5');

    /**
     * Write the contents of a file.
     *
     * This method writes data to a specified file path.
     *
     * @param  string  $path  The path to the file.
     * @param  string  $contents  The data to write to the file.
     * @param  string  $mode  The file mode for writing.
     * @param  bool  $lock  If true, an exclusive lock will be used while writing.
     *
     * @return int|bool  Returns the number of bytes written, or false on failure.
     */
    public function put(string $path, string $contents, string $mode = 'w+', bool $lock = false);

    /**
     * Write the contents of a file, replacing it atomically if it already exists.
     *
     * This method creates a temporary file and replaces the original file to prevent data loss.
     *
     * @param  string  $path  The path to the file.
     * @param  string  $content  The data to write to the file.
     * @param  int|null  $mode  Optional file mode for the temporary file.
     *
     * @return void
     */
    public function replace($path, $content, $mode = null): void;

    /**
     * Replace a given string within a given file.
     *
     * This method searches for a string or array of strings in the file and replaces them with new values.
     *
     * @param  array|string  $search  The string or array of strings to search for.
     * @param  array|string  $replace  The string or array of replacements.
     * @param  string  $path  The path to the file.
     *
     * @return void
     */
    public function replaceInFile($search, $replace, $path): void;

    /**
     * Prepend to a file.
     *
     * This method adds data to the beginning of a file.
     *
     * @param  string  $path  The path to the file.
     * @param  string  $data  The data to prepend to the file.
     *
     * @return int  Returns the number of bytes written to the file.
     */
    public function prepend($path, $data);

    /**
     * Append data to a file.
     *
     * This method adds data to the end of a file.
     *
     * @param  string  $path  The path to the file.
     * @param  string  $data  The data to append to the file.
     *
     * @return int  Returns the number of bytes written to the file.
     */
    public function append($path, $data);

    /**
     * Copy a file from one location to another.
     *
     * This method copies a file from the source path to the destination path.
     *
     * @param  string  $from  The path to the source file.
     * @param  string  $to  The path to the destination file.
     *
     * @return bool  Returns true on success, false on failure.
     */
    public function copy($from, $to);

    /**
     * Move a file from one location to another.
     *
     * This method moves a file from the source path to the destination path.
     *
     * @param  string  $from  The path to the source file.
     * @param  string  $to  The path to the destination file.
     *
     * @return bool  Returns true on success, false on failure.
     */
    public function move($from, $to);

    /**
     * Create a symlink to the target file or directory. On Windows, a hard link is created if the target is a file.
     *
     * @param  string  $target  The target file or directory to link to.
     * @param  string  $link    The path where the symlink will be created.
     *
     * @return bool  Returns true on success, false on failure.
     */
    public function link($target, $link): bool;

    /**
     * Create a relative symlink to the target file or directory.
     *
     * @param  string  $target  The target file or directory to link to.
     * @param  string  $link    The path where the symlink will be created.
     *
     * @throws RuntimeException  Throws an exception if the Symfony Filesystem class is not available.
     *
     * @return void
     */
    public function relativeLink($target, $link): void;

    /**
     * Extract the file name from a file path.
     *
     * @param  string  $path  The file path to extract the name from.
     *
     * @return string  The name of the file without its extension.
     */
    public function name($path);

    /**
     * Extract the trailing name component from a file path.
     *
     * @param  string  $path  The file path to extract the basename from.
     *
     * @return string  The basename of the file path.
     */
    public function basename($path);

    /**
     * Extract the parent directory from a file path.
     *
     * @param  string  $path  The file path to extract the directory from.
     *
     * @return string  The directory path of the file.
     */
    public function dirname($path);

    /**
     * Extract the file extension from a file path.
     *
     * @param  string  $path  The file path to extract the extension from.
     *
     * @return string  The file extension.
     */
    public function extension($path);

    /**
     * Guess the file extension from the mime-type of a given file.
     *
     * @param  string  $path  The file path to guess the extension from.
     *
     * @throws RuntimeException  Throws an exception if the Symfony Mime class is not available.
     *
     * @return string|null  The guessed file extension or null if not found.
     */
    public function guessExtension($path);

    /**
     * Get the file type of a given file.
     *
     * @param  string  $path  The file path to check the type.
     *
     * @return string  The type of the file.
     */
    public function type($path);

    /**
     * Get the mime-type of a given file.
     *
     * @param  string  $path  The file path to get the mime type.
     *
     * @return string|false  The mime type of the file or false on failure.
     */
    public function mimeType($path);

    /**
     * Get the file size of a given file.
     *
     * @param  string  $path  The file path to get the size.
     *
     * @return int  The size of the file in bytes.
     */
    public function size($path);

    /**
     * Get the file's last modification time.
     *
     * @param  string  $path  The file path to check the last modification time.
     *
     * @return int  The last modification time as a Unix timestamp.
     */
    public function lastModified($path);

    /**
     * Determine if the given path is a directory.
     *
     * @param  string  $directory The path to check if it is a directory.
     *
     * @return bool Returns true if the path is a directory, false otherwise.
     */
    public function isDirectory($directory);

    /**
     * Determine if the given path is a directory that does not contain any other files or directories.
     *
     * @param  string  $directory The path to check for emptiness.
     * @param  bool  $ignoreDotFiles Whether to ignore hidden files (starting with a dot).
     *
     * @return bool Returns true if the directory is empty, false otherwise.
     */
    public function isEmptyDirectory($directory, $ignoreDotFiles = false);

    /**
     * Determine if the given path is readable.
     *
     * @param  string  $path The path to check for readability.
     *
     * @return bool Returns true if the path is readable, false otherwise.
     */
    public function isReadable($path);

    /**
     * Determine if the given path is writable.
     *
     * @param  string  $path The path to check for writability.
     *
     * @return bool Returns true if the path is writable, false otherwise.
     */
    public function isWritable($path);

    /**
     * Determine if two files are the same by comparing their hashes.
     *
     * @param  string  $firstFile The first file path for comparison.
     * @param  string  $secondFile The second file path for comparison.
     *
     * @return bool Returns true if the files have the same hash, false otherwise.
     */
    public function hasSameHash($firstFile, $secondFile);

    /**
     * Determine if the given path is a file.
     *
     * @param  string  $file The path to check if it is a file.
     *
     * @return bool Returns true if the path is a file, false otherwise.
     */
    public function isFile($file);

    /**
     * Find path names matching a given pattern.
     *
     * @param  string  $pattern The pattern to match against file names.
     * @param  int  $flags Flags to modify the behavior of the glob function.
     *
     * @return array Returns an array of file paths that match the pattern.
     */
    public function glob($pattern, $flags = 0);

    /**
     * Get an array of all files in a directory.
     *
     * @param  string  $directory The path to the directory.
     * @param  bool  $hidden Whether to include hidden files in the result.
     *
     * @return SplFileInfo[] Returns an array of file info objects.
     */
    public function files(string $directory, bool $hidden = false): array;

    /**
     * Get all of the files from the given directory (recursive).
     *
     * @param  string  $directory The path to the directory.
     * @param  bool  $hidden Whether to include hidden files in the result.
     *
     * @return SplFileInfo[] Returns an array of file info objects.
     */
    public function allFiles(string $directory, bool $hidden = false): array;

    /**
     * Get all of the directories within a given directory.
     *
     * @param  string  $directory The path to the directory.
     *
     * @return array Returns an array of directory paths.
     */
    public function directories(string $directory);

    /**
     * Ensure a directory exists.
     *
     * @param  string  $path The path to the directory.
     *
     * @return void
     */
    public function ensureDirectoryExists($path): void;

    /**
     * Create a directory.
     *
     * @param  string  $path The path to create the directory.
     *
     * @return bool Returns true if the directory was created successfully, false otherwise.
     */
    public function makeDirectory($path);

    /**
     * Move a directory.
     *
     * @param  string  $from The source directory path.
     * @param  string  $to The destination directory path.
     * @param  bool  $overwrite Whether to overwrite the destination if it exists.
     *
     * @return bool Returns true if the directory was moved successfully, false otherwise.
     */
    public function moveDirectory($from, $to, $overwrite = false);

    /**
     * Copy a directory from one location to another.
     *
     * @param  string  $directory The source directory path.
     * @param  string  $destination The destination directory path.
     * @param  int|null  $options Options for the FilesystemIterator.
     *
     * @return bool Returns true if the directory was copied successfully, false otherwise.
     */
    public function copyDirectory($directory, $destination, $options = null);

    /**
     * Recursively delete a directory and all of its contents.
     *
     * @param  string  $directory The path to the directory to delete.
     * @param  bool  $preserve Whether to preserve the parent directory.
     *
     * @return bool Returns true if the directory was deleted successfully, false otherwise.
     */
    public function deleteDirectory($directory, $preserve = false);

    /**
     * Remove all of the directories within a given directory.
     *
     * @param  string  $directory The path to the parent directory.
     *
     * @return bool True if directories were deleted, false otherwise.
     */
    public function deleteDirectories($directory);

    /**
     * Empty the specified directory of all files and folders.
     *
     * @param  string  $directory The path to the directory to clean.
     *
     * @return bool True if the directory was cleaned, false otherwise.
     */
    public function cleanDirectory($directory);

    /**
     * Extracts the path and filename without its extension.
     *
     * @param string $path The full file path including the filename and extension.
     *
     * @return string The path and filename without the extension.
     */
    public function anyname(string $path): string;

    /**
     * Determines if the given directory is empty.
     *
     * @param string $directory The path to the directory to check.
     *
     * @return bool True if the directory is empty, false otherwise.
     */
    public function isDirectoryEmpty(string $directory): bool;

    /**
     * Converts a file size in bytes to a human-readable format.
     *
     * @param int $bytes The file size in bytes.
     *
     * @return string The human-readable file size.
     */
    public function sizeToString(int $bytes): string;

    /**
     * Converts a local file path to a public file path.
     *
     * @param string $path The absolute local path.
     *
     * @return string The public file path.
     */
    public function localToPublic(string $path): string;

    /**
     * Checks if the specified path is within the application's base path.
     *
     * @param string $path The path to check.
     * @param bool $realpath Whether to resolve the path before checking (default: true).
     *
     * @return bool True if the path is local, false otherwise.
     */
    public function isLocalPath(string $path, bool $realpath = true): bool;

    /**
     * Finds the file path of a given class.
     *
     * @param mixed $className Class name or object.
     *
     * @return string The file path of the class.
     */
    public function fromClass($className): string;

    /**
     * Checks if a file exists with case insensitivity.
     *
     * @param string $path The file path.
     *
     * @return string|bool The case-insensitive path if exists, false otherwise.
     */
    public function existsInsensitive(string $path);

    /**
     * Returns a normalized version of the supplied path.
     *
     * @param string $path The path to normalize.
     *
     * @return string The normalized path with consistent directory separators.
     */
    public function normalizePath(string $path): string;

    /**
     * Removes the base path from a local path and returns a user-friendly relative path.
     *
     * @param string $path The local path to convert.
     *
     * @return string The relative, user-friendly path.
     */
    public function nicePath(string $path): string;

    /**
     * Converts a path using path symbols, or returns the original path if no symbol is used.
     *
     * @param string $path The path to convert.
     * @param mixed $default Default value to return if no symbol is used (default: false).
     *
     * @return string The converted path or the default value.
     */
    public function symbolizePath(string $path, $default = false): string;

    /**
     * Checks if a path uses a known symbol and returns the symbol if found.
     *
     * @param string $path The path to check.
     *
     * @return string|bool The symbol if found, false otherwise.
     */
    public function isPathSymbol(string $path);

    /**
     * Reads the first portion of file contents up to a specified limit.
     *
     * This method opens a file at the specified path and reads its contents
     * up to a defined limit, given in kilobytes. The default limit is 1 KB.
     *
     * @param  string $path The file path to read from.
     * @param  float  $limitKbs The size limit in kilobytes (default: 1).
     *
     * @return string The portion of file contents read, or an empty string if the file cannot be opened.
     */
    public function getSafe(string $path, float $limitKbs = 1);

    /**
     * Recursively modifies file and folder permissions.
     *
     * This method sets the permissions for files and directories at a given path,
     * applying specified masks. If no masks are provided, default permissions are used.
     *
     * @param  string  $path The path of the file or directory.
     * @param  int|null $fileMask Permission mask for files (default: null).
     * @param  int|null $directoryMask Permission mask for directories (default: null).
     *
     * @return void
     */
    public function chmodRecursive($path, $fileMask = null, $directoryMask = null): void;

    /**
     * Returns the default file permission mask.
     *
     * This method retrieves the default file permission mask as an octal string.
     * If no permission mask is set, it returns null.
     *
     * @return int|null Permission mask as an octal integer (e.g., 0755) or null if not set.
     */
    public function getFilePermissions();

    /**
     * Returns the default folder permission mask.
     *
     * This method retrieves the default folder permission mask as an octal string.
     * If no permission mask is set, it returns null.
     *
     * @return int|null Permission mask as an octal integer (e.g., 0755) or null if not set.
     */
    public function getFolderPermissions();

    /**
     * Matches a filename against a pattern using wildcard characters.
     *
     * This method checks if a given filename matches a specified pattern that may
     * include wildcard characters '*' (any number of characters) and '?' (single character).
     *
     * @param  string|array $fileName The filename or an array of filenames to match.
     * @param  string       $pattern The pattern to match against (supports '*' and '?' wildcards).
     *
     * @return bool True if the filename matches the pattern, false otherwise.
     */
    public function fileNameMatch($fileName, $pattern);

    /**
     * Recursively finds the most recently modified file in a directory.
     *
     * This method traverses a directory and its subdirectories to find the
     * timestamp of the most recently modified file.
     *
     * @param  string $path The directory path to search in.
     *
     * @return int The timestamp of the most recently modified file.
     */
    public function lastModifiedRecursive($path);

    /**
     * Searches for a file within a directory and returns its relative path.
     *
     * This method searches for a specified file in a given directory and its subdirectories,
     * returning the relative path if found.
     *
     * @param  string $file The filename to search for.
     * @param  string $directory The root directory to start the search.
     * @param  string $rootDir The relative path of the directory (default: empty string).
     *
     * @return string|null The relative path of the directory containing the file, or null if not found.
     */
    public function searchDirectory($file, $directory, $rootDir = '');

    /**
     * Converts a relative path to an absolute path.
     *
     * This method retrieves the absolute path corresponding to a given relative path.
     * If the absolute path does not exist, it returns false.
     *
     * @param  string $path The relative path to convert.
     *
     * @return string|false The absolute path if it exists, or false if it does not.
     */
    public function realPath(string $path);

    /**
     * Retrieves an array of files from a directory with optional filtering and sorting.
     *
     * This method scans a specified directory for files matching a pattern,
     * excluding specified directories and files as necessary.
     *
     * @param  string       $directory The directory to search in.
     * @param  string       $name The pattern for including files (default: '*.php').
     * @param  array        $excludedDirectories An array of directory names to exclude (default: empty array).
     * @param  array|null   $excludedFiles An optional array of file patterns to exclude (default: null).
     *
     * @return SplFileInfo[] An array of SplFileInfo objects representing the files that match the criteria.
     */
    public function scanDirectory(
        string $directory,
        string $name = '*.php',
        array $excludedDirectories = [],
        ?array $excludedFiles = [],
    ): array;

    /**
     * Create an instance of directory with read permissions.
     *
     * @param string $directoryCode
     * @param string $driverCode
     *
     * @return ReadInterface
     */
    public function getDirectoryRead($directoryCode, $driverCode = DriverPool::FILE);

    /**
     * Create an instance of directory with read permissions by path.
     *
     * @param string $path
     * @param string $driverCode
     *
     * @return ReadInterface
     *
     * @since 102.0.0
     */
    public function getDirectoryReadByPath($path, $driverCode = DriverPool::FILE);

    /**
     * Create an instance of directory with write permissions.
     *
     * @param string $directoryCode
     * @param string $driverCode
     *
     * @throws FileSystemException
     *
     * @return WriteInterface
     */
    public function getDirectoryWrite($directoryCode, $driverCode = DriverPool::FILE);

    /**
     * Retrieve uri for given code.
     *
     * @param string $code
     *
     * @return string
     */
    public function getUri($code);
}
