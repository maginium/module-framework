<?php

declare(strict_types=1);

namespace Pixicommerce\Framework\Support\Facades;

use Pixicommerce\Framework\MessageQueue\Interfaces\PublisherInterface;
use Pixicommerce\Framework\Support\Facade;

/**
 * Facade for interacting with the Queue service.
 *
 * Provides methods for publishing messages to specified topics, including support for optional headers.
 *
 * @method static void dispatch(string $topicName, mixed $data, ?array $headers = []) Dispatches a message to a specified topic after validating, encoding, and preparing the message.
 * It also includes optional headers in the message metadata.
 *
 * @see PublisherInterface
 */
class Publisher extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return PublisherInterface::class;
    }
}
