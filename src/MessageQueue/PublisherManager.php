<?php

declare(strict_types=1);

namespace Pixicommerce\Framework\MessageQueue;

use Magento\Framework\Amqp\Config as AmqpConfig;
use Magento\Framework\MessageQueue\ConfigInterface as MessageQueueConfig;
use Magento\Framework\MessageQueue\EnvelopeFactory;
use Magento\Framework\MessageQueue\ExchangeRepository;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\MessageValidator;
use Magento\Framework\MessageQueue\Publisher as BasePublisher;
use Magento\Framework\MessageQueue\Publisher\ConfigInterface as PublisherConfig;
use Pixicommerce\Foundation\Enums\ContentType;
use Pixicommerce\Foundation\Exceptions\Exception;
use Pixicommerce\Foundation\Exceptions\LocalizedException;
use Pixicommerce\Framework\MessageQueue\Interfaces\PublisherInterface;
use Pixicommerce\Framework\Support\Facades\Config;
use Pixicommerce\Framework\Support\Facades\Date;
use Pixicommerce\Framework\Support\Facades\Json;
use Pixicommerce\Framework\Support\Facades\Log;
use Pixicommerce\Framework\Support\Str;
use Pixicommerce\Framework\Support\Validator;

/**
 * Class PublisherManager.
 *
 * This class extends Base's Publisher class and implements the PublisherInterface.
 * It provides methods to publish messages to different topics using Magento's message queue system.
 */
class PublisherManager extends BasePublisher implements PublisherInterface
{
    /**
     * @var ExchangeRepository
     */
    private $exchangeRepository;

    /**
     * @var EnvelopeFactory
     */
    private $envelopeFactory;

    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var MessageValidator
     */
    private $messageValidator;

    /**
     * @var PublisherConfig
     */
    private $publisherConfig;

    /**
     * @var AmqpConfig
     */
    private $amqpConfig;

    /**
     * Publisher constructor.
     *
     * @param MessageEncoder $messageEncoder Encoder to encode message data.
     * @param EnvelopeFactory $envelopeFactory Factory to create message envelopes.
     * @param MessageValidator $messageValidator Validator to validate message data.
     * @param AmqpConfig $amqpConfig Configuration for AMQP (Advanced Message Queuing Protocol).
     * @param ExchangeRepository $exchangeRepository Repository to manage message queue exchanges.
     */
    public function __construct(
        AmqpConfig $amqpConfig,
        MessageEncoder $messageEncoder,
        PublisherConfig $publisherConfig,
        EnvelopeFactory $envelopeFactory,
        MessageValidator $messageValidator,
        ExchangeRepository $exchangeRepository,
        MessageQueueConfig $messageQueueConfig,
    ) {
        parent::__construct(
            $exchangeRepository,
            $envelopeFactory,
            $messageQueueConfig,
            $messageEncoder,
            $messageValidator,
        );

        $this->amqpConfig = $amqpConfig;
        $this->messageEncoder = $messageEncoder;
        $this->publisherConfig = $publisherConfig;
        $this->envelopeFactory = $envelopeFactory;
        $this->messageValidator = $messageValidator;
        $this->exchangeRepository = $exchangeRepository;
    }

    /**
     * Publishes a message to a specified topic.
     *
     * This method ensures that the message data is properly validated, encoded, and sent to the
     * appropriate message queue or exchange. Any errors encountered during the process will
     * throw a localized exception with a meaningful error message.
     *
     * @param string $topicName The name of the topic to which the message will be published.
     * @param mixed $data The actual message data to publish. Can be any data type that is supported.
     *
     * @throws LocalizedException If there's an error during message publishing.
     *
     * @return null
     */
    public function publish($topicName, $data): void
    {
        try {
            // Step 1: Create an envelope for the message using the encoded data
            $envelope = $this->envelopeFactory->create($data);

            // Step 2: Retrieve the connection name based on the topic
            $connectionName = $this->getPublisherConnectionName($topicName);

            // Step 3: Retrieve the exchange associated with the connection name
            $exchange = $this->exchangeRepository->getByConnectionName($connectionName);

            // Step 4: Enqueue the message to the exchange with the topic and message envelope
            $exchange->enqueue($topicName, $envelope);
        } catch (Exception $e) {
            // In case of an exception, throw a localized exception with the error message
            throw LocalizedException::make(__('Failed to publish message: %1', $e->getMessage()));
        }
    }

    /**
     * Dispatches a message to a specified topic with optional headers.
     *
     * This method validates, encodes, and prepares the message before publishing it. It also
     * ensures that optional headers are included in the message metadata.
     *
     * @param string $topicName The name of the topic to publish the message to.
     * @param mixed $data The data to be published.
     * @param array|null $headers Optional headers for the message. Default is null.
     *
     * @throws LocalizedException If there's an error during message publishing.
     *
     * @return null
     */
    public function dispatch(string $topicName, $data, ?array $headers = []): void
    {
        // Step 1: Check if data is an object, and encode it appropriately
        if (Validator::isArray($data)) {
            // Convert to JSON
            $data = Json::encode($data);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new LocalizedException(__('Failed to encode data: Invalid JSON format'));
            }
        }

        // Step 2: Validate and encode the message data
        $encodedData = $this->validateAndEncodeMessage($topicName, $data);

        // Step 3: Prepare the envelope including metadata and the encoded data
        $data = $this->createEnvelope($topicName, $encodedData, $headers);

        // Step 4: Publish the prepared message
        $this->publish($topicName, $data);
    }

    /**
     * Validates and encodes the message data.
     *
     * This method validates the data for the specified topic and then encodes it into a suitable format
     * for transmission via the message queue. It ensures that only valid data is processed.
     *
     * @param string $topicName The name of the topic.
     * @param mixed $data The data to validate and encode.
     *
     * @throws LocalizedException If validation or encoding fails.
     *
     * @return string The encoded message data.
     */
    private function validateAndEncodeMessage(string $topicName, $data): string
    {
        // Step 1: Validate the message data based on the topic's constraints
        $this->messageValidator->validate($topicName, $data);

        // Step 2: Encode the validated data into a string format for message transmission
        return $this->messageEncoder->encode($topicName, $data);
    }

    /**
     * Creates an envelope for the message.
     *
     * This method prepares the message envelope which consists of the message's metadata (properties)
     * and the actual message body. It includes any optional headers provided.
     *
     * @param string $topicName The name of the topic for which the message is being sent.
     * @param mixed $data The encoded message data.
     * @param array|null $headers Optional headers to be included with the message. Default is null.
     *
     * @throws LocalizedException If there's an error during envelope creation.
     *
     * @return array The message envelope containing properties and body.
     */
    private function createEnvelope(string $topicName, $data, ?array $headers): array
    {
        try {
            // Step 1: Prepare the message metadata (properties)
            $messageProperties = $this->prepareMessageProperties($topicName, $headers);

            // Step 2: Return the envelope with properties (metadata) and the body (data)
            return ['properties' => $messageProperties, 'body' => $data];
        } catch (Exception $e) {
            // Log any issues with message preparation and throw an exception
            Log::error('Error preparing message: ' . $e->getMessage());

            throw LocalizedException::make(__('Error preparing message: %1', $e->getMessage()));
        }
    }

    /**
     * Prepares metadata for the message.
     *
     * This method generates necessary metadata (properties) for the message, such as the message ID,
     * event name, timestamp, and other required fields. It also includes any optional headers.
     *
     * @param string $topicName The name of the topic.
     * @param array|null $headers Optional headers to be included with the metadata. Default is an empty array.
     *
     * @throws LocalizedException If there's an error preparing the metadata.
     *
     * @return array The message metadata, which includes both required and optional fields.
     */
    private function prepareMessageProperties(string $topicName, ?array $headers = []): array
    {
        // Step 1: Generate a unique message ID using a combination of server information and topic name
        $messageId = md5(gethostname() . microtime(true) . uniqid($topicName, true));

        // Step 2: Retrieve the store name from the configuration
        $storeName = Config::getString(static::XML_PATH_STORE_NAME);

        // Step 3: Get the current timestamp in a format suitable for logging and tracking
        $timestamp = Date::now()->toDateTimeString();

        // Step 4: Construct the metadata array for the message
        $metadata = [
            'delivery_mode' => 2, // Set delivery mode to persistent (ensures message is saved to disk)
            'X-Event-Name' => $topicName, // The event or topic name associated with this message
            'message_id' => $messageId, // Unique identifier for the message
            'X-Timestamp' => $timestamp, // Timestamp indicating when the message was created
            'X-Event-Token' => $messageId, // A token for tracking this specific message
            'Content-Type' => ContentType::APPLICATION_JSON, // Content type for the message body
            'X-Event-Source' => Str::slug(Str::lower($storeName), '-'), // Source identifier based on store name
            ...$headers, // Spread the optional headers into the metadata array
        ];

        return $metadata;
    }

    /**
     * Checks if AMQP is configured.
     *
     * @return bool True if AMQP is configured; false otherwise.
     */
    private function isAmqpConfigured(): bool
    {
        // Check if the AMQP host configuration value is set
        return $this->amqpConfig->getValue(AmqpConfig::HOST) ? true : false;
    }

    /**
     * Retrieves the connection name for publishing based on the topic.
     *
     * @param string $topicName The name of the topic.
     *
     * @return string The connection name for publishing.
     */
    private function getPublisherConnectionName(string $topicName): string
    {
        // Retrieve the connection name associated with the specified topic from the publisher configuration
        $connectionName = $this->publisherConfig->getPublisher($topicName)->getConnection()->getName();

        // If AMQP is not configured and the connection name is 'amqp', fallback to 'db'
        return ($connectionName === 'amqp' && ! $this->isAmqpConfigured()) ? 'db' : $connectionName;
    }
}
