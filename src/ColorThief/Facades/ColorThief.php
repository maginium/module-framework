<?php

declare(strict_types=1);

namespace Maginium\Framework\ColorThief\Facades;

use Maginium\Framework\ColorThief\Interfaces\ColorThiefInterface;
use Maginium\Framework\Support\Facade;

/**
 * Class ColorThief.
 *
 * Facade for accessing the ColorThief service.
 *
 *
 * @method static string getDominantColor(mixed $sourceImage, int $quality = 10, array|null $area = null)
 *     Get the dominant color of an image using the ColorThief service.
 *     Parameters:
 *     - $sourceImage: Path/URL to the image, GD resource, Imagick instance, or image as binary string
 *     - $quality: Quality of color sampling (1-10)
 *     - $area: Optional area to sample [x, y, w, h]
 *     Returns:
 *     - string: Hex color code representing the dominant color.
 * @method static array getColorPalette(mixed $sourceImage, int $colorCount = 10, int $quality = 10, array|null $area = null)
 *     Get a palette of colors from an image using the ColorThief service.
 *     Parameters:
 *     - $sourceImage: Path/URL to the image, GD resource, Imagick instance, or image as binary string
 *     - $colorCount: Number of colors in the palette
 *     - $quality: Quality of color sampling (1-10)
 *     - $area: Optional area to sample [x, y, w, h]
 *     Returns:
 *     - array: Array of hex color codes representing the palette of colors.
 * @method static string adjustColorLightness(string $hexColor, int $adjustment)
 *     Adjust the lightness of a given color using the ColorThief service.
 *     Parameters:
 *     - $hexColor: Hex color code (e.g., #RRGGBB)
 *     - $adjustment: Lightness adjustment value (negative for darker, positive for lighter)
 *     Returns:
 *     - string: Adjusted hex color code.
 * @method static string darkenColor(string $hexColor, int $percentage)
 *     Darken a color represented as a hex code.
 *     Parameters:
 *     - $hexColor: Hex color code (e.g., #RRGGBB)
 *     - $percentage: Percentage to darken the color by (0-100)
 *     Returns:
 *     - string: Darkened hex color code.
 * @method static string lightenColor(string $hexColor, int $percentage)
 *     Lighten a color represented as a hex code.
 *     Parameters:
 *     - $hexColor: Hex color code (e.g., #RRGGBB)
 *     - $percentage: Percentage to lighten the color by (0-100)
 *     Returns:
 *     - string: Lightened hex color code.
 *
 * @see ColorThiefInterface
 */
class ColorThief extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string.
     */
    protected static function getAccessor(): string
    {
        return ColorThiefInterface::class;
    }
}
