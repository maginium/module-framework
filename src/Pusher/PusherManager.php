<?php

declare(strict_types=1);

namespace Maginium\Framework\Pusher\Services;

use Maginium\Foundation\Abstracts\AbstractPubSubService;
use Maginium\Foundation\Enums\ContentType;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Pusher\Interfaces\ClientInterface;
use Maginium\Framework\Pusher\Interfaces\PusherInterface;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Date;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use Pusher\Pusher as Client;

/**
 * Class PubSubService.
 *
 * A service class for interacting with PubSub in WordPress.
 */
class PusherManager extends AbstractPubSubService implements PusherInterface
{
    /**
     * @var Client The Ppusher client instance.
     */
    protected Client $client;

    /**
     * PubSubService constructor.
     *
     * @param Client $client The Ppusher client instance.
     */
    public function __construct(
        ClientInterface $client,
    ) {
        /** @var ClientInterface $client */
        // Initialize Ppusher client instance (null for now, to be set later).
        $this->client = $client->getClient();

        // Set Logger class name for logging purposes
        Log::setClassName(static::class);
    }

    /**
     * Publishes a message to a topic in PubSub with additional metadata.
     *
     * @param string $topicName The topic to publish to.
     * @param mixed $payload The payload to publish.
     * @param string|null $channel Optional channel name to publish to.
     *
     * @throws LocalizedException
     */
    public function publish(string $topicName, $payload, ?string $channel = null): void
    {
        try {
            // Use the provided channel name or default to the first part of the topic name
            $channelName = $channel ?? Php::explode('.', $topicName)[0];

            // Prepare the payload
            $payload = $this->prepareMessage($topicName, $payload);

            // Publish the message to the specified topic (channel)
            $this->client->trigger($channelName, $topicName, $payload);
        } catch (Exception $e) {
            // Throw the exception with an error message
            throw LocalizedException::make(
                __("Error publishing message to topic '%1'", $topicName),
                $e,
            );
        }
    }

    /**
     * Publishes multiple messages to a topic in PubSub with additional metadata.
     *
     * @param string $topicName The topic to publish to.
     * @param array  $messages An array of messages to publish.
     * @param string|null $channel Optional channel name to publish to.
     *
     * @throws LocalizedException
     */
    public function publishBulk($topicName, array $payloads, ?string $channel = null): void
    {
        try {
            foreach ($payloads as $payload) {
                $payload = $this->prepareMessage($topicName, $payload['data']);

                // Publish the message to the specified topic.
                $this->publish($topicName, $payload, $channel);
            }
        } catch (Exception $e) {
            // Throw the exception
            throw LocalizedException::make(
                __("Error bulk publishing messages to topic '%1'", $topicName),
                $e,
            );
        }
    }

    /**
     * Subscribe to a PubSub topic and execute a callback when a message is received.
     *
     * @param string   $topicName    The topic to subscribe to.
     * @param callable $callback The callback function to execute when a message is received.
     *
     * @throws LocalizedException
     */
    public function subscribe(string $topicName,  ?callable $callback = null): void
    {
    }

    /**
     * Prepares a message with metadata and payload.
     *
     * @param string $topicName The topic of the message.
     * @param mixed $payload The payload of the message.
     * @param bool $stringable Whether the return value should be a JSON-encoded string.
     *
     * @throws LocalizedException If there's an error preparing the message.
     *
     * @return mixed The prepared message, either as a JSON-encoded string or an array.
     */
    protected function prepareMessage(string $topicName, $payload, bool $stringable = false)
    {
        try {
            // Get the current timestamp
            $timestamp = Date::now()->toDateTimeString();

            // Generate a unique message ID for tracking and identification purposes
            $messageId = md5(gethostname() . microtime(true) . uniqid($topicName, true));

            // Get store name from configuration
            $storeName = Config::getString(static::XML_PATH_STORE_NAME);

            // Validate store name
            if (Validator::isEmpty($storeName)) {
                throw LocalizedException::make(__('Store name is not configured.'));
            }

            // Create metadata for the message
            $messageMetadata = [
                'X-Event-Name' => $topicName,
                'X-Event-Token' => $messageId,
                'X-Timestamp' => $timestamp,
                'Content-Type' => ContentType::APPLICATION_JSON,
                'X-Event-Source' => Str::slug(Str::lower($storeName), '-'),
            ];

            // Combine metadata with the payload
            $messageData = [
                'properties' => $messageMetadata,
                'data' => $payload,
            ];

            // Return JSON-encoded message if stringable, otherwise return as an array
            return $stringable ? Json::encode($messageData) : $messageData;
        } catch (Exception $e) {
            // Log the error
            Log::error('Error preparing message: ' . $e->getMessage());

            // Throw a localized exception with a meaningful error message
            throw LocalizedException::make(__('Error preparing message: %1', $e->getMessage()));
        }
    }

    /**
     * Handle the received message by decoding and processing the payload.
     *
     * @param string   $topicName    The topic from which the message was received.
     * @param string   $message  The received message.
     * @param callable $callback The callback function to execute with the extracted payload.
     *
     * @throws LocalizedException If there's an error processing the message.
     */
    protected function handleMessage(string $topicName, string $message,  ?callable $callback = null): void
    {
    }
}
