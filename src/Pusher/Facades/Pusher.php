<?php

declare(strict_types=1);

namespace Maginium\Framework\Pusher\Facades;

use Maginium\Framework\Pusher\Interfaces\PusherInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the PubSub service.
 *
 * @method static void publish(string $topicName, mixed $payload, ?string $channel = null) Publishes a message to a topic in PubSub with additional metadata.
 * @method static void publishBulk(string $topicName, array $messages, ?string $channel = null) Publishes multiple messages to a topic in PubSub with additional metadata.
 * @method static void subscribe(string $topic, ?callable $callback = null) Subscribe to a PubSub topic and execute a callback when a message is received.
 *
 * @see PusherInterface
 */
class Pusher extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return PusherInterface::class;
    }
}
