<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models;

use DateInterval;
use DateTimeInterface;
use Maginium\Foundation\Enums\Durations;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Database\ObjectModel;
use Maginium\Framework\Mail\Interfaces\Data\AddressInterfaceFactory;
use Maginium\Framework\Mail\Interfaces\Data\AttachmentInterfaceFactory;
use Maginium\Framework\Mail\Interfaces\Data\EnvelopeInterface;
use Maginium\Framework\Mail\Interfaces\Data\HeaderInterfaceFactory;
use Maginium\Framework\Mail\Interfaces\Data\MetadataInterfaceFactory;
use Maginium\Framework\Mail\Interfaces\Data\TemplateDataInterfaceFactory;
use Maginium\Framework\Mail\Interfaces\MailableInterface;
use Maginium\Framework\Mail\Traits\HasAttachment;
use Maginium\Framework\Mail\Traits\HasContent;
use Maginium\Framework\Mail\Traits\HasData;
use Maginium\Framework\Mail\Traits\HasHeaders;
use Maginium\Framework\Mail\Traits\HasMetadata;
use Maginium\Framework\Mail\Traits\HasRecipient;
use Maginium\Framework\Support\Reflection;

/**
 * MailData class for managing email data with support for
 * multiple recipients, templates, attachments, and headers.
 */
class Envelope extends ObjectModel implements EnvelopeInterface
{
    // Trait for handling email attachments
    use HasAttachment;
    // Trait for managing email content (body and template)
    use HasContent;
    // Trait for handling email-related data
    use HasData;
    // Trait for managing email headers
    use HasHeaders;
    // Trait for managing email metadata
    use HasMetadata;
    // Trait for managing email recipients
    use HasRecipient;

    /**
     * @var MailableInterface Service responsible for sending the email.
     */
    private MailableInterface $mailable;

    /**
     * @var AddressInterfaceFactory Factory for creating Address instances.
     */
    private AddressInterfaceFactory $addressFactory;

    /**
     * @var HeaderInterfaceFactory Factory for creating Header instances.
     */
    private HeaderInterfaceFactory $headerFactory;

    /**
     * @var MetadataInterfaceFactory Factory for creating Metadata instances.
     */
    private MetadataInterfaceFactory $metadataFactory;

    /**
     * @var AttachmentInterfaceFactory Factory for creating Template Data instances.
     */
    private AttachmentInterfaceFactory $attachmentFactory;

    /**
     * @var TemplateDataInterfaceFactory Factory for creating Address instances.
     */
    private TemplateDataInterfaceFactory $templateDataFactory;

    /**
     * Class constructor.
     *
     * Initializes the email message object with the necessary dependencies.
     *
     * @param MailableInterface $mailable Service responsible for sending the email.
     * @param HeaderInterfaceFactory $headerFactory Factory for creating Header instances.
     * @param AddressInterfaceFactory $addressFactory Factory for creating Address instances.
     * @param MetadataInterfaceFactory $metadataFactory Factory for creating Metadata instances.
     * @param AttachmentInterfaceFactory $attachmentFactory Factory for creating Attachment instances.
     * @param TemplateDataInterfaceFactory $templateDataFactory Factory for creating Template Data instances.
     */
    public function __construct(
        MailableInterface $mailable,
        HeaderInterfaceFactory $headerFactory,
        AddressInterfaceFactory $addressFactory,
        MetadataInterfaceFactory $metadataFactory,
        AttachmentInterfaceFactory $attachmentFactory,
        TemplateDataInterfaceFactory $templateDataFactory,
    ) {
        $this->mailable = $mailable;
        $this->headerFactory = $headerFactory;
        $this->addressFactory = $addressFactory;
        $this->metadataFactory = $metadataFactory;
        $this->attachmentFactory = $attachmentFactory;
        $this->templateDataFactory = $templateDataFactory;
    }

    /**
     * Send the email immediately.
     *
     * This method triggers the immediate sending of the email by invoking the
     * `handleTransport` method with the `send` operation.
     *
     * @return void
     */
    public function send(): void
    {
        // Call the transport handler to initiate the send operation
        $this->handleTransport(__FUNCTION__);
    }

    /**
     * Queue the email for sending.
     *
     * This method queues the email for future sending by invoking the `handleTransport`
     * method with the `queue` operation. The email will be sent asynchronously based on
     * your queue configuration.
     *
     * @return void
     */
    public function queue(): void
    {
        // Call the transport handler to initiate the queue operation
        $this->handleTransport(__FUNCTION__);
    }

    /**
     * Deliver the queued message after a delay.
     *
     * This method queues the email for delivery after a specified delay. The delay can be
     * a `DateTimeInterface`, `DateInterval`, or an integer (representing seconds).
     * The `handleTransport` method is responsible for invoking the delay behavior with the delay value.
     *
     * @param DateTimeInterface|DateInterval|int $delay
     *        The delay before the email is sent, can be a DateTimeInterface (for exact time),
     *        DateInterval (for specific interval), or an integer (for seconds).
     *
     * @return void
     */
    public function later(DateTimeInterface|DateInterval|int $delay = Durations::HOUR): void
    {
        // Pass the method name and the delay parameter to the transport handler
        $this->handleTransport(__FUNCTION__, $delay);
    }

    /**
     * Handles the transport sender logic with envelope validation.
     *
     * This private method abstracts the logic for handling different transport actions
     * like sending or queuing the email. It validates that the mailable object has the
     * correct method for the given action (send, queue, or later). If the method is not found,
     * it throws a `RuntimeException`. It also ensures that the delay logic is applied correctly
     * when queuing or delaying the email.
     *
     * @param string $method The method to call on the mailable object (e.g., "queue", "send").
     *                       This is determined dynamically based on the calling method.
     * @param mixed|null $delay The optional delay for queueing the email. Defaults to `null`.
     *
     * @throws RuntimeException If the specified method does not exist on the mailable object.
     *                          Or if the mailable envelope has not been properly initialized.
     *
     * @return void
     */
    private function handleTransport(string $method, $delay = null): void
    {
        // Ensure the method exists on the mailable object
        if (! Reflection::methodExists($this->mailable, $method)) {
            // If the method does not exist, throw an exception with a helpful message
            throw new RuntimeException(__("Method {$method} not found on the mailable object."));
        }

        // Apply the method on the mailable object based on the requested action (queue, send, or later)
        if ($method === 'queue') {
            // If the action is "queue", pass the current instance to the mailable's `queue` method
            $this->mailable->queue($this);
        } elseif ($method === 'later') {
            // If the action is "later", pass the current instance and delay to the mailable's `later` method
            $this->mailable->later($this, $delay);
        } else {
            // For all other methods (e.g., send), directly call the method on the mailable object
            $this->mailable->{$method}($this);
        }
    }
}
