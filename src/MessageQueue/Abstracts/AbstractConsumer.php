<?php

declare(strict_types=1);

namespace Maginium\Framework\MessageQueue\Abstracts;

use Magento\Framework\MessageQueue\ConsumerConfiguration;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facades\Json;
use Override;
use Validator;

/**
 * Class AbstractConsumer.
 *
 * Abstract class that defines the template for queue consumers.
 * It includes a common process method, a prepareData method,
 * and an abstract handle method for implementing specific message handling logic.
 */
abstract class AbstractConsumer extends ConsumerConfiguration
{
    /**
     * The name of the queue being consumed.
     *
     * @var string
     */
    protected string $queueName;

    /**
     * The name of the consumer.
     *
     * @var string
     */
    protected string $consumerName;

    /**
     * The maximum number of messages to process.
     *
     * @var int
     */
    protected int $maxMessages = 100;

    /**
     * Raw decoded message body.
     *
     * @var mixed
     */
    private mixed $rawData;

    /**
     * Prepared DataObject.
     *
     * @var DataObject|null
     */
    private ?DataObject $preparedData = null;

    /**
     * Process the incoming message from the queue.
     *
     * @param mixed|null $messageBody The raw message body from the queue.
     *
     * @return string A message indicating the result of processing.
     */
    public function process(mixed $messageBody = null): string
    {
        // Validate that the message body exists and return early if not.
        if ($messageBody === null) {
            return 'No message body provided.';
        }

        try {
            // Decode the message body if it's a string (JSON format), otherwise use it as-is.
            $this->rawData = Validator::isString($messageBody)
                ? Json::decode($messageBody) // Decode if string
                : $messageBody; // Otherwise, use as-is

            // Ensure that the raw data is converted to a DataObject (even if it's already an array).
            $this->preparedData = Validator::isArray($this->rawData) ? DataObject::make((array)$this->rawData) : null;

            // Call the handle method in the child class (specific logic implementation)
            $this->handle();

            return 'Message processed successfully.';
        } catch (Exception $e) {
            // Handle exceptions and return an error message with the exception details
            return 'Error processing message: ' . $e->getMessage();
        }
    }

    /**
     * Get the consumer name.
     *
     * @return string
     */
    #[Override]
    public function getConsumerName(): string
    {
        return $this->getData($this->consumerName);
    }

    /**
     * Get the queue name.
     *
     * @return string
     */
    #[Override]
    public function getQueueName(): string
    {
        return $this->getData($this->queueName);
    }

    /**
     * Get the maximum number of messages to process.
     *
     * @return int
     */
    #[Override]
    public function getMaxMessages(): int
    {
        return $this->getData((string)$this->maxMessages);
    }

    /**
     * Get the raw data from the message.
     *
     * @return mixed
     */
    protected function getRawData(): mixed
    {
        return $this->rawData;
    }

    /**
     * Get the prepared DataObject or a specific key's value if provided.
     *
     * @param string|null $key The optional key to retrieve data for.
     *
     * @return mixed|null The DataObject, the value for the key, or null.
     */
    protected function getData(?string $key = null): mixed
    {
        if ($this->preparedData === null) {
            // Return null if no prepared data exists
            return null;
        }

        // If no key is provided, return the entire DataObject
        if ($key === null) {
            return $this->preparedData;
        }

        // Try to retrieve the value for the given key
        return $this->preparedData->getData($key) ?? null;
    }

    /**
     * Handle the decoded data from the message queue.
     *
     * This method should be implemented by subclasses.
     *
     * @return void
     */
    abstract protected function handle(): void;
}
