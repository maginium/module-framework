<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands\Schedule;

use Maginium\Framework\Console\Command;
use Maginium\Framework\Console\Interfaces\ScheduleInterface;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command to clear cached mutex files created by the scheduler.
 *
 * This command iterates through scheduled events, checks if a mutex file exists
 * for each event, and deletes it if found. The command helps ensure that stale
 * mutex files do not prevent scheduled tasks from running due to overlapping.
 */
#[AsCommand(name: 'schedule:clear-cache')]
class ScheduleClearCacheCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string|null Description of the command displayed in the console.
     */
    protected ?string $description = 'Delete the cached mutex files created by scheduler';

    /**
     * Execute the console command.
     *
     * This method is the entry point when the command is executed. It iterates
     * over all scheduled events, checks for the existence of mutex files, and deletes
     * them. If no mutex files are found, it outputs a message indicating so.
     *
     * @param  ScheduleInterface  $schedule The scheduler instance containing the events.
     *
     * @return void
     */
    public function handle(ScheduleInterface $schedule): void
    {
        // Flag to track whether any mutex files were cleared during execution.
        $mutexCleared = false;

        // Iterate over all scheduled events defined in the Schedule instance.
        foreach ($schedule->events() as $event) {
            // Check if a mutex file exists for the current event.
            if ($event->mutex->exists($event)) {
                // Output an informational message indicating the deletion of the mutex file.
                $this->components->info(sprintf('Deleting mutex for [%s]', $event->command));

                // Delete the mutex file for the current event.
                $event->mutex->forget($event);

                // Set the flag to true, indicating that at least one mutex file was cleared.
                $mutexCleared = true;
            }
        }

        // If no mutex files were cleared, output a message to indicate that none were found.
        if (! $mutexCleared) {
            $this->components->info('No mutex files were found.');
        }
    }
}
