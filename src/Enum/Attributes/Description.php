<?php

declare(strict_types=1);

namespace Maginium\Framework\Enum\Attributes;

use Attribute;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Support\Validator;

/**
 * Attribute class for descriptioning enum constants or enum classes.
 *
 * This attribute can be applied to enum constants or enum classes to assign a description.
 * It validates that the provided description is a non-empty string.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::TARGET_CLASS)]
class Description
{
    /**
     * The description of the enum constant or enum class.
     */
    public string $description;

    /**
     * Constructor to initialize the description and validate it.
     *
     * @param  string  $description  The description for the enum constant or class.
     *
     * @throws InvalidArgumentException If the description is empty or not a valid string.
     */
    public function __construct(
        string $description,
    ) {
        // Validate the provided description
        $this->validate($description);

        // Set the description
        $this->description = $description;
    }

    /**
     * Validates the description to ensure it is a non-empty string.
     *
     * @param  string  $description  The description to validate.
     *
     * @throws InvalidArgumentException If the description is empty or not a valid string.
     */
    private function validate(string $description): void
    {
        // Ensure the description is not empty
        if (Validator::isEmpty($description)) {
            throw InvalidArgumentException::make('The description cannot be empty.');
        }

        // Ensure the description is a valid string
        if (! Validator::isString($description)) {
            throw InvalidArgumentException::make('The description must be a string.');
        }
    }
}
