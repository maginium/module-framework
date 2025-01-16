<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interfaces\Transporters;

use Magento\Framework\HTTP\Mime;
use Maginium\Framework\Mail\Interfaces\TransportBuilderInterface;

/**
 * Interface LaminasInterface.
 *
 * Provides an extension of the core TransportBuilder to include functionality
 * for adding attachments to email messages.
 */
interface LaminasInterface extends TransportBuilderInterface
{
    /**
     * Add cc address.
     *
     * @param array|string $address
     * @param string $name
     *
     * @return self
     */
    public function addCc($address, $name = '');

    /**
     * Add to address.
     *
     * @param array|string $address
     * @param string $name
     *
     * @return self
     */
    public function addTo($address, $name = '');

    /**
     * Add bcc address.
     *
     * @param array|string $address
     *
     * @return self
     */
    public function addBcc($address);

    /**
     * Set Reply-To Header.
     *
     * @param string $email
     * @param string|null $name
     *
     * @return self
     */
    public function setReplyTo($email, $name = null);

    /**
     * Set mail from address.
     *
     * @param string|array $from
     *
     * @return self
     *
     * @deprecated Use setFromByScope instead.
     */
    public function setFrom($from);

    /**
     * Set mail from address by scopeId.
     *
     * @param string|array $from
     * @param string|int $scopeId
     *
     * @throws MailException
     *
     * @return self
     */
    public function setFromByScope($from, $scopeId = null);

    /**
     * Set template identifier.
     *
     * @param string $templateIdentifier
     *
     * @return self
     */
    public function setTemplateIdentifier($templateIdentifier);

    /**
     * Set template model.
     *
     * @param string $templateModel
     *
     * @return self
     */
    public function setTemplateModel($templateModel);

    /**
     * Set template variables.
     *
     * @param array $templateVars
     *
     * @return self
     */
    public function setTemplateVars(array $templateVars);

    /**
     * Set template options.
     *
     * @param array $templateOptions
     *
     * @return self
     */
    public function setTemplateOptions(array $templateOptions);

    /**
     * Get mail transport.
     *
     * @throws LocalizedException
     *
     * @return TransportInterface
     */
    public function getTransport();

    /**
     * Get message subject.
     *
     * @return string|null
     */
    public function getSubject(): ?string;

    /**
     * Set message subject.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject): self;

    /**
     * Get custom headers for the email message.
     *
     * @return array|null
     */
    public function getHeaders(): ?array;

    /**
     * Set custom headers for the email message.
     *
     * @param array $headers Associative array of headers where the key is the header name and the value is the header value.
     *
     * @return $this
     */
    public function setHeaders(array $headers = []): self;

    /**
     * Get attachments to the email message.
     *
     * @return array|null
     */
    public function getAttachments(): ?array;

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
    ): self;
}
