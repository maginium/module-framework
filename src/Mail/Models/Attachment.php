<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models;

use Closure;
use Magento\Framework\Mail\Mailable;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Database\ObjectModel;
use Maginium\Framework\Mail\Interfaces\Data\AttachmentInterface;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Storage;

/**
 * Class Attachment
 * Represents a file attachment in an email message.
 *
 * This class allows for the creation of email attachments that can be dynamically
 * attached to an email message. It supports both file paths and URLs as sources
 * for the attachment and provides a flexible resolver mechanism to attach the file
 * to the message.
 */
class Attachment extends ObjectModel implements AttachmentInterface
{
    /**
     * A callback that defines how to attach the file to the mail message.
     * The callback will be executed with the attachment and a path strategy.
     *
     * @var Closure
     */
    protected Closure $resolver;

    /**
     * Attachment constructor.
     *
     * This private constructor ensures that the attachment can only be created via
     * the provided static methods (i.e., `fromPath()` or `fromUrl()`), ensuring
     * flexibility and proper encapsulation. The resolver closure is optional and
     * allows for customization of the attachment's behavior if necessary.
     *
     * @param array $attributes The attributes to initialize the attachment object with.
     * @param Closure|null $resolver A closure that defines the attachment's behavior (optional).
     */
    public function __construct($attributes = [], ?Closure $resolver = null)
    {
        // Initialize the parent construct
        parent::__construct($attributes);

        $this->resolver = $resolver;
    }

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
    public static function fromPath(string $path): self
    {
        // Return a new Attachment instance with a resolver that handles the path
        return new static(function($attachment, $pathStrategy) use ($path) {
            $pathStrategy($path, $attachment);
        });
    }

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
    public static function fromUrl(string $url): self
    {
        // Leverage fromPath method for URL, as URLs can be treated as paths
        return static::fromPath($url);
    }

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
    public static function fromData(Closure $data, ?string $name = null): self
    {
        return Container::make(static::class, [
            'resolver' => fn($attachment, $pathStrategy, $dataStrategy) => $dataStrategy($data, $attachment),
        ])->setData(AttachmentInterface::AS, $name);
    }

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
    public static function fromStorage(string $path): self
    {
        return static::fromStorageDisk(null, $path);
    }

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
    public static function fromStorageDisk(?string $disk, string $path): self
    {
        return Container::make(static::class, [
            'resolver' => function($attachment, $pathStrategy, $dataStrategy) use ($disk, $path) {
                $storage = Storage::disk($disk);

                // Set the attachment name if not already set, defaulting to the file name from the path
                $attachment->setData(
                    AttachmentInterface::AS,
                    $attachment->getData(AttachmentInterface::AS) ?? basename($path),
                );

                // Set the MIME type of the attachment if not already set, using the storage disk's MIME type retrieval
                $attachment->setData(
                    AttachmentInterface::MIME,
                    $attachment->getData(AttachmentInterface::MIME) ?? $storage->mimeType($path),
                );

                // Retrieve and attach file data using the provided data strategy
                return $dataStrategy(
                    fn() => $storage->get($path),
                    $attachment,
                );
            },
        ]);
    }

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
    public function as(?string $name): self
    {
        $this->setData(AttachmentInterface::AS, $name);

        return $this;
    }

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
    public function withMime(string $mime): self
    {
        $this->setData(AttachmentInterface::MIME, $mime);

        return $this;
    }

    /**
     * Retrieve the list of attachments for the email.
     *
     * @return AttachmentInterface[]|null
     */
    public function getAttachments(): ?array
    {
        return $this->getData(static::ATTACHMENTS);
    }

    /**
     * Set the list of attachments for the email.
     *
     * @param AttachmentInterface[]|null $attachments An array of file paths or attachment objects.
     *
     * @return AttachmentInterface Returns the current instance for method chaining.
     */
    public function setAttachments(?array $attachments): AttachmentInterface
    {
        $this->setData(static::ATTACHMENTS, $attachments);

        return $this;
    }

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
    public function attachWith(Closure $pathStrategy, Closure $dataStrategy)
    {
        return ($this->resolver)($this, $pathStrategy, $dataStrategy);
    }

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
    public function attachTo($mail, array $options = []): mixed
    {
        // Using the attachWith method to handle attachment logic
        return $this->attachWith(
            // The first closure deals with attaching a file path to the mail
            fn($path): mixed => $mail->attach($path, [
                // Use the provided 'as' option for filename, or fall back to the default 'as' property of the attachment
                AttachmentInterface::AS => $options[AttachmentInterface::AS] ?? $this->getData(AttachmentInterface::AS),
                // Use the provided 'mime' option for MIME type, or fall back to the default 'mime' property of the attachment
                AttachmentInterface::MIME => $options[AttachmentInterface::MIME] ?? $this->getData(AttachmentInterface::MIME),
            ]),
            // The second closure is used for attaching data, if the attachment is in-memory (as data, not file)
            function($data) use ($mail, $options) {
                // Prepare the options array to ensure 'as' and 'mime' are available
                $options = [
                    AttachmentInterface::AS => $options[AttachmentInterface::AS] ?? $this->getData(AttachmentInterface::AS), // Use provided 'as' or the default 'as'
                    AttachmentInterface::MIME => $options[AttachmentInterface::MIME] ?? $this->getData(AttachmentInterface::MIME), // Use provided 'mime' or the default 'mime'
                ];

                // Ensure a filename ('as') is provided. If not, throw an exception
                if ($options[AttachmentInterface::AS] === null) {
                    throw RuntimeException::make(__('Attachment requires a filename to be specified.'));
                }

                // Attach the data to the mail with the 'as' filename and 'mime' type
                return $mail->attachData($data(), $options[AttachmentInterface::AS], [AttachmentInterface::MIME => $options[AttachmentInterface::MIME]]);
            },
        );
    }

    /**
     * Determine if the given attachment is equivalent to this attachment.
     *
     * This method compares the current attachment with another attachment to check if they are equivalent based on
     * the file name and MIME type. Useful for deduplicating attachments.
     *
     * @param self $attachment The attachment to compare with the current one.
     * @param array $options Optional options to override the comparison (e.g., 'as' for filename, 'mime' for MIME type).
     *
     * @return bool Returns true if the attachments are equivalent, false otherwise.
     */
    public function isEquivalent(self $attachment, array $options = []): bool
    {
        return $this->attachWith(
            // Compare the file path with the other attachment
            fn($path) => [$path, $options],
            // Compare the data with the other attachment
            fn($data) => [$data(), $options],
        ) === $attachment->attachWith(
            // Compare the file path with the other attachment
            fn($path) => [$path, $options],
            // Compare the data with the other attachment
            fn($data) => [$data(), $options],
        );
    }
}
