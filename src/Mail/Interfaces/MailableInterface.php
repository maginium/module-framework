<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interfaces;

use DateInterval;
use DateTimeInterface;
use Magento\Framework\Exception\MailException;
use Maginium\Framework\Mail\Interfaces\Data\EnvelopeInterface;

/**
 * Interface MailableInterface.
 *
 * This interface defines the contract for a service that handles sending emails.
 * It provides methods for sending and queuing emails with specified content, recipients, and optional template variables.
 * Implementations should utilize Magento's email templates and mail transport mechanisms.
 */
interface MailableInterface
{
    /**
     * Queue name.
     * This constant is used to identify the message queue for email messages.
     *
     * @var string
     */
    public const QUEUE_NAME = 'email.messages';

    /**
     * Delay queue name.
     * This constant is used to identify the message queue for email messages.
     *
     * @var string
     */
    public const DELAY_QUEUE_NAME = 'email.messages.delay';

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
     * Send an email immediately.
     *
     * This method sends an email with the provided content and envelope, using the transport mechanism.
     * It is expected that any necessary processing, such as rendering templates or setting headers, will be
     * performed by the implementation of this method.
     *
     * @param EnvelopeInterface $mail The envelope containing email headers and content to be sent.
     *
     * @throws MailException If an error occurs while sending the email.
     */
    public function send(EnvelopeInterface $mail): void;

    /**
     * Queue an email for immediate processing.
     *
     * @param EnvelopeInterface $mail The envelope containing email headers and content to be queued.
     *
     * @throws MailException If an error occurs while queuing the email.
     */
    public function queue(EnvelopeInterface $mail): void;

    /**
     * Queue an email for later processing.
     *
     * @param EnvelopeInterface $mail The envelope containing email headers and content to be queued.
     * @param DateTimeInterface|DateInterval|int $delay The delay before processing the email, in milliseconds.
     *
     * @throws MailException If an error occurs while queuing the email.
     */
    public function later(EnvelopeInterface $mail, DateTimeInterface|DateInterval|int $delay): void;
}
