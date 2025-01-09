<?php

declare(strict_types=1);

namespace Maginium\Framework\ColorThief\Helpers;

use InvalidArgumentException;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Str;

/**
 * Color Helper Class.
 */
class Color
{
    /**
     * Generates a hex color code based on the color name.
     *
     * @param string $colorName Color name.
     *
     * @return string Hex color code.
     */
    public function generateHexColorCode(string $colorName): string
    {
        // Use a hash function to generate a consistent hex color code
        $hash = md5($colorName);

        return '#' . Str::substr($hash, 0, 6);
    }

    /**
     * Convert RGB values to a hex color code.
     *
     * @param array $rgb RGB values.
     *
     * @return string Hex color code.
     */
    public function rgbToHex(array $rgb): string
    {
        // Validate input
        if (Php::count($rgb) !== 3) {
            return ''; // Return empty string for invalid RGB values
        }

        return Str::format('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * Convert a hex color code to an RGB array.
     *
     * @param string $hex Hex color code (e.g., #RRGGBB)
     *
     * @return array RGB color values [R, G, B]
     */
    public function hexToRgb(string $hex): array
    {
        // Remove the hash if it exists
        $hex = ltrim($hex, '#');

        // Check if the hex value is valid
        if (! preg_match('/^([A-Fa-f0-9]{3}){1,2}$/', $hex)) {
            throw new InvalidArgumentException('Invalid hex color code');
        }

        // Convert shorthand hex color to full hex color
        if (Str::length($hex) === 3) {
            $hex = preg_replace('/(.)/', '$1$1', $hex);
        }

        // Split into R, G, B components
        $r = hexdec(Str::substr($hex, 0, 2));
        $g = hexdec(Str::substr($hex, 2, 2));
        $b = hexdec(Str::substr($hex, 4, 2));

        // Return as array
        return [$r, $g, $b];
    }

    /**
     * Adjust the lightness of a color.
     *
     * @param array $rgbColor RGB color values.
     * @param int   $adjustment Lightness adjustment value.
     *
     * @return string Adjusted hex color code.
     */
    public function adjustColorLightness(array $rgbColor, int $adjustment): string
    {
        // Validate input
        if (Php::count($rgbColor) !== 3) {
            return ''; // Return empty string for invalid RGB values
        }

        // Adjust lightness
        $adjustedRgb = Arr::map(
            $rgbColor,
            static function($channel) use ($adjustment) {
                $adjustedChannel = max(0, min(255, $channel + $adjustment)); // Ensure the channel stays in the 0-255 range

                return $adjustedChannel;
            },
        );

        // Convert adjusted RGB back to hex
        return self::rgbToHex($adjustedRgb);
    }

    /**
     * Generate a unique cache key for the dominant color calculation.
     *
     * @param mixed $sourceImage Path/URL to the image, GD resource, Imagick instance, or image as binary string
     * @param int $quality Quality of color sampling (1-10)
     *
     * @return string Cache key
     */
    public function generateCacheKey($sourceImage, int $quality): string
    {
        return 'dominant_color_' . md5(serialize($sourceImage) . '_' . $quality);
    }

    /**
     * Helper function to adjust a single RGB channel value.
     *
     * @param int $channelValue Original channel value (0-255)
     * @param int $adjustment Lightness adjustment value
     *
     * @return int Adjusted channel value
     */
    public function adjustChannel(int $channelValue, int $adjustment): int
    {
        // Adjust the channel value within valid RGB range
        return max(0, min(255, $channelValue + $adjustment));
    }
}
