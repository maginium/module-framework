<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Transporters;

use Laminas\Mime\Message;
use Laminas\Mime\Part;
use Laminas\Mime\PartFactory;
use Magento\Framework\App\Area;
use Magento\Framework\HTTP\Mime;
use Magento\Framework\Mail\AddressConverter;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder as BaseTransportBuilder;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\MailException;
use Maginium\Foundation\Exceptions\NoSuchEntityException;
use Maginium\Framework\Log\Facades\Log;
use Maginium\Framework\Mail\Interfaces\Data\EmailMessageInterface;
use Maginium\Framework\Mail\Interfaces\Data\HeaderInterface;
use Maginium\Framework\Mail\Interfaces\MailerInterface;
use Maginium\Framework\Mail\Interfaces\Transporters\LaminasInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;
use Mirasvit\Report\Model\Mail\Template\TransportBuilderInterface as MirasvitLaminasInterface;

class Laminas extends BaseTransportBuilder implements LaminasInterface
{
    /**
     * Message.
     *
     * @var EmailMessageInterface
     */
    protected $message;

    /**
     * @var string|null
     */
    private ?string $subject = null;

    /**
     * @var array
     */
    private array $headers = [];

    /**
     * @var array
     */
    private array $attachments = [];

    /**
     * @var PartFactory
     */
    private PartFactory $partFactory;

    /**
     * @var MimeMessageInterfaceFactory
     */
    private MimeMessageInterfaceFactory $mimeMessageFactory;

    /**
     * @var EmailMessageInterfaceFactory
     */
    private EmailMessageInterfaceFactory $emailMessageFactory;

    /**
     * @var StateInterface
     */
    private StateInterface $inlineTranslation;

    /**
     * TransportBuilder constructor.
     *
     * @param PartFactory $partFactory
     * @param MessageInterface $message
     * @param FactoryInterface $templateFactory
     * @param StateInterface $inlineTranslation
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
        StateInterface $inlineTranslation,
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
        $this->inlineTranslation = $inlineTranslation;
        $this->mimeMessageFactory = $mimeMessageFactory;
        $this->emailMessageFactory = $emailMessageFactory;

        $this->reset();
    }

    /**
     * Get message subject.
     *
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * Set message subject.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject): LaminasInterface
    {
        $this->subject = $subject;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get custom headers for the email message.
     *
     * @return array|null
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    /**
     * Set custom headers for the email message.
     *
     * @param array $headers Associative array of headers where the key is the header name and the value is the header value.
     *
     * @return $this
     */
    public function setHeaders(array $headers = []): LaminasInterface
    {
        $this->headers = $headers;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get attachments to the email message.
     *
     * @return array|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
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
     * @return LaminasInterface Fluent interface to allow method chaining.
     */
    public function addAttachment(
        string $body,
        string $mimeType = Mime::TYPE_OCTETSTREAM,
        string $disposition = Mime::DISPOSITION_ATTACHMENT,
        string $encoding = Mime::ENCODING_BASE64,
        ?string $filename = null,
    ): LaminasInterface {
        // Create a new MIME part for the attachment using the provided parameters.
        $attachment = $this->createAttachment($body, $mimeType, $disposition, $encoding, $filename);

        // Add the newly created attachment to the internal attachments list.
        $this->attachments[] = $attachment;

        // Return the current instance to allow method chaining
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
    public function reset(): LaminasInterface
    {
        // Call the parent reset method to reset base properties and configurations.
        parent::reset();

        // Clear the attachments list to ensure no leftover attachments are sent in the next email.
        $this->attachments = [];

        // Clear the headers list to ensure no leftover headers are sent in the next email.
        $this->headers = [];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Sends an email using the specified mailer mailer.
     *
     * This method dispatches the email defined by the provided mailer to the configured
     * transport layer for delivery. Inline translations are temporarily suspended to ensure
     * proper email content generation. Errors during the process are logged and exceptions are re-thrown.
     *
     * @param MailerInterface $mailer The mailer containing the email details to be sent.
     *
     * @throws NoSuchEntityException If the email template cannot be found.
     * @throws MailException If an error occurs during the email sending process.
     * @throws Exception For any unexpected errors during execution.
     */
    public function send(MailerInterface $mailer): void
    {
        // Temporarily suspend inline translations to avoid conflicts with email content
        $this->inlineTranslation->suspend();

        try {
            // Resolve the appropriate store ID (fall back to default if not provided)
            $storeId = $mailer->getStoreId();

            // Prepare and configure the email template for the specified store
            $this->setTemplateIdentifier($mailer->getTemplateId());

            // Set the template variables based on the provided data
            $templateData = DataObject::make($mailer->getTemplateData() ?? [])->toArray();
            $this->setTemplateVars($templateData);

            // Merge metadata with default template options and set them
            $metadata = DataObject::make($mailer->getMetadata() ?? [])->toArray();
            $templateOptions = $this->mergeTemplateOptions($metadata, $storeId);
            $this->setTemplateOptions($templateOptions);

            // Set the sender's information (name and email address) based on the provided scope
            $this->setFromByScope([
                'name' => $mailer->getFrom()->getName(),
                'email' => $mailer->getFrom()->getEmail(),
            ], $storeId);

            // Add the primary recipient to the email
            $this->addTo($mailer->getTo()->getEmail(), $mailer->getTo()->getName());

            // Add CC recipients if any are provided
            if ($mailer->getCc()) {
                Arr::map(
                    $mailer->getCc(),
                    fn($cc) => $this->addCc($cc->getEmail(), $cc->getName()),
                );
            }

            // Add BCC recipients if any are provided
            if ($mailer->getBcc()) {
                Arr::map(
                    $mailer->getBcc(),
                    fn($bcc) => $this->addCc($bcc->getEmail(), $bcc->getName()),
                );
            }

            // Set the reply-to address if specified
            if ($replyTo = $mailer->getReplyTo()) {
                $this->setReplyTo($replyTo->getEmail(), $replyTo->getName());
            }

            // Set custom headers if they are provided
            $headers = $mailer->getHeaders() ?? [];

            if (! Validator::isEmpty($headers)) {
                $this->setHeaders(DataObject::make($headers)->toArray());
            }

            // Set a custom subject line if provided
            if (! Validator::isEmpty($mailer->getSubject())) {
                $this->setSubject($mailer->getSubject());
            }

            // Add attachments if provided
            // $this->addAttachments($mailer->getAttachments());

            // Send the email using the transport object
            $this->getTransport()->sendMessage();
        } catch (NoSuchEntityException $e) {
            // Log and rethrow error if the email template cannot be found
            Log::error('Email template not found: ' . $e->getMessage());

            throw $e;
        } catch (MailException $e) {
            // Log and rethrow mail-specific exceptions
            Log::error('Error sending email: ' . $e->getMessage());

            throw $e;
        } catch (Exception $e) {
            // Log unexpected errors and wrap them in a MailException for consistent error handling
            Log::error('Unexpected error during email sending: ' . $e->getMessage());

            throw new MailException(__('Error sending email: %1', $e->getMessage()), $e);
        } finally {
            // Resume inline translations after the email sending process is complete
            $this->inlineTranslation->resume();
        }
    }

    /**
     * Prepares the email message by appending attachments, if any, to the body of the message.
     *
     * @return self Fluent interface to allow method chaining.
     */
    protected function prepareMessage(): LaminasInterface
    {
        // Call the parent method to initialize the base message preparation logic.
        parent::prepareMessage();

        /** @var Message $partsBody The MIME body of the message. */
        $partsBody = $this->message->getBody();

        // Retrieve existing parts of the message body.
        $parts = $partsBody->getParts();

        // Check if there are attachments and no Reflection class exists (indicating Mirasvit transport builder is not used).
        if (! Validator::isEmpty($this->getAttachments()) && ! Reflection::exists(MirasvitLaminasInterface::class)) {
            // Merge the existing parts with the attachments.
            $parts = $this->mergePartsWithAttachments($parts);
        }

        // Check if headers are empty and rebuild the message with the merged parts (or unchanged if no attachments).
        if (! Validator::isEmpty($this->getHeaders()) || ! Validator::isEmpty($this->getAttachments()) || ! Validator::isEmpty($this->getSubject())) {
            $this->message = $this->rebuildEmailMessage($parts);
        }

        // Return the current instance to allow method chaining
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
        return Arr::merge($parts, $this->getAttachments());
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
        if (! Validator::isEmpty($this->getHeaders())) {
            // Iterate over each header and add it to the message
            foreach ($this->getHeaders() as $header) {
                // Ensure that each header contains 'key' and 'value'
                if (isset($header[HeaderInterface::KEY], $header[HeaderInterface::VALUE])) {
                    $message->addHeader($header[HeaderInterface::KEY], value: $header[HeaderInterface::VALUE]);
                }
            }
        }

        // If subject is available, add it to the message
        if (! Validator::isEmpty($this->getSubject())) {
            $message->setSubject($this->getSubject());
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

    /**
     * Merge template options with default values.
     *
     * This method merges the provided template options with a set of default template options.
     * If no template options are provided, it returns the default options.
     *
     * @param array|DataObject|null $templateOptions The provided template options. Can be null.
     * @param int $storeId The store ID to include in the template options.
     *
     * @return array Merged template options.
     */
    private function mergeTemplateOptions(array|DataObject|null $templateOptions, int $storeId): array
    {
        // Default template options (store ID and frontend area are mandatory)
        $defaultTemplateOptions = [
            'store' => $storeId,
            'area' => Area::AREA_FRONTEND,
        ];

        // If templateOptions is a DataObject, extract its data
        if ($templateOptions instanceof DataObject && method_exists($templateOptions, 'getData')) {
            $templateOptions = $templateOptions->getData();
        }

        // Merge the provided options with default options and return the result
        return Arr::merge($defaultTemplateOptions, $templateOptions ?? []);
    }
}
