<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail;

use Maginium\Framework\Mail\Models\Attachment as BaseAttachment;

/**
 * Class Attachment
 * Represents a file attachment in an email message.
 *
 * This class allows for the creation of email attachments that can be dynamically
 * attached to an email message. It supports both file paths and URLs as sources
 * for the attachment and provides a flexible resolver mechanism to attach the file
 * to the message.
 */
class Attachment extends BaseAttachment
{
}
