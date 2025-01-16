<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interfaces;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\MailException;
use Maginium\Foundation\Exceptions\NoSuchEntityException;

/**
 * Interface TransportBuilderInterface.
 *
 * Provides an extension of the core TransportBuilder to include functionality
 * for adding attachments to email messages.
 */
interface TransportBuilderInterface
{
    /**
     * XML path constant for determining if mail content should be rendered right-to-left.
     *
     * This constant defines the XML path used to retrieve the configuration value for enabling
     * right-to-left rendering of email content, typically used for languages like Arabic or Hebrew.
     *
     * @var string
     */
    public const XML_PATH_MAILER_IS_RTL = 'mail/general/is_rtl';

    /**
     * Sends an email using the specified mailer envelope.
     *
     * This method dispatches the email defined by the provided envelope to the configured
     * transport layer for delivery. Inline translations are temporarily suspended to ensure
     * proper email content generation. Errors during the process are logged and exceptions are re-thrown.
     *
     * @param MailerInterface $envelope The envelope containing the email details to be sent.
     *
     * @throws NoSuchEntityException If the email template cannot be found.
     * @throws MailException If an error occurs during the email sending process.
     * @throws Exception For any unexpected errors during execution.
     */
    public function send(MailerInterface $envelope): void;
}
