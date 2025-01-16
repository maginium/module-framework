<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interfaces;

use DateInterval;
use DateTimeInterface;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\MailException;
use Maginium\Foundation\Exceptions\NoSuchEntityException;
use Maginium\Framework\Mail\Interfaces\Data\AddressInterface;
use Maginium\Framework\Mail\Interfaces\Data\AttachmentInterface;

/**
 * Interface for the Envelope class representing email data and functionality.
 *
 * This interface defines the methods to manage email properties such as recipients, sender,
 * subject, template data, attachments, CC, BCC, and headers.
 */
interface MailerInterface
{
    /**
     * Key representing the recipient's email address and name.
     * This is used to set or retrieve the "To" address for the email.
     */
    public const TO = 'to';

    /**
     * Key representing the sender's email address and name.
     * This is used to set or retrieve the "From" address for the email.
     */
    public const FROM = 'from';

    /**
     * Key representing the subject of the email.
     * This is used to set or retrieve the email's subject line.
     */
    public const SUBJECT = 'subject';

    /**
     * Key representing the email template ID.
     * This is used to set or retrieve the identifier for the email template to be used.
     */
    public const TEMPLATE_ID = 'template_id';

    /**
     * Key representing the data for the email template.
     * This is used to set or retrieve dynamic content or placeholders for the email template.
     */
    public const TEMPLATE_DATA = 'template_data';

    /**
     * Key representing headers for the email.
     * This can be used to set or retrieve additional information about the email.
     */
    public const HEADERS = 'headers';

    /**
     * Key representing metadata for the email.
     * This can be used to set or retrieve additional information about the email.
     */
    public const METADATA = 'metadata';

    /**
     * Key representing the store ID associated with the email.
     * This is used to set or retrieve the store context for the email.
     */
    public const STORE_ID = 'store_id';

    /**
     * Key representing the CC (carbon copy) recipients for the email.
     * This is used to set or retrieve the list of additional recipients to be copied on the email.
     */
    public const CC = 'cc';

    /**
     * Key representing the BCC (blind carbon copy) recipients for the email.
     * This is used to set or retrieve the list of recipients to be blind copied on the email.
     */
    public const BCC = 'bcc';

    /**
     * Key representing the reply to for the email.
     */
    public const REPLY_TO = 'reply_to';

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
     * Set the store ID.
     *
     * This method sets the store ID for the current email configuration.
     *
     * @param int $storeId The store ID.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function store(int $storeId): self;

    /**
     * Attach a file to the message.
     *
     * @param  AttachmentInterface|array|string  $file The file to attach, either as a string path, an array, or an AttachmentInterface.
     * @param  array  $options Additional options for the attachment (e.g., as, mime).
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function attach(array|string|AttachmentInterface $file, array $options = []): self;

    /**
     * Set the recipient email address.
     *
     * This method sets the recipient's email address and optionally their name for the "to" field.
     * It creates an Address object using the provided email and name.
     *
     * @param string $email Recipient's email address.
     * @param string $name Recipient's name (optional).
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function to(string $email, string $name = ''): self;

    /**
     * Retrieve the recipient details, including email and name.
     *
     * @return \Maginium\Framework\Mail\Interfaces\Data\AddressInterface|null
     */
    public function getTo(): ?AddressInterface;

    /**
     * Set the recipient details, including email and name.
     *
     * @param AddressInterface $to Array of recipient address objects.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setTo(AddressInterface $to): self;

    /**
     * Retrieve the sender details, including email and name.
     *
     * @return \Maginium\Framework\Mail\Interfaces\Data\AddressInterface|null
     */
    public function getFrom(): ?AddressInterface;

    /**
     * Set the sender details, including email and name.
     *
     * @param AddressInterface $from Array of sender address objects.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setFrom(AddressInterface $from): self;

    /**
     * Set the sender email address.
     *
     * This method sets the sender's email address and optionally their name for the "from" field.
     * It creates an Address object using the provided email and name.
     *
     * @param string $email Sender's email address.
     * @param string $name Sender's name (optional).
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function from(string $email, string $name = ''): self;

    /**
     * Set the reply-to email address.
     *
     * This method sets the reply-to email address, allowing the recipient's replies to be directed to a different address.
     *
     * @param string $email Reply-to email address.
     * @param string $name Optional reply-to name.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function replyTo(string $email, string $name = ''): self;

    /**
     * Set the reply-to email address.
     *
     * This method sets the reply-to email address, which directs the recipient's replies to a different address.
     * If no custom reply-to address is set, the default reply-to address is used.
     *
     * @return \Maginium\Framework\Mail\Interfaces\Data\AddressInterface Returns the reply-to address instance or default reply-to if none set.
     */
    public function getReplyTo(): AddressInterface;

    /**
     * Set the reply-to email address.
     *
     * @param AddressInterface $replyTo The reply-to address object.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setReplyTo(AddressInterface $replyTo): self;

    /**
     * Add a recipient to the CC (carbon copy) list.
     *
     * This method adds a recipient to the CC list, allowing them to receive a copy of the email.
     * It creates an Address object using the provided email and name, then adds it to the CC data.
     *
     * @param string $email CC recipient's email address.
     * @param string $name CC recipient's name (optional).
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function cc(string $email, string $name = ''): self;

    /**
     * Get the CC (carbon copy) recipients.
     *
     * This method retrieves the list of recipients in the CC field.
     *
     * @return \Maginium\Framework\Mail\Interfaces\Data\AddressInterface[]|null Returns the CC address instance, default CC if none set, or null if no CC.
     */
    public function getCc(): ?array;

    /**
     * Set the CC (carbon copy) recipients.
     *
     * @param array|string $email CC recipient's email address(es).
     * @param string $name CC recipient's name (optional for single email).
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setCc(array|string $email, string $name = ''): self;

    /**
     * Add a recipient to the BCC (blind carbon copy) list.
     *
     * This method adds a recipient to the BCC list, allowing them to receive a copy of the email without other recipients knowing.
     * It creates an Address object using the provided email and name, then adds it to the BCC data.
     *
     * @param string $email BCC recipient's email address.
     * @param string $name BCC recipient's name (optional).
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function bcc(string $email, string $name = ''): self;

    /**
     * Get the BCC (blind carbon copy) recipients.
     *
     * This method retrieves the list of recipients in the BCC field.
     *
     * @return \Maginium\Framework\Mail\Interfaces\Data\AddressInterface[]|null Returns the BCC address instance, default BCC if none set, or null if no BCC.
     */
    public function getBcc(): ?array;

    /**
     * Set the BCC (blind carbon copy) recipients.
     *
     * This method allows setting one or multiple recipients in the BCC list.
     *
     * @param array|string $email BCC recipient's email address(es).
     * @param string $name BCC recipient's name (optional, for single email).
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setBcc(array|string $email, string $name = ''): self;

    /**
     * Set headers for the email.
     *
     * This method allows you to add additional headers to the email, which can be useful for tracking or other purposes.
     *
     * @param HeaderInterface[] $headers Additional headers for the email.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function headers(array $headers): self;

    /**
     * Retrieve the additional headers for the email.
     *
     * @return \Maginium\Framework\Mail\Interfaces\Data\HeaderInterface[]|null
     */
    public function getHeaders(): ?array;

    /**
     * Set the additional headers for the email.
     *
     * @param HeaderInterface[]|null $headers An array of header key-value pairs.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setHeaders(?array $headers): self;

    /**
     * Set metadata for the email.
     *
     * This method allows you to add additional metadata to the email, which can be useful for tracking or other purposes.
     *
     * @param MetadataInterface[] $metadata Additional metadata for the email.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function metadata(array $metadata): self;

    /**
     * Retrieve the additional metadata for the email.
     *
     * @return \Maginium\Framework\Mail\Interfaces\Data\MetadataInterface[]|null
     */
    public function getMetadata(): ?array;

    /**
     * Set the additional metadata for the email.
     *
     * @param MetadataInterface[]|null $metadata An array of metadata key-value pairs.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setMetadata(?array $metadata): self;

    /**
     * Retrieve the store ID for the email.
     *
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * Set the store ID for the email.
     *
     * @param int|null $storeId The ID of the store.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setStoreId(?int $storeId): self;

    /**
     * Set the subject of the email.
     *
     * This method sets the subject line for the email, specifying the main topic of the message.
     *
     * @param string $subject Subject line of the email.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function subject(string $subject): self;

    /**
     * Retrieve the subject of the email.
     *
     * @return string|null
     */
    public function getSubject(): ?string;

    /**
     * Set the subject of the email.
     *
     * @param string|null $subject The email subject.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setSubject(?string $subject): self;

    /**
     * Set the template ID for the email.
     *
     * This method specifies the template to be used for the email, providing a reference for the layout and content.
     *
     * @param string $templateId Template identifier.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function template(string $templateId): self;

    /**
     * Retrieve the template ID for the email.
     *
     * @return string|null
     */
    public function getTemplateId(): ?string;

    /**
     * Set the template ID for the email.
     *
     * @param string|null $templateId The template ID.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setTemplateId(?string $templateId): self;

    /**
     * Set template data variables for the email.
     *
     * This method allows you to pass dynamic variables to the email template.
     * These variables can be replaced in the template during rendering.
     *
     * @param TemplateDataInterface[] $data Key-value pairs for template variables.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function withData(array $data): self;

    /**
     * Retrieve the template data for the email.
     *
     * @return \Maginium\Framework\Mail\Interfaces\Data\TemplateDataInterface[]|null
     */
    public function getTemplateData(): ?array;

    /**
     * Set the template data for the email.
     *
     * @param TemplateDataInterface[]|null $data An array of template variables and values.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setTemplateData(?array $data): self;

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
    public function send(?self $envelope = null): void;

    /**
     * Queue the email for sending.
     *
     * @return void
     */
    public function queue(): void;

    /**
     * Deliver the queued message after a delay.
     *
     * @param DateTimeInterface|DateInterval|int $delay
     *
     * @return void
     */
    public function later(DateTimeInterface|DateInterval|int $delay = 5000): void;
}
