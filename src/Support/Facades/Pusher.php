<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Pusher\Interfaces\PusherInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the PubSub service.
 *
 * @method static void publish(string $topic, mixed $payload)
 *     Publishes a message to a topic in PubSub with additional metadata.
 *     Parameters:
 *     - $topic: The topic to which the message will be published.
 *     - $payload: The data to publish.
 *     Returns:
 *     - void
 * @method static void bulkPublish(string $topic, array $payloads)
 *     Publishes multiple payloads to a topic in PubSub with additional metadata.
 *     Parameters:
 *     - $topic: The topic to which the payloads will be published.
 *     - $payloads: Array of payloads to publish.
 *     Returns:
 *     - void
 * @method static void subscribe(string $topic, callable $callback = null)
 *     Subscribes to a PubSub topic and executes a callback when a message is received.
 *     Parameters:
 *     - $topic: The topic to subscribe to.
 *     - $callback: Optional. Callback function to execute when a message is received.
 *     Returns:
 *     - void
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
