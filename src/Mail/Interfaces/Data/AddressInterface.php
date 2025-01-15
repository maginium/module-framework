<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interfaces\Data;

/**
 * Interface AddressInterface.
 */
interface AddressInterface
{
    /**
     * Constant for the 'name' property key.
     */
    public const NAME = 'name';

    /**
     * Constant for the 'email' property key.
     */
    public const EMAIL = 'email';

    /**
     * Get the name.
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Set the name.
     *
     * @param string|null $name
     *
     * @return AddressInterface
     */
    public function setName(?string $name): self;

    /**
     * Get the email.
     *
     * @return string|null
     */
    public function getEmail(): ?string;

    /**
     * Set the email.
     *
     * @param string|null $email
     *
     * @return AddressInterface
     */
    public function setEmail(?string $email): self;
}
