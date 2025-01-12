<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Schedule;

use Illuminate\Support\ProcessUtils;
use Maginium\Framework\Application\Application;
use Maginium\Framework\Console\Scheduling\Event;

/**
 * Class CommandBuilder.
 *
 * This class is responsible for building the shell command for a given event.
 * It handles the process of constructing commands for both foreground and background execution
 * and ensures the correct user is set for the command execution.
 */
class CommandBuilder
{
    /**
     * Build the command for the given event.
     *
     * This method checks if the event should run in the background or foreground
     * and delegates the responsibility to the appropriate method for building the command.
     *
     * @param  Event  $event
     *
     * @return string The generated shell command for the event
     */
    public function buildCommand(Event $event): string
    {
        // Check if the event needs to run in the background
        if ($event->runInBackground) {
            return $this->buildBackgroundCommand($event);
        }

        // If not in the background, run the event in the foreground
        return $this->buildForegroundCommand($event);
    }

    /**
     * Build the command for running the event in the foreground.
     *
     * This method generates a shell command that runs the event in the foreground,
     * redirecting its output to a specified file or appending to it based on the event configuration.
     *
     * @param  Event  $event
     *
     * @return string The generated shell command for foreground execution
     */
    protected function buildForegroundCommand(Event $event): string
    {
        // Escape the output file argument to avoid any issues with special characters
        $output = ProcessUtils::escapeArgument($event->output);

        // Ensure the correct user is used to run the command and return the final command
        return $this->ensureCorrectUser(
            $event,
            $event->command . ($event->shouldAppendOutput ? ' >> ' : ' > ') . $output . ' 2>&1',
        );
    }

    /**
     * Build the command for running the event in the background.
     *
     * This method generates a shell command that runs the event in the background,
     * handling both Windows and Unix-based systems by properly formatting the command.
     *
     * @param  Event  $event
     *
     * @return string The generated shell command for background execution
     */
    protected function buildBackgroundCommand(Event $event): string
    {
        // Escape the output file argument for background execution
        $output = ProcessUtils::escapeArgument($event->output);

        // Determine whether to append output or not
        $redirect = $event->shouldAppendOutput ? ' >> ' : ' > ';

        // Define the command to finalize the event
        $finished = Application::formatCommandString('schedule:finish') . ' "' . $event->mutexName() . '"';

        // Handle Windows operating system specific command format
        if (windows_os()) {
            return 'start /b cmd /v:on /c "(' . $event->command . ' & ' . $finished . ' ^!ERRORLEVEL^!)' . $redirect . $output . ' 2>&1"';
        }

        // Handle Unix-based operating system specific command format
        return $this->ensureCorrectUser(
            $event,
            '(' . $event->command . $redirect . $output . ' 2>&1 ; ' . $finished . ' "$?") > '
            . ProcessUtils::escapeArgument($event->getDefaultOutput()) . ' 2>&1 &',
        );
    }

    /**
     * Finalize the event's command syntax with the correct user.
     *
     * This method ensures that the command runs under the correct user, utilizing sudo
     * if needed, based on the event configuration. It also accounts for Windows-based systems
     * where sudo is not available.
     *
     * @param  Event  $event
     * @param  string  $command
     *
     * @return string The finalized command with the correct user (if needed)
     */
    protected function ensureCorrectUser(Event $event, string $command): string
    {
        // If the event has a user set and it's not a Windows OS, use sudo to run the command as the specified user
        return $event->user && ! windows_os() ? 'sudo -u ' . $event->user . ' -- sh -c \'' . $command . '\'' : $command;
    }
}
