<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Consumers;

use Exception;
use Maginium\Framework\MessageQueue\Abstracts\AbstractConsumer;
use Maginium\Framework\Support\Facades\Publisher;

/**
 * Class DelayedEmailConsumer.
 *
 * A consumer class responsible for processing messages from the 'email.messages' queue.
 * This class extends Magento's ConsumerConfiguration and defines how incoming messages are handled.
 */
class DelayedEmailConsumer extends AbstractConsumer
{
    /**
     * The name of the queue being consumed.
     *
     * @var string
     */
    protected string $queueName = 'email.messages.delay';

    /**
     * The name of the consumer.
     *
     * @var string
     */
    protected string $consumerName = 'email.message.delay.consumer';

    /**
     * Handle the decoded data from the message queue.
     *
     * @return void
     */
    protected function handle(): void
    {
        try {
            file_put_contents(BP . '/var/log/DelayedEmailConsumer.log', print_r('DelayedEmailConsumer', true) . "\n", FILE_APPEND);

            Publisher::dispatch('email.messages', $this->getRawData());
        } catch (Exception $e) {
            // Handle any exceptions that may occur during processing.
            throw new Exception('Error processing message: ' . $e->getMessage());
        }
    }
}
