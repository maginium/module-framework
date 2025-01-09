<?php

declare(strict_types=1);

namespace Maginium\Framework\Filesystem\Facades;

use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Config\Interfaces\ConfigInterface;
use Maginium\Framework\Filesystem\Interfaces\FactoryInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facade;

/**
 * @method static \Maginium\Framework\Filesystem\Interfaces\FilesystemInterface drive(string|null $name = null)
 * @method static \Maginium\Framework\Filesystem\Interfaces\FilesystemInterface disk(string|null $name = null)
 * @method static \Maginium\Framework\Filesystem\Interfaces\CloudInterface cloud()
 * @method static \Maginium\Framework\Filesystem\Interfaces\FilesystemInterface build(string|array $config)
 * @method static \Maginium\Framework\Filesystem\Interfaces\FilesystemInterface createLocalDriver(array $config, string $name = 'local')
 * @method static \Maginium\Framework\Filesystem\Interfaces\FilesystemInterface createFtpDriver(array $config)
 * @method static \Maginium\Framework\Filesystem\Interfaces\FilesystemInterface createSftpDriver(array $config)
 * @method static \Maginium\Framework\Filesystem\Interfaces\CloudInterface createS3Driver(array $config)
 * @method static \Maginium\Framework\Filesystem\Interfaces\FilesystemInterface createScopedDriver(array $config)
 * @method static \Maginium\Framework\Filesystem\FilesystemManager set(string $name, mixed $disk)
 * @method static string getDefaultDriver()
 * @method static string getDefaultCloudDriver()
 * @method static \Maginium\Framework\Filesystem\FilesystemManager forgetDisk(array|string $disk)
 * @method static void purge(string|null $name = null)
 * @method static \Maginium\Framework\Filesystem\FilesystemManager extend(string $driver, \Closure $callback)
 * @method static \Maginium\Framework\Filesystem\FilesystemManager setApplication(\Illuminate\Contracts\Foundation\Application $app)
 * @method static string path(string $path)
 * @method static bool exists(string $path)
 * @method static string|null get(string $path)
 * @method static resource|null readStream(string $path)
 * @method static bool put(string $path, \Psr\Http\Message\StreamInterface|\Illuminate\Http\File|\Illuminate\Http\UploadedFile|string|resource $contents, mixed $options = [])
 * @method static string|false putFile(\Illuminate\Http\File|\Illuminate\Http\UploadedFile|string $path, \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string|array|null $file = null, mixed $options = [])
 * @method static string|false putFileAs(\Illuminate\Http\File|\Illuminate\Http\UploadedFile|string $path, \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string|array|null $file, string|array|null $name = null, mixed $options = [])
 * @method static bool writeStream(string $path, resource $resource, array $options = [])
 * @method static string getVisibility(string $path)
 * @method static bool setVisibility(string $path, string $visibility)
 * @method static bool prepend(string $path, string $data)
 * @method static bool append(string $path, string $data)
 * @method static bool delete(string|array $paths)
 * @method static bool copy(string $from, string $to)
 * @method static bool move(string $from, string $to)
 * @method static int size(string $path)
 * @method static int lastModified(string $path)
 * @method static array files(string|null $directory = null, bool $recursive = false)
 * @method static array allFiles(string|null $directory = null)
 * @method static array directories(string|null $directory = null, bool $recursive = false)
 * @method static array allDirectories(string|null $directory = null)
 * @method static bool makeDirectory(string $path)
 * @method static bool deleteDirectory(string $directory)
 * @method static \Illuminate\Filesystem\FilesystemAdapter assertExists(string|array $path, string|null $content = null)
 * @method static \Illuminate\Filesystem\FilesystemAdapter assertCount(string $path, int $count, bool $recursive = false)
 * @method static \Illuminate\Filesystem\FilesystemAdapter assertMissing(string|array $path)
 * @method static \Illuminate\Filesystem\FilesystemAdapter assertDirectoryEmpty(string $path)
 * @method static bool missing(string $path)
 * @method static bool fileExists(string $path)
 * @method static bool fileMissing(string $path)
 * @method static bool directoryExists(string $path)
 * @method static bool directoryMissing(string $path)
 * @method static array|null json(string $path, int $flags = 0)
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse response(string $path, string|null $name = null, array $headers = [], string|null $disposition = 'inline')
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse serve(\Illuminate\Http\Request $request, string $path, string|null $name = null, array $headers = [])
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse download(string $path, string|null $name = null, array $headers = [])
 * @method static string|false checksum(string $path, array $options = [])
 * @method static string|false mimeType(string $path)
 * @method static string url(string $path)
 * @method static bool providesTemporaryUrls()
 * @method static string temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = [])
 * @method static array temporaryUploadUrl(string $path, \DateTimeInterface $expiration, array $options = [])
 * @method static \League\Flysystem\FilesystemOperator getDriver()
 * @method static \League\Flysystem\FilesystemAdapter getAdapter()
 * @method static array getConfig()
 * @method static void serveUsing(\Closure $callback)
 * @method static void buildTemporaryUrlsUsing(\Closure $callback)
 * @method static \Illuminate\Filesystem\FilesystemAdapter|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Illuminate\Filesystem\FilesystemAdapter|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static bool has(string $location)
 * @method static string read(string $location)
 * @method static \League\Flysystem\DirectoryListing listContents(string $location, bool $deep = false)
 * @method static int fileSize(string $path)
 * @method static string visibility(string $path)
 * @method static void write(string $location, string $contents, array $config = [])
 * @method static void createDirectory(string $location, array $config = [])
 *
 * @see FilesystemManager
 */
class Storage extends Facade
{
    /**
     * Get the root path of the given disk.
     *
     * This method constructs and returns the root path where the specified disk's files are stored.
     * The path is formed by combining the base storage path with a subdirectory specific to the disk.
     *
     * @param  string  $disk The name of the disk whose root path is to be retrieved.
     *
     * @return string The root path of the disk, including the storage path and disk-specific subdirectory.
     */
    protected static function getRootPath(string $disk): string
    {
        // Return the full path to the disk's root directory within the 'framework/testing/disks' directory.
        return storage_path('framework/testing/disks/' . $disk);
    }

    /**
     * Assemble the configuration of the given disk.
     *
     * This method combines the provided configuration array with the original configuration
     * for the given disk. It also adds the root path for the disk and other configurations
     * like the 'throw' flag, which determines if exceptions should be thrown on errors.
     *
     * @param  string  $disk The name of the disk whose configuration is to be built.
     * @param  array  $config The custom configuration to merge with the default disk configuration.
     * @param  string  $root The root directory for the disk to be set in the configuration.
     *
     * @return array The merged disk configuration array, including the custom settings, root path, and 'throw' flag.
     */
    protected static function buildDiskConfiguration(string $disk, array $config, string $root): array
    {
        // Retrieve the original configuration for the given disk from the global configuration, or an empty array if not found.
        $originalConfig = static::resolve(ConfigInterface::class)::driver(ConfigDrivers::DEPLOYMENT)->get("filesystems.{$disk}", []);

        // Merge the original configuration with the provided custom configuration and the root path.
        return Arr::merge(
            [
                // Add 'throw' setting from the original configuration or default to false if not set.
                'throw' => $originalConfig['throw'] ?? false,
            ],
            $config,  // Merge the provided custom configuration.
            [
                // Add the 'root' setting to the configuration with the provided root path.
                'root' => $root,
            ],
        );
    }

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
        return FactoryInterface::class;
    }
}
