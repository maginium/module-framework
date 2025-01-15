<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interfaces\Data;

use Maginium\Foundation\Exceptions\RuntimeException;

/**
 * Interface defining the structure for an email attachment.
 */
interface AttachmentInterface
{
    /**
     * Constant for the attachment file name attribute.
     *
     * @var string
     */
    public const AS = 'as';

    /**
     * Constant for the attachment MIME type attribute.
     *
     * @var string
     */
    public const MIME = 'mime';

    public const ATTACHMENTS = 'attachments';

    /**
     * Attach the attachment to a built-in mail type (e.g., Mailable).
     *
     * This method allows attaching the current attachment to a Mailable, including optional parameters for file name
     * and MIME type. If no parameters are provided, the attachment's properties are used.
     *
     * @param Mailable $mail The mail object to which the attachment will be added.
     * @param array $options Optional additional options for the attachment (e.g., 'as' for filename, 'mime' for MIME type).
     *
     * @throws RuntimeException If the filename is not provided.
     *
     * @return mixed Returns the result of the mail attachment operation.
     */
    public function attachTo($mail, array $options = []): mixed;
}
