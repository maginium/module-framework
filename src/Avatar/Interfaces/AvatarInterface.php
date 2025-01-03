<?php

declare(strict_types=1);

namespace Maginium\Framework\Avatar\Interfaces;

use Intervention\Image\Image;
use Intervention\Image\Interfaces\ImageInterface;
use InvalidArgumentException;

/**
 * Class AvatarInterface.
 *
 * This class is responsible for generating and managing avatar images.
 * It supports different shapes, fonts, background and foreground colors,
 * and various customization options for the avatar's appearance.
 */
interface AvatarInterface
{
    /**
     * The key for the theme.
     *
     * @var string
     */
    public const THEME = 'theme';

    /**
     * The key for the background property.
     *
     * @var string
     */
    public const BACKGROUND = 'background';

    /**
     * The key for the foreground property.
     *
     * @var string
     */
    public const FOREGROUND = 'foreground';

    /**
     * The key for the width property.
     *
     * @var string
     */
    public const WIDTH = 'width';

    /**
     * The key for the height property.
     *
     * @var string
     */
    public const HEIGHT = 'height';

    /**
     * The key for the font size property.
     *
     * @var string
     */
    public const FONT_SIZE = 'fontSize';

    /**
     * The key for the font family property.
     *
     * @var string
     */
    public const FONT_FAMILY = 'fontFamily';

    /**
     * The key for the border size property.
     *
     * @var string
     */
    public const BORDER_SIZE = 'borderSize';

    /**
     * The key for the border color property.
     *
     * @var string
     */
    public const BORDER_COLOR = 'borderColor';

    /**
     * The key for the border radius property.
     *
     * @var string
     */
    public const BORDER_RADIUS = 'borderRadius';

    /**
     * The key for the shape property.
     *
     * @var string
     */
    public const SHAPE = 'shape';

    /**
     * The key for the character set.
     *
     * @var string
     */
    public const CHARS = 'chars';

    /**
     * The key for the font property.
     *
     * @var string
     */
    public const FONT = 'font';

    /**
     * Create an avatar with the given name.
     *
     * This method initializes the avatar creation process by setting the avatar's name
     * and applying the theme settings. It returns the current instance to allow method chaining.
     *
     * @param string $name The name to be used for the avatar.
     *
     * @return static The current instance of the Avatar class for method chaining.
     */
    public function create(string $name): static;

    /**
     * Save the generated avatar image to a file.
     *
     * This method generates the avatar and saves it to the specified path with the given quality.
     * It validates the provided file path and checks if the file already exists.
     *
     * @param string|null $path The file path where the avatar will be saved.
     * @param int $quality The quality of the saved image (1-100). Defaults to 90.
     * @param bool $overwrite Whether to overwrite the file if it already exists. Defaults to true.
     *
     * @throws InvalidArgumentException If the provided path is invalid or not writable.
     *
     * @return ImageInterface The saved image instance, or null if the file exists and overwrite is false.
     */
    public function save(?string $path, int $quality = 90, bool $overwrite = true): ?ImageInterface;

    /**
     * Set a custom generator for the avatar creation.
     *
     * This method allows overriding the default avatar generator with a custom one.
     * The generator is responsible for how the avatar image is created.
     *
     * @param GeneratorInterface $generator The custom avatar generator.
     *
     * @return void
     */
    public function setGenerator(GeneratorInterface $generator): void;

    /**
     * Apply theme settings to the avatar.
     *
     * This method validates and applies the configuration options for the avatar's theme.
     * The configuration includes settings like shape, size, font, border, and background.
     *
     * @param array $config The theme configuration.
     *
     * @return void
     */
    public function applyTheme(array $config): void;

    /**
     * Add a new theme to the avatar system.
     *
     * This method allows the addition of new theme configurations that can be applied
     * to avatars. The theme will be validated and added to the list of available themes.
     *
     * @param string $name The name of the new theme.
     * @param array $config The configuration settings for the new theme.
     *
     * @return static The current instance of the Avatar class for method chaining.
     */
    public function addTheme(string $name, array $config): static;

    /**
     * Generate the avatar as a Base64 encoded string.
     *
     * This method generates the avatar image and returns it as a Base64-encoded PNG string.
     * The Base64 string can be used directly in HTML or other applications.
     *
     * The result is cached to avoid regenerating the same avatar multiple times.
     *
     * @return string The Base64-encoded PNG representation of the avatar.
     */
    public function toBase64(): string;

    /**
     * Generate and return the avatar as a Base64-encoded PNG string.
     *
     * This method builds the avatar, converts it to PNG format, and encodes it as a Base64 string.
     * The Base64 string can be used directly in HTML, image tags, or other applications.
     *
     * The result is cached using a unique key based on the avatar's configuration to avoid
     * regenerating the same avatar multiple times.
     *
     * @return string The Base64-encoded PNG representation of the avatar.
     */
    public function toPng(): string;

    /**
     * Generate and return the avatar as a JPG file path.
     *
     * This method builds the avatar, converts it to JPG format, and saves it to a file.
     * If the JPG already exists, it returns the path to the existing file without regenerating the avatar.
     *
     * The result is cached using a unique key based on the avatar's configuration to avoid
     * regenerating the same avatar multiple times, improving performance and reducing unnecessary processing.
     *
     * @return string The relative path to the saved JPG avatar.
     */
    public function toJpg(): string;

    /**
     * Generate and return the avatar as a WebP file path.
     *
     * This method builds the avatar, converts it to WebP format, and saves it to a file.
     * If the WebP already exists, it returns the path to the existing file without regenerating the avatar.
     *
     * The result is cached using a unique key based on the avatar's configuration to avoid
     * regenerating the same avatar multiple times, improving performance and reducing unnecessary processing.
     *
     * @return string The relative path to the saved WebP avatar.
     */
    public function toWebp(): string;

    /**
     * Generate the avatar as an SVG string.
     *
     * This method generates the avatar as an SVG image. The SVG format is vector-based
     * and can be scaled without loss of quality. It includes an optional border and background
     * depending on the configuration.
     *
     * @return string The generated SVG representation of the avatar.
     */
    public function toSvg(): string;

    /**
     * Generates a Gravatar URL for the user's avatar.
     * The URL is generated based on the user's name and optional parameters (like width).
     *
     * @param array|null $param Optional parameters to be added to the Gravatar URL (e.g., size, rating).
     *
     * @return string The full URL to the user's Gravatar image.
     */
    public function toGravatar(?array $param = null): string;

    /**
     * Retrieves the initials of the user (used for avatars).
     *
     * @return string The initials of the user.
     */
    public function getInitial(): string;

    /**
     * Returns the image object representing the avatar.
     * If the avatar hasn't been built yet, it will be constructed before returning the image object.
     *
     * @return Image The image object representing the avatar.
     */
    public function getImageObject(): Image;

    /**
     * Builds the avatar image by generating the initials, applying the selected shape, and adding the text.
     * This method is called internally to create the avatar image if it doesn't already exist.
     *
     * @return static The current instance for method chaining.
     */
    public function buildAvatar(): static;
}
