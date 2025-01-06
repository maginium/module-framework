<?php

declare(strict_types=1);

namespace Maginium\Framework\ColorThief;

use ColorThief\ColorThief as CoreColorThief;
use Maginium\Framework\ColorThief\Helpers\Cache as CacheManager;
use Maginium\Framework\ColorThief\Helpers\Color as ColorHelper;
use Maginium\Framework\ColorThief\Interfaces\ColorThiefInterface;
use Maginium\Framework\Media\Facades\Media;

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
    public function __construct(ColorHelper $colorHelper, CacheManager $cache)
    {
        $this->cache = $cache;
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
        $cacheKey = $this->colorHelper->generateCacheKey($sourceImage, $quality);

        // Check if the result is already cached
        if ($this->cache->has($cacheKey)) {
            return $this->cache->load($cacheKey);
        }

        // Convert the source image to an absolute path
        $sourceImage = Media::fromUrl($sourceImage)->toAbsolute();

        // Retrieve the dominant color using ColorThief
        $rgbColor = (array)CoreColorThief::getColor($sourceImage, $quality, $area);

        // Convert RGB color array to hexadecimal representation
        $hexColor = $this->colorHelper->rgbToHex($rgbColor);

        // Cache the result for future use
        $this->cache->save($cacheKey, $hexColor);

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
        $cacheKey = $this->cache->generateCacheKey("color_palette_{$sourceImage}_{$colorCount}_{$quality}");

        // Check if the result is already cached
        if ($this->cache->has($cacheKey)) {
            return $this->cache->load($cacheKey);
        }

        // If not cached, use ColorThief to get the color palette
        $palette = CoreColorThief::getPalette($sourceImage, $colorCount, $quality, $area);

        // Convert RGB colors to Hex format
        $hexPalette = array_map(fn($rgbColor) => $this->colorHelper->rgbToHex($rgbColor), $palette);

        // Cache the result for future use
        $this->cache->save($cacheKey, $hexPalette);

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
        $cacheKey = $this->cache->generateCacheKey('adjust_lightness_' . $hexColor . "_{$adjustment}");

        // Check if the result is already cached
        if ($this->cache->has($cacheKey)) {
            return $this->cache->load($cacheKey);
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
        $this->cache->save($cacheKey, $adjustedHexColor);

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
        $cacheKey = $this->cache->generateCacheKey('darken_color_' . $hexColor . "_{$percentage}");

        // Check if the result is already cached
        if ($this->cache->has($cacheKey)) {
            return $this->cache->load($cacheKey);
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
        $this->cache->save($cacheKey, $darkenedHexColor);

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
        $cacheKey = $this->cache->generateCacheKey('lighten_color_' . $hexColor . "_{$percentage}");

        // Check if the result is already cached
        if ($this->cache->has($cacheKey)) {
            return $this->cache->load($cacheKey);
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
        $this->cache->save($cacheKey, $lightenedHexColor);

        return $lightenedHexColor;
    }
}
