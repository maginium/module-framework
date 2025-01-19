<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interfaces\Data;

use Closure;
use Magento\Framework\Mail\Mailable;
use Magento\Framework\Mail\Message;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Support\Facades\Storage;

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

    /**
     * Create a mail attachment from a file path.
     *
     * This method allows an attachment to be created from a specified file path.
     * The file will be retrieved using the provided path strategy and attached
     * to the mail message. The path can point to a local file or a cloud storage
     * location.
     *
     * @param string $path The path to the file to be attached.
     *
     * @return static Returns a new instance of the Attachment class.
     */
    public static function fromPath(string $path): self;

    /**
     * Create a mail attachment from a URL.
     *
     * This method provides an alternative way to create an attachment by using
     * a URL. The URL can point to a remote file, and the attachment will be
     * fetched and attached to the email message.
     *
     * @param string $url The URL of the file to be attached.
     *
     * @return static Returns a new instance of the Attachment class.
     */
    public static function fromUrl(string $url): self;

    /**
     * Create a mail attachment from in-memory data.
     *
     * This method allows you to create an attachment from dynamic, in-memory data.
     * The provided closure will be executed to generate the attachment data,
     * and you can also specify an optional custom name for the attachment.
     *
     * @param Closure $data The closure to retrieve the in-memory attachment data.
     * @param string|null $name Optional custom name for the attachment file.
     *
     * @return static Returns an instance of the Attachment class.
     */
    public static function fromData(Closure $data, ?string $name = null): self;

    /**
     * Create a mail attachment from a file in the default storage disk.
     *
     * This method creates an attachment from a file stored in the default storage disk, providing an easy way
     * to attach files that are stored within the application’s storage.
     *
     * @param string $path The path to the file to be attached.
     *
     * @return static Returns an instance of the Attachment class.
     */
    public static function fromStorage(string $path): self;

    /**
     * Create a mail attachment from a file in the specified storage disk.
     *
     * This method allows for attaching a file from a specific storage disk, which is useful
     * when working with various storage systems like local file storage, S3, etc.
     *
     * @param string|null $disk The storage disk where the file is stored (e.g., 'local', 's3'). Defaults to null.
     * @param string $path The path to the file on the specified storage disk.
     *
     * @return static Returns an instance of the Attachment class.
     */
    public static function fromStorageDisk(?string $disk, string $path): self;

    /**
     * Set the attached file's filename.
     *
     * This method allows you to specify the filename that the attachment will have in the email. If no filename
     * is provided, the original file name will be used.
     *
     * @param string|null $name The desired filename for the attachment.
     *
     * @return $this Returns the current instance of Attachment for method chaining.
     */
    public function as(?string $name): self;

    /**
     * Get the file name for the attachment.
     *
     * @return string
     */
    public function getAs(): string;

    /**
     * Set the file name for the attachment.
     *
     * @param string $as The file name.
     *
     * @return AttachmentInterface
     */
    public function setAs(string $as): self;

    /**
     * Set the attached file's MIME type.
     *
     * The MIME type of the attachment is essential for email clients to interpret the file correctly (e.g., PDF,
     * image, etc.).
     *
     * @param string $mime The MIME type of the attachment.
     *
     * @return $this Returns the current instance of Attachment for method chaining.
     */
    public function withMime(string $mime): self;

    /**
     * Get the MIME type for the attachment.
     *
     * @return string
     */
    public function getMime(): string;

    /**
     * Set the MIME type for the attachment.
     *
     * @param string $mime The MIME type.
     *
     * @return AttachmentInterface
     */
    public function setMime(string $mime): self;

    /**
     * Attach the attachment with the given strategies.
     *
     * This method triggers the attachment process using the provided path and data strategies. The strategies
     * define how to retrieve the attachment's data and how to handle its file path.
     *
     * @param Closure $pathStrategy The closure for retrieving the file path.
     * @param Closure $dataStrategy The closure for retrieving the in-memory data.
     *
     * @return mixed Returns the result of the attachment operation.
     */
    public function attachWith(Closure $pathStrategy, Closure $dataStrategy);

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

    /**
     * Determine if the given attachment is equivalent to this attachment.
     *
     * This method compares the current attachment with another attachment to check if they are equivalent based on
     * the file name and MIME type. Useful for deduplicating attachments.
     *
     * @param AttachmentInterface $attachment The attachment to compare with the current one.
     * @param array $options Optional options to override the comparison (e.g., 'as' for filename, 'mime' for MIME type).
     *
     * @return bool Returns true if the attachments are equivalent, false otherwise.
     */
    public function isEquivalent(self $attachment, array $options = []): bool;
}
