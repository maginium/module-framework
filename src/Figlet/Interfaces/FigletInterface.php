<?php

declare(strict_types=1);

namespace Maginium\Framework\Figlet\Interfaces;

/**
 * Interface FigletInterface.
 *
 * Responsible for managing the rendering of text in the Figlet format with options for color,
 * font selection, and stretching. Uses external managers for font and color handling.
 */
interface FigletInterface
{
    /**
     * Defines first ASCII character code (blank/space).
     */
    public const FIRST_ASCII_CHARACTER = 32;

    /**
     * Renders the given text in Figlet format.
     *
     * This method loads the font using the font manager, generates the Figlet text,
     * and applies any color settings (if defined).
     *
     * @param string $text The text to be rendered into Figlet format.
     *
     * @return string The Figlet-formatted text, potentially colorized.
     */
    public function render($text): string;

    /**
     * Clears the internal cache by unsetting certain arrays and objects.
     *
     * Specifically, it clears the characters array and the font object.
     */
    public function clear(): void;

    /**
     * Writes the rendered Figlet text to the output.
     *
     * This method echoes the generated Figlet text followed by a newline.
     * It acts as a wrapper around the `render()` method for directly outputting the text.
     *
     * @param string $text The text to be rendered into Figlet format.
     *
     * @return FigletManager Returns the current instance for method chaining.
     */
    public function write($text): self;

    /**
     * Sets the background color for the rendered Figlet text.
     *
     * This method allows the user to specify the background color that will be applied
     * when rendering the Figlet text.
     *
     * @param string $color The background color to be applied.
     *
     * @return FigletManager Returns the current instance for method chaining.
     */
    public function setBackgroundColor($color): self;

    /**
     * Sets the font color for rendering Figlet text.
     *
     * This method allows the user to specify the color for the font used in rendering
     * the Figlet text. It returns the current instance for method chaining.
     *
     * @param string $color The color to be applied to the font.
     *
     * @return FigletManager Returns the current instance for method chaining.
     */
    public function setFontColor($color): self;

    /**
     * Sets the font to be used for rendering Figlet text.
     *
     * This method allows the user to specify which font to use when rendering the
     * Figlet text. It returns the current instance for method chaining.
     *
     * @param string $fontName The name of the font to be used.
     *
     * @return FigletManager Returns the current instance for method chaining.
     */
    public function setFont($fontName): self;

    /**
     * Sets the directory where the font files are located.
     *
     * This method allows the user to specify the directory where the font files
     * are stored. It returns the current instance for method chaining.
     *
     * @param string $fontDir The path to the directory containing font files.
     *
     * @return FigletManager Returns the current instance for method chaining.
     */
    public function setFontDir($fontDir): self;

    /**
     * Sets the stretching factor for the Figlet text.
     *
     * This method allows the user to set how much the Figlet text should be stretched
     * in width or height. It returns the current instance for method chaining.
     *
     * @param int $stretching The stretching factor (positive or negative value).
     *
     * @return FigletManager Returns the current instance for method chaining.
     */
    public function setFontStretching($stretching): self;
}
