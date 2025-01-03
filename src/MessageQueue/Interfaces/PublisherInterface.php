<?php

declare(strict_types=1);

namespace Pixicommerce\Framework\MessageQueue\Interfaces;

use Pixicommerce\Foundation\Exceptions\LocalizedException;

/**
 * Interface PublisherInterface.
 *
 * Provides an interface for dispatching queues and retrieving observers.
 */
interface PublisherInterface
{
    /**
     * XML path for retrieving the store name from the configuration.
     */
    public const XML_PATH_STORE_NAME = 'general/store_information/name';

    /**
     * Publishes a message to a specified topic.
     *
     * @param string $topicName The name of the topic.
     * @param mixed $data The data to publish.
     *
     * @throws LocalizedException If there's an error during publishing.
     *
     * @return null
     */
    public function publish($topicName, $data);
}
