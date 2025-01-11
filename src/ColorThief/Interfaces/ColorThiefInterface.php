<?php

declare(strict_types=1);

namespace Maginium\Framework\ColorThief\Interfaces;

/**
 * Interface ColorThiefInterface.
 *
 * Interface defining methods for extracting color information from images.
 */
interface ColorThiefInterface
{
    /**
     * Cache type identifier.
     */
    public const TYPE_IDENTIFIER = 'scope_color_thief';

    /**
     * Cache tag.
     */
    public const CACHE_TAG = 'COLOR_THEIF';

    /**
     * Get the dominant color of an image.
     *
     * @param mixed $sourceImage Path/URL to the image, GD resource, Imagick instance, or image as binary string
     * @param int $quality Quality of color sampling (1-10)
     * @param array|null $area Area to sample [x, y, w, h]
     *
     * @return string Hex color code
     */
    public function getDominantColor($sourceImage, int $quality = 10, ?array $area = null): string;

    /**
     * Get a palette of colors from an image.
     *
     * @param mixed $sourceImage Path/URL to the image, GD resource, Imagick instance, or image as binary string
     * @param int $colorCount Number of colors in the palette
     * @param int $quality Quality of color sampling (1-10)
     * @param array|null $area Area to sample [x, y, w, h]
     *
     * @return array Array of hex color codes
     */
    public function getColorPalette($sourceImage, $colorCount = 10, $quality = 10, $area = null): array;

    /**
     * Adjust the lightness of a color.
     * Caches the result for faster retrieval.
     *
     * @param string $hexColor Hex color code (e.g., #RRGGBB)
     * @param int $adjustment Lightness adjustment value
     *
     * @return string Adjusted hex color code
     */
    public function adjustColorLightness(string $hexColor, int $adjustment): string;

    /**
     * Darken a color by a specified percentage.
     * Caches the result for faster retrieval.
     *
     * @param string $hexColor Hex color code (e.g., #RRGGBB)
     * @param int $percentage Percentage to darken the color by (0-100)
     *
     * @return string Darkened hex color code
     */
    public function darkenColor(string $hexColor, int $percentage): string;

    /**
     * Lighten a color by a specified percentage.
     * Caches the result for faster retrieval.
     *
     * @param string $hexColor Hex color code (e.g., #RRGGBB)
     * @param int $percentage Percentage to lighten the color by (0-100)
     *
     * @return string Lightened hex color code
     */
    public function lightenColor(string $hexColor, int $percentage): string;
}
