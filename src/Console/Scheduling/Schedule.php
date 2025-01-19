<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Scheduling;

use DateTimeInterface;
use DateTimeZone;
use Illuminate\Bus\UniqueLock;
use Illuminate\Console\Scheduling\CacheAware;
use Illuminate\Console\Scheduling\Schedule as BaseSchedule;
use Illuminate\Console\Scheduling\SchedulingMutex as SchedulingMutexInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Application\Application;
use Maginium\Framework\Application\Interfaces\ApplicationInterface;
use Maginium\Framework\Console\Interfaces\EventMutexInterface;
use Maginium\Framework\Console\Interfaces\ScheduleInterface;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Reflection;

/**
 * Custom Schedule class to manage scheduled tasks and events.
 *
 * This class extends the base Schedule functionality by incorporating an
 * EventFactory for event creation and managing mutexes for concurrency control.
 */
class Schedule extends BaseSchedule implements ScheduleInterface
{
    /**
     * All of the events on the schedule.
     *
     * @var Event[]
     */
    protected $events = [];

    /**
     * Factory for creating Event instances.
     *
     * @var EventFactory
     */
    protected EventFactory $eventFactory;

    /**
     * Factory for creating CallbackEvent instances.
     *
     * @var CallbackEventFactory
     */
    protected CallbackEventFactory $callbackEventFactory;

    /**
     * Constructor to initialize the Schedule instance with required mutexes and timezone.
     *
     * @param EventFactory $eventFactory Factory for creating Event instances.
     * @param CallbackEventFactory $callbackEventFactory Factory for creating CallbackEvent instances.
     * @param EventMutexInterface $eventMutex Handles event-level locking to prevent overlapping events.
     * @param SchedulingMutexInterface $schedulingMutex Handles schedule-level locking to ensure proper execution.
     * @param DateTimeZone|string|null $timezone The timezone for the scheduled events.
     */
    public function __construct(
        EventFactory $eventFactory,
        EventMutexInterface $eventMutex,
        SchedulingMutexInterface $schedulingMutex,
        CallbackEventFactory $callbackEventFactory,
        $timezone = null,
        $events = [],
    ) {
        $this->timezone = $timezone;
        $this->eventMutex = $eventMutex;
        $this->eventFactory = $eventFactory;
        $this->schedulingMutex = $schedulingMutex;
        $this->callbackEventFactory = $callbackEventFactory;

        // Initialize events using the helper function
        $this->initializeEvents($events);
    }

    /**
     * Add a new callback event to the schedule.
     *
     * This method allows scheduling a task that executes a callback function.
     * It uses the CallbackEventFactory to create the event, passing necessary
     * parameters such as mutex, callback, and timezone.
     *
     * @param  string|callable  $callback The callback function or command to execute.
     * @param  array  $parameters Optional parameters to pass to the callback function.
     *
     * @return CallbackEvent The created CallbackEvent instance representing the scheduled task.
     */
    public function call($callback, array $parameters = []): CallbackEvent
    {
        // Create the callback event using the factory.
        $this->events[] = $event = $this->callbackEventFactory->create([
            'callback' => $callback,
            'parameters' => $parameters,
            'mutex' => $this->eventMutex,
            'timezone' => $this->timezone,
        ]);

        // Return the created event to allow method chaining or further manipulation.
        return $event;
    }

    /**
     * Add a new command event to the schedule.
     *
     * This method schedules a new command with optional parameters by creating an event
     * using the event factory. The created event is added to the list of scheduled events.
     *
     * @param string $command The command to be executed.
     * @param array $parameters The parameters to be passed to the command.
     *
     * @return Event The newly created event instance.
     */
    public function exec($command, array $parameters = []): Event
    {
        // Append parameters to the command if any are provided.
        if (! empty($parameters)) {
            $command .= ' ' . $this->compileParameters($parameters);
        }

        // Create a new event using the event factory and store it in the events array.
        $this->events[] = $event = $this->eventFactory->create([
            'command' => $command,
            'mutex' => $this->eventMutex,
            'timezone' => $this->timezone,
        ]);

        return $event;
    }

    /**
     * Add a new Magento command event to the schedule.
     *
     * @param string $command    The Magento command to execute.
     * @param array $parameters  Parameters to be passed to the command.
     *
     * @return Event Returns the newly created Event instance.
     */
    public function command($command, array $parameters = [])
    {
        // Check if the command class exists and resolve it via the container.
        if (Reflection::exists($command)) {
            $command = Container::resolve($command);

            return $this->exec(
                Application::formatCommandString($command->getName()),
                $parameters,
            )->description($command->getDescription());
        }

        // Execute the command string if the class is not resolvable.
        return $this->exec(
            Application::formatCommandString($command),
            $parameters,
        );
    }

    /**
     * Add a new job callback event to the schedule.
     *
     * @param object|string $job The job class or instance.
     * @param string|null $queue The name of the queue to dispatch the job to.
     * @param string|null $connection  The connection to use for the queue.
     *
     * @return CallbackEvent Returns the newly created callback event.
     */
    public function job($job, $queue = null, $connection = null)
    {
        // Create a callback to dispatch the job.
        return $this->call(function() use ($job, $queue, $connection) {
            // Resolve the job instance if it is a string.
            $job = is_string($job) ? Container::resolve($job) : $job;

            // Dispatch the job to the queue or execute it immediately based on its type.
            if ($job instanceof ShouldQueue) {
                $this->dispatchToQueue($job, $queue ?? $job->queue, $connection ?? $job->connection);
            } else {
                $this->dispatchNow($job);
            }
        })->name(is_string($job) ? $job : get_class($job));
    }

    /**
     * Get all of the events on the schedule that are due.
     *
     * @param  ApplicationInterface  $app
     *
     * @return Collection
     */
    public function dueEvents($app)
    {
        return collect($this->events())->filter->isDue($app);
    }

    /**
     * Get all of the events on the schedule.
     *
     * @return Event[]
     */
    public function events(): array
    {
        return $this->events;
    }

    /**
     * Determine if the server is allowed to run this event.
     *
     * @param  Event  $event
     * @param  DateTimeInterface  $time
     *
     * @return bool
     */
    public function serverShouldRun($event, DateTimeInterface $time): bool
    {
        return $this->mutexCache[$event->mutexName()] ??= $this->schedulingMutex->create($event, $time);
    }

    /**
     * Specify the cache store that should be used to store mutexes.
     *
     * @param  string  $store
     *
     * @return $this
     */
    public function useCache($store)
    {
        if ($this->eventMutex instanceof CacheAware) {
            $this->eventMutex->useStore($store);
        }

        if ($this->schedulingMutex instanceof CacheAware) {
            $this->schedulingMutex->useStore($store);
        }

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Initialize the events property with the provided events.
     *
     * @param array $events List of events to initialize.
     *
     * @return void
     */
    protected function initializeEvents(array $events): void
    {
        foreach ($events as $event) {
            if ($event instanceof Event) {
                $this->events[] = $event;
            } else {
                // Create the callback event using the factory.
                $this->events[] = $event = $this->callbackEventFactory->create([
                    'callback' => $event,
                    'mutex' => $this->eventMutex,
                    'timezone' => $this->timezone,
                ])->name(Reflection::getShortName($event));
            }
        }
    }

    /**
     * Dispatch a unique job to the queue to ensure no duplicates are created.
     *
     * @param object $job        The job instance.
     * @param string|null $queue The name of the queue to dispatch to.
     * @param string|null $connection The connection to use for the queue.
     *
     * @throws RuntimeException If unable to acquire a unique lock or dispatch fails.
     *
     * @return void
     */
    protected function dispatchUniqueJobToQueue($job, $queue, $connection): void
    {
        // Acquire a unique lock for the job to prevent duplicates.
        if (! (new UniqueLock(Container::resolve(Cache::class)))->acquire($job)) {
            return;
        }

        // Dispatch the job to the queue.
        $this->getDispatcher()->dispatch(
            $job->onConnection($connection)->onQueue($queue),
        );
    }

    /**
     * Get the job dispatcher instance.
     *
     * @throws RuntimeException If the dispatcher cannot be resolved from the container.
     *
     * @return Dispatcher The dispatcher instance.
     */
    protected function getDispatcher(): Dispatcher
    {
        if ($this->dispatcher === null) {
            try {
                // Attempt to resolve the dispatcher from the service container.
                $this->dispatcher = Container::resolve(Dispatcher::class);
            } catch (BindingResolutionException $e) {
                // Throw an exception if the dispatcher is unavailable.
                throw RuntimeException::make(
                    'Unable to resolve the dispatcher from the service container. Please bind it or install the illuminate/bus package.',
                    $e,
                    is_int($e->getCode()) ? $e->getCode() : 0,
                );
            }
        }

        return $this->dispatcher;
    }
}
