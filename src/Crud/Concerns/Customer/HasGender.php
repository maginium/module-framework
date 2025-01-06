<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Concerns\Customer;

use Maginium\Customer\Interfaces\Data\CustomerInterface;
use Maginium\Foundation\Enums\Gender;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Support\Str;

/**
 * Trait for managing gender attributes dynamically.
 *
 * This trait provides getter and setter methods for a `gender` field,
 * dynamically pulling the gender options from the EAV configuration.
 * It includes methods for setting, getting, and checking the gender value,
 * as well as setting predefined gender values such as male, female, and other.
 *
 * @property string|null $gender Custom field name for gender attribute
 */
trait HasGender
{
    /**
     * Get the gender value.
     *
     * This method retrieves the gender value associated with the customer. If no gender is set,
     * it will return null. The method translates the stored gender code to its corresponding label
     * using the `Gender::getValue` method.
     *
     * @return string|null The translated gender value or null if not set.
     */
    public function getGender(): ?string
    {
        // Retrieve the gender value stored in the customer attributes
        $gender = (int)$this->getData(CustomerInterface::GENDER);

        // Check if gender is not null and valid
        if ($gender !== null) {
            $value = Gender::getKey($gender);

            // Return the translated gender value in lowercase, if available
            return $value ? Str::lower($value) : null;
        }

        // Return null if gender is not set or valid
        return null;
    }

    /**
     * Set the gender value.
     *
     * This method sets the gender attribute for the model. It validates that the
     * provided gender value exists in the predefined gender types. If the value
     * is invalid, an exception is thrown.
     *
     * @param string $gender The gender value to set (e.g., 'male', 'female', 'other').
     *
     * @throws InvalidArgumentException If the provided gender value is not valid.
     *
     * @return $this The current instance for method chaining.
     */
    public function setGender($gender): self
    {
        // Validate if the provided gender exists in the gender types.
        if (! Gender::hasValue($gender)) {
            // Throw an exception if the gender is not valid.
            throw InvalidArgumentException::make('Invalid gender value.');
        }

        // Set the gender value to the instance.
        $this->setData(CustomerInterface::GENDER, Gender::getKey($gender));

        // Return the current instance to allow method chaining.
        return $this;
    }

    /**
     * Check if the gender is male.
     *
     * This method checks if the current gender value is 'male'.
     *
     * @return bool True if the gender is male, false otherwise.
     */
    public function isMale(): bool
    {
        return $this->getGender() === Gender::MALE();
    }

    /**
     * Check if the gender is female.
     *
     * This method checks if the current gender value is 'female'.
     *
     * @return bool True if the gender is female, false otherwise.
     */
    public function isFemale(): bool
    {
        return $this->getGender() === Gender::FEMALE();
    }

    /**
     * Check if the gender is other.
     *
     * This method checks if the current gender value is 'other'.
     *
     * @return bool True if the gender is 'other', false otherwise.
     */
    public function isOther(): bool
    {
        return $this->getGender() === Gender::OTHER();
    }

    /**
     * Set the gender to male.
     *
     * This method sets the gender to 'male' by calling the `setGender` method
     * with the appropriate value from the Gender enum.
     *
     * @return $this The current instance for method chaining.
     */
    public function setGenderMale(): self
    {
        // Set the gender to 'male'.
        return $this->setGender(Gender::MALE()->value);
    }

    /**
     * Set the gender to female.
     *
     * This method sets the gender to 'female' by calling the `setGender` method
     * with the appropriate value from the Gender enum.
     *
     * @return $this The current instance for method chaining.
     */
    public function setGenderFemale(): self
    {
        // Set the gender to 'female'.
        return $this->setGender(Gender::FEMALE()->value);
    }

    /**
     * Set the gender to other.
     *
     * This method sets the gender to 'other' by calling the `setGender` method
     * with the appropriate value from the Gender enum.
     *
     * @return $this The current instance for method chaining.
     */
    public function setGenderOther(): self
    {
        // Set the gender to 'other'.
        return $this->setGender(Gender::OTHER()->value);
    }
}
