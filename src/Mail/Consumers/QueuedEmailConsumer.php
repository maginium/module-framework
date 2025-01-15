<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Consumers;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Mail\Interfaces\Data\EnvelopeInterface;
use Maginium\Framework\Mail\Interfaces\Data\EnvelopeInterfaceFactory;
use Maginium\Framework\Mail\Interfaces\MailableInterface;
use Maginium\Framework\MessageQueue\Abstracts\AbstractConsumer;
use Maginium\Framework\Support\Validator;

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
     * @var MailableInterface
     */
    private MailableInterface $mailable;

    /**
     * Envelope interface factory for creating envelope instances.
     *
     * @var EnvelopeInterfaceFactory
     */
    private EnvelopeInterfaceFactory $envelopeFactory;

    /**
     * EmailQueueConsumer constructor.
     *
     * @param MailableInterface $mailable The mailable instance.
     */
    public function __construct(
        MailableInterface $mailable,
        EnvelopeInterfaceFactory $envelopeFactory,
    ) {
        $this->mailable = $mailable;
        $this->envelopeFactory = $envelopeFactory;
    }

    /**
     * Handle the decoded data from the message queue.
     *
     * @return void
     */
    protected function handle(): void
    {
        try {
            // Validate data
            $this->validateData();

            // Create an envelope using the factory
            $envelope = $this->getRawData();

            // Send the email using the mailable
            $this->mailable->send($envelope);
        } catch (Exception $e) {
            // Handle any exceptions that may occur during processing.
            throw Exception::make(__('Error processing message: %1', $e->getMessage()));
        }
    }

    /**
     * Validate the provided raw data to ensure it is not empty.
     *
     * This method checks if the provided raw data is empty using the Validator class.
     * If the data is empty, it throws an exception with a relevant message.
     *
     * @throws Exception Throws an exception if the raw data is empty.
     *
     * @return void
     */
    private function validateData(): void
    {
        // Initialize raw data
        $rawData = $this->getRawData();

        // Check if the raw data is empty using the Validator class
        if (! $rawData instanceof EnvelopeInterface) {
            // If the data is empty, throw an exception with an error message
            throw new Exception(__('Message data should be instanceof %1.', EnvelopeInterface::class));
        }
    }
}
