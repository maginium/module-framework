<?php

declare(strict_types=1);

namespace Maginium\Framework\MessageQueue;

use Magento\Framework\Amqp\Config as AmqpConfig;
use Magento\Framework\Communication\ConfigInterface as CommunicationConfig;
use Magento\Framework\MessageQueue\ConfigInterface as MessageQueueConfig;
use Magento\Framework\MessageQueue\EnvelopeFactory;
use Magento\Framework\MessageQueue\ExchangeRepository;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\MessageValidator;
use Magento\Framework\MessageQueue\Publisher as BasePublisher;
use Magento\Framework\MessageQueue\Publisher\ConfigInterface as PublisherConfig;
use Maginium\Foundation\Enums\ContentTypes;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\MessageQueue\Interfaces\PublisherInterface;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Date;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use PhpAmqpLib\Wire\AMQPTableFactory;

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
     * @var AMQPTableFactory
     */
    private $amqpTableFactory;

    /**
     * @var CommunicationConfig
     */
    private $communicationConfig;

    /**
     * Publisher constructor.
     *
     * @param MessageEncoder $messageEncoder Encoder to encode message data.
     * @param AMQPTableFactory $amqpTableFactory Factory for AMQPTable instance.
     * @param EnvelopeFactory $envelopeFactory Factory to create message envelopes.
     * @param MessageValidator $messageValidator Validator to validate message data.
     * @param AmqpConfig $amqpConfig Configuration for AMQP (Advanced Message Queuing Protocol).
     * @param ExchangeRepository $exchangeRepository Repository to manage message queue exchanges.
     * @param CommunicationConfig $communicationConfig Instance of CommunicationConfig.
     */
    public function __construct(
        AmqpConfig $amqpConfig,
        MessageEncoder $messageEncoder,
        PublisherConfig $publisherConfig,
        EnvelopeFactory $envelopeFactory,
        AMQPTableFactory $amqpTableFactory,
        MessageValidator $messageValidator,
        ExchangeRepository $exchangeRepository,
        MessageQueueConfig $messageQueueConfig,
        CommunicationConfig $communicationConfig,
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
        $this->amqpTableFactory = $amqpTableFactory;
        $this->messageValidator = $messageValidator;
        $this->exchangeRepository = $exchangeRepository;
        $this->communicationConfig = $communicationConfig;
    }

    /**
     * Publishes a message to a specified topic.
     *
     * This method ensures that the message data is properly validated, encoded, and sent to the
     * appropriate message queue or exchange. Any errors encountered during the process will
     * throw a localized exception with a meaningful error message.
     *
     * @param string $topic The name of the topic to which the message will be published.
     * @param mixed $data The actual message data to publish. Can be any data type that is supported.
     *
     * @throws LocalizedException If there's an error during message publishing.
     *
     * @return null
     */
    public function publish($topic, $data): void
    {
        try {
            // Step 1: Create an envelope for the message using the encoded data
            $envelope = $this->envelopeFactory->create($data);

            // Step 2: Retrieve the connection name based on the topic
            $connectionName = $this->getPublisherConnectionName($topic);

            // Step 3: Retrieve the exchange associated with the connection name
            $exchange = $this->exchangeRepository->getByConnectionName($connectionName);

            // Step 4: Enqueue the message to the exchange with the topic and message envelope
            $exchange->enqueue($topic, $envelope);
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
     * @param string $topic The name of the topic to publish the message to.
     * @param mixed $data The data to be published.
     * @param array|null $headers Optional headers for the message. Default is null.
     *
     * @throws LocalizedException If there's an error during message publishing.
     *
     * @return null
     */
    public function dispatch(string $topic, $data, ?array $headers = []): void
    {
        // Step 1: Check if data is an object, and encode it appropriately
        if (Validator::isArray($data)) {
            // Convert to JSON
            $data = Json::encode($data);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw LocalizedException::make(__('Failed to encode data: Invalid JSON format'));
            }
        }

        // Step 2: Validate and encode the message data
        $encodedData = $this->validateAndEncodeMessage($topic, $data);

        // Step 3: Prepare the envelope including metadata and the encoded data
        $data = $this->createEnvelope($topic, $encodedData, $headers);

        // Step 4: Publish the prepared message
        $this->publish($topic, $data);
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
     * @param string $topic The name of the topic for which the message is being sent.
     * @param mixed $data The encoded message data.
     * @param array|null $headers Optional headers to be included with the message. Default is null.
     *
     * @throws LocalizedException If there's an error during envelope creation.
     *
     * @return array The message envelope containing properties and body.
     */
    private function createEnvelope(string $topic, $data, ?array $headers): array
    {
        try {
            // Step 1: Prepare the metadata for the message
            $properties = $this->prepareMessageProperties($topic, $headers);

            // Step 2: Return the envelope with properties (metadata) and the body (data)
            return ['properties' => $properties, 'body' => $data];
        } catch (Exception $e) {
            // Log any issues with message preparation and throw an exception
            Log::error('Error preparing message: ' . $e->getMessage());

            throw LocalizedException::make(__('Error preparing message: %1', $e->getMessage()));
        }
    }

    /**
     * Prepares metadata for the message.
     *
     * This method generates the necessary metadata (properties) for the message, including a unique message ID,
     * event name, timestamp, content type, and other required fields. Optional headers can also be merged into the metadata.
     *
     * @param string $topic The name of the topic associated with this message.
     * @param array|null $headers Optional headers to include in the metadata. Default is an empty array.
     *
     * @throws LocalizedException If there is an error during metadata preparation.
     *
     * @return array The message metadata, including properties and AMQP application headers.
     */
    private function prepareMessageProperties(string $topic, ?array $headers = []): array
    {
        try {
            // Step 1: Generate a unique message ID
            $messageId = md5(gethostname() . microtime(true) . uniqid($topic, true));

            // Step 2: Retrieve the store name and normalize it for use in metadata
            $storeName = Config::getString(static::XML_PATH_STORE_NAME);
            $normalizedStoreName = Str::slug(Str::lower($storeName), '-');

            // Step 3: Get the current timestamp in ISO 8601 format
            $timestamp = Date::now()->toDateTimeString();

            // Step 4: Prepare base properties for the message
            $baseProperties = [
                'delivery_mode' => 2, // Persistent delivery mode for message durability
                'message_id' => $messageId,
            ];

            // Step 5: Build metadata with required fields and optional headers
            $metadata = [
                'X-Event-Name' => $topic, // Name of the topic/event
                'X-Timestamp' => $timestamp, // ISO 8601 timestamp
                'X-Event-Token' => $messageId, // Unique token for this message
                'X-Content-Type' => ContentTypes::APPLICATION_JSON, // Message content type
                'X-Event-Source' => $normalizedStoreName,  // Source identifier based on store name
                ...$headers, // Spread the optional headers into the metadata array
            ];

            // Step 6: Create AMQP headers using the metadata
            $amqpHeaders = $this->amqpTableFactory->create(['data' => $metadata]);

            // Step 7: Return the properties array with application headers
            return [
                ...$baseProperties, // Spread base properties
                'application_headers' => $amqpHeaders, // Include AMQP headers
            ];
        } catch (Exception $exception) {
            // Log the error for debugging purposes
            Log::error(sprintf(
                'Failed to prepare message metadata for topic "%s": %s',
                $topic,
                $exception->getMessage(),
            ));

            // Throw a localized exception with a user-friendly error message
            throw LocalizedException::make(
                __('Failed to prepare message metadata: %1', $exception->getMessage()),
            );
        }
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
     * @param string $topic The name of the topic.
     *
     * @return string The connection name for publishing.
     */
    private function getPublisherConnectionName(string $topic): string
    {
        // Retrieve the connection name associated with the specified topic from the publisher configuration
        $connectionName = $this->publisherConfig->getPublisher($topic)->getConnection()->getName();

        // If AMQP is not configured and the connection name is 'amqp', fallback to 'db'
        return ($connectionName === 'amqp' && ! $this->isAmqpConfigured()) ? 'db' : $connectionName;
    }

    /**
     * Identify message data schema by topic.
     *
     * This method determines the schema type and schema value for the given topic and request type.
     *
     * @param string $topic The topic name.
     * @param string $requestType The request type (default: CommunicationConfig::TOPIC_REQUEST_TYPE).
     *
     * @throws LocalizedException If the topic is not declared in the configuration.
     *
     * @return DataObject The schema information, including type and value.
     */
    private function getTopicSchema(string $topic, string $requestType = CommunicationConfig::TOPIC_REQUEST_TYPE): DataObject
    {
        // Retrieve the topic configuration from the communication config
        $topicConfig = $this->communicationConfig->getTopic($topic);

        if ($topicConfig === null) {
            throw new LocalizedException(__('Specified topic "%topic" is not declared.', ['topic' => $topic]));
        }

        // Determine the schema type and value based on the request type
        $schemaType = null;
        $schemaValue = null;

        if ($requestType === CommunicationConfig::TOPIC_REQUEST_TYPE) {
            $schemaType = $topicConfig[CommunicationConfig::TOPIC_REQUEST_TYPE] ?? null;
            $schemaValue = $topicConfig[CommunicationConfig::TOPIC_REQUEST] ?? null;
        } else {
            $schemaType = isset($topicConfig[CommunicationConfig::TOPIC_RESPONSE])
                ? CommunicationConfig::TOPIC_REQUEST_TYPE_CLASS
                : null;
            $schemaValue = $topicConfig[CommunicationConfig::TOPIC_RESPONSE] ?? null;
        }

        // Return schema information encapsulated in a DataObject
        return DataObject::make([
            'type' => $schemaType,
            'class' => $schemaValue,
        ]);
    }
}
