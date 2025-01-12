<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands\Schedule;

use Illuminate\Support\Carbon;
use Illuminate\Support\ProcessUtils;
use Maginium\Framework\Application\Application;
use Maginium\Framework\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class ScheduleWorkCommand.
 *
 * This command is responsible for running scheduled tasks in the application.
 * It simulates a worker that triggers the `schedule:run` command every minute
 * to execute any due scheduled tasks. It allows the output of the scheduled tasks
 * to be redirected to a specified file for logging purposes.
 */
#[AsCommand(name: 'schedule:work')]
class ScheduleWorkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected ?string $signature = 'schedule:work {--run-output-file= : The file to direct <info>schedule:run</info> output to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected ?string $description = 'Start the schedule worker';

    /**
     * Execute the console command.
     *
     * This method runs the scheduled tasks every minute. It starts the `schedule:run`
     * command in the background and allows its output to be captured or logged.
     * The tasks are executed only if the current time is at the start of a new minute.
     *
     * @return int
     */
    public function handle(): int
    {
        // Display an informational message to indicate the command is running.
        // Set the verbosity level based on the environment (local or production).
        $this->components->info(
            'Running scheduled tasks every minute.',
            $this->getApp()->isLocal() ? OutputInterface::VERBOSITY_NORMAL : OutputInterface::VERBOSITY_VERBOSE,
        );

        // Initialize the variables for tracking the last execution time and the list of executions.
        // The last execution started 10 minutes ago to account for any missed schedules.
        [$lastExecutionStartedAt, $executions] = [Carbon::now()->subMinutes(10), []];

        // Build the command to run the scheduled tasks using PHP and the Magento CLI.
        $command = implode(' ', array_map(fn($arg) => ProcessUtils::escapeArgument($arg), [
            PHP_BINARY,
            defined('MAGENTO_BINARY') ? MAGENTO_BINARY : Application::DEFAULT_MAGENTO_BINARY,
            'schedule:run',
        ]));

        // If the output file option is set, append the redirection to the command.
        if ($this->option('run-output-file')) {
            $command .= ' >> ' . ProcessUtils::escapeArgument($this->option('run-output-file')) . ' 2>&1';
        }

        // Set a maximum iteration limit to prevent the infinite loop from running indefinitely.
        // This is useful for testing or when an external signal can stop the process.
        $maxIterations = 100; // Limit the number of iterations
        $iterations = 0; // Track the number of loop iterations

        // Start the infinite loop (or until max iterations) to check for task execution.
        while ($iterations < $maxIterations) {
            // Sleep for 100 milliseconds to avoid high CPU usage in the loop.
            usleep(100 * 1000);

            // Check if it's the start of a new minute and if the previous execution was not started in the same minute.
            if (Carbon::now()->second === 0 &&
                ! Carbon::now()->startOfMinute()->equalTo($lastExecutionStartedAt)) {
                // Create a new process to run the schedule:run command and start it.
                $executions[] = $execution = Process::fromShellCommandline($command);
                $execution->start();

                // Update the last execution time to the start of the current minute.
                $lastExecutionStartedAt = Carbon::now()->startOfMinute();
            }

            // Iterate over all active executions to handle their outputs and completion.
            foreach ($executions as $key => $execution) {
                // Capture both standard output and error output of the process.
                $output = $execution->getIncrementalOutput() .
                    $execution->getIncrementalErrorOutput();

                // Write the output to the console.
                $this->output->write(ltrim($output, "\n"));

                // If the execution has finished, remove it from the list of active executions.
                if (! $execution->isRunning()) {
                    unset($executions[$key]);
                }
            }

            // Increment the iteration count (can be adjusted based on exit conditions or signals).
            $iterations++;
        }

        // Inform the user that the command has been successfully executed.
        $this->components->info('Command executed successfully.');

        // Return success status to indicate the command completed without errors.
        return self::SUCCESS;
    }
}
