<?php

declare(strict_types=1);

namespace Maginium\Framework\Media;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\View\Asset\Repository;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Foundation\Exceptions\NotFoundException;
use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Media\Interfaces\MediaInterface;
use Maginium\Framework\Resize\Resizer;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\StoreManager;
use Maginium\Framework\Support\Path;
use Maginium\Framework\Support\Str;
use Maginium\Store\Models\Store;

/**
 * Class MediaManager.
 *
 * Handles media file management, including uploading and retrieving asset URLs.
 */
class MediaManager implements MediaInterface
{
    /**
     * Asset repository for fetching asset URLs.
     *
     * @var Repository
     */
    protected Repository $assetRepository;

    /**
     * Factory for handling file uploads.
     *
     * @var UploaderFactory
     */
    protected UploaderFactory $uploaderFactory;

    /**
     * Media directory for file storage operations.
     *
     * @var WriteInterface
     */
    protected WriteInterface $mediaDirectory;

    /**
     * Base URL for accessing uploaded files.
     *
     * @var string
     */
    protected string $fileUrl;

    /**
     * MediaManager constructor.
     *
     * @param Repository $assetRepository The asset repository instance for URL management.
     * @param UploaderFactory $uploaderFactory Factory for creating file upload handlers.
     * @param Filesystem $filesystem Filesystem instance for accessing the media directory.
     *
     * @throws LocalizedException If the media directory is not writable.
     */
    public function __construct(
        Repository $assetRepository,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
    ) {
        $this->assetRepository = $assetRepository;
        $this->uploaderFactory = $uploaderFactory;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->fileUrl = $this->mediaDirectory->getAbsolutePath();

        if (! $this->mediaDirectory->isWritable()) {
            throw LocalizedException::make(__('Media directory is not writable.'));
        }

        // Set Logger class name for logging purposes
        Log::setClassName(static::class);
    }

    /**
     * resize and/or crop an image to the given dimensions while preserving the options set for the image.
     *
     * @param int $newWidth The new width of the image.
     * @param int $newHeight The new height of the image.
     * @param array $options Additional resizing options, such as mode or offset.
     *
     * @return self Returns the instance with the resized (and optionally cropped) image.
     */
    public function resize(string $imageUrl, $newWidth, $newHeight, $options = []): Resizer
    {
        return Resizer::open($imageUrl)->resize($newWidth, $newHeight, $options);
    }

    /**
     * Crops an image from its center.
     *
     * @param int $cropStartX Start position on the X axis for the crop
     * @param int $cropStartY Start position on the Y axis for the crop
     * @param int $newWidth The desired width of the cropped image
     * @param int $newHeight The desired height of the cropped image
     * @param int $srcWidth Source width of the area to crop, defaults to $newWidth if not provided
     * @param int $srcHeight Source height of the area to crop, defaults to $newHeight if not provided
     */
    public function crop(string $imageUrl, $cropStartX, $cropStartY, $newWidth, $newHeight, $srcWidth = null, $srcHeight = null): Resizer
    {
        return Resizer::open($imageUrl)->crop($cropStartX, $cropStartY, $newWidth, $newHeight, $srcWidth, $srcHeight);
    }

    /**
     * Upload an image file.
     *
     * @param string $fileName The name of the file to upload.
     * @param bool $withPrefix Whether to include a prefix in the URL.
     *
     * @return string|null Uploaded image URL or null if upload fails.
     */
    public function upload(string $fileName, bool $withPrefix = false): ?string
    {
        try {
            // Create an uploader instance
            $uploader = $this->uploaderFactory->create(['fileId' => $fileName]);

            // Configure the uploader settings
            $uploader->setFilesDispersion(true);
            $uploader->setAllowRenameFiles(true);
            $uploader->setAllowCreateFolders(true);
            $uploader->setAllowedExtensions(self::ALLOWED_EXTENSIONS);

            // Get the media directory path
            $mediaDirectory = Filesystem::getDirectoryWrite(DirectoryList::MEDIA)->getAbsolutePath();

            // Save the uploaded image
            $image = $uploader->save($mediaDirectory);

            // Return the uploaded image URL
            return Path::join($this->baseUrl(), $this->_prepareFilePath($image['file'], $withPrefix));
        } catch (Exception $e) {
            // Log any exceptions that occur during the retrieval process
            Log::error(__('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));

            // Handle the exception, log, or return null based on your needs.
            return null;
        }
    }

    /**
     * Get the URL for the specified media file.
     *
     * @param string $file The name of the media file.
     * @param bool $withPrefix Whether to include a prefix in the URL.
     *
     * @throws NotFoundException If the media model does not exist.
     *
     * @return string The URL of the media file.
     */
    public function url(string $file, bool $withPrefix = false): string
    {
        return Path::join($this->baseUrl(), $this->_prepareFilePath($file, $withPrefix));
    }

    /**
     * Chainable method to specify the file URL.
     *
     * @param string $fileUrl The media file URL.
     *
     * @return MediaInterface
     */
    public function fromUrl(string $fileUrl): MediaInterface
    {
        // Normalize URLs by removing trailing slashes and the protocol (http:// or https://).
        $this->fileUrl = rtrim(parse_url($fileUrl, PHP_URL_PATH), '/');

        // Return the instance to allow method chaining.
        return $this;
    }

    /**
     * Set the path to be absolute or relative.
     *
     * @param bool $absolute Whether to return the absolute path.
     *
     * @return string|false
     */
    public function toAbsolute(): string|false
    {
        // Return the absolute path if it exists, or null if it doesn't.
        return $this->getPath(true);
    }

    /**
     * Get the absolute path for the specified media file.
     *
     * This method calls the general path method with the absolute flag set to true.
     *
     * @param string $file The media file name.
     * @param bool $withPrefix Whether to include a prefix in the path. Defaults to false.
     *
     * @throws NotFoundException If the media model does not exist.
     *
     * @return string The absolute file path for the media file.
     */
    public function absolutePath(string $file, bool $withPrefix = false): string
    {
        // Call the path method with the absolute flag set to true.
        return $this->path($file, $withPrefix, true);
    }

    /**
     * Get the relative path for the specified media file.
     *
     * This method calls the general path method with the absolute flag set to false.
     *
     * @param string $file The media file name.
     * @param bool $withPrefix Whether to include a prefix in the path. Defaults to false.
     *
     * @throws NotFoundException If the media model does not exist.
     *
     * @return string The relative file path for the media file.
     */
    public function relativePath(string $file, bool $withPrefix = false): string
    {
        // Call the path method with the absolute flag set to false.
        return $this->path($file, $withPrefix, false);
    }

    /**
     * Get the base URL for media assets.
     *
     * This method retrieves the base URL for media assets for the current store.
     *
     * @throws NotFoundException If the store does not exist.
     *
     * @return string The base URL for media assets.
     */
    public function baseUrl(): string
    {
        // Retrieve the current store instance.
        /** @var Store $store */
        $store = StoreManager::getStore();

        // Get the media base URL from the store's configuration.
        $mediaUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        // Parse and return the path component of the media URL, ensuring it's properly formatted.
        return Str::rtrim(parse_url($mediaUrl, PHP_URL_PATH), '/');
    }

    /**
     * Get the path based on the previously set URL.
     *
     * @param bool $absolute Whether to return the absolute path.
     *
     * @return string|null The file path, or null if the file does not exist.
     */
    private function getPath(bool $absolute = true): ?string
    {
        // Remove the base URL from the provided file URL to get the relative path.
        $relativePath = Str::replace($this->baseUrl(), '', $this->fileUrl);

        // Get the absolute file path based on the relative path.
        $absolutePath = $this->mediaDirectory->getAbsolutePath($relativePath);

        // If absolute path is requested, return the absolute path, or null if the file doesn't exist.
        if ($absolute) {
            return Filesystem::exists($absolutePath) ? $absolutePath : null;
        }

        // Otherwise, return the relative path if the file exists, or null if not.
        return Filesystem::exists($absolutePath) ? $relativePath : null;
    }

    /**
     * Get the media path (absolute or relative) for the specified file.
     *
     * This method determines whether to return the absolute or relative path
     * based on the $absolute parameter. It also handles including prefixes if needed.
     *
     * @param string $file The media file name.
     * @param bool $withPrefix Whether to include a prefix in the path.
     * @param bool $absolute Whether to return the absolute path (true) or relative path (false).
     *
     * @return string The media file path.
     */
    private function path(string $file, bool $withPrefix, bool $absolute): string
    {
        // Prepare the file path by possibly including a prefix.
        $preparedFile = $this->_prepareFilePath($file, $withPrefix);

        // Return the absolute or relative path based on the $absolute flag.
        return $absolute
            ? $this->mediaDirectory->getAbsolutePath($preparedFile)  // Absolute path if $absolute is true.
            : $this->mediaDirectory->getRelativePath($preparedFile);  // Relative path if $absolute is false.
    }

    /**
     * Prepares the file path for use.
     *
     * This method formats the provided file path to ensure it adheres to the
     * system's directory separator conventions and optionally includes a prefix.
     *
     * @param string $file The file path to prepare.
     * @param bool $withPrefix Whether to include a prefix in the file path.
     *
     * @return string The prepared file path, properly formatted.
     */
    private function _prepareFilePath(string $file, bool $withPrefix = false): string
    {
        // Get the prefix from the config, or use a default prefix if not set.
        $prefix = Config::driver(ConfigDrivers::ENV)->getString('APP_NAME') ?? self::PREFIX_PATH;

        // If a prefix is requested and it's not already present in the file path, prepend it to the file name.
        if ($withPrefix && ! str_contains($file, $prefix)) {
            $file = Path::join($prefix, $file);  // Add the prefix to the file path.
        }

        // Replace backslashes with the system's directory separator (usually "/"), and trim leading separators.
        return Str::ltrim(Str::replace('\\', SP, $file), SP);
    }
}
