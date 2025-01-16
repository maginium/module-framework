<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Traits;

use Magento\Framework\Mail\Message;
use Maginium\Framework\Mail\Interfaces\Data\AttachmentInterface;
use Maginium\Framework\Mail\Interfaces\MailerInterface;

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

        // Add the attachment to the list, ensuring uniqueness
        $this->setData(AttachmentInterface::ATTACHMENTS, collect($this->attachments ?? [])
            ->push(compact('file', 'options'))
            ->unique('file') // Ensure uniqueness based on the 'file' key
            ->values() // Reindex the array
            ->all());

        return $this;
    }
}
