<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands\Schedule;

use Illuminate\Contracts\Events\Dispatcher;
use Maginium\Framework\Console\Command;
use Maginium\Framework\Console\Interfaces\ScheduleInterface;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command to handle the completion of a scheduled task.
 *
 * This command is invoked after a scheduled command completes its execution.
 * It processes the finished task by releasing the mutex and dispatching an event
 * to notify that the task has completed in the background.
 */
#[AsCommand(name: 'schedule:finish')]
class ScheduleFinishCommand extends Command
{
    /**
     * The console command signature.
     *
     * Defines the name of the command and its required/optional arguments.
     *
     * @var string|null
     */
    protected ?string $signature = 'schedule:finish {id} {code=0}';

    /**
     * The console command description.
     *
     * Provides a brief summary of the command's purpose.
     *
     * @var string|null
     */
    protected ?string $description = 'Handle the completion of a scheduled command';

    /**
     * Indicates whether the command should be visible in the command list.
     *
     * This command is hidden because it is typically called internally
     * and not meant for direct user invocation.
     *
     * @var bool
     */
    protected bool $hidden = true;

    /**
     * Execute the console command.
     *
     * This method handles the logic for processing a completed scheduled task.
     * It filters the scheduled events to find the matching event by its mutex name,
     * releases its mutex, and dispatches a notification event.
     *
     * @param ScheduleInterface $schedule  The scheduler instance containing the events.
     *
     * @return void
     */
    public function handle(
        ScheduleInterface $schedule,
    ): void {
        // Collect all scheduled events and filter to find the one matching the given ID.
        collect($schedule->events())
            ->filter(fn($value) => $value->mutexName() === $this->argument('id')) // Filter events by mutex name matching the provided ID.
            ->each(function($event): void {
                // Mark the event as finished by calling its finish method.
                $event->finish($this->app, $this->argument('code'));

                // Dispatch the ScheduledBackgroundTaskFinished event for the completed task.
                $this->app->resolve(Dispatcher::class) // Resolve the Dispatcher instance from the container.
                    ->dispatch(
                        $this->createScheduledBackgroundTaskFinishedEvent([ // Create the event using the factory.
                            'task' => $event, // Pass the task details to the factory.
                        ]),
                    );
            });
    }
}
