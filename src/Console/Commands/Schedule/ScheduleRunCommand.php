<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands\Schedule;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Sleep;
use Maginium\Framework\Application\Application;
use Maginium\Framework\Console\Command;
use Maginium\Framework\Console\Interfaces\ScheduleInterface;
use Maginium\Framework\Console\Scheduling\Event;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

/**
 * Command for running scheduled tasks.
 *
 * This command is responsible for executing scheduled tasks in the system. It handles both
 * single server and repeating events, ensuring that tasks are run based on certain criteria
 * such as server availability, maintenance mode, and task filters. The command also tracks
 * the execution time and logs relevant information about each task.
 */
#[AsCommand(name: 'schedule:run')]
class ScheduleRunCommand extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected ?string $name = 'schedule:run';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected ?string $description = 'Run the scheduled commands';

    /**
     * The schedule instance that contains all the scheduled events.
     *
     * @var Schedule
     */
    protected $schedule;

    /**
     * The timestamp for when the scheduler command started running.
     *
     * @var Carbon
     */
    protected $startedAt;

    /**
     * Flag to check if any events ran during this command execution.
     *
     * @var bool
     */
    protected $eventsRan = false;

    /**
     * The event dispatcher for broadcasting events.
     *
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * The exception handler used for catching and logging exceptions.
     *
     * @var ExceptionHandler
     */
    protected $handler;

    /**
     * The cache repository used for cache-related operations.
     *
     * @var Repository
     */
    protected $cache;

    /**
     * The PHP binary used to run magento commands.
     *
     * @var string
     */
    protected $phpBinary;

    /**
     * Initialize the command with the current timestamp for the start of the command.
     */
    public function _construct(): void
    {
        // Store the time when the scheduler starts executing
        $this->startedAt = Date::now();
    }

    /**
     * Execute the console command.
     *
     * This method is the main handler that runs the scheduled tasks.
     * It fetches the due events, dispatches appropriate events, and runs the tasks.
     *
     * @param ScheduleInterface $schedule The schedule instance holding the scheduled tasks.
     * @param Dispatcher $dispatcher The event dispatcher.
     * @param Cache $cache The cache repository for caching purposes.
     * @param ExceptionHandler $handler The exception handler to manage errors.
     *
     * @return void
     */
    public function handle(ScheduleInterface $schedule, Dispatcher $dispatcher, Cache $cache, ExceptionHandler $handler)
    {
        $this->cache = $cache;
        $this->handler = $handler;
        $this->schedule = $schedule;
        $this->dispatcher = $dispatcher;
        $this->phpBinary = Application::phpBinary();

        // Clear interrupt signals to allow the task to run uninterrupted
        $this->clearInterruptSignal();

        // Output a new line for separation
        $this->newLine();

        // Get all events that are due to be executed
        $events = $this->schedule->dueEvents($this->app);

        // Iterate over each event and execute them if applicable
        foreach ($events as $event) {
            // Check if the event passes the filters (e.g., conditions like day of the week, time, etc.)
            if (! $event->filtersPass($this->app)) {
                // Dispatch a skipped event if it doesn't pass the filter
                $this->dispatcher->dispatch($this->createScheduledTaskSkippedEvent(['task' => $event]));

                // Skip to the next event
                continue;
            }

            // If the event should only run on one server (in case of a multi-server setup)
            if ($event->onOneServer) {
                // Run the event on a single server
                $this->runSingleServerEvent($event);
            } else {
                // Run the event on all servers
                $this->runEvent($event);
            }

            // Mark that at least one event has been executed
            $this->eventsRan = true;
        }

        // If any of the events are repeatable, schedule them to run again
        if ($events->contains->isRepeatable()) {
            // Handle repeatable events that need to run again after a certain period
            $this->repeatEvents($events->filter->isRepeatable());
        }

        // If no events were executed, notify the user
        if (! $this->eventsRan) {
            $this->components->info('No scheduled commands are ready to run.');
        } else {
            // Add a new line after all events are handled for better console readability
            $this->newLine();
        }
    }

    /**
     * Run the given single server event.
     *
     * This method checks if the event should run on the current server. If it is eligible to run,
     * it will invoke the `runEvent` method to execute the event. Otherwise, it will skip the event
     * and log a message indicating that the event has already run on another server.
     *
     * @param  Event  $event  The event that needs to be executed.
     *
     * @return void
     */
    protected function runSingleServerEvent($event)
    {
        // Check if the event should run on this server
        if ($this->schedule->serverShouldRun($event, $this->startedAt)) {
            // Run the event if it is eligible to execute
            $this->runEvent($event);
        } else {
            // Log the message if the event has already been executed on another server
            $this->components->info(sprintf(
                'Skipping [%s], as command already run on another server.',
                $event->getSummaryForDisplay(),
            ));
        }
    }

    /**
     * Run the given event.
     *
     * This method executes a single event, dispatches the task-related events such as
     * `ScheduledTaskStarting`, `ScheduledTaskFinished`, and handles any errors that occur
     * during the execution of the event. It reports exceptions and logs the time it took
     * for the event to run.
     *
     * @param  Event  $event  The event to execute.
     *
     * @return void
     */
    protected function runEvent(Event $event): void
    {
        // Get the summary of the event to display
        $summary = $event->getSummaryForDisplay();

        // Determine the command that will be executed, removing the PHP binary if needed
        $command = $event instanceof CallbackEvent
            ? $summary
            : trim(str_replace($this->phpBinary, '', $event->command));

        // Prepare the description for the event execution message
        $description = sprintf(
            '<fg=gray>%s</> Running [%s]%s',
            Carbon::now()->format('Y-m-d H:i:s'),  // Current timestamp
            $command,
            $event->runInBackground ? ' in background' : '',  // Indicate if the event runs in background
        );

        // Start the task and execute the event
        $this->components->task($description, function() use ($event) {
            // Dispatch event indicating the task is starting
            $this->dispatcher->dispatch($this->createScheduledTaskStartingEvent(['event' => $event]));

            // Measure the start time for execution
            $start = microtime(true);

            try {
                // Run the event
                $event->run($this->app);

                // Dispatch event indicating the task is finished with execution time
                $this->dispatcher->dispatch($this->createScheduledTaskFinishedEvent([
                    'task' => $event,
                    'runtime' => round(microtime(true) - $start, 2),  // Round the execution time to 2 decimal places
                ]));

                // Mark that events have run
                $this->eventsRan = true;
            } catch (Throwable $e) {
                // Dispatch event indicating the task failed
                $this->dispatcher->dispatch($this->createScheduledTaskFailedEvent(['task' => $event, 'exception' => $e]));

                // Report the exception
                $this->handler->report($e);
            }

            // Return whether the event was successfully executed based on exit code
            return $event->exitCode === 0;
        });

        // If the event is not a CallbackEvent, display the event summary in a bullet list
        if (! $event instanceof CallbackEvent) {
            $this->components->bulletList([
                $event->getSummaryForDisplay(),
            ]);
        }
    }

    /**
     * Run the given repeating events.
     *
     * This method handles the execution of repeating events that need to be run at intervals.
     * It continuously checks for eligible events to run as long as the scheduler is within
     * the allowed time frame (up until the end of the current minute). It respects maintenance
     * mode settings and task filters.
     *
     * @param  Collection<Event>  $events  The collection of events that are eligible to repeat.
     *
     * @return void
     */
    protected function repeatEvents($events): void
    {
        // Flag to track whether the system has entered maintenance mode
        $hasEnteredMaintenanceMode = false;

        // Continuously run events until the current minute ends
        while (Date::now()->lte($this->startedAt->endOfMinute())) {
            // Loop through each event to check if it should be executed
            foreach ($events as $event) {
                // Check if the process should be interrupted
                if ($this->shouldInterrupt()) {
                    return;
                }

                // Skip the event if it shouldn't repeat at this time
                if (! $event->shouldRepeatNow()) {
                    continue;
                }

                // If the system is in maintenance mode, ensure the event can run in that state
                $hasEnteredMaintenanceMode = $hasEnteredMaintenanceMode || $this->app->isDownForMaintenance();

                if ($hasEnteredMaintenanceMode && ! $event->runsInMaintenanceMode()) {
                    continue;
                }

                // Skip the event if the filters do not pass
                if (! $event->filtersPass($this->app)) {
                    $this->dispatcher->dispatch($this->createScheduledTaskSkippedEvent(['task' => $event]));

                    continue;
                }

                // Run the event either on a single server or multiple servers
                if ($event->onOneServer) {
                    $this->runSingleServerEvent($event);
                } else {
                    $this->runEvent($event);
                }

                // Mark that events have run
                $this->eventsRan = true;
            }

            // Sleep for 100 milliseconds before checking again
            Sleep::usleep(100000);
        }
    }

    /**
     * Determine if the schedule run should be interrupted.
     *
     * @return bool
     */
    protected function shouldInterrupt(): bool
    {
        return $this->cache->get('illuminate:schedule:interrupt', false);
    }

    /**
     * Ensure the interrupt signal is cleared.
     *
     * @return void
     */
    protected function clearInterruptSignal(): void
    {
        $this->cache->forget('illuminate:schedule:interrupt');
    }
}
