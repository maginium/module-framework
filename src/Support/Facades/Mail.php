<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Mail\Interfaces\FactoryInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Mail service.
 *
 * This facade provides a simplified interface for dispatching emails using the Mail service.
 *
 * @method static \Maginium\Framework\Mail\Interfaces\MailerInterface store(int $storeId) Set the store ID for the email configuration.
 * @method static \Maginium\Framework\Mail\Interfaces\MailerInterface to(string $email, string $name = '') Set the recipient's email and optional name.
 * @method static \Maginium\Framework\Mail\Interfaces\MailerInterface from(string $email, string $name = '') Set the sender's email and optional name.
 * @method static \Maginium\Framework\Mail\Interfaces\MailerInterface replyTo(string $email, string $name = '') Set the reply-to email address and optional name.
 * @method static \Maginium\Framework\Mail\Interfaces\MailerInterface cc(string $email, string $name = '') Add a recipient to the CC list.
 * @method static \Maginium\Framework\Mail\Interfaces\MailerInterface bcc(string $email, string $name = '') Add a recipient to the BCC list.
 * @method static \Maginium\Framework\Mail\Interfaces\MailerInterface subject(string $subject) Set the subject of the email.
 * @method static \Maginium\Framework\Mail\Interfaces\MailerInterface template(string $templateId) Set the template ID for the email.
 * @method static \Maginium\Framework\Mail\Interfaces\MailerInterface withData(array $data) Set template variables for the email.
 * @method static \Maginium\Framework\Mail\Interfaces\MailerInterface metadata(array $metadata) Set additional metadata for the email.
 * @method static \Maginium\Framework\Mail\Interfaces\MailerInterface headers(array $headers) Set additional headers for the email.
 * @method static \Maginium\Framework\Mail\Interfaces\MailerInterface attachment(string $filePath, string $fileName) Add an attachment to the email.
 * @method static bool hasCc(string $address, ?string $name = null) Check if the email has the specified CC recipient.
 * @method static bool hasBcc(string $address, ?string $name = null) Check if the email has the specified BCC recipient.
 * @method static bool hasSubject(string $subject) Check if the email has the specified subject.
 * @method static bool hasHeader(string $key) Check if the email has the specified header.
 * @method static bool hasMetadata(string $key) Check if the email has the specified metadata.
 * @method static void send() Send the email using the provided Envelope.
 * @method static void queue() Send the email using the provided Envelope.
 * @method static void later(\DateTimeInterface|\DateInterval|int $delay = null) Deliver the queued message after a delay.
 *
 * @see FactoryInterface
 */
class Mail extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return FactoryInterface::class;
    }
}
