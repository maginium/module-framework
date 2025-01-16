<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail;

use Carbon\CarbonInterval;
use DateInterval;
use DateTimeInterface;
use Maginium\Foundation\Enums\Durations;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\MailException;
use Maginium\Foundation\Exceptions\NoSuchEntityException;
use Maginium\Framework\Database\ObjectModel;
use Maginium\Framework\Mail\Interfaces\Data\AddressInterfaceFactory;
use Maginium\Framework\Mail\Interfaces\Data\AttachmentInterfaceFactory;
use Maginium\Framework\Mail\Interfaces\Data\HeaderInterfaceFactory;
use Maginium\Framework\Mail\Interfaces\Data\MetadataInterfaceFactory;
use Maginium\Framework\Mail\Interfaces\Data\TemplateDataInterfaceFactory;
use Maginium\Framework\Mail\Interfaces\MailerInterface;
use Maginium\Framework\Mail\Interfaces\TransportBuilderInterface;
use Maginium\Framework\Mail\Traits\HasAttachment;
use Maginium\Framework\Mail\Traits\HasContent;
use Maginium\Framework\Mail\Traits\HasData;
use Maginium\Framework\Mail\Traits\HasHeaders;
use Maginium\Framework\Mail\Traits\HasMetadata;
use Maginium\Framework\Mail\Traits\HasRecipient;
use Maginium\Framework\Support\Facades\Date;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Publisher;

/**
 * MailData class for managing email data with support for
 * multiple recipients, templates, attachments, and headers.
 */
class Mailer extends ObjectModel implements MailerInterface
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
     * @var TransportBuilderInterface Service responsible for sending the email.
     */
    private TransportBuilderInterface $transport;

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
     * @param TransportBuilderInterface $transport Service responsible for sending the email.
     * @param HeaderInterfaceFactory $headerFactory Factory for creating Header instances.
     * @param AddressInterfaceFactory $addressFactory Factory for creating Address instances.
     * @param MetadataInterfaceFactory $metadataFactory Factory for creating Metadata instances.
     * @param AttachmentInterfaceFactory $attachmentFactory Factory for creating Attachment instances.
     * @param TemplateDataInterfaceFactory $templateDataFactory Factory for creating Template Data instances.
     */
    public function __construct(
        TransportBuilderInterface $transport,
        HeaderInterfaceFactory $headerFactory,
        AddressInterfaceFactory $addressFactory,
        MetadataInterfaceFactory $metadataFactory,
        AttachmentInterfaceFactory $attachmentFactory,
        TemplateDataInterfaceFactory $templateDataFactory,
    ) {
        $this->transport = $transport;
        $this->headerFactory = $headerFactory;
        $this->addressFactory = $addressFactory;
        $this->metadataFactory = $metadataFactory;
        $this->attachmentFactory = $attachmentFactory;
        $this->templateDataFactory = $templateDataFactory;
    }

    /**
     * Sends an email using the specified mailer envelope.
     *
     * This method dispatches the email defined by the provided envelope to the configured
     * transport layer for delivery. Inline translations are temporarily suspended to ensure
     * proper email content generation. Errors during the process are logged and exceptions are re-thrown.
     *
     * @param MailerInterface|null $envelope The envelope containing the email details to be sent.
     *
     * @throws NoSuchEntityException If the email template cannot be found.
     * @throws MailException If an error occurs during the email sending process.
     * @throws Exception For any unexpected errors during execution.
     *
     * @return void
     */
    public function send(?MailerInterface $envelope = null): void
    {
        // Call the transport handler to initiate the send operation
        $this->transport->send($envelope ?? $this);
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
        $this->processQueue(self::QUEUE_NAME);
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
        $this->processQueue(self::DELAY_QUEUE_NAME, $delay);
    }

    /**
     * Internal method to process and queue emails.
     *
     * Adds delay headers if applicable and dispatches the email to the specified queue.
     *
     * @param string $queueName Queue name for dispatching the email.
     * @param DateTimeInterface|DateInterval|int|null $delay Optional delay in milliseconds.
     *
     * @throws MailException If queuing fails.
     */
    private function processQueue(
        string $queueName,
        DateTimeInterface|DateInterval|int|null $delay = null,
    ): void {
        $headers = [];

        // Add delay headers if a delay is provided
        if ($delay !== null) {
            $milliseconds = $this->convertDelayToMilliseconds($delay);
            $headers['x-delay'] = $milliseconds;
        }

        try {
            // Dispatch email
            Publisher::dispatch($queueName, $this, $headers);
        } catch (Exception $e) {
            Log::error('Error queuing email: ' . $e->getMessage());

            throw new MailException(__('Error queuing email: %1', $e->getMessage()), $e);
        }
    }

    /**
     * Convert the delay to milliseconds using Carbon.
     *
     * @param DateTimeInterface|DateInterval|int $delay The delay value.
     *
     * @return int The delay in milliseconds.
     */
    private function convertDelayToMilliseconds(DateTimeInterface|DateInterval|int $delay): int
    {
        // If delay is an integer, it's already in milliseconds
        if (is_int($delay)) {
            return $delay;
        }

        // If delay is a DateTimeInterface (Carbon instance or similar), calculate the difference in milliseconds from now
        if ($delay instanceof DateTimeInterface) {
            // Use Carbon to handle DateTime and calculate the difference in milliseconds
            $now = Date::now();

            return $now->diffInMilliseconds(Date::instance($delay)); // Returns the difference in milliseconds
        }

        // If delay is a DateInterval, convert it to milliseconds (assuming it's a future interval)
        if ($delay instanceof DateInterval) {
            // Use CarbonInterval to handle DateInterval
            $interval = CarbonInterval::instance($delay);

            return (int)$interval->totalMinutes * 60 * 1000; // Convert interval to milliseconds
        }

        // Default return (if no valid delay provided, return 0)
        return 0;
    }
}
