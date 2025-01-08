<?php

declare(strict_types=1);

namespace Maginium\Framework\Pusher\Services;

use Maginium\Foundation\Abstracts\Observer\AbstractObserver;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Pusher;

/**
 * Class BulkPublishObserver.
 *
 * Observer for publishing messages to a Pusher topic.
 *
 * This class is responsible for handling event-driven logic that involves publishing
 * data to a Pusher channel. It ensures that the required data is present, processes
 * the payload, and logs the success or failure of the operation.
 */
class BulkPublishObserver extends AbstractObserver
{
    /**
     * Handles the event logic for publishing data to Pusher.
     *
     * This method is triggered by an observer mechanism. It retrieves the `topic` and
     * `payload` from the data object, validates them, and then publishes the data to
     * the specified Pusher topic. If any step fails, it throws a localized exception
     * with a meaningful error message.
     *
     * @throws LocalizedException If the topic or payload is missing, or if the publish operation fails.
     */
    protected function handle(): void
    {
        try {
            // Step 1: Validate the required data fields in the data object.
            $this->validate();

            // Step 2: Publish the validated payload to the specified topic using the Pusher service.
            Pusher::bulkPublish(
                $this->data->getTopic(),  // Topic/channel to publish to.
                $this->data->getPayload(), // Data payload to send.
            );

            // Step 3: Log the success of the publish operation for diagnostic and audit purposes.
            $this->log();
        } catch (Exception $e) {
            // Step 4: Convert the caught exception to a localized exception with a user-friendly message.
            throw new LocalizedException(
                __('Failed to publish to Pusher: %s', $e->getMessage()), // Message with context.
            );
        }
    }

    /**
     * Validates the data object to ensure it contains the required properties.
     *
     * The `dataObject` must have non-empty `topic` and `payload` properties. If these
     * properties are missing or invalid, an exception is thrown to prevent further
     * processing and ensure data integrity.
     *
     * @throws Exception If the `topic` or `payload` property is missing or invalid.
     */
    private function validate(): void
    {
        // Ensure the `topic` property exists and is not empty.
        if (! $this->data->has('topic') || empty($this->data->getTopic())) {
            throw new Exception('The data object is missing a valid "topic" property.');
        }

        // Ensure the `payload` property exists and is not empty.
        if (! $this->data->has('payload') || empty($this->data->getPayload())) {
            throw new Exception('The data object is missing a valid "payload" property.');
        }
    }

    /**
     * Logs a success message after successfully publishing to Pusher.
     *
     * This method retrieves the `topic` and `payload` from the data object and logs
     * the details for diagnostic purposes. The payload is encoded to JSON for readability.
     */
    private function log(): void
    {
        // Retrieve the topic (channel name) from the data object.
        $topic = $this->data->getTopic();

        // Encode the payload to a JSON string for logging.
        $payload = Json::encode($this->data->getPayload());

        // Log an informational message with the topic and payload.
        Log::info(
            __('Successfully published to topic "%s" with payload: %s', $topic, $payload),
        );
    }
}
