<?php

declare(strict_types=1);

namespace Maginium\Framework\Media\Interfaces;

use Maginium\Framework\Resize\Resizer;

/**
 * Interface MediaInterface.
 *
 * Defines the contract for the media management operations.
 */
interface MediaInterface
{
    /**
     * Prefix for storing resized images.
     */
    public const PREFIX_PATH = 'maginium';

    /**
     * Path for storing resized images.
     */
    public const RESIZE_PATH = 'resize';

    /**
     * Allowed image file extensions.
     */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'gif', 'png', 'svg'];

    /**
     * resize and/or crop an image to the given dimensions while preserving the options set for the image.
     *
     * @param int $newWidth The new width of the image.
     * @param int $newHeight The new height of the image.
     * @param array $options Additional resizing options, such as mode or offset.
     *
     * @return self Returns the instance with the resized (and optionally cropped) image.
     */
    public function resize(string $imageUrl, $newWidth, $newHeight, $options = []): Resizer;

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
    public function crop(string $imageUrl, $cropStartX, $cropStartY, $newWidth, $newHeight, $srcWidth = null, $srcHeight = null): Resizer;

    /**
     * Upload an image file.
     *
     * @param string $fileName The name of the file to upload.
     * @param bool $withPrefix Whether to include a prefix in the URL.
     *
     * @return string|null Uploaded image URL or null if upload fails.
     */
    public function upload(string $fileName, bool $withPrefix = false): ?string;

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
    public function url(string $file, bool $withPrefix = false): string;

    /**
     * Chainable method to specify the file URL.
     *
     * @param string $fileUrl The media file URL.
     *
     * @return MediaInterface
     */
    public function fromUrl(string $fileUrl): self;

    /**
     * Set the path to be absolute or relative.
     *
     * @param bool $absolute Whether to return the absolute path.
     *
     * @return string|false
     */
    public function toAbsolute(): string|false;

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
    public function absolutePath(string $file, bool $withPrefix = false): string;

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
    public function relativePath(string $file, bool $withPrefix = false): string;

    /**
     * Get the base URL for media assets.
     *
     * This method retrieves the base URL for media assets for the current store.
     *
     * @throws NotFoundException If the store does not exist.
     *
     * @return string The base URL for media assets.
     */
    public function baseUrl(): string;
}
