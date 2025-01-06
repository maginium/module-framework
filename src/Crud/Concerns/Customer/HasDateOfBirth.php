<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Concerns\Customer;

use Maginium\Customer\Interfaces\Data\CustomerInterface;
use Maginium\Foundation\Exceptions\LocalizedException;

/**
 * Trait HasDateOfBirth.
 *
 * Provides common methods to work with the `dateOfBirth` attribute on models.
 *
 * @method string getDateOfBirth() Get the date of birth of the model.
 * @method void setDateOfBirth(string $dateOfBirth) Set the date of birth for the model.
 * @method bool hasDateOfBirth() Check if the date of birth exists for the model.
 * @method void removeDateOfBirth() Remove the date of birth from the model.
 */
trait HasDateOfBirth
{
    /**
     * Get the date of birth of the model.
     *
     * @return string|null The date of birth if it exists, otherwise null.
     */
    public function getDateOfBirth(): ?string
    {
        return $this->getData(CustomerInterface::DOB) ?? null;
    }

    /**
     * Set the date of birth for the model.
     *
     * @param string $dateOfBirth The date of birth to set.
     *
     * @return $this The current model instance for fluent interface.
     */
    public function setDateOfBirth(string $dateOfBirth)
    {
        $this->setData(CustomerInterface::DOB, $dateOfBirth);

        return $this;
    }

    /**
     * Check if the date of birth exists for the model.
     *
     * @return bool True if the date of birth exists, otherwise false.
     */
    public function hasDateOfBirth(): bool
    {
        return $this->hasData(CustomerInterface::DOB);
    }

    /**
     * Remove the date of birth from the model.
     *
     * @throws LocalizedException If the date of birth does not exist.
     *
     * @return $this The current model instance for fluent interface.
     */
    public function removeDateOfBirth()
    {
        if (! $this->hasDateOfBirth()) {
            throw LocalizedException::make(__('Date of birth does not exist.'));
        }

        $this->unsetData(CustomerInterface::DOB);

        return $this;
    }
}
