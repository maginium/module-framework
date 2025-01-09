<?php

declare(strict_types=1);

namespace Maginium\Framework\Filesystem\Drivers;

use BadMethodCallException;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Filesystem\Cloud as CloudInterface;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToProvideChecksum;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Maginium\Framework\Http\File;
use Maginium\Framework\Http\UploadedFile;
use Maginium\Framework\Support\Facades\Request;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @mixin FilesystemOperator
 *
 * A filesystem adapter class to handle file storage operations with assertions
 * for testing and integration with Flysystem.
 */
class DriverFilesystem implements CloudInterface
{
    use Conditionable;
    use Macroable {
        __call as macroCall;
    }

    /**
     * The Flysystem filesystem implementation.
     *
     * @var FilesystemOperator
     */
    protected FilesystemOperator $driver;

    /**
     * The Flysystem adapter implementation.
     *
     * @var FlysystemAdapter
     */
    protected FlysystemAdapter $adapter;

    /**
     * The filesystem configuration array.
     *
     * @var array
     */
    protected array $config;

    /**
     * The Flysystem PathPrefixer instance.
     *
     * @var PathPrefixer
     */
    protected PathPrefixer $prefixer;

    /**
     * A callback to serve files.
     *
     * @var Closure|null
     */
    protected ?Closure $serveCallback = null;

    /**
     * A callback for generating temporary URLs.
     *
     * @var Closure|null
     */
    protected ?Closure $temporaryUrlCallback = null;

    /**
     * Create a new filesystem adapter instance.
     *
     * @param FilesystemOperator $driver The Flysystem driver implementation.
     * @param FlysystemAdapter $adapter The Flysystem adapter implementation.
     * @param array $config The filesystem configuration.
     */
    public function __construct(FilesystemOperator $driver, FlysystemAdapter $adapter, array $config = [])
    {
        // Assign the driver instance.
        $this->driver = $driver;

        // Assign the adapter instance.
        $this->adapter = $adapter;

        // Store the configuration.
        $this->config = $config;

        // Set up the path prefixer using the root directory and separator.
        $separator = $config['directory_separator'] ?? DIRECTORY_SEPARATOR;
        $this->prefixer = new PathPrefixer($config['root'] ?? '', $separator);

        // If a prefix is defined in the configuration, adjust the path prefixer.
        if (isset($config['prefix'])) {
            $this->prefixer = new PathPrefixer(
                $this->prefixer->prefixPath($config['prefix']),
                $separator,
            );
        }
    }

    /**
     * Get the contents of a file.
     *
     * @param string $path The path to the file.
     *
     * @throws UnableToReadFile If the file cannot be read and exceptions are enabled.
     *
     * @return string|null The file contents, or null if not found.
     */
    public function get($path): ?string
    {
        try {
            return $this->driver->read($path);
        } catch (UnableToReadFile $e) {
            // Re-throw if exceptions are enabled.
            throw_if($this->throwsExceptions(), $e);
        }

        return null;
    }

    /**
     * Delete the file(s) at the given path(s).
     *
     * @param string|array $paths The path(s) of files to delete.
     *
     * @return bool True if all files were deleted successfully, false otherwise.
     */
    public function delete($paths): bool
    {
        // Normalize input to an array.
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                $this->driver->delete($path);
            } catch (UnableToDeleteFile $e) {
                // Re-throw if exceptions are enabled.
                throw_if($this->throwsExceptions(), $e);

                // Mark failure for this path.
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Assert that the given file or directory exists and optionally matches the content.
     *
     * @param string|array $path The path(s) to check.
     * @param string|null $content Optional content to match.
     *
     * @return $this
     */
    public function assertExists($path, ?string $content = null): self
    {
        // Clear file status cache to ensure fresh checks.
        clearstatcache();

        // Wrap the path(s) into an array.
        $paths = Arr::wrap($path);

        foreach ($paths as $path) {
            PHPUnit::assertTrue(
                $this->exists($path),
                "Unable to find a file or directory at path [{$path}].",
            );

            if ($content !== null) {
                $actual = $this->get($path);

                PHPUnit::assertSame(
                    $content,
                    $actual,
                    "File or directory [{$path}] was found, but content [{$actual}] does not match [{$content}].",
                );
            }
        }

        return $this;
    }

    /**
     * Assert that the given file or directory does not exist.
     *
     * @param string|array $path The path(s) to check.
     *
     * @return $this
     */
    public function assertMissing($path): self
    {
        // Clear file status cache to ensure fresh checks.
        clearstatcache();

        // Wrap the path(s) into an array.
        $paths = Arr::wrap($path);

        foreach ($paths as $path) {
            PHPUnit::assertFalse(
                $this->exists($path),
                "Found unexpected file or directory at path [{$path}].",
            );
        }

        return $this;
    }

    /**
     * Assert that the specified directory is empty.
     *
     * @param string $path The path of the directory to check.
     *
     * @return $this Returns the current instance for method chaining.
     */
    public function assertDirectoryEmpty($path): self
    {
        // Use PHPUnit to assert the directory contains no files.
        PHPUnit::assertEmpty(
            $this->allFiles($path),
            "Directory [{$path}] is not empty.",
        );

        return $this;
    }

    /**
     * Check if a file or directory exists at the specified path.
     *
     * @param string $path The path to check.
     *
     * @return bool True if the path exists, false otherwise.
     */
    public function exists($path): bool
    {
        // Delegate the existence check to the underlying driver.
        // The driver returns true if the path exists, false otherwise.
        return $this->driver->fileExists($path);
    }

    /**
     * Check if a file or directory is missing at the specified path.
     *
     * @param string $path The path to check.
     *
     * @return bool True if the path does not exist, false otherwise.
     */
    public function missing($path): bool
    {
        // Negates the result of the `exists` method to check if the path is missing.
        return ! $this->exists($path);
    }

    /**
     * Check if a file exists at the specified path.
     *
     * @param string $path The path to the file.
     *
     * @return bool True if the file exists, false otherwise.
     */
    public function fileExists($path): bool
    {
        // Delegate the check for file existence to the driver.
        return $this->driver->fileExists($path);
    }

    /**
     * Check if a file is missing at the specified path.
     *
     * @param string $path The path to the file.
     *
     * @return bool True if the file does not exist, false otherwise.
     */
    public function fileMissing($path): bool
    {
        // Negates the result of the `fileExists` method to check if the file is missing.
        return ! $this->fileExists($path);
    }

    /**
     * Check if a directory exists at the specified path.
     *
     * @param string $path The path to the directory.
     *
     * @return bool True if the directory exists, false otherwise.
     */
    public function directoryExists($path): bool
    {
        // Delegate the check for directory existence to the driver.
        return $this->driver->fileExists($path);
    }

    /**
     * Check if a directory is missing at the specified path.
     *
     * @param string $path The path to the directory.
     *
     * @return bool True if the directory does not exist, false otherwise.
     */
    public function directoryMissing($path): bool
    {
        // Negates the result of the `directoryExists` method to check if the directory is missing.
        return ! $this->directoryExists($path);
    }

    /**
     * Get the full path of a file or directory based on the given relative path.
     *
     * @param string $path The relative path.
     *
     * @return string The full path, including any necessary prefix.
     */
    public function path($path): string
    {
        // Prefix the path using the configured prefixer to ensure it's an absolute path.
        return $this->prefixer->prefixPath($path);
    }

    /**
     * Get the JSON-decoded contents of a file.
     *
     * @param string $path The path to the file.
     * @param int $flags Optional JSON decoding flags (default is 0).
     *
     * @return array|null The JSON-decoded contents as an array, or null if the file doesn't exist.
     */
    public function json($path, $flags = 0): ?array
    {
        // Retrieve the content of the file.
        $content = $this->get($path);

        // If the file does not exist (content is null), return null.
        if ($content === null) {
            return null;
        }

        // Decode the JSON content and return it as an associative array.
        return json_decode($content, true, 512, $flags);
    }

    /**
     * Create a streamed HTTP response for a file.
     *
     * @param string $path The path to the file.
     * @param string|null $name Optional name for the file in the response.
     * @param array $headers Optional HTTP headers to include in the response.
     * @param string|null $disposition The content disposition (either 'inline' or 'attachment').
     *
     * @return StreamedResponse The streamed response containing the file data.
     */
    public function response($path, $name = null, array $headers = [], $disposition = 'inline'): StreamedResponse
    {
        // Create a new StreamedResponse instance for file streaming.
        $response = new StreamedResponse;

        // Set the Content-Type header if it's not already set.
        $headers['Content-Type'] ??= $this->mimeType($path);

        // Set the Content-Length header if it's not already set.
        $headers['Content-Length'] ??= $this->size($path);

        // If the Content-Disposition header is not present, create it.
        if (! Arr::exists($headers, 'Content-Disposition')) {
            // Determine the filename (either from the provided name or the base name of the path).
            $filename = $name ?? basename($path);

            // Create the content disposition header (either inline or as an attachment).
            $disposition = $response->headers->makeDisposition(
                $disposition,
                $filename,
                $this->fallbackName($filename),
            );

            // Set the Content-Disposition header.
            $headers['Content-Disposition'] = $disposition;
        }

        // Replace the response headers with the provided or generated headers.
        $response->headers->replace($headers);

        // Define the callback function to stream the file content.
        $response->setCallback(function() use ($path) {
            // Open a stream to the file and pass its contents to the response.
            $stream = $this->readStream($path);
            fpassthru($stream);
            fclose($stream);
        });

        // Return the constructed response with the streamed file data.
        return $response;
    }

    /**
     * Create a streamed download response for a given file.
     *
     * @param Request $request The incoming HTTP request.
     * @param string $path The path to the file.
     * @param string|null $name Optional custom name for the file in the response.
     * @param array $headers Optional headers for the response.
     *
     * @return StreamedResponse The response containing the file data for download.
     */
    public function serve(Request $request, $path, $name = null, array $headers = []): StreamedResponse
    {
        // If a custom callback for serving files is provided, use it.
        if (isset($this->serveCallback)) {
            return call_user_func($this->serveCallback, $request, $path, $headers);
        }

        // If no custom callback is provided, fall back to the default response method.
        return $this->response($path, $name, $headers);
    }

    /**
     * Create a download response for a file.
     *
     * @param string $path The path to the file.
     * @param string|null $name Optional name for the file.
     * @param array $headers HTTP headers for the response.
     *
     * @return StreamedResponse The download response.
     */
    public function download($path, $name = null, array $headers = [])
    {
        // Call the response method with a disposition of 'attachment'.
        return $this->response($path, $name, $headers, 'attachment');
    }

    /**
     * Write contents to a file.
     *
     * @param string $path The path to the file.
     * @param mixed $contents The contents to write (string, resource, or file instance).
     * @param mixed $options Options for writing, such as visibility.
     *
     * @throws UnableToWriteFile|UnableToSetVisibility If writing fails and exceptions are enabled.
     *
     * @return string|bool Returns the file path if successful, or false on failure.
     */
    public function put($path, $contents, $options = []): bool
    {
        // Convert options to an array if it's a string.
        $options = is_string($options) ? ['visibility' => $options] : (array)$options;

        // Handle file or uploaded file instances.
        if ($contents instanceof File || $contents instanceof UploadedFile) {
            return $this->putFile($path, $contents, $options);
        }

        try {
            // Handle streams and resources.
            if ($contents instanceof StreamInterface) {
                $this->driver->writeStream($path, $contents->detach(), $options);

                return true;
            }

            // Write the contents based on their type.
            is_resource($contents)
                ? $this->driver->writeStream($path, $contents, $options)
                : $this->driver->write($path, $contents, $options);
        } catch (UnableToWriteFile|UnableToSetVisibility $e) {
            throw_if($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    /**
     * Store the uploaded file on the disk.
     *
     * If no file is provided, it treats the path as the file and stores it accordingly.
     *
     * @param File|UploadedFile|string $path    Path where the file will be stored or the file itself.
     * @param File|UploadedFile|string|array|null $file The file to store or an array of options.
     * @param mixed $options                     Additional storage options.
     *
     * @return string|false                      The stored file path on success, or false on failure.
     */
    public function putFile($path, $file = null, $options = []): bool|string
    {
        // Handle cases where the file is null or options are provided as an array
        if ($file === null || is_array($file)) {
            [$path, $file, $options] = ['', $path, $file ?? []];
        }

        // If the file is a string path, convert it to a File instance
        $file = is_string($file) ? new File($file) : $file;

        // Store the file using a hashed name
        return $this->putFileAs($path, $file, $file->hashName(), $options);
    }

    /**
     * Store the uploaded file on the disk with a given name.
     *
     * Allows specifying a custom file name for storage.
     *
     * @param File|UploadedFile|string $path  Path where the file will be stored.
     * @param File|UploadedFile|string|array|null $file The file to store or an array of options.
     * @param string|array|null $name         Custom file name or additional options.
     * @param mixed $options                  Additional storage options.
     *
     * @return string|false                   The stored file path on success, or false on failure.
     */
    public function putFileAs($path, $file, $name = null, $options = [])
    {
        // Handle cases where the name is null or options are provided as an array
        if ($name === null || is_array($name)) {
            [$path, $file, $name, $options] = ['', $path, $file, $name ?? []];
        }

        // Open the file stream for reading
        $stream = fopen(is_string($file) ? $file : $file->getRealPath(), 'r');

        // Format the file path and store the file using a stream
        $result = $this->put(
            $path = trim($path . '/' . $name, '/'),
            $stream,
            $options,
        );

        // Close the stream after writing
        if (is_resource($stream)) {
            fclose($stream);
        }

        return $result ? $path : false;
    }

    /**
     * Get the visibility of the given path.
     *
     * This method checks the visibility of a file or directory at the specified path.
     * It returns whether the file or directory is publicly or privately visible.
     *
     * @param string $path The file or directory path.
     *
     * @return string Visibility status (either public or private).
     */
    public function getVisibility($path): string
    {
        // Check the visibility of the file and return the corresponding visibility constant.
        return $this->driver->visibility($path) === Visibility::PUBLIC
            ? FilesystemInterface::VISIBILITY_PUBLIC
            : FilesystemInterface::VISIBILITY_PRIVATE;
    }

    /**
     * Set the visibility of the given path.
     *
     * This method allows you to set the visibility (public or private) of a file or directory.
     * If the visibility change fails, the method returns false. If configured to throw exceptions,
     * an exception will be thrown on failure.
     *
     * @param string $path        The file or directory path.
     * @param string $visibility  Desired visibility (either public or private).
     *
     * @return bool True on success, false on failure.
     */
    public function setVisibility($path, $visibility): bool
    {
        try {
            // Set the visibility using the parsed visibility value.
            $this->driver->setVisibility($path, $this->parseVisibility($visibility));
        } catch (UnableToSetVisibility $e) {
            // If an error occurs, either throw the exception or return false depending on configuration.
            throw_if($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    /**
     * Prepend data to a file.
     *
     * This method prepends data to an existing file. If the file does not exist, it creates
     * the file with the specified data. A separator can be specified between the new data
     * and the existing content.
     *
     * @param string $path The file path.
     * @param string $data Data to prepend.
     * @param string $separator Separator between prepended data and existing content.
     *
     * @return bool True on success, false on failure.
     */
    public function prepend($path, $data, $separator = PHP_EOL): bool
    {
        if ($this->fileExists($path)) {
            // If the file exists, prepend the data to the beginning.
            return $this->put($path, $data . $separator . $this->get($path));
        }

        // If the file does not exist, create it with the provided data.
        return $this->put($path, $data);
    }

    /**
     * Append data to a file.
     *
     * This method appends data to an existing file. If the file does not exist, it creates
     * the file with the specified data. A separator can be specified between the existing
     * content and the new appended data.
     *
     * @param string $path      The file path.
     * @param string $data      Data to append.
     * @param string $separator Separator between existing content and appended data.
     *
     * @return bool             True on success, false on failure.
     */
    public function append($path, $data, $separator = PHP_EOL): bool
    {
        if ($this->fileExists($path)) {
            // If the file exists, append the data to the end.
            return $this->put($path, $this->get($path) . $separator . $data);
        }

        // If the file does not exist, create it with the provided data.
        return $this->put($path, $data);
    }

    /**
     * Copy a file to a new location.
     *
     * This method copies a file from the source path to the destination path. It handles
     * any errors that occur during the copy process and returns false if an error occurs
     * and exceptions are disabled in the configuration.
     *
     * @param string $from Source file path.
     * @param string $to   Destination file path.
     *
     * @return bool True on success, false on failure.
     */
    public function copy($from, $to): bool
    {
        try {
            // Attempt to copy the file to the new location.
            $this->driver->copy($from, $to);
        } catch (UnableToCopyFile $e) {
            // If an error occurs, either throw the exception or return false based on configuration.
            throw_if($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    /**
     * Move a file to a new location.
     *
     * This method moves a file from the source path to the destination path. If an error
     * occurs during the move operation, it will return false unless exceptions are enabled.
     *
     * @param string $from Source file path.
     * @param string $to   Destination file path.
     *
     * @return bool        True on success, false on failure.
     */
    public function move($from, $to): bool
    {
        try {
            // Attempt to move the file to the new location.
            $this->driver->move($from, $to);
        } catch (UnableToMoveFile $e) {
            // If an error occurs, either throw the exception or return false depending on configuration.
            throw_if($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    /**
     * Get the file size of a given file in bytes.
     *
     * @param string $path The path to the file whose size is to be retrieved.
     *
     * @return int The size of the file in bytes.
     */
    public function size($path): int
    {
        // Use the driver to retrieve the file size for the given path.
        return $this->driver->fileSize($path);
    }

    /**
     * Get the checksum for a file using a specified algorithm.
     *
     * @param string $path The path to the file.
     * @param array $options Options for computing the checksum (e.g., algorithm).
     *
     * @throws UnableToProvideChecksum If the checksum cannot be generated.
     *
     * @return string|false The checksum as a string, or false if unable to compute it.
     */
    public function checksum($path, $options = []): string|false
    {
        try {
            // Retrieve the checksum for the given file using the specified options.
            return $this->driver->checksum($path, $options);
        } catch (UnableToProvideChecksum $e) {
            // If an exception is thrown, rethrow it or return false based on configuration.
            throw_if($this->throwsExceptions(), $e);

            return false;
        }
    }

    /**
     * Get the MIME type of a given file.
     *
     * @param string $path The path to the file.
     *
     * @throws UnableToRetrieveMetadata If the MIME type cannot be retrieved.
     *
     * @return string|false The MIME type of the file, or false if unable to retrieve it.
     */
    public function mimeType($path): string|false
    {
        try {
            // Use the driver to retrieve the MIME type of the file.
            return $this->driver->mimeType($path);
        } catch (UnableToRetrieveMetadata $e) {
            // Handle the exception based on the configuration.
            throw_if($this->throwsExceptions(), $e);
        }

        return false;
    }

    /**
     * Get the last modification time of a given file.
     *
     * @param string $path The path to the file.
     *
     * @return int The Unix timestamp of the last modification time.
     */
    public function lastModified($path): int
    {
        // Retrieve the last modified timestamp for the file.
        return $this->driver->lastModified($path);
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path The path to the file to be read.
     *
     * @throws UnableToReadFile If the file cannot be read.
     *
     * @return resource|false The file resource if successful, or false on failure.
     */
    public function readStream($path): mixed
    {
        try {
            // Attempt to open the file as a readable stream.
            return $this->driver->readStream($path);
        } catch (UnableToReadFile $e) {
            // Handle exceptions according to the configured behavior.
            throw_if($this->throwsExceptions(), $e);
        }
    }

    /**
     * Write data to a file using a stream.
     *
     * @param string $path The path where the file will be written.
     * @param resource $resource The data to be written as a stream.
     * @param array $options Additional options for writing the file.
     *
     * @throws UnableToWriteFile|UnableToSetVisibility If the file cannot be written or its visibility cannot be set.
     *
     * @return bool True if the file is successfully written, or false on failure.
     */
    public function writeStream($path, $resource, array $options = []): bool
    {
        try {
            // Write the resource stream to the specified path with the provided options.
            $this->driver->writeStream($path, $resource, $options);
        } catch (UnableToWriteFile|UnableToSetVisibility $e) {
            // Handle exceptions according to the configured behavior.
            throw_if($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    /**
     * Get the URL for the file at the given path.
     *
     * This method constructs and returns the URL for a file, taking into account
     * the configuration prefix and checking the appropriate methods of the adapter
     * and driver to retrieve the URL. It supports various adapters like FTP, SFTP, and Local.
     *
     * @param  string  $path The path to the file.
     *
     * @throws RuntimeException If the driver does not support retrieving URLs.
     *
     * @return string The URL of the file.
     */
    public function url($path): string
    {
        // If a prefix is configured, prepend it to the given file path.
        if (isset($this->config['prefix'])) {
            $path = $this->concatPathToUrl($this->config['prefix'], $path);
        }

        // Retrieve the adapter instance.
        $adapter = $this->adapter;

        // Check if the adapter supports URL retrieval.
        if (method_exists($adapter, 'getUrl')) {
            return $adapter->getUrl($path);
        }

        // Fallback to checking if the driver supports URL retrieval.
        if (method_exists($this->driver, 'getUrl')) {
            return $this->driver->getUrl($path);
        }

        // Handle specific adapter types (FTP and SFTP).
        if ($adapter instanceof FtpAdapter || $adapter instanceof SftpAdapter) {
            return $this->getFtpUrl($path);
        }

        // Handle the case for local files.
        if ($adapter instanceof LocalAdapter) {
            return $this->getLocalUrl($path);
        }

        // If no URL retrieval method is supported, throw an exception.
        throw new RuntimeException('This driver does not support retrieving URLs.');
    }

    /**
     * Determine if temporary URLs can be generated for the current adapter.
     *
     * This method checks whether the adapter or a custom callback supports the
     * generation of temporary URLs.
     *
     * @return bool True if temporary URLs can be generated, false otherwise.
     */
    public function providesTemporaryUrls(): bool
    {
        // Check if the adapter has a method to generate temporary URLs or if a custom callback is available.
        return method_exists($this->adapter, 'getTemporaryUrl') || isset($this->temporaryUrlCallback);
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * This method generates a temporary URL for a file, typically used for granting
     * temporary access to a file with an expiration time. It supports custom callbacks
     * if the adapter does not provide this functionality natively.
     *
     * @param  string  $path The path to the file.
     * @param  DateTimeInterface  $expiration The expiration time of the temporary URL.
     * @param  array  $options Additional options for generating the temporary URL.
     *
     * @throws RuntimeException If the driver does not support creating temporary URLs.
     *
     * @return string The temporary URL for the file.
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        // If the adapter supports generating a temporary URL, use it.
        if (method_exists($this->adapter, 'getTemporaryUrl')) {
            return $this->adapter->getTemporaryUrl($path, $expiration, $options);
        }

        // If a custom callback is set for generating temporary URLs, invoke it.
        if ($this->temporaryUrlCallback) {
            return $this->temporaryUrlCallback->bindTo($this, static::class)(
                $path,
                $expiration,
                $options
            );
        }

        // If no method is found, throw an exception.
        throw new RuntimeException('This driver does not support creating temporary URLs.');
    }

    /**
     * Get a temporary upload URL for the file at the given path.
     *
     * This method generates a temporary URL specifically for uploading a file. It is
     * typically used for situations where files need to be uploaded temporarily with
     * a time-limited URL.
     *
     * @param  string  $path The path to the file.
     * @param  DateTimeInterface  $expiration The expiration time of the temporary upload URL.
     * @param  array  $options Additional options for generating the temporary upload URL.
     *
     * @throws RuntimeException If the driver does not support creating temporary upload URLs.
     *
     * @return array An array containing the temporary upload URL and other related information.
     */
    public function temporaryUploadUrl(string $path, DateTimeInterface $expiration, array $options = []): array
    {
        // Check if the adapter supports generating temporary upload URLs.
        if (method_exists($this->adapter, 'temporaryUploadUrl')) {
            return $this->adapter->temporaryUploadUrl($path, $expiration, $options);
        }

        // If not, throw an exception as temporary upload URL creation is unsupported.
        throw new RuntimeException('This driver does not support creating temporary upload URLs.');
    }

    /**
     * Get an array of all files in a directory.
     *
     * This method returns a list of all files in a specified directory, with the option
     * to include files from subdirectories (recursive listing). It also filters out
     * directories, ensuring only files are returned.
     *
     * @param  string|null  $directory The directory to list files from. If null, lists files from the root.
     * @param  bool  $recursive Whether to include files from subdirectories.
     *
     * @return array An array of file paths.
     */
    public function files($directory = null, $recursive = false): array
    {
        // List contents of the specified directory (or root if no directory is provided).
        return $this->driver->listContents($directory ?? '', $recursive)
            // Filter the list to only include files, excluding directories.
            ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())
            // Sort the files by their path.
            ->sortByPath()
            // Map the attributes to only return the file paths.
            ->map(fn(StorageAttributes $attributes) => $attributes->path())
            // Convert the result to an array.
            ->toArray();
    }

    /**
     * Get all of the files from the given directory (recursive).
     *
     * This method retrieves all files from the specified directory, including those
     * within subdirectories. It internally calls the `files` method with the `recursive`
     * parameter set to true to fetch files from all levels.
     *
     * @param  string|null  $directory The directory to list files from. If null, the root directory is used.
     *
     * @return array An array of file paths.
     */
    public function allFiles($directory = null): array
    {
        // Fetch all files from the directory and its subdirectories.
        return $this->files($directory, true);
    }

    /**
     * Get all of the directories within a given directory.
     *
     * This method retrieves all directories within the specified directory, with the
     * option to include subdirectories recursively. It uses the `listContents` method
     * to get the directory structure and filters only directories.
     *
     * @param  string|null  $directory The directory to list directories from. If null, the root directory is used.
     * @param  bool  $recursive Whether to list subdirectories recursively. Defaults to false.
     *
     * @return array An array of directory paths.
     */
    public function directories($directory = null, $recursive = false): array
    {
        // List contents and filter only directories.
        return $this->driver->listContents($directory ?? '', $recursive)
            ->filter(fn(StorageAttributes $attributes) => $attributes->isDir())
            ->map(fn(StorageAttributes $attributes) => $attributes->path())
            ->toArray();
    }

    /**
     * Get all the directories within a given directory (recursive).
     *
     * This method retrieves all directories recursively from the specified directory.
     * It calls the `directories` method with the `recursive` parameter set to true.
     *
     * @param  string|null  $directory The directory to list directories from. If null, the root directory is used.
     *
     * @return array An array of directory paths.
     */
    public function allDirectories($directory = null): array
    {
        // Fetch all directories from the directory and its subdirectories.
        return $this->directories($directory, true);
    }

    /**
     * Create a directory.
     *
     * This method attempts to create a new directory at the given path. If an error occurs
     * during directory creation (e.g., insufficient permissions), it catches the exception
     * and optionally throws it based on the configuration.
     *
     * @param  string  $path The path where the directory should be created.
     *
     * @return bool True if the directory was created successfully, false otherwise.
     */
    public function makeDirectory($path): bool
    {
        try {
            // Attempt to create the directory using the driver.
            $this->driver->createDirectory($path);
        } catch (UnableToCreateDirectory|UnableToSetVisibility $e) {
            // If directory creation fails, throw exception if configured to do so.
            throw_if($this->throwsExceptions(), $e);

            return false;
        }

        // Return true if the directory was created without errors.
        return true;
    }

    /**
     * Recursively delete a directory.
     *
     * This method attempts to delete the specified directory and its contents. If any errors
     * occur during deletion, the exception is caught, and depending on the configuration,
     * it may throw an exception.
     *
     * @param  string  $directory The directory to delete.
     *
     * @return bool True if the directory was deleted successfully, false otherwise.
     */
    public function deleteDirectory($directory): bool
    {
        try {
            // Attempt to delete the directory using the driver.
            $this->driver->deleteDirectory($directory);
        } catch (UnableToDeleteDirectory $e) {
            // If directory deletion fails, throw exception if configured to do so.
            throw_if($this->throwsExceptions(), $e);

            return false;
        }

        // Return true if the directory was deleted without errors.
        return true;
    }

    /**
     * Get the Flysystem driver.
     *
     * This method returns the current Flysystem driver instance, which is used to interact
     * with the underlying storage system (e.g., local, FTP, SFTP).
     *
     * @return FilesystemOperator The Flysystem driver instance.
     */
    public function getDriver(): FilesystemOperator
    {
        return $this->driver;
    }

    /**
     * Get the Flysystem adapter.
     *
     * This method returns the current Flysystem adapter, which is responsible for interacting
     * with the storage medium, whether local, FTP, or other supported types.
     *
     * @return FlysystemAdapter The Flysystem adapter instance.
     */
    public function getAdapter(): FlysystemAdapter
    {
        return $this->adapter;
    }

    /**
     * Get the configuration values.
     *
     * This method returns the configuration settings for the current file storage setup.
     * This can include things like prefix paths, visibility settings, and other parameters.
     *
     * @return array An array of configuration values.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Define a custom callback that generates file download responses.
     *
     * This method allows the user to define a custom callback function that will be
     * used to generate download responses for files. The callback should handle the
     * logic for creating the response when a file is requested for download.
     *
     * @param  Closure  $callback The callback function to handle file download responses.
     *
     * @return void
     */
    public function serveUsing(Closure $callback): void
    {
        $this->serveCallback = $callback;
    }

    /**
     * Define a custom temporary URL builder callback.
     *
     * This method allows the user to define a custom callback that will be used to
     * generate temporary URLs for files. The callback should return the temporary URL
     * for a given file path and expiration time.
     *
     * @param  Closure  $callback The callback function to build temporary URLs.
     *
     * @return void
     */
    public function buildTemporaryUrlsUsing(Closure $callback): void
    {
        $this->temporaryUrlCallback = $callback;
    }

    /**
     * Convert the string to ASCII characters that are equivalent to the given name.
     *
     * This method converts a given string into ASCII characters, removing any special characters
     * that may not be supported in ASCII. It utilizes the `Str::ascii` method to convert the string
     * and removes any '%' characters from the resulting string.
     *
     * @param  string  $name The name or string to be converted to ASCII.
     *
     * @return string The ASCII-encoded string without special characters.
     */
    protected function fallbackName($name): string
    {
        // Convert the name to ASCII and remove any '%' characters.
        return str_replace('%', '', Str::ascii($name));
    }

    /**
     * Get the URL for the file at the given path for FTP storage.
     *
     * This method generates a URL for the given file path using FTP configuration. If a base URL
     * is defined in the configuration, it concatenates the path with the base URL. Otherwise, it
     * simply returns the path as-is.
     *
     * @param  string  $path The path to the file.
     *
     * @return string The generated URL for the file.
     */
    protected function getFtpUrl($path): string
    {
        // If a base URL is set in the configuration, concatenate it with the file path.
        return isset($this->config['url'])
                ? $this->concatPathToUrl($this->config['url'], $path)
                : $path;
    }

    /**
     * Get the URL for the file at the given path for local storage.
     *
     * This method generates a URL for the given file path using local storage configuration.
     * If a base URL is provided in the configuration, it will be used as the base URL for the
     * generated URL. Otherwise, a default `/storage/` path will be used. The method also handles
     * edge cases such as paths containing the string `storage/public`.
     *
     * @param  string  $path The path to the file.
     *
     * @return string The generated URL for the file.
     */
    protected function getLocalUrl($path): string
    {
        // Check if a base URL is defined in the configuration.
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $path);
        }

        // Default path if no base URL is defined.
        $path = '/storage/' . $path;

        // Remove '/public/' from the path if it exists, to handle cases where the path
        // should refer to a public disk instead of the default 'storage' disk.
        if (str_contains($path, '/storage/public/')) {
            return Str::replaceFirst('/public/', '/', $path);
        }

        return $path;
    }

    /**
     * Concatenate a path to a URL.
     *
     * This method ensures that a given path is correctly appended to a URL. It trims the
     * trailing slash from the URL and the leading slash from the path to avoid double slashes
     * in the final URL.
     *
     * @param  string  $url The base URL.
     * @param  string  $path The path to append to the URL.
     *
     * @return string The concatenated URL with the path appended.
     */
    protected function concatPathToUrl($url, $path): string
    {
        // Concatenate the URL and path, ensuring no double slashes occur.
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Replace the scheme, host, and port of the given UriInterface with values from the given URL.
     *
     * This method modifies the URI by extracting the scheme, host, and port from the given URL
     * and replacing the corresponding components of the `UriInterface`.
     *
     * @param  UriInterface  $uri The URI to modify.
     * @param  string  $url The new URL to extract the scheme, host, and port from.
     *
     * @return UriInterface The modified URI with the new scheme, host, and port.
     */
    protected function replaceBaseUrl($uri, $url): UriInterface
    {
        // Parse the provided URL to extract its components.
        $parsed = parse_url($url);

        // Return the URI with the updated scheme, host, and port.
        return $uri
            ->withScheme($parsed['scheme'])
            ->withHost($parsed['host'])
            ->withPort($parsed['port'] ?? null);
    }

    /**
     * Parse the given visibility value.
     *
     * This method parses a visibility string and converts it to the appropriate constant value
     * based on the provided visibility configuration. It throws an exception if the visibility
     * value is unknown.
     *
     * @param  string|null  $visibility The visibility value to parse.
     *
     * @throws InvalidArgumentException If the visibility value is unknown.
     *
     * @return string|null The parsed visibility value (either public or private), or null.
     */
    protected function parseVisibility($visibility)
    {
        if ($visibility === null) {
            // Return null if no visibility is provided.
            return;
        }

        // Match the visibility value to the appropriate constant.
        return match ($visibility) {
            FilesystemInterface::VISIBILITY_PUBLIC => Visibility::PUBLIC,
            FilesystemInterface::VISIBILITY_PRIVATE => Visibility::PRIVATE,
            default => throw new InvalidArgumentException("Unknown visibility: {$visibility}."),
        };
    }

    /**
     * Determine if Flysystem exceptions should be thrown.
     *
     * This method checks the configuration setting to determine if exceptions
     * from Flysystem should be thrown. If the 'throw' key in the configuration
     * is set to true, this method will return true, indicating that exceptions
     * should be thrown; otherwise, it returns false.
     *
     * @return bool `true` if exceptions should be thrown, `false` otherwise.
     */
    protected function throwsExceptions(): bool
    {
        // Return whether the 'throw' configuration is true (casting it to a boolean).
        return (bool)($this->config['throw'] ?? false);
    }

    /**
     * Pass dynamic method calls onto Flysystem.
     *
     * This method is used to handle dynamic method calls that are forwarded to
     * the underlying Flysystem driver. If the method being called is a macro,
     * it will be passed to the macro handler. Otherwise, the method is invoked
     * directly on the Flysystem driver.
     *
     * @param  string  $method The name of the method being called.
     * @param  array  $parameters The parameters to pass to the method.
     *
     * @throws BadMethodCallException If the method does not exist on the Flysystem driver.
     *
     * @return mixed The result of the method call on the Flysystem driver or macro.
     */
    public function __call($method, $parameters)
    {
        // If the method is a macro, handle it using the macroCall method.
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        // Otherwise, forward the method call to the Flysystem driver.
        return $this->driver->{$method}(...$parameters);
    }
}
