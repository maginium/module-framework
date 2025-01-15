<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands\Schedule;

use Maginium\Framework\Application\Application;
use Maginium\Framework\Console\Command;
use Maginium\Framework\Console\Interfaces\ScheduleInterface;
use Maginium\Framework\Console\Scheduling\CallbackEvent;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\select;

/**
 * Class ScheduleTestCommand.
 *
 * This command is responsible for executing scheduled tasks in the application.
 * It allows users to list and execute any scheduled command, either by selecting
 * from a list of commands or by specifying the command name as an option.
 * It is primarily used for testing or manually triggering scheduled commands.
 */
#[AsCommand(name: 'schedule:test')]
class ScheduleTestCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected ?string $signature = 'schedule:test {--name= : The name of the scheduled command to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected ?string $description = 'Run a scheduled command';

    /**
     * Execute the console command.
     *
     * This method retrieves all scheduled commands, allows the user to select one, and then executes the selected command.
     * If no command is selected, the task will notify the user and exit.
     *
     * @param  ScheduleInterface  $schedule  The schedule instance containing all the scheduled commands.
     *
     * @return void
     */
    public function handle(ScheduleInterface $schedule)
    {
        // Get the PHP binary path to run the commands
        $phpBinary = Application::phpBinary();

        // Fetch all scheduled events (commands)
        $commands = $schedule->events();

        // Initialize an array to hold the command names for user selection
        $commandNames = [];

        // Loop through all commands and prepare their names for selection
        foreach ($commands as $command) {
            $commandNames[] = $command->command ?? $command->getSummaryForDisplay();
        }

        // If no commands are found, inform the user and exit
        if (empty($commandNames)) {
            return $this->components->info('No scheduled commands have been defined.');
        }

        // If a specific command name is provided via option, filter the list of commands
        if (! empty($name = $this->option('name'))) {
            // Build the full command name (with php and magento binaries)
            $commandBinary = $phpBinary . ' ' . Application::magentoBinary();

            // Filter commands matching the provided name
            $matches = Arr::filter($commandNames, fn($commandName) => Str::trim(Str::replace($commandBinary, '', $commandName)) === $name);

            // If no matching command is found, inform the user and exit
            if (count($matches) !== 1) {
                $this->components->info('No matching scheduled command found.');

                return;
            }

            // Get the index of the matching command
            $index = key($matches);
        } else {
            // If no name is provided, prompt the user to select a command by index
            $index = $this->getSelectedCommandByIndex($commandNames);
        }

        // Fetch the selected event (command)
        $event = $commands[$index];

        // Get the summary of the selected event (command)
        $summary = $event->getSummaryForDisplay();

        // Prepare the command string for execution (either the summary or the actual command)
        $command = $event instanceof CallbackEvent
            ? $summary
            : trim(str_replace($phpBinary, '', $event->command));

        // Prepare the description message for the task
        $description = sprintf(
            'Running [%s]%s',
            $command,
            $event->runInBackground ? ' in background' : '',
        );

        // Display a task message and execute the event's run method
        $this->components->task($description, fn() => $event->run($this->app));

        // If the event is not a callback, display the command's summary in a bullet list
        if (! $event instanceof CallbackEvent) {
            $this->components->bulletList([$event->getSummaryForDisplay()]);
        }

        // Output a newline for better formatting
        $this->newLine();
    }

    /**
     * Get the selected command name by index.
     *
     * This method handles the selection of a command by the user. If multiple commands
     * have the same name (due to closures or other factors), it appends a unique index
     * to each option for differentiation.
     *
     * @param  array  $commandNames  The array of command names to choose from.
     *
     * @return int  The index of the selected command.
     */
    protected function getSelectedCommandByIndex(array $commandNames): int
    {
        // Check for duplicate command names
        if (count($commandNames) !== count(Arr::unique($commandNames))) {
            // For duplicate command names (likely closures), append a unique index
            $uniqueCommandNames = array_map(fn($index, $value) => "{$value} [{$index}]", Arr::keys($commandNames), $commandNames);

            // Prompt the user to select one of the commands
            $selectedCommand = select('Which command would you like to run?', $uniqueCommandNames);

            // Extract the selected index from the user's input
            preg_match('/\[(\d+)\]/', $selectedCommand, $choice);

            return (int)$choice[1];
        }

        // If no duplicates are found, simply return the index of the selected command
        return Arr::search(
            select('Which command would you like to run?', $commandNames),
            $commandNames,
        );
    }
}
