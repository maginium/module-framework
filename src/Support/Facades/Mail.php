<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facade;

use Maginium\Framework\Mail\Interfaces\Data\EnvelopeInterface;
use Maginium\Framework\Mail\Interfaces\Data\EnvelopeInterfaceFactory;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Mail service.
 *
 * This facade provides a simplified interface for dispatching emails using the Mail service.
 *
 * @method static EnvelopeInterface store(int $storeId) Set the store ID for the email configuration.
 * @method static EnvelopeInterface to(string $email, string $name = '') Set the recipient's email and optional name.
 * @method static EnvelopeInterface from(string $email, string $name = '') Set the sender's email and optional name.
 * @method static EnvelopeInterface replyTo(string $email, string $name = '') Set the reply-to email address and optional name.
 * @method static EnvelopeInterface cc(string $email, string $name = '') Add a recipient to the CC list.
 * @method static EnvelopeInterface bcc(string $email, string $name = '') Add a recipient to the BCC list.
 * @method static EnvelopeInterface subject(string $subject) Set the subject of the email.
 * @method static EnvelopeInterface template(string $templateId) Set the template ID for the email.
 * @method static EnvelopeInterface withData(array $data) Set template variables for the email.
 * @method static EnvelopeInterface metadata(array $metadata) Set additional metadata for the email.
 * @method static EnvelopeInterface headers(array $headers) Set additional headers for the email.
 * @method static EnvelopeInterface attachment(string $filePath, string $fileName) Add an attachment to the email.
 * @method static bool hasCc(string $address, ?string $name = null) Check if the email has the specified CC recipient.
 * @method static bool hasBcc(string $address, ?string $name = null) Check if the email has the specified BCC recipient.
 * @method static bool hasSubject(string $subject) Check if the email has the specified subject.
 * @method static bool hasHeader(string $key) Check if the email has the specified header.
 * @method static bool hasMetadata(string $key) Check if the email has the specified metadata.
 * @method static void send() Send the email using the provided Envelope.
 * @method static void queue() Send the email using the provided Envelope.
 * @method static void later(\DateTimeInterface|\DateInterval|int $delay = null) Deliver the queued message after a delay.
 *
 * @see EnvelopeInterface
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
        return EnvelopeInterfaceFactory::class;
    }

    /**
     * Proxy method calls to the envelope.
     *
     * @param string $method The method name being called.
     * @param string[] $args The arguments passed to the method.
     *
     * @return mixed The result of the method call.
     */
    public static function __callStatic($method, $args)
    {
        // Resolve the envelope instance
        $envelope = static::getFacadeRoot()->create();

        // Call the method on the envelope instance
        return call_user_func_array([$envelope, $method], $args);
    }
}
