<?php

declare(strict_types=1);

namespace Maginium\Framework\Filesystem;

use FilesystemIterator;
use FilesystemIteratorFactory;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Magento\Framework\App\Filesystem\DirectoryList as DirsList;
use Magento\Framework\Filesystem as BaseFilesystem;
use Magento\Framework\Filesystem\Directory\Read;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Filesystem\Interfaces\FilesystemInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Event;
use Maginium\Framework\Support\Path;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;
use ReflectionClass;
use SplFileInfo;
use SplFileObject;
use SplFileObjectFactory;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Finder\FinderFactory;
use Symfony\Component\Mime\MimeTypes;

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
class Filesystem extends BaseFilesystem implements FilesystemInterface
{
    use Conditionable;
    use Macroable;

    /**
     * @var string|null Default file permission mask in octal format (e.g., "0755").
     */
    public ?string $filePermissions = null;

    /**
     * @var string|null Default folder permission mask in octal format (e.g., "0755").
     */
    public ?string $folderPermissions = null;

    /**
     * @var array Mapping of path symbols to their prefixes.
     */
    public array $pathSymbols = [];

    /**
     * @var array|null Cache of symlinked root directories.
     */
    protected ?array $symlinkRootCache;

    /**
     * @var Write The write directory instance for the root directory.
     */
    protected Write $rootWrite;

    /**
     * @var Read The read directory instance for the root directory.
     */
    protected Read $rootRead;

    /**
     * @var FinderFactory The factory for creating Finder instances.
     */
    protected FinderFactory $finderFactory;

    /**
     * @var SplFileObjectFactory The factory for creating SplFileObject instances.
     */
    protected SplFileObjectFactory $splFileObjectFactory;

    /**
     * @var FilesystemIteratorFactory The factory for creating FilesystemIterator instances.
     */
    protected FilesystemIteratorFactory $filesystemIteratorFactory;

    /**
     * FilesystemManager constructor.
     *
     * Initializes the filesystem manager with the required factories and directory handlers.
     *
     * @param ReadFactory $readFactory The factory for creating read directory instances.
     * @param WriteFactory $writeFactory The factory for creating write directory instances.
     * @param DirectoryList $directoryList The directory list to manage paths.
     * @param FinderFactory $finderFactory The factory for creating Finder instances.
     * @param SplFileObjectFactory $splFileObjectFactory The factory for creating SplFileObject instances.
     * @param FilesystemIteratorFactory $filesystemIteratorFactory The factory for creating FilesystemIterator instances.
     */
    public function __construct(
        ReadFactory $readFactory,
        WriteFactory $writeFactory,
        DirectoryList $directoryList,
        FinderFactory $finderFactory,
        SplFileObjectFactory $splFileObjectFactory,
        FilesystemIteratorFactory $filesystemIteratorFactory,
    ) {
        // Store the factory instances for later use.
        $this->finderFactory = $finderFactory;
        $this->splFileObjectFactory = $splFileObjectFactory;
        $this->filesystemIteratorFactory = $filesystemIteratorFactory;

        // Initialize the parent class with the necessary directory management dependencies.
        parent::__construct($directoryList, $readFactory, $writeFactory);

        // Initialize the root directory read and write instances.
        $this->rootRead = self::getDirectoryRead(DirsList::ROOT);
        $this->rootWrite = self::getDirectoryWrite(DirsList::ROOT);
    }

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
    public function get($path, $lock = false)
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    /**
     * Delete the file or directory at the specified path.
     *
     * This method deletes the file or directory located at the given path.
     *
     * @param  string  $path  The path to the file or directory.
     *
     * @return bool  Returns true if the file or directory was deleted successfully, false otherwise.
     */
    public function delete($path)
    {
        // Delete the file or directory.
        return $this->exists($path) ? unlink($path) : false;
    }

    /**
     * Determine if a file or directory exists.
     *
     * This method checks if the file or directory at the specified path exists.
     *
     * @param  string  $path  The path to the file or directory.
     *
     * @return bool  Returns true if the file or directory exists, false otherwise.
     */
    public function exists($path)
    {
        return $this->rootRead->isFile($path);
    }

    /**
     * Determine if a file or directory is missing.
     *
     * This method checks if the file or directory at the specified path does not exist.
     *
     * @param  string  $path  The path to the file or directory.
     *
     * @return bool  Returns true if the file or directory is missing, false otherwise.
     */
    public function missing($path)
    {
        return ! $this->exists($path);
    }

    /**
     * Get contents of a file with shared access.
     *
     * This method retrieves the contents of a file while allowing other processes to read from it.
     *
     * @param  string  $path  The path to the file.
     *
     * @return string  Returns the contents of the file as a string.
     */
    public function sharedGet($path)
    {
        $contents = '';

        // Open the file in binary read mode.
        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                // Acquire a shared lock for reading.
                if (flock($handle, LOCK_SH)) {
                    // Clear cache for accurate file size.
                    clearstatcache(true, $path);

                    // Read the contents of the file.
                    $contents = fread($handle, $this->size($path) ?: 1);

                    // Release the lock after reading.
                    flock($handle, LOCK_UN);
                }
            } finally {
                // Ensure the file is always closed.
                fclose($handle);
            }
        }

        // Return the contents read from the file.
        return $contents;
    }

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
    public function getRequire($path, array $data = [])
    {
        if ($this->isFile($path)) {
            $__path = $path;
            $__data = $data;

            return (static function() use ($__path, $__data) {
                // Extract data into the local scope.
                extract($__data, EXTR_SKIP);

                // Require the file and return its result.
                return require $__path;
            })();
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

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
    public function requireOnce($path, array $data = [])
    {
        if ($this->isFile($path)) {
            $__path = $path;
            $__data = $data;

            return (static function() use ($__path, $__data) {
                // Extract data into the local scope.
                extract($__data, EXTR_SKIP);

                // Require the file once and return its result.
                return require_once $__path;
            })();
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

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
    public function lines($path)
    {
        if (! $this->isFile($path)) {
            throw new FileNotFoundException("File does not exist at path {$path}.");
        }

        return LazyCollection::make(function() use ($path) {
            // Create a file object.
            $file = $this->splFileObjectFactory->create(['filename' => $path]);

            // Drop new lines from each line read.
            $file->setFlags(SplFileObject::DROP_NEW_LINE);

            // Loop through the file until the end is reached.
            while (! $file->eof()) {
                // Yield each line from the file.
                yield $file->fgets();
            }
        });
    }

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
    public function hash($path, $algorithm = 'md5')
    {
        // Compute and return the hash of the file.
        return hash_file($algorithm, $path);
    }

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
    public function put(string $path, string $contents, string $mode = 'w+', bool $lock = false)
    {
        // Write the contents to the file
        return $this->rootWrite->writeFile($path, $contents, $mode, $lock);
    }

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
    public function replace($path, $content, $mode = null): void
    {
        // If the path already exists and is a symlink, get the real path...

        // Clear file stats cache.
        clearstatcache(true, $path);

        // Get the real path of the file if it exists.
        $path = realpath($path) ?: $path;

        // Create a temporary file.
        $tempPath = tempnam(dirname($path), basename($path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        if ($mode !== null) {
            // Set specified permissions.
            $this->chmod($tempPath, $mode);
        } else {
            // Set default permissions.
            $this->chmod($tempPath, 0777 - umask());
        }

        // Write contents to the temporary file.
        $this->rootWrite->writeFile($tempPath, $content);

        // Atomically replace the original file with the temporary file.
        rename($tempPath, $path);
    }

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
    public function replaceInFile($search, $replace, $path): void
    {
        // Read the file contents, replace the strings, and write back to the file.
        $this->rootWrite->writeFile($path, str_replace($search, $replace, file_get_contents($path)));
    }

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
    public function prepend($path, $data)
    {
        if ($this->exists($path)) {
            // If the file exists, prepend data and append the existing contents.
            return $this->put($path, $data . $this->get($path));
        }

        // If the file does not exist, create it with the data.
        return $this->put($path, $data);
    }

    /**
     * Append data to a file.
     *
     * This method adds data to the end of a file.
     *
     * @param  string  $path  The path to the file.
     * @param  string  $data  The data to append to the file.
     *
     * @return void
     */
    public function append($path, $data): void
    {
        $fullPath = Path::join($this->rootRead->getAbsolutePath(), $path);

        // Append data to the file.
        file_put_contents($fullPath, $data, FILE_APPEND);
    }

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
    public function copy($from, $to)
    {
        // Copy the file from source to destination.
        return $this->rootWrite->copyFile($from, $to);
    }

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
    public function move($from, $to)
    {
        // Move the file from source to destination.
        return $this->rootWrite->renameFile($from, $to);
    }

    /**
     * Create a symlink to the target file or directory. On Windows, a hard link is created if the target is a file.
     *
     * @param  string  $target  The target file or directory to link to.
     * @param  string  $link    The path where the symlink will be created.
     *
     * @return bool  Returns true on success, false on failure.
     */
    public function link($target, $link): bool
    {
        // Check if the operating system is Windows.
        if (! windows_os()) {
            // Create a symlink for non-Windows systems.
            return symlink($target, $link);
        }

        // Determine the type of link to create: 'J' for directory junction, 'H' for hard link.
        $mode = $this->isDirectory($target) ? 'J' : 'H';

        // Execute the mklink command to create the link on Windows.
        exec("mklink /{$mode} " . escapeshellarg($link) . ' ' . escapeshellarg($target));

        // Optionally indicate success for the exec call.
        return true;
    }

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
    public function relativeLink($target, $link): void
    {
        // Ensure that the Symfony Filesystem class exists for creating relative links.
        if (! Php::isClassExists(SymfonyFilesystem::class)) {
            throw RuntimeException::make(
                'To enable support for relative links, please install the symfony/filesystem package.',
            );
        }

        // Generate a relative path from the target to the link's directory.
        $relativeTarget = (new SymfonyFilesystem)->makePathRelative($target, dirname($link));

        // Create the link using the relative path.
        $this->link($this->isFile($target) ? rtrim($relativeTarget, SP) : $relativeTarget, $link);
    }

    /**
     * Extract the file name from a file path.
     *
     * @param  string  $path  The file path to extract the name from.
     *
     * @return string  The name of the file without its extension.
     */
    public function name($path)
    {
        // Return the file name without extension.
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component from a file path.
     *
     * @param  string  $path  The file path to extract the basename from.
     *
     * @return string  The basename of the file path.
     */
    public function basename($path)
    {
        // Return the base name of the path.
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Extract the parent directory from a file path.
     *
     * @param  string  $path  The file path to extract the directory from.
     *
     * @return string  The directory path of the file.
     */
    public function dirname($path)
    {
        // Return the directory name of the path.
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Extract the file extension from a file path.
     *
     * @param  string  $path  The file path to extract the extension from.
     *
     * @return string  The file extension.
     */
    public function extension($path)
    {
        // Return the file extension.
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Guess the file extension from the mime-type of a given file.
     *
     * @param  string  $path  The file path to guess the extension from.
     *
     * @throws RuntimeException  Throws an exception if the Symfony Mime class is not available.
     *
     * @return string|null  The guessed file extension or null if not found.
     */
    public function guessExtension($path)
    {
        // Ensure that the Symfony Mime class exists for guessing extensions.
        if (! Php::isClassExists(MimeTypes::class)) {
            throw RuntimeException::make(
                'To enable support for guessing extensions, please install the symfony/mime package.',
            );
        }

        // Return the first guessed extension for the file's mime type.
        return (new MimeTypes)->getExtensions($this->mimeType($path))[0] ?? null;
    }

    /**
     * Get the file type of a given file.
     *
     * @param  string  $path  The file path to check the type.
     *
     * @return string  The type of the file.
     */
    public function type($path)
    {
        // Return the file type.
        return filetype($path);
    }

    /**
     * Get the mime-type of a given file.
     *
     * @param  string  $path  The file path to get the mime type.
     *
     * @return string|false  The mime type of the file or false on failure.
     */
    public function mimeType($path)
    {
        // Return the mime type of the file.
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Get the file size of a given file.
     *
     * @param  string  $path  The file path to get the size.
     *
     * @return int  The size of the file in bytes.
     */
    public function size($path)
    {
        // Return the file size in bytes.
        return filesize($path);
    }

    /**
     * Get the file's last modification time.
     *
     * @param  string  $path  The file path to check the last modification time.
     *
     * @return int  The last modification time as a Unix timestamp.
     */
    public function lastModified($path)
    {
        // Return the last modification time of the file.
        return filemtime($path);
    }

    /**
     * Determine if the given path is a directory.
     *
     * @param  string  $directory The path to check if it is a directory.
     *
     * @return bool Returns true if the path is a directory, false otherwise.
     */
    public function isDirectory($directory)
    {
        // Use PHP's built-in is_dir function to check if the path is a directory.
        return $this->rootRead->isExist($directory);
    }

    /**
     * Determine if the given path is a directory that does not contain any other files or directories.
     *
     * @param  string  $directory The path to check for emptiness.
     * @param  bool  $ignoreDotFiles Whether to ignore hidden files (starting with a dot).
     *
     * @return bool Returns true if the directory is empty, false otherwise.
     */
    public function isEmptyDirectory($directory, $ignoreDotFiles = false)
    {
        // Create a Finder instance to search for files in the specified directory, ignoring dot files if specified.
        return ! $this->finderFactory->create()->ignoreDotFiles($ignoreDotFiles)->in($directory)->depth(0)->hasResults();
    }

    /**
     * Determine if the given path is readable.
     *
     * @param  string  $path The path to check for readability.
     *
     * @return bool Returns true if the path is readable, false otherwise.
     */
    public function isReadable($path)
    {
        return is_readable($path);  // Use PHP's built-in is_readable function to check the path's readability.
    }

    /**
     * Determine if the given path is writable.
     *
     * @param  string  $path The path to check for writability.
     *
     * @return bool Returns true if the path is writable, false otherwise.
     */
    public function isWritable($path)
    {
        return is_writable($path);  // Use PHP's built-in is_writable function to check the path's writability.
    }

    /**
     * Determine if two files are the same by comparing their hashes.
     *
     * @param  string  $firstFile The first file path for comparison.
     * @param  string  $secondFile The second file path for comparison.
     *
     * @return bool Returns true if the files have the same hash, false otherwise.
     */
    public function hasSameHash($firstFile, $secondFile)
    {
        // Calculate the MD5 hash of the first file. Suppress errors with the @ operator.
        $hash = @md5_file($firstFile);

        // Compare the hashes of the two files to determine if they are the same.
        return $hash && $hash === @md5_file($secondFile);
    }

    /**
     * Determine if the given path is a file.
     *
     * @param  string  $file The path to check if it is a file.
     *
     * @return bool Returns true if the path is a file, false otherwise.
     */
    public function isFile($file)
    {
        return is_file($file);  // Use PHP's built-in is_file function to check if the path is a file.
    }

    /**
     * Find path names matching a given pattern.
     *
     * @param  string  $pattern The pattern to match against file names.
     * @param  int  $flags Flags to modify the behavior of the glob function.
     *
     * @return array Returns an array of file paths that match the pattern.
     */
    public function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);  // Use PHP's built-in glob function to find matching file paths.
    }

    /**
     * Get an array of all files in a directory.
     *
     * This method scans a specified directory for files matching a pattern,
     * excluding specified directories and files as necessary.
     *
     * @param  string       $directory The directory to search in.
     * @param  string       $pattern The pattern for including files (default: '*.php').
     * @param  array        $excludedDirectories An array of directory names to exclude (default: empty array).
     * @param  array|null   $excludedFiles An optional array of file patterns to exclude (default: null).
     *
     * @return SplFileInfo[] An array of SplFileInfo objects representing the files that match the criteria.
     */
    public function files(
        string $directory,
        string $pattern = '*.php',
        array $excludedDirectories = [],
        ?array $excludedFiles = [],
    ): array {
        // Create a new Finder instance to search for files in the specified directory.
        $finder = $this->finderFactory->create()
            ->in($directory) // Set the directory to search in.
            ->files() // Search for files only.
            ->name($pattern) // Filter files by the specified name pattern.

            // Exclude specified directories from the search.
            ->exclude($excludedDirectories);

        // Apply additional filtering if excluded files patterns are provided.
        if ($excludedFiles !== null) {
            // Exclude files matching the specified patterns.
            $finder->notName($excludedFiles);
        }

        // Convert the Finder result to an array of SplFileInfo objects and return it.
        return iterator_to_array($finder, false);
    }

    /**
     * Get all of the directories within a given directory.
     *
     * @param  string  $directory The path to the directory.
     *
     * @return array Returns an array of directory paths.
     */
    public function directories(string $directory)
    {
        $directories = [];  // Initialize an array to hold directory paths.

        // Use the Finder component to locate directories in the specified directory.
        foreach ($this->finderFactory->create()->in($directory)->directories()->depth(0)->sortByName() as $dir) {
            $directories[] = $dir->getPathname();  // Add each directory's path to the array.
        }

        return $directories;  // Return the array of directory paths.
    }

    /**
     * Ensure a directory exists.
     *
     * @param  string  $path The path to the directory.
     * @param  int  $mode The permissions to set on the directory.
     * @param  bool  $recursive Whether to create directories recursively.
     *
     * @return void
     */
    public function ensureDirectoryExists($path): void
    {
        // Check if the specified path is not a directory, and create it if necessary.
        if (! $this->isDirectory($path)) {
            $this->makeDirectory($path);  // Create the directory with the specified permissions and recursion.
        }
    }

    /**
     * Create a directory.
     *
     * @param  string  $path The path to create the directory.
     * @param  int  $mode The permissions to set on the directory.
     * @param  bool  $recursive Whether to create directories recursively.
     * @param  bool  $force Whether to suppress errors on directory creation.
     *
     * @return bool Returns true if the directory was created successfully, false otherwise.
     */
    public function makeDirectory($path)
    {
        // Create the directory with the specified permissions and recursion.
        return $this->rootWrite->create($path);
    }

    /**
     * Move a directory.
     *
     * @param  string  $from The source directory path.
     * @param  string  $to The destination directory path.
     * @param  bool  $overwrite Whether to overwrite the destination if it exists.
     *
     * @return bool Returns true if the directory was moved successfully, false otherwise.
     */
    public function moveDirectory($from, $to, $overwrite = false)
    {
        // If overwriting is allowed and the destination directory exists, attempt to delete it.
        if ($overwrite && $this->isDirectory($to) && ! $this->deleteDirectory($to)) {
            return false;  // Return false if the deletion failed.
        }

        return $this->rootWrite->copyFile($from, $to) === true;  // Rename (move) the directory, suppressing errors.
    }

    /**
     * Copy a directory from one location to another.
     *
     * @param  string  $directory The source directory path.
     * @param  string  $destination The destination directory path.
     * @param  int|null  $options Options for the FilesystemIterator.
     *
     * @return bool Returns true if the directory was copied successfully, false otherwise.
     */
    public function copyDirectory($directory, $destination, $options = null)
    {
        // Check if the source directory exists; return false if it does not.
        if (! $this->isDirectory($directory)) {
            return false;
        }

        // Set default options for the FilesystemIterator if none are provided.
        $options = $options ?: FilesystemIterator::SKIP_DOTS;

        // Ensure the destination directory exists, creating it if necessary.
        $this->ensureDirectoryExists($destination);

        // Create a new FilesystemIterator to iterate over the contents of the source directory.
        $items = $this->filesystemIteratorFactory->create(['directory' => $directory, 'flags' => $options]);

        foreach ($items as $item) {
            // Build the target path for the item being processed.
            $target = $destination . SP . $item->getBasename();

            // If the item is a directory, call this function recursively to copy its contents.
            if ($item->isDir()) {
                $path = $item->getPathname();

                if (! $this->copyDirectory($path, $target, $options)) {
                    return false;  // Return false if the recursive copy fails.
                }
            }
            // If the item is a file, copy it to the new location.
            elseif (! $this->copy($item->getPathname(), $target)) {
                return false;  // Return false if the file copy fails.
            }
        }

        return true;  // Return true if all items were copied successfully.
    }

    /**
     * Recursively delete a directory and all of its contents.
     *
     * @param  string  $directory The path to the directory to delete.
     * @param  bool  $preserve Whether to preserve the parent directory.
     *
     * @return bool Returns true if the directory was deleted successfully, false otherwise.
     */
    public function deleteDirectory($directory, $preserve = false)
    {
        // Check if the path is a directory; return false if it is not.
        if (! $this->isDirectory($directory)) {
            return false;  // Return false if the path is not a directory.
        }

        // Iterate through each item in the directory.
        foreach ($this->files($directory) as $file) {
            unlink($file->getPathname());  // Delete the file.
        }

        // Iterate through each subdirectory and recursively delete it.
        foreach ($this->directories($directory) as $dir) {
            $this->deleteDirectory($dir);  // Call this function recursively to delete the subdirectory.
        }

        // If preserving the parent directory is not desired, remove it.
        return $preserve ? true : rmdir($directory);  // Remove the directory itself.
    }

    /**
     * Remove all of the directories within a given directory.
     *
     * @param  string  $directory The path to the parent directory.
     *
     * @return bool True if directories were deleted, false otherwise.
     */
    public function deleteDirectories($directory)
    {
        $allDirectories = $this->directories($directory);

        // Check if there are any directories to delete.
        if (! Validator::isEmpty($allDirectories)) {
            // Iterate through each directory and delete it.
            foreach ($allDirectories as $directoryName) {
                $this->deleteDirectory($directoryName);
            }

            // Directories were deleted.
            return true;
        }

        // No directories were found to delete.
        return false;
    }

    /**
     * Empty the specified directory of all files and folders.
     *
     * @param  string  $directory The path to the directory to clean.
     *
     * @return bool True if the directory was cleaned, false otherwise.
     */
    public function cleanDirectory($directory)
    {
        return $this->deleteDirectory($directory, true);
    }

    /**
     * Extracts the path and filename without its extension.
     *
     * @param string $path The full file path including the filename and extension.
     *
     * @return string The path and filename without the extension.
     */
    public function anyname(string $path): string
    {
        // Check if the filename contains a dot, indicating an extension exists.
        return str_contains(basename($path), '.')
            // If it contains a dot, remove the extension using substring operations.
            ? mb_substr($path, 0, mb_strrpos($path, '.'))
            // If it doesn't contain a dot, return the path as-is.
            : $path;
    }

    /**
     * Determines if the given directory is empty.
     *
     * @param string $directory The path to the directory to check.
     *
     * @return bool True if the directory is empty, false otherwise.
     */
    public function isDirectoryEmpty(string $directory): bool
    {
        // Check if the directory is readable.
        if (! is_readable($directory)) {
            return false;
        }

        // Open the directory for reading.
        $handle = opendir($directory);

        // If the directory handle couldn't be opened, return false.
        if ($handle === false) {
            return false;
        }

        // Iterate over directory entries.
        while (false !== ($entry = readdir($handle))) {
            // Ignore '.' and '..' entries which represent the current and parent directory.
            if ($entry !== '.' && $entry !== '..') {
                closedir($handle);  // Close the directory handle.

                return false;       // Return false as the directory is not empty.
            }
        }

        closedir($handle);  // Close the directory handle.

        return true;        // Return true as the directory is empty.
    }

    /**
     * Converts a file size in bytes to a human-readable format.
     *
     * @param int $bytes The file size in bytes.
     *
     * @return string The human-readable file size.
     */
    public function sizeToString(int $bytes): string
    {
        // Convert bytes to gigabytes if greater than or equal to 1 GB.
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        // Convert bytes to megabytes if greater than or equal to 1 MB.
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        // Convert bytes to kilobytes if greater than or equal to 1 KB.
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        // Return the size in bytes if greater than 1 byte.
        if ($bytes > 1) {
            return $bytes . ' bytes';
        }

        // Return the size for 1 byte.
        if ($bytes === 1) {
            return $bytes . ' byte';
        }

        // Return '0 bytes' for zero size.
        return '0 bytes';
    }

    /**
     * Converts a local file path to a public file path.
     *
     * @param string $path The absolute local path.
     *
     * @return string The public file path.
     */
    public function localToPublic(string $path): string
    {
        /**
         * @event filesystem.localToPublic
         * Allows custom logic for converting local to public paths on non-standard installations.
         *
         * Example usage:
         *     Event::listen('filesystem.localToPublic', function ($path) {
         *         return '/custom/public/path';
         *     });
         */
        // Fire the event to allow custom path conversion logic and return the result if available.
        if (($event = Event::fire('filesystem.localToPublic', [$path], true)) !== null) {
            return $event;
        }

        $basePath = BP;

        // If the local path starts with the base path, replace the base path with a public path.
        if (str_starts_with($path, $basePath)) {
            return str_replace('\\', SP, mb_substr($path, mb_strlen($basePath)));
        }

        // Iterate over root symlinks to resolve the path.
        foreach ($this->getRootSymlinks() as $dir) {
            $resolvedDir = readlink($dir);

            // If the path starts with a resolved symlink directory, convert the path accordingly.
            if (str_starts_with($path, $resolvedDir)) {
                $relativePath = mb_substr($path, mb_strlen($resolvedDir));

                return str_replace('\\', SP, mb_substr($dir, mb_strlen($basePath)) . $relativePath);
            }
        }

        // Return the original path if no conversions were applied.
        return $path;
    }

    /**
     * Checks if the specified path is within the application's base path.
     *
     * @param string $path The path to check.
     * @param bool $realpath Whether to resolve the path before checking (default: true).
     *
     * @return bool True if the path is local, false otherwise.
     */
    public function isLocalPath(string $path, bool $realpath = true): bool
    {
        $base = BP;

        // Resolve the real path if the realpath option is true.
        if ($realpath) {
            $path = realpath($path);
        }

        // Check if the resolved path starts with the base path.
        return ! ($path === false || strncmp($path, $base, mb_strlen($base)) !== 0);
    }

    /**
     * Finds the file path of a given class.
     *
     * @param mixed $className Class name or object.
     *
     * @return string The file path of the class.
     */
    public function fromClass($className): string
    {
        // Use ReflectionClass to get the file path of the given class.
        $reflector = Reflection::getClass($className);

        return $reflector->getFileName();
    }

    /**
     * Checks if a file exists with case insensitivity.
     *
     * @param string $path The file path.
     *
     * @return string|bool The case-insensitive path if exists, false otherwise.
     */
    public function existsInsensitive(string $path)
    {
        // Check if the file exists with the given path.
        if ($this->exists($path)) {
            return $path;
        }

        $directoryName = dirname($path);
        $pathLower = mb_strtolower($path);

        // Get a list of files in the directory.
        if (! $files = $this->glob($directoryName . '/*', GLOB_NOSORT)) {
            return false;
        }

        // Iterate over the files to find a case-insensitive match.
        foreach ($files as $file) {
            if (mb_strtolower($file) === $pathLower) {
                return $file;
            }
        }

        // No case-insensitive match found.
        return false;
    }

    /**
     * Returns a normalized version of the supplied path.
     *
     * @param string $path The path to normalize.
     *
     * @return string The normalized path with consistent directory separators.
     */
    public function normalizePath(string $path): string
    {
        // Replace backslashes with the system's directory separator.
        return str_replace('\\', SP, $path);
    }

    /**
     * Removes the base path from a local path and returns a user-friendly relative path.
     *
     * @param string $path The local path to convert.
     *
     * @return string The relative, user-friendly path.
     */
    public function nicePath(string $path): string
    {
        // Replace the base path in the local path with a tilde '~' for a more user-friendly representation.
        return $this->normalizePath(str_replace([
            BP,
            $this->normalizePath(BP),
        ], '~', $path));
    }

    /**
     * Converts a path using path symbols, or returns the original path if no symbol is used.
     *
     * @param string $path The path to convert.
     * @param mixed $default Default value to return if no symbol is used (default: false).
     *
     * @return string The converted path or the default value.
     */
    public function symbolizePath(string $path, $default = false): string
    {
        // Check if the path starts with a known symbol.
        if (! $firstChar = $this->isPathSymbol($path)) {
            return $default === false ? $path : $default;
        }

        $_path = mb_substr($path, 1);

        // Convert the path using the symbol's replacement value.
        return $this->pathSymbols[$firstChar] . $_path;
    }

    /**
     * Checks if a path uses a known symbol and returns the symbol if found.
     *
     * @param string $path The path to check.
     *
     * @return string|bool The symbol if found, false otherwise.
     */
    public function isPathSymbol(string $path)
    {
        // Check the first character of the path against known symbols.
        return ! Validator::isEmpty($path) && Arr::keyExists(mb_substr($path, 0, 1), $this->pathSymbols) ? mb_substr($path, 0, 1) : false;
    }

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
    public function getSafe(string $path, float $limitKbs = 1)
    {
        // Convert the limit from kilobytes to bytes (4096 bytes per kilobyte).
        $limit = (int)$limitKbs * 4096;

        // Open the file for reading.
        $parser = fopen($path, 'r');

        // If the file couldn't be opened, return an empty string.
        if ($parser === false) {
            return '';
        }

        // Read and return the specified portion of the file contents.
        return fread($parser, $limit);
    }

    /**
     * chmod modifies file/folder permissions.
     *
     * @param  string $path The path of the file or directory.
     * @param  octal|null $mask Permission mask (default: null).
     *
     * @return void
     */
    public function chmod($path, $mask = null): void
    {
        if (! $mask) {
            $mask = $this->isDirectory($path)
                ? $this->getFolderPermissions()
                : $this->getFilePermissions();
        }

        if (! $mask) {
            // If no mask is set, exit the method early.
            return;
        }

        $this->rootWrite->changePermissions($path, $mask);
    }

    /**
     * Recursively modifies file and folder permissions.
     *
     * This method sets the permissions for files and directories at a given path,
     * applying specified masks. If no masks are provided, default permissions are used.
     *
     * @param  string $path The path of the file or directory.
     * @param  octal|null $fileMask Permission mask for files (default: null).
     * @param  octal|null $directoryMask Permission mask for directories (default: null).
     *
     * @return void
     */
    public function chmodRecursive($path, $fileMask = null, $directoryMask = null): void
    {
        // If no file mask is provided, use the default file permissions.
        if (! $fileMask) {
            $fileMask = $this->getFilePermissions();
        }

        // If no directory mask is provided, use the default directory permissions or fall back to the file mask.
        if (! $directoryMask) {
            $directoryMask = $this->getFolderPermissions() ?: $fileMask;
        }

        // If no file mask is set, return early.
        if (! $fileMask) {
            return;
        }

        // If the path is not a directory, set the file permissions.
        if (! $this->isDirectory($path)) {
            $this->chmod($path, $fileMask);

            // Return early after setting the file permissions.
            return;
        }

        // If the item is a directory, recursively set permissions for the directory and its contents.
        $this->rootWrite->changePermissionsRecursively($path, $directoryMask, $fileMask);
    }

    /**
     * Returns the default file permission mask.
     *
     * This method retrieves the default file permission mask as an octal string.
     * If no permission mask is set, it returns null.
     *
     * @return int|null Permission mask as an octal integer (e.g., 0755) or null if not set.
     */
    public function getFilePermissions()
    {
        return $this->filePermissions
            ? octdec($this->filePermissions) // Convert the octal permission string to decimal.
            : null;
    }

    /**
     * Returns the default folder permission mask.
     *
     * This method retrieves the default folder permission mask as an octal string.
     * If no permission mask is set, it returns null.
     *
     * @return int|null Permission mask as an octal integer (e.g., 0755) or null if not set.
     */
    public function getFolderPermissions()
    {
        return $this->folderPermissions
            ? octdec($this->folderPermissions) // Convert the octal permission string to decimal.
            : null;
    }

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
    public function fileNameMatch($fileName, $pattern)
    {
        // If the pattern exactly matches the filename, return true.
        if ($pattern === $fileName) {
            return true;
        }

        // Convert the pattern to a regex pattern, replacing '*' with '.*' and '?' with '.'.
        $regex = strtr(preg_quote($pattern, '#'), ['\*' => '.*', '\?' => '.']);

        // Check if the filename matches the regex pattern.
        return (bool)preg_match('#^' . $regex . '$#i', $fileName);
    }

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
    public function lastModifiedRecursive($path)
    {
        // Initialize the modification time to zero.
        $mtime = 0;

        // Iterate through all files in the directory and update the modification time.
        foreach ($this->allFiles($path) as $file) {
            $mtime = max($mtime, $this->lastModified($file->getPathname()));
        }

        return $mtime;
    }

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
    public function searchDirectory($file, $directory, $rootDir = '')
    {
        // Get all files and directories within the specified directory.
        $files = $this->files($directory);
        $directories = $this->directories($directory);

        // Check if the file is in the current directory.
        foreach ($files as $directoryFile) {
            if ($directoryFile->getFileName() === $file) {
                // File found, return the current relative path.
                return $rootDir;
            }
        }

        // Recursively search subdirectories for the file.
        foreach ($directories as $subdirectory) {
            $relativePath = mb_strlen($rootDir)
                ? $rootDir . SP . basename($subdirectory)
                : basename($subdirectory);

            $result = $this->searchDirectory($file, $subdirectory, $relativePath);

            if ($result !== null) {
                // File found in subdirectory, return the relative path.
                return $result;
            }
        }

        // File not found in the directory or subdirectories.
    }

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
    public function realPath(string $path)
    {
        // Return absolute path or false.
        return realpath($path) ?: false;
    }

    /**
     * Retrieves the list of root symlink directories from the cache.
     *
     * This method checks if the symlink root directories are cached. If they are not cached, it builds the list and caches it.
     *
     * @return array The list of symlink root directories.
     */
    protected function getRootSymlinks(): array
    {
        // If the symlink root cache is null, build and cache the root symlink directories.
        if ($this->symlinkRootCache === null) {
            $this->symlinkRootCache = $this->buildRootSymlinks();
        }

        // Return the cached list of symlink root directories.
        return $this->symlinkRootCache;
    }

    /**
     * Builds the list of root symlink directories by scanning the base path.
     *
     * This method scans the base path for directories that are symlinks and returns their real paths.
     * It only performs this operation if the operating system is not Windows.
     *
     * @return array The list of symlink root directories.
     */
    protected function buildRootSymlinks(): array
    {
        // Initialize an empty array to store the symlink directories.
        $symlinks = [];

        // Check if the operating system is not Windows, as symlink handling is different on Windows.
        if (PHP_OS_FAMILY !== 'Windows') {
            // Create a new Finder instance for directory searching.
            $finder = $this->finderFactory->create()
                ->in(BP) // Set the base path to search in.
                ->directories() // Search for directories only.
                ->followLinks() // Follow symlinked directories.

                // Limit the search to the top level directories.
                ->depth(0);

            // Iterate over the directories found by the Finder.
            foreach ($finder as $dir) {
                // Get the real path of the directory.
                $path = $dir->getRealPath();

                // Check if the path is a symlink and add it to the list.
                if ($path && is_link($path)) {
                    $symlinks[] = $path;
                }
            }
        }

        // Return the list of symlink root directories.
        return $symlinks;
    }
}
