<?php

declare(strict_types=1);

namespace Maginium\Framework\Figlet\Facades;

use Maginium\Framework\Figlet\Interfaces\FigletInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Figlet service.
 *
 * This class acts as a simplified interface to access the FigletInterface.
 * By extending AbstractFacade, it inherits basic functionality for service access.
 *
 * @method static FigletInterface setBackgroundColor(string $color) Sets the background color for the text rendering.
 * @method static FigletInterface setFont(string $fontName) Sets the font used for ASCII art rendering.
 * @method static FigletInterface setFontColor(string $color) Sets the color of the rendered font.
 * @method static FigletInterface setFontDir(string $fontDir) Sets the directory path where FIGlet fonts are located.
 * @method static FigletInterface setFontStretching(int $stretching) Adjusts the horizontal stretching of the font.
 * @method static FigletInterface write(string $text) Writes the given text with the configured Figlet settings.
 * @method static string render(string $text) Renders the given text as ASCII art and returns it as a string.
 *
 * @see FigletInterface
 */
class Figlet extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return FigletInterface::class;
    }
}
