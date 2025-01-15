<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models;

use Laminas\Mime\Message;
use Laminas\Mime\Part;
use Laminas\Mime\PartFactory;
use Magento\Framework\HTTP\Mime;
use Magento\Framework\Mail\AddressConverter;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder as BaseTransportBuilder;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use Maginium\Framework\Mail\Interfaces\Data\EmailMessageInterface;
use Maginium\Framework\Mail\Interfaces\Data\HeaderInterface;
use Maginium\Framework\Mail\Interfaces\MessageInterface;
use Maginium\Framework\Mail\Interfaces\TransportBuilderInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;
use Mirasvit\Report\Model\Mail\Template\TransportBuilderInterface as MirasvitTransportBuilderInterface;

class TransportBuilder extends BaseTransportBuilder implements TransportBuilderInterface
{
    /**
     * @var array
     */
    protected array $attachments = [];

    /**
     * @var PartFactory
     */
    protected PartFactory $partFactory;

    /**
     * Message.
     *
     * @var EmailMessageInterface
     */
    protected $message;

    /**
     * @var MimeMessageInterfaceFactory
     */
    private MimeMessageInterfaceFactory $mimeMessageFactory;

    /**
     * @var EmailMessageInterfaceFactory
     */
    private EmailMessageInterfaceFactory $emailMessageFactory;

    /**
     * @var string|null
     */
    private ?string $subject;

    /**
     * @var array
     */
    private array $headers = [];

    /**
     * TransportBuilder constructor.
     *
     * @param PartFactory $partFactory
     * @param MessageInterface $message
     * @param FactoryInterface $templateFactory
     * @param AddressConverter $addressConverter
     * @param ObjectManagerInterface $objectManager
     * @param SenderResolverInterface $senderResolver
     * @param MimePartInterfaceFactory $mimePartFactory
     * @param MessageInterfaceFactory|null $messageFactory
     * @param MimeMessageInterfaceFactory $mimeMessageFactory
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param EmailMessageInterfaceFactory $emailMessageFactory
     */
    public function __construct(
        PartFactory $partFactory,
        MessageInterface $message,
        FactoryInterface $templateFactory,
        AddressConverter $addressConverter,
        ObjectManagerInterface $objectManager,
        SenderResolverInterface $senderResolver,
        MessageInterfaceFactory $messageFactory,
        MimePartInterfaceFactory $mimePartFactory,
        TransportInterfaceFactory $mailTransportFactory,
        MimeMessageInterfaceFactory $mimeMessageFactory,
        EmailMessageInterfaceFactory $emailMessageFactory,
    ) {
        parent::__construct(
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory,
            $messageFactory,
            $emailMessageFactory,
            $mimeMessageFactory,
            $mimePartFactory,
            $addressConverter,
        );

        $this->partFactory = $partFactory;
        $this->mimeMessageFactory = $mimeMessageFactory;
        $this->emailMessageFactory = $emailMessageFactory;

        $this->reset();
    }

    /**
     * Set message subject.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject): TransportBuilderInterface
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set custom headers for the email message.
     *
     * @param array $headers Associative array of headers where the key is the header name and the value is the header value.
     *
     * @return $this
     */
    public function setHeaders(array $headers = []): TransportBuilderInterface
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Adds an attachment to the email message.
     *
     * This method allows the addition of a file or content as an attachment to the email.
     * The attachment is created as a MIME part with specified parameters and added
     * to the list of attachments for the email.
     *
     * @param string $body       The content of the attachment (e.g., file data or content string).
     * @param string $mimeType   The MIME type of the attachment (e.g., application/pdf, image/jpeg).
     *                           Defaults to `application/octet-stream`.
     * @param string $disposition The disposition of the attachment, indicating its behavior
     *                            (e.g., `inline` for inline display or `attachment` for download).
     *                            Defaults to `attachment`.
     * @param string $encoding    The encoding used for the attachment content (e.g., `base64`, `7bit`).
     *                            Defaults to `base64`.
     * @param string|null $filename Optional filename for the attachment to suggest to the recipient.
     *
     * @return TransportBuilderInterface Fluent interface to allow method chaining.
     */
    public function addAttachment(
        string $body,
        string $mimeType = Mime::TYPE_OCTETSTREAM,
        string $disposition = Mime::DISPOSITION_ATTACHMENT,
        string $encoding = Mime::ENCODING_BASE64,
        ?string $filename = null,
    ): TransportBuilderInterface {
        // Create a new MIME part for the attachment using the provided parameters.
        $attachment = $this->createAttachment($body, $mimeType, $disposition, $encoding, $filename);

        // Add the newly created attachment to the internal attachments list.
        $this->attachments[] = $attachment;

        // Return the current instance for method chaining.
        return $this;
    }

    /**
     * Resets the transport builder to its initial state.
     *
     * This method clears all data stored in the transport builder, including any attachments
     * that were added. It also calls the parent `reset` method to reset the base class's state.
     *
     * @return $this Fluent interface to allow method chaining.
     */
    public function reset(): TransportBuilderInterface
    {
        // Call the parent reset method to reset base properties and configurations.
        parent::reset();

        // Clear the attachments list to ensure no leftover attachments are sent in the next email.
        $this->attachments = [];

        // Clear the headers list to ensure no leftover headers are sent in the next email.
        $this->headers = [];

        // Return the current instance for method chaining.
        return $this;
    }

    /**
     * Prepares the email message by appending attachments, if any, to the body of the message.
     *
     * @return self Fluent interface to allow method chaining.
     */
    protected function prepareMessage(): TransportBuilderInterface
    {
        // Call the parent method to initialize the base message preparation logic.
        parent::prepareMessage();

        /** @var Message $partsBody The MIME body of the message. */
        $partsBody = $this->message->getBody();

        // Retrieve existing parts of the message body.
        $parts = $partsBody->getParts();

        // Check if there are attachments and no Reflection class exists (indicating Mirasvit transport builder is not used).
        if (! Validator::isEmpty($this->attachments) && ! Reflection::exists(MirasvitTransportBuilderInterface::class)) {
            // Merge the existing parts with the attachments.
            $parts = $this->mergePartsWithAttachments($parts);
        }

        // Check if headers are empty and rebuild the message with the merged parts (or unchanged if no attachments).
        if (! Validator::isEmpty($this->headers) || ! Validator::isEmpty($this->attachments) || ! Validator::isEmpty($this->subject)) {
            $this->message = $this->rebuildEmailMessage($parts);
        }

        return $this;
    }

    /**
     * Merges existing message parts with the current attachments.
     *
     * @param array $parts The existing parts of the message body.
     *
     * @return array The merged array of parts and attachments.
     */
    private function mergePartsWithAttachments(array $parts): array
    {
        return Arr::merge($parts, $this->attachments);
    }

    /**
     * Rebuilds the email message with the provided parts and current message properties.
     *
     * This method constructs a new email message, including headers, body parts,
     * and other necessary details such as recipients, sender, subject, etc.
     *
     * @param array $parts The body parts to be included in the email message.
     *
     * @return EmailMessageInterface The newly created email message object.
     */
    private function rebuildEmailMessage(array $parts): EmailMessageInterface
    {
        // Create a new email message with the necessary properties
        /** @var EmailMessageInterface $message */
        $message = $this->emailMessageFactory->create([
            'cc' => $this->message->getCc(), // Carbon copy recipients
            'to' => $this->message->getTo(), // To recipients
            'bcc' => $this->message->getBcc(), // Blind carbon copy recipients
            'from' => $this->message->getFrom(), // From email address
            'sender' => $this->message->getSender(), // Sender email address
            'replyTo' => $this->message->getReplyTo(), // Reply-to email address
            'subject' => $this->message->getSubject(), // Email subject
            'encoding' => $this->message->getEncoding(), // Encoding type
            'body' => $this->mimeMessageFactory->create([  // Mime body with all parts
                'parts' => $parts,
            ]),
        ]);

        // If headers are available, add them to the message
        if (! Validator::isEmpty($this->headers)) {
            // Iterate over each header and add it to the message
            foreach ($this->headers as $header) {
                // Ensure that each header contains 'key' and 'value'
                if (isset($header[HeaderInterface::KEY], $header[HeaderInterface::VALUE])) {
                    $message->addHeader($header[HeaderInterface::KEY], value: $header[HeaderInterface::VALUE]);
                }
            }
        }

        // If subject is available, add it to the message
        if (Validator::isEmpty($this->subject)) {
            $message->setSubject($this->subject);
        }

        // Return the newly created message
        return $message;
    }

    /**
     * Creates a MIME part for an attachment.
     *
     * This method encapsulates the logic for creating and configuring a MIME part
     * for a given attachment.
     *
     * @param string $body The content of the attachment.
     * @param string $mimeType The MIME type of the attachment (e.g., `application/pdf`).
     * @param string $disposition The disposition of the attachment (e.g., `attachment` or `inline`).
     * @param string $encoding The encoding used for the attachment (e.g., `base64`).
     * @param string|null $filename Optional filename to assign to the attachment.
     *
     * @return Part The configured MIME part for the attachment.
     */
    private function createAttachment(
        string $body,
        string $mimeType,
        string $disposition,
        string $encoding,
        ?string $filename,
    ): Part {
        // Create a new MIME part with the provided content.
        $attachment = $this->partFactory->create(['content' => $body]);

        // Set the MIME type, encoding, disposition, and filename for the attachment.
        $attachment->setType($mimeType);
        $attachment->setEncoding($encoding);
        $attachment->setDisposition($disposition);
        $attachment->setFileName($filename);

        return $attachment;
    }
}
