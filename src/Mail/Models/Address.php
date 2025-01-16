<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models;

use Maginium\Framework\Database\ObjectModel;
use Maginium\Framework\Mail\Interfaces\Data\AddressInterface;

/**
 * Class Address.
 *
 * A Data Object representing an email address and its associated name.
 * Implements AddressInterface to standardize email address handling in the email system.
 */
class Address extends ObjectModel implements AddressInterface
{
    /**
     * Gets the name of the address.
     *
     * Retrieves the name associated with the email address.
     *
     * @return string|null The name associated with the email address, or null if not provided.
     */
    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    /**
     * Gets the email address.
     *
     * Retrieves the email address stored in the DataObject.
     *
     * @return string|null The email address, or null if not provided.
     */
    public function getEmail(): ?string
    {
        return $this->getData(self::EMAIL);
    }

    /**
     * Sets the name associated with the address.
     *
     * Updates the name value in the DataObject.
     *
     * @param string|null $name The name to be associated with the email address.
     *
     * @return void
     */
    public function setName(?string $name): AddressInterface
    {
        // Store the name in the DataObject using setData
        $this->setData(self::NAME, $name);

        return $this;
    }

    /**
     * Sets the email address.
     *
     * Updates the email value in the DataObject.
     *
     * @param string|null $email The email address to be set.
     *
     * @return void
     */
    public function setEmail(?string $email): AddressInterface
    {
        // Store the email in the DataObject using setData
        $this->setData(self::EMAIL, $email);

        return $this;
    }
}
