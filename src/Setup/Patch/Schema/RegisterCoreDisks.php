<?php

declare(strict_types=1);

namespace Maginium\Framework\Setup\Patch\Schema;

use Magento\Framework\Config\File\ConfigFilePool;
use Maginium\Framework\Database\Interfaces\RevertablePatchInterface;
use Maginium\Framework\Database\Setup\Migration\Migration;

/**
 * Class RegisterCoreDisks.
 *
 * This class registers filesystem disks in the deployment configuration and manages
 * module states. It implements the RevertablePatchInterface for migration rollback capability.
 */
class RegisterCoreDisks extends Migration implements RevertablePatchInterface
{
    /**
     * Array of modules to disable or enable during migration.
     *
     * @var array<string, int>
     */
    protected array $modules = [
        'Amasty_Geoip' => 0,
    ];

    /**
     * Executes the process of updating deployment configuration by registering filesystem disks
     * and disabling specified modules.
     *
     * @return void
     */
    public function up(): void
    {
        $filesystemsConfig = $this->getFilesystemsConfig();

        // Combine configurations
        $config = [
            ConfigFilePool::APP_ENV => [
                'filesystems' => $filesystemsConfig,
            ],
        ];

        // Save the combined configuration into the deployment config
        $this->context->getDeploymentConfig()->saveConfig($config);
    }

    /**
     * Rolls back the changes applied in the `up` method by enabling the modules again.
     *
     * @return void
     */
    public function down(): void
    {
        // Combine configurations
        $config = [
            ConfigFilePool::APP_ENV => [
                'filesystems' => [],
            ],
        ];

        // Save the combined configuration into the deployment config
        $this->context->getDeploymentConfig()->saveConfig($config);
    }

    /**
     * Returns the full configuration array for the filesystem disks.
     *
     * This method assembles all the disk configurations into a single associative array
     * where each key represents the disk name (e.g., 'default', 'cloud', 'local') and
     * the value is the corresponding configuration array. The method uses other
     * private methods to retrieve the configurations for specific disks, ensuring a clean
     * and modular implementation.
     *
     * @return array<string, mixed> Associative array containing disk configurations.
     */
    private function getFilesystemsConfig(): array
    {
        return [
            'default' => env('FILESYSTEM_DISK', 'local'), // Default disk used for storage
            'cloud' => env('FILESYSTEM_CLOUD', default: 's3'), // Cloud disk configuration (e.g., S3)
            'local' => $this->getLocalDiskConfig(), // Local disk configuration
            'public' => $this->getPublicDiskConfig(), // Public disk configuration
            'uploads' => $this->getUploadsDiskConfig(), // Uploads disk configuration
            'media' => $this->getMediaDiskConfig(), // Media disk configuration
            's3' => $this->getS3DiskConfig(), // S3 cloud storage configuration
        ];
    }

    /**
     * Returns the configuration for the local disk.
     *
     * This method defines the configuration for the 'local' disk, which is typically
     * used to store files on the server's filesystem. It specifies the driver type,
     * the root directory for storage, and the exception-handling behavior for
     * filesystem operations.
     *
     * @return array<string, mixed> Configuration array for the local disk.
     */
    private function getLocalDiskConfig(): array
    {
        return [
            'driver' => 'local', // Use the local filesystem driver
            'root' => storage_path(), // Root directory for file storage
            'throw' => false, // Disable exception throwing on errors
        ];
    }

    /**
     * Returns the configuration for the public disk.
     *
     * This method provides the configuration for the 'public' disk, used for storing
     * publicly accessible files. It specifies the driver type, storage location,
     * public URL, and visibility settings, allowing public access to stored files.
     *
     * @return array<string, mixed> Configuration array for the public disk.
     */
    private function getPublicDiskConfig(): array
    {
        return [
            'driver' => 'local', // Use the local filesystem driver
            'root' => storage_path('public'), // Directory for public storage
            'url' => env('APP_URL') . '/pub', // Base URL for public files
            'visibility' => 'public', // Set visibility to public
            'throw' => false, // Disable exception throwing on errors
        ];
    }

    /**
     * Returns the configuration for the uploads disk.
     *
     * This method defines the configuration for the 'uploads' disk, used for storing
     * user-uploaded files. It specifies the driver type, root directory, URL for
     * accessing files, and public visibility.
     *
     * @return array<string, mixed> Configuration array for the uploads disk.
     */
    private function getUploadsDiskConfig(): array
    {
        return [
            'driver' => 'local', // Use the local filesystem driver
            'root' => storage_path('uploads'), // Directory for uploads storage
            'url' => '/pub/uploads', // Base URL for accessing uploaded files
            'visibility' => 'public', // Set visibility to public
            'throw' => false, // Disable exception throwing on errors
        ];
    }

    /**
     * Returns the configuration for the media disk.
     *
     * This method provides the configuration for the 'media' disk, used for storing
     * media files such as images or videos. It specifies the driver type, storage
     * location, and public access settings.
     *
     * @return array<string, mixed> Configuration array for the media disk.
     */
    private function getMediaDiskConfig(): array
    {
        return [
            'driver' => 'local', // Use the local filesystem driver
            'root' => storage_path('media'), // Directory for media storage
            'url' => '/pub/media', // Base URL for accessing media files
            'visibility' => 'public', // Set visibility to public
            'throw' => false, // Disable exception throwing on errors
        ];
    }

    /**
     * Returns the configuration for the S3 disk.
     *
     * This method provides the configuration for the 's3' disk, which integrates
     * with Amazon S3 cloud storage. It includes the S3 access credentials, region,
     * bucket details, and additional optional settings for endpoint customization
     * and path-style usage.
     *
     * @return array<string, mixed> Configuration array for the S3 disk.
     */
    private function getS3DiskConfig(): array
    {
        return [
            'driver' => 's3', // Use the S3 cloud storage driver
            'key' => env('AWS_ACCESS_KEY_ID'), // AWS access key ID
            'secret' => env('AWS_SECRET_ACCESS_KEY'), // AWS secret access key
            'region' => env('AWS_DEFAULT_REGION'), // AWS region
            'bucket' => env('S3_BUCKET'), // S3 bucket name
            'url' => env('S3_URL'), // Base URL for S3 bucket
            'endpoint' => env('AWS_ENDPOINT'), // Custom endpoint for S3
            'use_path_style_endpoint' => env('S3_USE_PATH_STYLE_ENDPOINT', false), // Use path-style URLs
            'throw' => true, // Enable exception throwing on errors
        ];
    }
}
