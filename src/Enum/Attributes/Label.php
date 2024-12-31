<?php

declare(strict_types=1);

namespace Maginium\Framework\Enum\Attributes;

use Attribute;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Support\Validator;

/**
 * Attribute class for labeling enum constants or enum classes.
 *
 * This attribute can be applied to enum constants or enum classes to assign a label.
 * It validates that the provided label is a non-empty string.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::TARGET_CLASS)]
class Label
{
    /**
     * The label of the enum constant or enum class.
     */
    public string $label;

    /**
     * Constructor to initialize the label and validate it.
     *
     * @param  string  $label  The label for the enum constant or class.
     *
     * @throws InvalidArgumentException If the label is empty or not a valid string.
     */
    public function __construct(
        string $label,
    ) {
        // Validate the provided label
        $this->validate($label);

        // Set the label
        $this->label = $label;
    }

    /**
     * Validates the label to ensure it is a non-empty string.
     *
     * @param  string  $label  The label to validate.
     *
     * @throws InvalidArgumentException If the label is empty or not a valid string.
     */
    private function validate(string $label): void
    {
        // Ensure the label is not empty
        if (Validator::isEmpty($label)) {
            throw InvalidArgumentException::make('The label cannot be empty.');
        }

        // Ensure the label is a valid string
        if (! Validator::isString($label)) {
            throw InvalidArgumentException::make('The label must be a string.');
        }
    }
}
