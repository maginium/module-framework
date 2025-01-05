<?php

declare(strict_types=1);

namespace Maginium\Framework\MessageQueue\Abstracts;

use Exception;
use Magento\Framework\MessageQueue\ConsumerConfiguration;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Validator;
use Override;

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
     * @param string|null $messageBody The raw message body from the queue.
     *
     * @return string A message indicating the result of processing.
     */
    public function process(?string $messageBody = null): string
    {
        // Validate that the message body exists
        if ($messageBody === null) {
            return 'No message body provided.';
        }

        try {
            // Decode the message body from JSON
            $this->rawData = Json::decode($messageBody);

            // Prepare the data: convert arrays to DataObject recursively
            $this->preparedData = $this->prepareData($this->rawData);

            // Call the handle method in the child class
            $this->handle();

            return 'Message processed successfully.';
        } catch (Exception $e) {
            // Handle exceptions and return an error message
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
     * Prepare the data by converting nested arrays into DataObject instances.
     *
     * @param mixed $data The input data to process.
     *
     * @return mixed The prepared data with arrays converted to DataObject instances.
     */
    protected function prepareData(mixed $data): mixed
    {
        // If the data is already an object, return it as-is.
        if (Validator::isObject($data)) {
            return $data;
        }

        // If the data is an array, iterate over it.
        if (Validator::isArray($data)) {
            foreach ($data as $key => $value) {
                // Recursively process each value in the array.
                $data[$key] = $this->prepareData($value);
            }

            // Convert the array into a DataObject.
            return DataObject::make($data);
        }

        // If the data is neither an object nor an array, return it as-is.
        return $data;
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
