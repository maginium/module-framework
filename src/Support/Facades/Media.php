<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Media\Interfaces\MediaInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Media service.
 *
 * This class acts as a simplified interface to access the MediaInterface.
 * By extending AbstractFacade, it inherits basic functionality for service access.
 *
 * @method static \Maginium\Framework\Resize\Resizer resize(string $imageUrl, int $newWidth, int $newHeight, array $options = []) Resize and/or crop an image to the given dimensions.
 * @method static \Maginium\Framework\Resize\Resizer crop(string $imageUrl, int $cropStartX, int $cropStartY, int $newWidth, int $newHeight, ?int $srcWidth = null, ?int $srcHeight = null) Crop an image to the specified dimensions from a starting point.
 * @method static string|null upload(string $fileName, bool $withPrefix = false) Upload a media file.
 * @method static string url(string $file, bool $withPrefix = false) Get the URL of a media file.
 * @method static MediaInterface fromUrl(string $fileUrl, bool $absolute = false) Get the file path from the given media URL.
 * @method static string absolutePath(string $file, bool $withPrefix = false) Get the absolute path of a media file.
 * @method static string relativePath(string $file, bool $withPrefix = false) Get the relative path of a media file.
 * @method static string baseUrl() Get the base URL for media assets.
 *
 * @see MediaInterface
 */
class Media extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return MediaInterface::class;
    }
}
