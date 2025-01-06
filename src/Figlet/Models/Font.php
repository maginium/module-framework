<?php

declare(strict_types=1);

namespace Maginium\Framework\Figlet\Models;

use Maginium\Framework\Database\ObjectModel;
use Maginium\Framework\Figlet\Interfaces\Data\FontInterface;

/**
 * Class Font.
 *
 * Represents a font in the Figlet system, encapsulating its characteristics and attributes.
 * This class uses constants for property keys and allows dynamic data access via `getData` and `setData` methods.
 */
class Font extends ObjectModel implements FontInterface
{
    /**
     * Gets the name of the font.
     *
     * @return string|null The name of the font or null if not set.
     */
    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    /**
     * Sets the name of the font.
     *
     * @param string $name The font name to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setName(string $name): self
    {
        $this->setData(self::NAME, $name);

        return $this;
    }

    /**
     * Gets the collection of files associated with the font.
     *
     * @return array|null The file collection or null if not set.
     */
    public function getFileCollection(): ?array
    {
        return $this->getData(self::FILE_COLLECTION);
    }

    /**
     * Sets the collection of files for the font.
     *
     * @param array $fileCollection The file collection to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setFileCollection(array $fileCollection): self
    {
        $this->setData(self::FILE_COLLECTION, $fileCollection);

        return $this;
    }

    /**
     * Gets the signature of the font.
     *
     * @return string|null The signature of the font or null if not set.
     */
    public function getSignature(): ?string
    {
        return $this->getData(self::SIGNATURE);
    }

    /**
     * Sets the signature for the font.
     *
     * @param string $signature The signature to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setSignature(string $signature): self
    {
        $this->setData(self::SIGNATURE, $signature);

        return $this;
    }

    /**
     * Gets the hard blank character used in the font.
     *
     * @return string|null The hard blank character or null if not set.
     */
    public function getHardBlank(): ?string
    {
        return $this->getData(self::HARD_BLANK);
    }

    /**
     * Sets the hard blank character for the font.
     *
     * @param string $hardBlank The hard blank character to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setHardBlank(string $hardBlank): self
    {
        $this->setData(self::HARD_BLANK, $hardBlank);

        return $this;
    }

    /**
     * Gets the height of the font.
     *
     * @return int|null The height of the font or null if not set.
     */
    public function getHeight(): ?int
    {
        return $this->getData(self::HEIGHT);
    }

    /**
     * Sets the height of the font.
     *
     * @param int $height The height to set for the font.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setHeight(int $height): self
    {
        $this->setData(self::HEIGHT, $height);

        return $this;
    }

    /**
     * Gets the maximum length of the font.
     *
     * @return int|null The maximum length or null if not set.
     */
    public function getMaxLength(): ?int
    {
        return $this->getData(self::MAX_LENGTH);
    }

    /**
     * Sets the maximum length for the font.
     *
     * @param int $maxLength The maximum length to set for the font.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setMaxLength(int $maxLength): self
    {
        $this->setData(self::MAX_LENGTH, $maxLength);

        return $this;
    }

    /**
     * Gets the old layout version of the font.
     *
     * @return int|null The old layout version or null if not set.
     */
    public function getOldLayout(): ?int
    {
        return $this->getData(self::OLD_LAYOUT);
    }

    /**
     * Sets the old layout version for the font.
     *
     * @param int $oldLayout The old layout version to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setOldLayout(int $oldLayout): self
    {
        $this->setData(self::OLD_LAYOUT, $oldLayout);

        return $this;
    }

    /**
     * Gets the number of comment lines in the font's metadata.
     *
     * @return int|null The number of comment lines or null if not set.
     */
    public function getCommentLines(): ?int
    {
        return $this->getData(self::COMMENT_LINES);
    }

    /**
     * Sets the number of comment lines for the font.
     *
     * @param int $commentLines The number of comment lines to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setCommentLines(int $commentLines): self
    {
        $this->setData(self::COMMENT_LINES, $commentLines);

        return $this;
    }

    /**
     * Gets the print direction of the font.
     *
     * @return int|null The print direction or null if not set.
     */
    public function getPrintDirection(): ?int
    {
        return $this->getData(self::PRINT_DIRECTION);
    }

    /**
     * Sets the print direction for the font.
     *
     * @param int $printDirection The print direction to set for the font.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setPrintDirection(int $printDirection): self
    {
        $this->setData(self::PRINT_DIRECTION, $printDirection);

        return $this;
    }

    /**
     * Gets the full layout version of the font.
     *
     * @return int|null The full layout version or null if not set.
     */
    public function getFullLayout(): ?int
    {
        return $this->getData(self::FULL_LAYOUT);
    }

    /**
     * Sets the full layout version for the font.
     *
     * @param int $fullLayout The full layout version to set for the font.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setFullLayout(int $fullLayout): self
    {
        $this->setData(self::FULL_LAYOUT, $fullLayout);

        return $this;
    }
}
