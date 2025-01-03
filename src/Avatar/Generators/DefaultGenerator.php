<?php

declare(strict_types=1);

namespace Maginium\Framework\Avatar\Generators;

use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Avatar\Interfaces\GeneratorInterface;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\Str;

/**
 * Default generator for creating avatar initials.
 *
 * This class implements the GeneratorInterface and provides logic for generating
 * initials based on a given name. It handles single-word and multi-word names,
 * applies transformations like uppercase and RTL (right-to-left), and validates the input.
 */
class DefaultGenerator implements GeneratorInterface
{
    /**
     * @var string|null The name to generate initials from, after processing.
     */
    protected ?string $name;

    /**
     * Generates initials from the given name with various options.
     *
     * This method processes the provided name, handles transformations based on
     * configuration (length, uppercase, ASCII, and RTL), and returns the generated
     * initials or string.
     *
     * @param string|null $name The name or string to generate initials from.
     * @param int $length The desired length of the generated initials (default: 2).
     * @param bool $uppercase Whether the initials should be in uppercase (default: false).
     * @param bool $ascii Whether to convert the name to ASCII characters (default: false).
     * @param bool $rtl Whether to reverse the order of characters for right-to-left languages (default: false).
     *
     * @return string The generated initials or string based on the name and options.
     */
    public function make(?string $name, int $length = 2, bool $uppercase = false, bool $ascii = false, bool $rtl = false): string
    {
        // Set the processed name (convert to ASCII if needed)
        $this->setName($name, $ascii);

        // Split name into words
        $words = new Collection(explode(' ', (string)$this->name));

        // Determine initials based on the number of words
        if ($words->count() === 1) {
            $initial = $this->getInitialFromOneWord($words, $length);
        } else {
            $initial = $this->getInitialFromMultipleWords($words, $length);
        }

        // Apply uppercase transformation if needed
        if ($uppercase) {
            $initial = mb_strtoupper($initial);
        }

        // Reverse string for right-to-left languages if needed
        if ($rtl) {
            $initial = collect(mb_str_split($initial))->reverse()->implode('');
        }

        return $initial;
    }

    /**
     * Sets the name for generating initials after performing necessary checks and transformations.
     *
     * @param string|null $name The name to be processed.
     * @param bool $ascii Whether to convert the name to ASCII characters.
     *
     * @throws InvalidArgumentException If the provided name is an array or an invalid object.
     */
    protected function setName(?string $name, bool $ascii): void
    {
        // Validate that name is not an array
        if (is_array($name)) {
            throw InvalidArgumentException::make(
                'Passed value cannot be an array',
            );
        }

        // Validate that name is a string or object with a __toString method
        if (is_object($name) && ! method_exists($name, '__toString')) {
            throw InvalidArgumentException::make(
                'Passed object must have a __toString method',
            );
        }

        // Handle email input and convert it to a name format (e.g., "john.doe@gmail.com" -> "John Doe")
        if (filter_var($name, FILTER_VALIDATE_EMAIL)) {
            $name = str_replace('.', ' ', Str::before($name, '@'));
        }

        // Convert name to ASCII if the option is enabled
        if ($ascii) {
            $name = Str::ascii($name);
        }

        $this->name = $name;
    }

    /**
     * Extracts the initials from a single word.
     *
     * If the word's length is greater than or equal to the requested length,
     * the initials will be truncated to the specified length.
     *
     * @param Collection $words The collection of words (only one in this case).
     * @param int $length The desired length of the initials.
     *
     * @return string The generated initials.
     */
    protected function getInitialFromOneWord(Collection $words, int $length): string
    {
        $initial = (string)$words->first();

        // If name is longer than the specified length, truncate it
        if (mb_strlen((string)$this->name) >= $length) {
            $initial = Str::substr($this->name, 0, $length);
        }

        return $initial;
    }

    /**
     * Extracts the initials from multiple words.
     *
     * For multi-word names, the first character of each word is used to generate
     * the initials. These are then combined to form the final initials.
     *
     * @param Collection $words The collection of words.
     * @param int $length The desired length of the initials.
     *
     * @return string The generated initials.
     */
    protected function getInitialFromMultipleWords(Collection $words, int $length): string
    {
        // Collect the first character of each word
        $initials = new Collection;
        $words->each(function(string $word) use ($initials) {
            $initials->push(Str::substr($word, 0, 1));
        });

        // Return the selected initials based on the specified length
        return $this->selectInitialFromMultipleInitials($initials, $length);
    }

    /**
     * Selects a subset of initials based on the specified length.
     *
     * This method combines the initials and slices them to the desired length.
     *
     * @param Collection $initials The collection of initials.
     * @param int $length The desired length of the initials.
     *
     * @return string The selected initials as a string.
     */
    protected function selectInitialFromMultipleInitials(Collection $initials, int $length): string
    {
        // Return the initials sliced to the desired length
        return (string)$initials->slice(0, $length)->implode('');
    }
}
