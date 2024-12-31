<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Figlet\Interfaces\FigletInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Figlet service.
 *
 * This class acts as a simplified interface to access the FigletInterface.
 * By extending AbstractFacade, it inherits basic functionality for service access.
 *
 *
 * @method static FigletInterface setBackgroundColor(string $color)
 *     Set the background color.
 *     Parameters:
 *     - $color: The background color to set.
 *     Returns:
 *     - FigletInterface: Fluent interface, returns the Figlet service instance.
 * @method static FigletInterface setFont(string $fontName)
 *     Set the font.
 *     Parameters:
 *     - $fontName: The name of the font to set.
 *     Returns:
 *     - FigletInterface: Fluent interface, returns the Figlet service instance.
 * @method static FigletInterface setFontColor(string $color)
 *     Set the font color.
 *     Parameters:
 *     - $color: The font color to set.
 *     Returns:
 *     - FigletInterface: Fluent interface, returns the Figlet service instance.
 * @method static FigletInterface setFontDir(string $fontDir)
 *     Set the font directory.
 *     Parameters:
 *     - $fontDir: The directory path where fonts are located.
 *     Returns:
 *     - FigletInterface: Fluent interface, returns the Figlet service instance.
 * @method static FigletInterface setFontStretching(int $stretching)
 *     Set the font stretching.
 *     Parameters:
 *     - $stretching: The font stretching value to set.
 *     Returns:
 *     - FigletInterface: Fluent interface, returns the Figlet service instance.
 * @method static FigletInterface write(string $text)
 *     Write text using the configured settings.
 *     Parameters:
 *     - $text: The text to write.
 *     Returns:
 *     - FigletInterface: Fluent interface, returns the Figlet service instance.
 * @method static FigletInterface render(string $text)
 *     Render text using the configured settings.
 *     Parameters:
 *     - $text: The text to render.
 *     Returns:
 *     - FigletInterface: Fluent interface, returns the Figlet service instance.
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
