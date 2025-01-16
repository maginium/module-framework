<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interfaces;

use Illuminate\Contracts\Mail\Factory;

/**
 * Interface FactoryInterface.
 *
 * Defines the contract for managing mail implementations across different storage systems.
 * This includes retrieving specific mail instances, such as resend, SES, or any custom mailer configuration.
 */
interface FactoryInterface extends Factory
{
    /**
     * Get a mail implementation by name.
     *
     * This method retrieves a mail instance based on the provided mailer name.
     * The mailer could represent a specific storage solution (e.g., resend, SES, or a custom configuration).
     *
     * @param  string|null  $name Optional name of the mail mailer to retrieve.
     *
     * @return MailerInterface The mail instance corresponding to the provided name or default.
     */
    public function mailer($name = null): MailerInterface;
}
