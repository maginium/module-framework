<?php

declare(strict_types=1);

namespace Maginium\Framework\Avatar\Interfaces;

/**
 * Interface for generating avatar initials or other generated content.
 *
 * This interface defines the contract for a generator class responsible for
 * creating avatar-related content, such as initials, based on various configuration options.
 */
interface GeneratorInterface
{
    /**
     * Generates a string based on the provided configuration.
     *
     * This method generates a string (typically initials) based on the provided name,
     * length, and additional options like case sensitivity, ASCII characters, and
     * right-to-left text direction.
     *
     * @param string|null $name The name or string to generate content from. Can be null.
     * @param int $length The desired length of the generated content (e.g., number of initials).
     * @param bool $uppercase Whether the result should be in uppercase.
     * @param bool $ascii Whether to restrict the result to ASCII characters.
     * @param bool $rtl Whether to generate content in right-to-left direction (for languages like Arabic).
     *
     * @return string The generated string (e.g., initials or other content).
     */
    public function make(?string $name, int $length, bool $uppercase, bool $ascii, bool $rtl): string;
}
