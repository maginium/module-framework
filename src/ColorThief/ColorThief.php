<?php

declare(strict_types=1);

namespace Maginium\Framework\ColorThief;

use ColorThief\ColorThief as CoreColorThief;
use Magento\Store\Model\Store;
use Maginium\Framework\ColorThief\Helpers\Cache as CacheManager;
use Maginium\Framework\ColorThief\Helpers\Color as ColorHelper;
use Maginium\Framework\ColorThief\Interfaces\ColorThiefInterface;
use Maginium\Framework\Media\Facades\Media;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Cache;
use Maginium\Framework\Support\Str;

/**
 * Class ColorThief.
 *
 * Service class for extracting color information from images using ColorThief library.
 * Implements caching for color operations.
 */
class ColorThief implements ColorThiefInterface
{
    /**
     * @var ColorHelper
     */
    private $colorHelper;

    /**
     * @var CacheManager
     */
    private $cache;

    /**
     * ColorThief constructor.
     *
     * @param ColorHelper $colorHelper Helper class for color operations.
     * @param CacheManager $cache Cache helper for storing and retrieving color data.
     */
    public function __construct(ColorHelper $colorHelper)
    {
        $this->colorHelper = $colorHelper;
    }

    /**
     * Use the median cut algorithm to get the dominant color of an image.
     * Caches the result for faster retrieval.
     *
     * @param mixed $sourceImage Path/URL to the image, GD resource, Imagick instance, or image as binary string
     * @param int $quality Quality of color sampling (1-10)
     * @param array|null $area Area to sample [x, y, w, h]
     *
     * @return string Hex color code
     */
    public function getDominantColor($sourceImage, int $quality = 10, ?array $area = null): string
    {
        // Generate a unique cache key based on the input parameters
        $cacheKey = $this->generateCacheKey($sourceImage, $quality);

        // Check if the result is already cached
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Convert the source image to an absolute path
        $sourceImage = Media::fromUrl($sourceImage)->toAbsolute();

        // Retrieve the dominant color using ColorThief
        $rgbColor = (array)CoreColorThief::getColor($sourceImage, $quality, $area);

        // Convert RGB color array to hexadecimal representation
        $hexColor = $this->colorHelper->rgbToHex($rgbColor);

        // Cache the result for future use
        Cache::forever($cacheKey, $hexColor, [static::CACHE_TAG]);

        return $hexColor;
    }

    /**
     * Use the median cut algorithm to get a palette of colors from an image.
     * Caches the result for faster retrieval.
     *
     * @param mixed $sourceImage Path/URL to the image, GD resource, Imagick instance, or image as binary string
     * @param int $colorCount Number of colors in the palette
     * @param int $quality Quality of color sampling (1-10)
     * @param array|null $area Area to sample [x, y, w, h]
     *
     * @return array Array of hex color codes
     */
    public function getColorPalette($sourceImage, $colorCount = 10, $quality = 10, $area = null): array
    {
        // Generate a unique cache key based on the input parameters
        $cacheKey = Cache::generateCacheKey("color_palette_{$sourceImage}_{$colorCount}_{$quality}");

        // Check if the result is already cached
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // If not cached, use ColorThief to get the color palette
        $palette = CoreColorThief::getPalette($sourceImage, $colorCount, $quality, $area);

        // Convert RGB colors to Hex format
        $hexPalette = Arr::map($palette, fn($rgbColor) => $this->colorHelper->rgbToHex($rgbColor));

        // Cache the result for future use
        Cache::forever($cacheKey, $hexPalette, [static::CACHE_TAG]);

        return $hexPalette;
    }

    /**
     * Adjust the lightness of a color.
     * Caches the result for faster retrieval.
     *
     * @param string $hexColor Hex color code (e.g., #RRGGBB)
     * @param int $adjustment Lightness adjustment value
     *
     * @return string Adjusted hex color code
     */
    public function adjustColorLightness(string $hexColor, int $adjustment): string
    {
        // Generate a unique cache key based on the input parameters
        $cacheKey = Cache::generateCacheKey('adjust_lightness_' . $hexColor . "_{$adjustment}");

        // Check if the result is already cached
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Convert hex color to RGB array
        $rgbColor = $this->colorHelper->hexToRgb($hexColor);

        // Adjust the lightness of the RGB color
        $adjustedRgbColor = [
            $this->colorHelper->adjustChannel($rgbColor[0], $adjustment),
            $this->colorHelper->adjustChannel($rgbColor[1], $adjustment),
            $this->colorHelper->adjustChannel($rgbColor[2], $adjustment),
        ];

        // Convert adjusted RGB color array back to hexadecimal representation
        $adjustedHexColor = $this->colorHelper->rgbToHex($adjustedRgbColor);

        // Cache the result for future use
        Cache::forever($cacheKey, $adjustedHexColor, [static::CACHE_TAG]);

        return $adjustedHexColor;
    }

    /**
     * Darken a color by a specified percentage.
     * Caches the result for faster retrieval.
     *
     * @param string $hexColor Hex color code (e.g., #RRGGBB)
     * @param int $percentage Percentage to darken the color by (0-100)
     *
     * @return string Darkened hex color code
     */
    public function darkenColor(string $hexColor, int $percentage): string
    {
        // Calculate the darken adjustment value
        $adjustment = (int)round(255 * ($percentage / 100));

        // Generate a unique cache key based on the input parameters
        $cacheKey = Cache::generateCacheKey('darken_color_' . $hexColor . "_{$percentage}");

        // Check if the result is already cached
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Convert hex color to RGB array
        $rgbColor = $this->colorHelper->hexToRgb($hexColor);

        // Darken the color by reducing each RGB component
        $darkenedRgbColor = [
            max(0, $rgbColor[0] - $adjustment),
            max(0, $rgbColor[1] - $adjustment),
            max(0, $rgbColor[2] - $adjustment),
        ];

        // Convert adjusted RGB color array back to hexadecimal representation
        $darkenedHexColor = $this->colorHelper->rgbToHex($darkenedRgbColor);

        // Cache the result for future use
        Cache::forever($cacheKey, $darkenedHexColor, [static::CACHE_TAG]);

        return $darkenedHexColor;
    }

    /**
     * Lighten a color by a specified percentage.
     * Caches the result for faster retrieval.
     *
     * @param string $hexColor Hex color code (e.g., #RRGGBB)
     * @param int $percentage Percentage to lighten the color by (0-100)
     *
     * @return string Lightened hex color code
     */
    public function lightenColor(string $hexColor, int $percentage): string
    {
        // Calculate the lighten adjustment value
        $adjustment = (int)round(255 * ($percentage / 100));

        // Generate a unique cache key based on the input parameters
        $cacheKey = Cache::generateCacheKey('lighten_color_' . $hexColor . "_{$percentage}");

        // Check if the result is already cached
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Convert hex color to RGB array
        $rgbColor = $this->colorHelper->hexToRgb($hexColor);

        // Lighten the color by increasing each RGB component
        $lightenedRgbColor = [
            min(255, $rgbColor[0] + $adjustment),
            min(255, $rgbColor[1] + $adjustment),
            min(255, $rgbColor[2] + $adjustment),
        ];

        // Convert adjusted RGB color array back to hexadecimal representation
        $lightenedHexColor = $this->colorHelper->rgbToHex($lightenedRgbColor);

        // Cache the result for future use
        Cache::forever($cacheKey, $lightenedHexColor, [static::CACHE_TAG]);

        return $lightenedHexColor;
    }

    /**
     * Generate cache key for Color value.
     *
     * @param string $path The key of the Color variable.
     * @param int|string|null $storeId The store ID to retrieve the Color for. Default is null.
     *
     * @return string The generated cache key.
     */
    public function generateCacheKey(string $path, int|string|null $storeId = null): string
    {
        // Replace null store ID with the default store ID (0)
        $storeId ??= Store::DEFAULT_STORE_ID;

        // Replace forward slashes with underscores in the path
        $path = Str::replace(SP, '_', $path);

        // Construct the cache key
        $cacheKey = self::TYPE_IDENTIFIER . '_' . $path . '_' . $storeId;

        return $cacheKey;
    }
}
