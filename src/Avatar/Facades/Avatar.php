<?php

declare(strict_types=1);

namespace Maginium\Framework\Avatar\Facades;

use Intervention\Image\Image;
use Intervention\Image\Interfaces\ImageInterface;
use Maginium\Framework\Avatar\Interfaces\AvatarInterface;
use Maginium\Framework\Support\Facade;

/**
 * @method static AvatarInterface create(string $name) Create an avatar with the given name, initializing the avatar creation process and allowing method chaining.
 * @method static ImageInterface save(?string $path, int $quality = 90)Save the generated avatar image to a file with optional path and quality settings, and return the saved image.
 * @method static void setGenerator(\Maginium\Framework\Avatar\Interfaces\GeneratorInterface $generator)Set a custom generator for the avatar creation, allowing customization of the avatar generation process.
 * @method static void applyTheme(array $config)Apply theme settings to the avatar using a given configuration, including shape, size, font, border, and background.
 * @method static AvatarInterface addTheme(string $name, array $config)Add a new theme configuration to the avatar system, allowing the application of custom themes.
 * @method static string toBase64()Generate the avatar as a Base64-encoded string (PNG format), which can be used directly in HTML or applications.
 * @method static string toSvg()Generate the avatar as an SVG string, which can be scaled without quality loss and includes optional border and background.
 * @method static string toGravatar(?array $param = null)Generate a Gravatar URL for the avatar using the user's name and optional parameters like size and rating.
 * @method static string getInitial()Retrieve the initials of the user for use in avatars, based on the user's name.
 * @method static Image getImageObject()Return the image object representing the avatar, constructing it if it hasn't been created yet.
 * @method static AvatarInterface buildAvatar()Build the avatar image, generating initials, applying the selected shape, and adding the text, enabling method chaining.
 *
 * @see AvatarInterface
 */
class Avatar extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return AvatarInterface::class;
    }
}
