<?php

declare(strict_types=1);

namespace Maginium\Framework\Pusher\Interfaces;

use Maginium\Foundation\Exceptions\LocalizedException;

/**
 * Interface PusherInterface.
 *
 * Interface for PubSub service classes.
 */
interface PusherInterface
{
    /**
     * XML path for retrieving the store name from the configuration.
     */
    public const XML_PATH_STORE_NAME = 'general/store_information/name';

    /**
     * Event triggered for publishing data to a Pusher topic.
     */
    public const PUSHER_PUBLISH_EVENT = 'pusher_publish_event';

    /**
     * Event triggered for publishing bulk data to a Pusher topic.
     */
    public const PUSHER_BULK_PUBLISH_EVENT = 'pusher_bulk_publish_event';

    /**
     * Publishes a message to a topic in PubSub with additional metadata.
     *
     * @param string $topicName The topic to publish to.
     * @param mixed $payload The payload to publish.
     * @param string|null $channel Optional channel name to publish to.
     *
     * @throws LocalizedException
     */
    public function publish(string $topicName, $payload, ?string $channel = null): void;

    /**
     * Publishes multiple messages to a topic in PubSub with additional metadata.
     *
     * @param string $topicName The topic to publish to.
     * @param array  $messages An array of messages to publish.
     * @param string|null $channel Optional channel name to publish to.
     *
     * @throws LocalizedException
     */
    public function publishBulk($topicName, array $messages, ?string $channel = null): void;

    /**
     * Subscribe to a PubSub topic and execute a callback when a message is received.
     *
     * @param string   $topic    The topic to subscribe to.
     * @param callable $callback The callback function to execute when a message is received.
     *
     * @throws LocalizedException
     */
    public function subscribe(string $topic, ?callable $callback = null): void;
}
