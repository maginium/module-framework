<?php

declare(strict_types=1);

namespace Maginium\Framework\Figlet\Helpers;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Php;

/**
 * Class ColorManager.
 *
 * This class provides functionality to colorize text with specific font and background colors using ANSI escape codes.
 * It manages predefined sets of font and background colors and applies the appropriate color codes to text.
 */
class ColorManager
{
    /**
     * Predefined font colors and their corresponding ANSI color codes.
     *
     * @var array
     */
    private array $fontColors = [
        'red' => '0;31',
        'cyan' => '0;36',
        'blue' => '0;34',
        'black' => '0;30',
        'green' => '0;32',
        'brown' => '0;33',
        'white' => '1;37',
        'purple' => '0;35',
        'yellow' => '1;33',
        'light_red' => '1;31',
        'dark_gray' => '1;30',
        'light_gray' => '0;37',
        'light_cyan' => '1;36',
        'light_blue' => '1;34',
        'light_green' => '1;32',
        'light_purple' => '1;35',
    ];

    /**
     * Predefined background colors and their corresponding ANSI background color codes.
     *
     * @var array
     */
    private array $backgroundColors = [
        'red' => '41',
        'cyan' => '46',
        'blue' => '44',
        'black' => '40',
        'green' => '42',
        'yellow' => '43',
        'magenta' => '45',
        'light_gray' => '47',
    ];

    /**
     * Colorizes the provided text by applying the specified font and background colors.
     *
     * The method checks if font and background colors are provided, validates them, and applies the color codes
     * to the text before returning the colorized text.
     *
     * @param string $text The text to be colorized.
     * @param string|null $fontColor The font color to apply (optional).
     * @param string|null $backgroundColor The background color to apply (optional).
     *
     * @throws Exception If the specified color is invalid.
     *
     * @return string The colorized text.
     */
    public function colorize($text, $fontColor, $backgroundColor): string
    {
        // Initialize the colored text with an empty string.
        $coloredText = '';

        // Apply font color if specified.
        if ($fontColor !== null) {
            $coloredText = $this->colorizeFont($fontColor, $coloredText);
        }

        // Apply background color if specified.
        if ($backgroundColor !== null) {
            $coloredText = $this->colorizeBackground(
                $backgroundColor,
                $coloredText,
            );
        }

        // Add the actual text and reset the color formatting.
        $coloredText .= $text . "\033[0m";

        return $coloredText;
    }

    /**
     * Colorizes the font of the text by adding the corresponding color code.
     *
     * This method checks if the provided font color exists in the predefined font colors list and then applies
     * the appropriate color code to the text. If the font color is invalid, an exception is thrown.
     *
     * @param string $fontColor  The font color to apply.
     * @param string $coloredText The text to which the color code will be added.
     *
     * @throws InvalidArgumentException If the provided font color is invalid.
     *
     * @return string The text with the font color code applied.
     */
    private function colorizeFont($fontColor, $coloredText): ?string
    {
        // Check if the font color exists in the predefined colors list.
        if (isset($this->fontColors[$fontColor])) {
            // Add the color code to the text.
            return $this->addColorCode(
                $coloredText,
                $this->fontColors[$fontColor],
            );
        }

        // If the color is invalid, throw an exception with a descriptive message.
        throw InvalidArgumentException::make(
            __('Font color "' .
                $fontColor .
                '" doesn\'t exist' .
                PHP_EOL .
                'Available font colors: ' .
                Php::implode(',', $this->getFontColors())),
        );
    }

    /**
     * Colorizes the background of the text by adding the corresponding background color code.
     *
     * This method checks if the provided background color exists in the predefined background colors list and then
     * applies the appropriate background color code to the text.
     *
     * @param string $backgroundColor The background color to apply.
     * @param string $coloredText    The text to which the background color code will be added.
     *
     * @throws Exception If the provided background color is invalid.
     *
     * @return string The text with the background color code applied.
     */
    private function colorizeBackground($backgroundColor, $coloredText): ?string
    {
        // Check if the background color exists in the predefined colors list.
        if (isset($this->backgroundColors[$backgroundColor])) {
            // Add the background color code to the text.
            return $this->addColorCode(
                $coloredText,
                $this->backgroundColors[$backgroundColor],
            );
        }

        // If the color is invalid, throw an exception with a descriptive message.
        throw InvalidArgumentException::make(
            __('Background color "' .
                $backgroundColor .
                '" doesn\'t exist ' .
                PHP_EOL .
                'Available background colors: ' .
                Php::implode(',', $this->getBackgroundColors())),
        );
    }

    /**
     * Returns an array of available font colors.
     *
     * This method simply returns the keys of the `$fontColors` array, which represent the available font colors.
     *
     * @return array An array of font color names.
     */
    private function getFontColors(): array
    {
        return Arr::keys($this->fontColors);
    }

    /**
     * Returns an array of available background colors.
     *
     * This method simply returns the keys of the `$backgroundColors` array, which represent the available background colors.
     *
     * @return array An array of background color names.
     */
    private function getBackgroundColors(): array
    {
        return Arr::keys($this->backgroundColors);
    }

    /**
     * Adds the appropriate color code to the provided text.
     *
     * This method appends the given color code (either for font or background) to the text, enabling the
     * colorization effect when displayed in the terminal.
     *
     * @param string $coloredText The text to which the color code will be added.
     * @param string $color       The color code to apply.
     *
     * @return string The text with the color code applied.
     */
    private function addColorCode($coloredText, $color): string
    {
        // Append the color code to the text and return the result.
        $coloredText .= "\033[" . $color . 'm';

        return $coloredText;
    }
}
