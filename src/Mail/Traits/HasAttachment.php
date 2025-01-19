<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Traits;

use Laminas\Mime\Mime;
use Magento\Framework\Mail\Message;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Log\Facades\Log;
use Maginium\Framework\Mail\Interfaces\Data\AttachmentInterface;
use Maginium\Framework\Mail\Interfaces\MailerInterface;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Facades\Media;
use Maginium\Framework\Support\Validator;

/**
 * Trait HasAttachment.
 *
 * This trait provides functionality for handling attachments within an envelope context.
 * It enables attaching files to an email message and retrieving or setting attachments.
 * The trait supports different types of attachment inputs, including file paths, arrays,
 * and objects that implement the AttachmentInterface. It ensures that attachments are
 * unique within the list.
 */
trait HasAttachment
{
    /**
     * Attach a file to the message.
     *
     * @param  array|string|AttachmentInterface  $file The file to attach, either as a string path, an array, or an AttachmentInterface.
     * @param  array  $options Additional options for the attachment (e.g., as, mime).
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function attach(array|string|AttachmentInterface $file, array $options = []): MailerInterface
    {
        if ($file instanceof AttachmentInterface) {
            return $file->attachTo($this, $options);
        }

        if (Validator::isArray($file)) {
            foreach ($file as $attachment) {
                if ($attachment instanceof AttachmentInterface) {
                    $attachments[] = $this->buildAttachmentData($attachment);
                }
            }
        }

        // Set the email attachments in the internal data store.
        return $this->setAttachments(collect($this->getData(MailerInterface::ATTACHMENTS) ?? [])
            ->push(compact('file', 'options'))
            ->unique('file') // Ensure uniqueness based on the 'file' key
            ->values() // Reindex the array
            ->all());
    }

    /**
     * Attach multiple files to the message.
     *
     * @param  array  $files
     *
     * @return MailerInterface
     */
    public function attachMany(array $files): MailerInterface
    {
        foreach ($files as $file => $options) {
            if (is_int($file)) {
                $this->attach($options);
            } else {
                $this->attach($file, $options);
            }
        }

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Attach in-memory data as an attachment.
     *
     * @param  string  $data
     * @param  string  $name
     * @param  array  $options
     *
     * @return MailerInterface
     */
    public function attachData(string $data, string $name, array $options = []): MailerInterface
    {
        return $this->setAttachments(collect($this->rawAttachments)
            ->push(compact('data', 'name', 'options'))
            ->unique(fn($file) => $file['name'] . $file['data'])->all());
    }

    /**
     * Retrieve the list of attachments for the email.
     *
     * @return AttachmentInterface[]|null
     */
    public function getAttachments(): ?array
    {
        return $this->getData(MailerInterface::ATTACHMENTS);
    }

    /**
     * Set the list of attachments for the email.
     *
     * @param AttachmentInterface[]|null $attachments An array of file paths or attachment objects.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setAttachments(?array $attachments): MailerInterface
    {
        $this->setData(MailerInterface::ATTACHMENTS, $attachments);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Build attachment data from a file path.
     *
     * Fetches file content, MIME type, and other properties from a file path.
     *
     * @param AttachmentInterface $attachment The file path.
     *
     * @return array|null The standardized attachment data or null on failure.
     */
    private function buildAttachmentData(AttachmentInterface $attachment): ?array
    {
        try {
            $absolutePath = Media::absolutePath($attachment['path']);
            $fileContent = Filesystem::get($absolutePath);

            if ($fileContent === false) {
                throw new Exception("Failed to fetch file content from path: {$absolutePath}");
            }

            return [
                'content' => $fileContent,
                'filename' => $attachment->getAs() ?? Filesystem::basename($absolutePath),
                'type' => $attachment->getMime() ?? Filesystem::mimeType($absolutePath) ?? Mime::TYPE_OCTETSTREAM,
                'disposition' => Mime::DISPOSITION_ATTACHMENT,
                'encoding' => Mime::ENCODING_BASE64,
            ];
        } catch (Exception $e) {
            Log::error('Error fetching attachment data: ' . $e->getMessage());

            return null;
        }
    }
}
