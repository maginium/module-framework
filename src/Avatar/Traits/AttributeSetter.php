<?php

declare(strict_types=1);

namespace Maginium\Framework\Avatar\Traits;

use Maginium\Framework\Avatar\Interfaces\AvatarInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Validator;

/**
 * Trait AttributeSetter.
 *
 * Provides setter methods for setting various attributes of an object.
 * The setter methods use `setData` to assign values to the attributes dynamically.
 */
trait AttributeSetter
{
    /**
     * Sets the theme for the avatar.
     *
     * The theme can be a string or an array. If a string is passed, it is validated
     * against the available themes. If it is not valid, the method returns the current instance.
     *
     * @param mixed $theme The theme to set, either a string or an array.
     *
     * @return static The current instance for method chaining.
     */
    public function setTheme($theme): static
    {
        // Validate and set theme using the `setData` method
        if (Validator::isString($theme) || Validator::isArray($theme)) {
            if (Validator::isString($theme) && ! Arr::keyExists($theme, $this->themes)) {
                // Return the current instance if the theme is invalid
                return $this;
            }

            // Set the theme dynamically using constant key
            $this->setData(AvatarInterface::THEME, $theme);
        }

        // Initialize theme if it's valid
        $this->initTheme();

        return $this;
    }

    /**
     * Sets the background color.
     *
     * @param string $hex The hex color value for the background.
     *
     * @return static The current instance for method chaining.
     */
    public function setBackground(string $hex): static
    {
        // Set background color using `setData` method and constant key
        $this->setData(AvatarInterface::BACKGROUND, $hex);

        return $this;
    }

    /**
     * Sets the foreground color.
     *
     * @param string $hex The hex color value for the foreground.
     *
     * @return static The current instance for method chaining.
     */
    public function setForeground(string $hex): static
    {
        // Set foreground color using `setData` method and constant key
        $this->setData(AvatarInterface::FOREGROUND, $hex);

        return $this;
    }

    /**
     * Sets the dimensions of the avatar.
     *
     * If only one dimension is passed, it is used for both width and height.
     * The dimensions are set using the `setData` method.
     *
     * @param int $width The width of the avatar.
     * @param int|null $height The height of the avatar (optional, defaults to width).
     *
     * @return static The current instance for method chaining.
     */
    public function setDimension(int $width, ?int $height = null): static
    {
        // If height is not provided, use width as height
        $height ??= $width;

        // Set width and height using `setData` method and constant keys
        $this->setData(AvatarInterface::WIDTH, $width);
        $this->setData(AvatarInterface::HEIGHT, $height);

        return $this;
    }

    /**
     * Sets the font size for the avatar.
     *
     * @param int $size The font size to set.
     *
     * @return static The current instance for method chaining.
     */
    public function setFontSize(int $size): static
    {
        // Set font size using `setData` method and constant key
        $this->setData(AvatarInterface::FONT_SIZE, $size);

        return $this;
    }

    /**
     * Sets the font family for the avatar.
     *
     * @param string $font The font family to set.
     *
     * @return static The current instance for method chaining.
     */
    public function setFontFamily(string $font): static
    {
        // Set font family using `setData` method and constant key
        $this->setData(AvatarInterface::FONT_FAMILY, $font);

        return $this;
    }

    /**
     * Sets the border properties of the avatar.
     *
     * This includes the border size, color, and radius. These properties are set
     * using the `setData` method.
     *
     * @param int $size The border size.
     * @param string $color The border color.
     * @param int $radius The border radius (optional, defaults to 0).
     *
     * @return static The current instance for method chaining.
     */
    public function setBorder(int $size, string $color, int $radius = 0): static
    {
        // Set border properties using `setData` method and constant keys
        $this->setData(AvatarInterface::BORDER_SIZE, $size);
        $this->setData(AvatarInterface::BORDER_COLOR, $color);
        $this->setData(AvatarInterface::BORDER_RADIUS, $radius);

        return $this;
    }

    /**
     * Sets the border radius of the avatar.
     *
     * @param int $radius The border radius to set.
     *
     * @return static The current instance for method chaining.
     */
    public function setBorderRadius(int $radius): static
    {
        // Set border radius using `setData` method and constant key
        $this->setData(AvatarInterface::BORDER_RADIUS, $radius);

        return $this;
    }

    /**
     * Sets the shape of the avatar.
     *
     * @param string $shape The shape of the avatar (e.g., "circle", "square").
     *
     * @return static The current instance for method chaining.
     */
    public function setShape(string $shape): static
    {
        // Set shape using `setData` method and constant key
        $this->setData(AvatarInterface::SHAPE, $shape);

        return $this;
    }

    /**
     * Sets the number of characters to display in the avatar.
     *
     * @param int $chars The number of characters.
     *
     * @return static The current instance for method chaining.
     */
    public function setChars(int $chars): static
    {
        // Set characters using `setData` method and constant key
        $this->setData(AvatarInterface::CHARS, $chars);

        return $this;
    }

    /**
     * Sets the font for the avatar.
     *
     * @param string $font The font file path.
     *
     * @return static The current instance for method chaining.
     */
    public function setFont(string $font): static
    {
        // Check if the font file exists, then set it using `setData` method and constant key
        if (is_file($font)) {
            $this->setData(AvatarInterface::FONT, $font);
        }

        return $this;
    }
}
