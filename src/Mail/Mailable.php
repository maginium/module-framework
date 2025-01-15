<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail;

use Carbon\CarbonInterval;
use DateInterval;
use DateTimeInterface;
use InvalidArgumentException;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Mime;
use Magento\Framework\Translate\Inline\StateInterface;
use Maginium\Foundation\Enums\Directions;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Mail\Helpers\Data as MailHelper;
use Maginium\Framework\Mail\Interfaces\Data\AddressInterface;
use Maginium\Framework\Mail\Interfaces\Data\EnvelopeInterface;
use Maginium\Framework\Mail\Interfaces\MailableInterface;
use Maginium\Framework\Mail\Interfaces\TransportBuilderInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Date;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Facades\Locale;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Media;
use Maginium\Framework\Support\Facades\Publisher;
use Maginium\Framework\Support\Facades\StoreManager;
use Maginium\Framework\Support\Validator;
use Maginium\Store\Interfaces\Data\StoreInterface;

/**
 * Class Mailable.
 *
 * Service class for sending emails using Magento's TransportBuilder.
 */
class Mailable implements MailableInterface
{
    /**
     * @var MailHelper
     */
    protected MailHelper $mailerHelper;

    /**
     * @var StateInterface
     */
    protected StateInterface $inlineTranslation;

    /**
     * @var TemplateFactory
     */
    protected TemplateFactory $templateFactory;

    /**
     * @var TransportBuilderInterface
     */
    protected TransportBuilderInterface $transportBuilder;

    /**
     * Mailable constructor.
     *
     * @param MailHelper $mailerHelper
     * @param TemplateFactory $templateFactory
     * @param StateInterface $inlineTranslation
     * @param TransportBuilderInterface $transportBuilder
     */
    public function __construct(
        MailHelper $mailerHelper,
        TemplateFactory $templateFactory,
        StateInterface $inlineTranslation,
        TransportBuilderInterface $transportBuilder,
    ) {
        $this->mailerHelper = $mailerHelper;
        $this->templateFactory = $templateFactory;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
    }

    /**
     * Send an email immediately.
     *
     * This method sends an email with the provided content and envelope, using the transport mechanism.
     * It is expected that any necessary processing, such as rendering templates or setting headers, will be
     * performed by the implementation of this method.
     *
     * @param EnvelopeInterface $mail The envelope containing email headers and content to be sent.
     *
     * @throws MailException If an error occurs while sending the email.
     */
    public function send(EnvelopeInterface $mail): void
    {
        // Suspend inline translation during email sending process
        $this->inlineTranslation->suspend();

        try {
            // Determine store information based on provided or default store ID
            $storeId = $this->getStoreId($mail->getStoreId());

            // Determine the email template ID or content type
            $this->prepareEmailTemplate($mail->getTemplateId(), storeId: $storeId);

            // Set email template variables
            $templateData = DataObject::make($mail->getTemplateData() ?? [])->toArray();
            $this->transportBuilder->setTemplateVars($templateData);

            // Set email template options
            $metadata = DataObject::make($mail->getMetadata() ?? [])->toArray();

            $templateOptions = $this->mergeTemplateOptions($metadata, $storeId);
            $this->transportBuilder->setTemplateOptions($templateOptions);

            // Set sender information
            $this->transportBuilder->setFromByScope([
                'name' => $mail->getFrom()->getName(),
                'email' => $mail->getFrom()->getEmail(),
            ], $storeId);

            // Add recipient's email address and name
            $this->transportBuilder->addTo($mail->getTo()->getEmail(), $mail->getTo()->getName());

            // Add CC recipients if provided and not empty
            if ($mail->getCc()) {
                $this->addCcRecipients($mail->getCc());
            }

            // Add BCC recipients if provided and not empty
            if ($mail->getBcc()) {
                $this->addBccRecipients($mail->getBcc());
            }

            // Set reply-to email if provided
            $this->setReplyTo($mail->getReplyTo());

            // Set custom headers if provided
            $headers = DataObject::make($mail->getHeaders() ?? [])->toArray();

            if (! Validator::isEmpty($headers)) {
                $this->transportBuilder->setHeaders($headers);
            }

            // Set custom headers if provided
            if (! Validator::isEmpty($mail->getSubject())) {
                $this->transportBuilder->setSubject($mail->getSubject());
            }

            // Add attachments if provided
            // $this->addAttachments($mail->getAttachments());

            // Build the transport object
            $transport = $this->transportBuilder->getTransport();

            // Send the email
            $transport->sendMessage();
        } catch (NoSuchEntityException $e) {
            Log::error('Email template not found: ' . $e->getMessage());

            throw $e;
        } catch (MailException $e) {
            Log::error('Error sending email: ' . $e->getMessage());

            throw $e;
        } catch (Exception $e) {
            Log::error('An unexpected error occurred while sending email: ' . $e->getMessage());

            throw new MailException(__('Error sending email: %1', $e->getMessage()), $e);
        } finally {
            // Resume inline translation after email sending process
            $this->inlineTranslation->resume();
        }
    }

    /**
     * Queue an email for immediate processing.
     *
     * @param EnvelopeInterface $mail The envelope containing email headers and content to be queued.
     *
     * @throws MailException If an error occurs while queuing the email.
     */
    public function queue(EnvelopeInterface $mail): void
    {
        // Call the processQueue method to handle queuing the email to the correct queue with or without delay.
        $this->processQueue($mail, self::QUEUE_NAME);
    }

    /**
     * Queue an email for later processing.
     *
     * @param EnvelopeInterface $mail The envelope containing email headers and content to be queued.
     * @param DateTimeInterface|DateInterval|int $delay The delay before processing the email, in milliseconds.
     *
     * @throws MailException If an error occurs while queuing the email.
     */
    public function later(EnvelopeInterface $mail, DateTimeInterface|DateInterval|int $delay): void
    {
        // Call the processQueue method to queue the email for later processing, passing the 'delayed' queue name.
        $this->processQueue($mail, self::DELAY_QUEUE_NAME, $delay);
    }

    /**
     * Queue an email for processing (immediate or delayed).
     *
     * @param EnvelopeInterface $mail The envelope containing email headers and content to be queued.
     * @param string $queueName The name of the queue to dispatch the email to.
     * @param DateTimeInterface|DateInterval|int|null $delay The delay before processing the email, in milliseconds. Default is null.
     *
     * @throws MailException If an error occurs while queuing the email.
     */
    private function processQueue(EnvelopeInterface $mail, string $queueName, DateTimeInterface|DateInterval|int|null $delay = null): void
    {
        // Initialize headers array
        $headers = [];

        // If delay is provided, convert it to milliseconds and add the 'x-delay' and 'expiration' headers
        if ($delay !== null) {
            $headers['x-delay'] = $this->convertDelayToMilliseconds($delay);
            $headers['expiration'] = $this->convertDelayToMilliseconds($delay);
        }

        // Dispatch the message to the specified queue
        Publisher::dispatch($queueName, $mail, $headers);
    }

    /**
     * Convert the delay to milliseconds using Carbon.
     *
     * @param DateTimeInterface|DateInterval|int $delay The delay value.
     *
     * @return int The delay in milliseconds.
     */
    private function convertDelayToMilliseconds(DateTimeInterface|DateInterval|int $delay): int
    {
        // If delay is an integer, it's already in milliseconds
        if (is_int($delay)) {
            return $delay;
        }

        // If delay is a DateTimeInterface (Carbon instance or similar), calculate the difference in milliseconds from now
        if ($delay instanceof DateTimeInterface) {
            // Use Carbon to handle DateTime and calculate the difference in milliseconds
            $now = Date::now();

            return $now->diffInMilliseconds(Date::instance($delay)); // Returns the difference in milliseconds
        }

        // If delay is a DateInterval, convert it to milliseconds (assuming it's a future interval)
        if ($delay instanceof DateInterval) {
            // Use CarbonInterval to handle DateInterval
            $interval = CarbonInterval::instance($delay);

            return (int)$interval->totalMinutes * 60 * 1000; // Convert interval to milliseconds
        }

        // Default return (if no valid delay provided, return 0)
        return 0;
    }

    /**
     * Prepare the email content for sending.
     *
     * This method prepares the email template, considering special conditions such as Right-To-Left (RTL) direction
     * for specific locales.
     *
     * @param string $templateId The email template identifier.
     * @param int $storeId The store ID for which the email is being sent.
     *
     * @throws InvalidArgumentException If email content is empty.
     *
     * @return void
     */
    private function prepareEmailTemplate(string $templateId, int $storeId): void
    {
        // Append RTL direction to template if needed based on locale or configuration
        if ($this->shouldAppendRtlDirection($templateId, $storeId)) {
            $templateId .= Directions::RTL; // Modify template ID for RTL locales
        }

        // Set the prepared template identifier for the email transport builder
        $this->transportBuilder->setTemplateIdentifier($templateId);
    }

    /**
     * Check if RTL direction should be appended to the template ID.
     *
     * This method determines whether the template should have RTL (Right-to-Left) direction based on
     * the store locale or specific configuration settings.
     *
     * @param string $templateId The template ID.
     * @param int $storeId The store ID.
     *
     * @return bool Whether RTL direction should be appended.
     */
    private function shouldAppendRtlDirection(string &$templateId, int $storeId): bool
    {
        // Get the locale of the store
        $storeLocale = $this->getStoreLocale($storeId);

        // Set the scope of the configuration to the store ID
        Config::setScopeId($storeId);

        // Check if RTL should be appended based on configuration or store locale
        return Config::getBool(static::XML_PATH_MAILER_IS_RTL)
            || (isset($storeLocale) && Locales::isRtl($storeLocale)); // Returns true if RTL is required
    }

    /**
     * Get the store locale based on the provided or default store ID.
     *
     * This method determines the locale of a store based on the provided store ID. If no store ID is provided,
     * it will use the default store's ID to fetch the locale.
     *
     * @param int|null $storeId The store ID. If null, the default store ID will be used.
     *
     * @return string|null The locale of the store. Returns null if no store is found.
     */
    private function getStoreLocale(?int $storeId): ?string
    {
        // If no store ID is provided, use the default store's ID and return its locale
        if (! $storeId) {
            /** @var StoreInterface $defaultStore */
            $defaultStore = StoreManager::getStore();
            $storeId = $defaultStore->getId();

            return $defaultStore->getLocale();
        }

        // If a specific store ID is provided, return the locale of that store
        /** @var StoreInterface $specifiedStore */
        $specifiedStore = StoreManager::getStore($storeId);

        return $specifiedStore->getLocale();
    }

    /**
     * Get the store ID based on the provided or default store ID.
     *
     * This method resolves the store ID by either returning the provided store ID
     * or the default store ID if none is provided.
     *
     * @param int|null $storeId The store ID. If null, the default store ID will be used.
     *
     * @return int The resolved store ID.
     */
    private function getStoreId(?int $storeId): int
    {
        // Return the provided store ID or the default store ID if none is provided
        return $storeId ?: (int)StoreManager::getStore()->getId();
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

    /**
     * Add CC recipients to the email.
     *
     * This method adds CC recipients to the email being built by the TransportBuilder.
     *
     * @param array|null $ccRecipients List of CC recipients. Each recipient should be an object with getEmail() and getName() methods.
     */
    private function addCcRecipients(?array $ccRecipients): void
    {
        // If there are CC recipients, add each one to the TransportBuilder
        if (! Validator::isEmpty($ccRecipients)) {
            foreach ($ccRecipients as $ccRecipient) {
                $this->transportBuilder->addCc($ccRecipient->getEmail(), $ccRecipient->getName());
            }
        }
    }

    /**
     * Add BCC recipients to the email.
     *
     * This method adds BCC recipients to the email being built by the TransportBuilder.
     *
     * @param array|null $bccRecipients List of BCC recipients. Each recipient should be an object with a getEmail() method.
     */
    private function addBccRecipients(?array $bccRecipients): void
    {
        // If there are BCC recipients, add each one to the TransportBuilder
        if (! Validator::isEmpty($bccRecipients)) {
            foreach ($bccRecipients as $bccRecipient) {
                $this->transportBuilder->addBcc($bccRecipient->getEmail());
            }
        }
    }

    /**
     * Set the reply-to email address and name.
     *
     * This method sets the reply-to email address and name for the email being built by the TransportBuilder.
     *
     * @param AddressInterface|null $replyTo The reply-to address and name. Can be null.
     */
    private function setReplyTo(?AddressInterface $replyTo): void
    {
        // If a reply-to address is provided, set it on the TransportBuilder
        if ($replyTo) {
            $this->transportBuilder->setReplyTo($replyTo->getEmail(), $replyTo->getName());
        }
    }

    /**
     * Add attachments to the email.
     *
     * This method processes and adds attachments to the email from the provided URLs.
     *
     * @param array|DataObject|null $attachments List of attachment URLs or a DataObject containing attachment data.
     */
    private function addAttachments(array|DataObject|null $attachments): void
    {
        // If no attachments are provided, return early to avoid unnecessary processing
        if (Validator::isEmpty($attachments)) {
            return;
        }

        // If attachments are in a DataObject, extract the data from it
        if ($attachments instanceof DataObject) {
            $attachments = $attachments->getData();
        }

        // If attachments are not an array, log an error and exit the method
        if (! is_array($attachments)) {
            Log::error('Invalid attachment format. Expected an array or DataObject.');

            return;
        }

        // Process each attachment URL and add it to the email transport builder
        foreach ($attachments as $url) {
            $this->processAttachment($url);
        }
    }

    /**
     * Process an individual attachment.
     *
     * This method builds the attachment data from the URL, checks its validity,
     * and then adds it to the transport builder if valid.
     *
     * @param string $url The attachment URL.
     */
    private function processAttachment(string $url): void
    {
        try {
            // Build the attachment data using the provided URL
            $attachmentData = $this->buildAttachmentData($url);

            // If valid attachment data is returned, add it to the transport builder
            if ($attachmentData) {
                $this->transportBuilder->addAttachment(
                    $attachmentData['content'],
                    $attachmentData['type'],
                    $attachmentData['disposition'],
                    $attachmentData['encoding'],
                    $attachmentData['filename'],
                );
            } else {
                Log::warning("Attachment data could not be built for URL: {$url}");
            }
        } catch (Exception $e) {
            // Log any errors and skip the current attachment if processing fails
            Log::error('Error processing attachment from URL: ' . $url . ' - ' . $e->getMessage());
        }
    }

    /**
     * Build the attachment data from a given URL.
     *
     * This method fetches the file content, determines its MIME type, and prepares the attachment data.
     *
     * @param string $url The URL of the attachment.
     *
     * @return array|null The attachment data, or null if an error occurred.
     */
    private function buildAttachmentData(string $url): ?array
    {
        try {
            // Fetch the absolute file path from the URL
            $absolutePath = Media::absolutePath($url);

            // Retrieve the file content
            $fileContent = Filesystem::get($absolutePath);

            // If file content is not found, throw an exception
            if ($fileContent === false) {
                throw new Exception("Failed to fetch file content from URL: {$absolutePath}");
            }

            // Extract the filename and MIME type from the file
            $fileName = Filesystem::basename($absolutePath);
            $mimeType = Filesystem::mimeType($absolutePath);

            // If MIME type is not detected, default to octet-stream
            if ($mimeType === false) {
                $mimeType = Mime::TYPE_OCTETSTREAM;
            }

            // Return the attachment data array
            return [
                'content' => $fileContent,
                'filename' => $fileName,
                'type' => $mimeType,
                'disposition' => Mime::DISPOSITION_ATTACHMENT,
                'encoding' => Mime::ENCODING_BASE64,
            ];
        } catch (Exception $e) {
            // Log the error if there is an issue building the attachment data
            Log::error('Error building attachment data from URL: ' . $url . ' - ' . $e->getMessage());

            return null;
        }
    }
}
