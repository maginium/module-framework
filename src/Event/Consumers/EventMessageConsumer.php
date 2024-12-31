<?php

declare(strict_types=1);

namespace Maginium\Framework\Event\Consumers;

use Magento\Framework\MessageQueue\ConsumerConfiguration;
use Maginium\Framework\Support\Facades\Json;

/**
 * Class Consumer.
 */
class EventMessageConsumer extends ConsumerConfiguration
{
    /**
     * Process incoming message.
     */
    public function process(?string $messageBody = null): string
    {
        $decodedMessage = Json::decode($messageBody);

        // Perform message processing logic here

        return 'Message processed successfully.';
    }
}
