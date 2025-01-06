<?php

declare(strict_types=1);

namespace Maginium\Framework\Figlet\Interfaces\Data;

use Maginium\Foundation\Interfaces\DataObjectInterface;

/**
 * Interface FontInterface.
 *
 * Represents a font in the Figlet system, encapsulating its characteristics and attributes.
 * This class uses constants for property keys and allows dynamic data access via `getData` and `setData` methods.
 */
interface FontInterface extends DataObjectInterface
{
    /**
     * @const string The name of the font.
     */
    public const NAME = 'name';

    /**
     * @const string The collection of files associated with the font.
     */
    public const FILE_COLLECTION = 'file_collection';

    /**
     * @const string The signature or identifier of the font.
     */
    public const SIGNATURE = 'signature';

    /**
     * @const string The character representing a hard blank space in the font.
     */
    public const HARD_BLANK = 'hard_blank';

    /**
     * @const string The height of the font in characters.
     */
    public const HEIGHT = 'height';

    /**
     * @const string The maximum length of the font.
     */
    public const MAX_LENGTH = 'max_length';

    /**
     * @const string The old layout version of the font.
     */
    public const OLD_LAYOUT = 'old_layout';

    /**
     * @const string The number of comment lines in the font metadata.
     */
    public const COMMENT_LINES = 'comment_lines';

    /**
     * @const string The print direction of the font.
     */
    public const PRINT_DIRECTION = 'print_direction';

    /**
     * @const string The full layout version of the font.
     */
    public const FULL_LAYOUT = 'full_layout';

    /**
     * Gets the name of the font.
     *
     * @return string|null The name of the font or null if not set.
     */
    public function getName(): ?string;

    /**
     * Sets the name of the font.
     *
     * @param string $name The font name to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setName(string $name): self;

    /**
     * Gets the collection of files associated with the font.
     *
     * @return array|null The file collection or null if not set.
     */
    public function getFileCollection(): ?array;

    /**
     * Sets the collection of files for the font.
     *
     * @param array $fileCollection The file collection to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setFileCollection(array $fileCollection): self;

    /**
     * Gets the signature of the font.
     *
     * @return string|null The signature of the font or null if not set.
     */
    public function getSignature(): ?string;

    /**
     * Sets the signature for the font.
     *
     * @param string $signature The signature to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setSignature(string $signature): self;

    /**
     * Gets the hard blank character used in the font.
     *
     * @return string|null The hard blank character or null if not set.
     */
    public function getHardBlank(): ?string;

    /**
     * Sets the hard blank character for the font.
     *
     * @param string $hardBlank The hard blank character to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setHardBlank(string $hardBlank): self;

    /**
     * Gets the height of the font.
     *
     * @return int|null The height of the font or null if not set.
     */
    public function getHeight(): ?int;

    /**
     * Sets the height of the font.
     *
     * @param int $height The height to set for the font.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setHeight(int $height): self;

    /**
     * Gets the maximum length of the font.
     *
     * @return int|null The maximum length or null if not set.
     */
    public function getMaxLength(): ?int;

    /**
     * Sets the maximum length for the font.
     *
     * @param int $maxLength The maximum length to set for the font.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setMaxLength(int $maxLength): self;

    /**
     * Gets the old layout version of the font.
     *
     * @return int|null The old layout version or null if not set.
     */
    public function getOldLayout(): ?int;

    /**
     * Sets the old layout version for the font.
     *
     * @param int $oldLayout The old layout version to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setOldLayout(int $oldLayout): self;

    /**
     * Gets the number of comment lines in the font's metadata.
     *
     * @return int|null The number of comment lines or null if not set.
     */
    public function getCommentLines(): ?int;

    /**
     * Sets the number of comment lines for the font.
     *
     * @param int $commentLines The number of comment lines to set.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setCommentLines(int $commentLines): self;

    /**
     * Gets the print direction of the font.
     *
     * @return int|null The print direction or null if not set.
     */
    public function getPrintDirection(): ?int;

    /**
     * Sets the print direction for the font.
     *
     * @param int $printDirection The print direction to set for the font.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setPrintDirection(int $printDirection): self;

    /**
     * Gets the full layout version of the font.
     *
     * @return int|null The full layout version or null if not set.
     */
    public function getFullLayout(): ?int;

    /**
     * Sets the full layout version for the font.
     *
     * @param int $fullLayout The full layout version to set for the font.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setFullLayout(int $fullLayout): self;
}
