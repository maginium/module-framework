<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Consumers;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Mail\Interfaces\FactoryInterface;
use Maginium\Framework\Mail\Interfaces\MailerInterface;
use Maginium\Framework\MessageQueue\Abstracts\AbstractConsumer;

/**
 * Class QueuedEmailConsumer.
 *
 * A consumer class responsible for processing messages from the 'email.messages' queue.
 * This class extends Magento's ConsumerConfiguration and defines how incoming messages are handled.
 */
class QueuedEmailConsumer extends AbstractConsumer
{
    /**
     * The name of the queue being consumed.
     *
     * @var string
     */
    protected string $queueName = 'email.messages';

    /**
     * The name of the consumer.
     *
     * @var string
     */
    protected string $consumerName = 'email.message.consumer';

    /**
     * Mailable interface for sending emails.
     *
     * @var FactoryInterface
     */
    private FactoryInterface $mailer;

    /**
     * EmailQueueConsumer constructor.
     *
     * @param MailerInterface $mailer The mailer instance.
     */
    public function __construct(
        FactoryInterface $mailer,
    ) {
        $this->mailer = $mailer;
    }

    /**
     * Handle the decoded data from the message queue.
     *
     * @return void
     */
    protected function handle(): void
    {
        try {
            // Create an envelope using the factory
            $envelope = $this->getRawData();

            // Send the email using the mailer
            $this->mailer->mailer()->send($envelope);
        } catch (Exception $e) {
            // Handle any exceptions that may occur during processing.
            throw Exception::make(__('Error processing message: %1', $e->getMessage()));
        }
    }
}
