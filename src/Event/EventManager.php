<?php

declare(strict_types=1);

namespace Maginium\Framework\Event;

use Magento\Framework\Event\ConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Event\Interfaces\EventInterface;
use Maginium\Framework\Support\Validator;

/**
 * Manages events in the Magento framework.
 */
class EventManager implements EventInterface
{
    /**
     * The Magento 2 Event Configuration instance.
     */
    protected ConfigInterface $config;

    /**
     * The Magento 2 Event Manager instance.
     */
    protected ManagerInterface $eventManager;

    /**
     * The Magento Message Queue Publisher instance.
     */
    private PublisherInterface $publisher;

    /**
     * Constructor.
     *
     * @param ConfigInterface $config The Magento 2 Event Configuration instance.
     * @param ManagerInterface $eventManager The Magento 2 Event Manager instance.
     * @param PublisherInterface $publisher The Magento Message Queue Publisher instance.
     */
    public function __construct(
        ConfigInterface $config,
        ManagerInterface $eventManager,
        PublisherInterface $publisher,
    ) {
        $this->config = $config;
        $this->publisher = $publisher;
        $this->eventManager = $eventManager;
    }

    /**
     * Dispatches an event.
     *
     * Publishes an event to the message queue, ensuring that it can be processed
     * asynchronously by consumers.
     *
     * @param string $eventName The name of the event to dispatch.
     * @param array<string, mixed> $data Optional. The data payload associated with the event.
     */
    public function dispatch(string $eventName, array $data = []): void
    {
        $this->eventManager->dispatch($eventName, $data);

        // // Validate event name and data
        // if (empty($eventName)) {
        //     throw InvalidArgumentException::make(__('Event name cannot be empty.'));

        // }

        // // Publish the event data to the message queue
        // $this->publisher->publish($eventName, $data);
    }

    /**
     * Dispatches an event immediately without queueing.
     *
     * @param string $eventName The name of the event.
     * @param array $data Optional. Data associated with the event.
     */
    public function dispatchNow(string $eventName, array $data = []): void
    {
        $this->eventManager->dispatch($eventName, $data);
    }

    /**
     * Dispatches the event with the given arguments if the given truth test passes.
     *
     * @param bool $boolean The condition to check.
     * @param mixed ...$arguments The event name and data associated with the event.
     */
    public function dispatchIf(bool $boolean, ...$arguments): void
    {
        if ($boolean) {
            [$eventName, $data] = $arguments;
            $this->eventManager->dispatch($eventName, $data);
        }
    }

    /**
     * Dispatches the event with the given arguments unless the given truth test passes.
     *
     * @param bool $boolean The condition to check.
     * @param mixed ...$arguments The event name and data associated with the event.
     */
    public function dispatchUnless(bool $boolean, ...$arguments): void
    {
        if (! $boolean) {
            [$eventName, $data] = $arguments;
            $this->eventManager->dispatch($eventName, $data);
        }
    }

    /**
     * Checks if there are Observers for a specific event.
     *
     * @param string $eventName The name of the event.
     */
    public function hasObservers(string $eventName): bool
    {
        $listeners = $this->config->getObservers($eventName);

        return Validator::isEmpty($listeners);
    }

    /**
     * Retrieve all event Observers for a specific event.
     *
     * @param string $eventName The name of the event.
     */
    public function getObservers(string $eventName): array
    {
        return $this->config->getObservers($eventName);
    }
}
