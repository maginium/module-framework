<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands\Schedule;

use Closure;
use DateTimeZone;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maginium\Framework\Application\Application;
use Maginium\Framework\Console\Command;
use Maginium\Framework\Console\Interfaces\ScheduleInterface;
use Maginium\Framework\Console\Scheduling\CallbackEvent;
use Maginium\Framework\Console\Scheduling\Event;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Class ScheduleListCommand.
 *
 * This class defines a console command that lists all scheduled tasks.
 * It retrieves and displays scheduled tasks in a formatted output, including
 * information about their cron expressions, next execution times, and time zones.
 *
 * Features:
 * - Displays scheduled tasks and their details.
 * - Supports sorting tasks by their next execution time.
 * - Allows specifying a custom timezone for task display.
 *
 * Usage:
 * This command can be executed via the CLI:
 * `php bin/magento schedule:list [--timezone=<timezone>] [--next]`
 */
#[AsCommand(name: 'schedule:list')]
class ScheduleListCommand extends Command
{
    /**
     * The console command signature.
     *
     * Defines the command name and available options/arguments.
     *
     * @var string
     */
    protected ?string $signature = 'schedule:list
   {--timezone= : The timezone that times should be displayed in}
   {--next : Sort the listed tasks by their next due date}
    ';

    /**
     * The console command description.
     *
     * Describes the purpose of this command, displayed in the command list.
     *
     * @var string
     */
    protected ?string $description = 'List all scheduled tasks';

    /**
     * Execute the console command to retrieve and display all scheduled tasks.
     *
     * This method handles the process of fetching all scheduled tasks, formatting
     * them according to the terminal's width, and sorting them by their next due
     * time if requested. Additionally, it adjusts the displayed times according
     * to the specified timezone for accurate scheduling.
     *
     * @param ScheduleInterface $schedule The scheduler instance containing the events to be displayed.
     *
     * @throws Exception If an invalid timezone is provided or an error occurs during execution, such as issues with task retrieval or processing.
     *
     * @return void
     */
    public function handle(ScheduleInterface $schedule): void
    {
        // Collect all scheduled events into a collection.
        $events = collect($schedule->events());

        // Check cif there are no scheduled tasks defined.
        if ($events->isEmpty()) {
            $this->components->info('No scheduled tasks have been defined.');

            return;
        }

        // Determine the terminal width for proper output formatting.
        $terminalWidth = self::getTerminalWidth();

        // Calculate the spacing required for cron expressions and repeat expressions.
        $expressionSpacing = $this->getCronExpressionSpacing($events);
        $repeatExpressionSpacing = $this->getRepeatExpressionSpacing($events);

        // Get the timezone to display event times.
        $timezone = $this->createDateTimeZone(['timezone' => $this->option('timezone') ?? config('app.timezone')]);

        // Sort the events if the --next option is specified.
        $events = $this->sortEvents($events, $timezone);

        // Format each event for display.
        $events = $events->map(
            fn($event) => $this->listEvent($event, $terminalWidth, $expressionSpacing, $repeatExpressionSpacing, $timezone),
        );

        // Output the formatted list of events.
        $this->line(
            $events->flatten()->filter()->prepend('')->push('')->toArray(),
        );
    }

    /**
     * Get the spacing to be used on each event row for cron expressions.
     *
     * This method calculates the maximum width of each segment of the cron
     * expression across all events for consistent alignment.
     *
     * @param Collection $events A collection of scheduled events.
     *
     * @return array<int, int> An array of maximum segment widths for each part of the cron expression.
     */
    private function getCronExpressionSpacing($events): array
    {
        // Calculate the width of each segment of the cron expression for all events.
        $rows = $events->map(
            fn($event) => array_map('mb_strlen', preg_split("/\s+/", $event->expression)),
        );

        // Determine the maximum width for each segment.
        return collect($rows[0] ?? [])->keys()->map(fn($key) => $rows->max($key))->all();
    }

    /**
     * Get the spacing to be used on each event row for repeat expressions.
     *
     * This method calculates the maximum width required for repeat expressions
     * (e.g., hourly, daily) to ensure consistent alignment.
     *
     * @param Collection $events A collection of scheduled events.
     *
     * @return int The maximum width of the repeat expression across all events.
     */
    private function getRepeatExpressionSpacing($events): int
    {
        // Calculate the maximum length of repeat expressions across all events.
        return $events->map(
            fn($event) => mb_strlen($this->getRepeatExpression($event)),
        )->max();
    }

    /**
     * List the given event in the console.
     *
     * This method generates a formatted list of events, showing details such as cron expression,
     * repeat expression, command, next due date, and more. It supports verbose output mode for
     * displaying additional event descriptions.
     *
     * @param  Event $event The event to be listed.
     * @param  int   $terminalWidth  The width of the terminal for formatting purposes.
     * @param  array $expressionSpacing The required spacing for the cron expression columns.
     * @param  int   $repeatExpressionSpacing The required spacing for the repeat expression column.
     * @param  DateTimeZone   $timezone The timezone to display the event times in.
     *
     * @return array Returns an array of formatted event details.
     */
    private function listEvent($event, $terminalWidth, $expressionSpacing, $repeatExpressionSpacing, $timezone): array
    {
        // Format the cron expression with appropriate spacing.
        $expression = $this->formatCronExpression($event->expression, $expressionSpacing);

        // Format the repeat expression if available, padded to the correct length.
        $repeatExpression = Str::padRight($this->getRepeatExpression($event), $repeatExpressionSpacing);

        // Get the event's command, if it exists.
        $command = $event->command ?? '';

        // Get the event's description, if available.
        $description = $event->description ?? '';

        // If not in verbose mode, replace certain elements of the command for readability.
        if (! $this->output->isVerbose()) {
            $command = str_replace([Application::phpBinary(), Application::magentoBinary()], [
                'php',
                preg_replace("#['\"]#", '', Application::magentoBinary()),
            ], $command);
        }

        // If the event is a callback, display the closure's location or summary.
        if ($event instanceof CallbackEvent) {
            $command = $event->getSummaryForDisplay();

            // Handle closures or callbacks in the event.
            if (in_array($command, ['Closure', 'Callback'])) {
                $command = 'Closure at: ' . $this->getClosureLocation($event);
            }
        }

        // Ensure that the command is formatted with a space if it's non-empty.
        $command = mb_strlen($command) > 1 ? "{$command} " : '';

        // Label for next due date.
        $nextDueDateLabel = 'Next Due:';

        // Get the next due date for the event.
        $nextDueDate = $this->getNextDueDateForEvent($event, $timezone);

        // Format the next due date depending on whether verbose output is enabled.
        $nextDueDate = $this->output->isVerbose()
        ? $nextDueDate->format('Y-m-d H:i:s P')
        : $nextDueDate->diffForHumans();

        // Check if the event has a mutex (lock).
        $hasMutex = $event->mutex->exists($event) ? 'Has Mutex › ' : '';

        // Calculate the dots used to fill the remaining space in the terminal.
        $dots = str_repeat('.', max(
            $terminalWidth - mb_strlen($expression . $repeatExpression . $command . $nextDueDateLabel . $nextDueDate . $hasMutex) - 8,
            0,
        ));

        // Highlight the parameters of the command by applying a bold yellow color.
        $command = preg_replace("#(php bin/magento [\w\-:]+) (.+)#", '$1 <fg=yellow;options=bold>$2</>', $command);

        // Return the formatted string for the event, with additional description if verbose mode is enabled.
        return [sprintf(
            '  <fg=yellow>%s</> <fg=#6C7280>%s</> %s<fg=#6C7280>%s %s%s %s</>',
            $expression,
            $repeatExpression,
            $command,
            $dots,
            $hasMutex,
            $nextDueDateLabel,
            $nextDueDate,
        ), $this->output->isVerbose() && mb_strlen($description) > 1 ? sprintf(
            '  <fg=#6C7280>%s%s %s</>',
            str_repeat(' ', mb_strlen($expression) + 2),
            '⇁',
            $description,
        ) : ''];
    }

    /**
     * Get the repeat expression for an event.
     *
     * This method returns the repeat expression for an event if it is repeatable,
     * otherwise returns an empty string.
     *
     * @param  Event  $event  The event to check for repeatability.
     *
     * @return string The repeat expression, or an empty string if not repeatable.
     */
    private function getRepeatExpression($event): string
    {
        // If the event is repeatable, return the repeat seconds in the format "xxs ".
        return $event->isRepeatable() ? "{$event->repeatSeconds}s " : '';
    }

    /**
     * Sort the events by their next due date if the "next" option is set.
     *
     * This method checks if the "next" option is provided. If so, it sorts the events
     * by their next due date using the `getNextDueDateForEvent` method to calculate the
     * next due date for each event based on its cron expression and timezone.
     * If no sorting is required, it simply returns the events collection as is.
     *
     * @param  Collection  $events  The collection of events to be sorted.
     * @param  DateTimeZone  $timezone  The timezone to be used for calculating the next due date.
     *
     * @return Collection  A sorted collection of events if "next" option is set, otherwise the original collection.
     */
    private function sortEvents(Collection $events, DateTimeZone $timezone): Collection
    {
        // Check if the "next" option is set for sorting events by their next due date.
        if ($this->option('next')) {
            // Sort the events by the next due date.
            return $events->sortBy(fn($event) => $this->getNextDueDateForEvent($event, $timezone));
        }

        // Return the events as they are if no sorting is required.
        return $events;
    }

    /**
     * Get the next due date for the given event based on its cron expression and repeatability.
     *
     * This method calculates the next due date for the event using the cron expression. If the event
     * is repeatable, it will also handle the logic for calculating the next due date based on the repeat
     * interval.
     *
     * @param  Event  $event  The event to calculate the next due date for.
     * @param  DateTimeZone  $timezone  The timezone in which the next due date should be calculated.
     *
     * @return Carbon  The next due date for the event.
     */
    private function getNextDueDateForEvent($event, DateTimeZone $timezone): Carbon
    {
        // Get the current time in the event's timezone.
        $currentTimeInEventTimezone = Carbon::now()->setTimezone($event->timezone);

        // Create cron expression object from the event's cron expression.
        $cronExpression = $this->createCronExpression(['expression' => $event->expression]);

        // Calculate the next run date for the event based on the cron expression.
        $nextDueDate = Carbon::instance(
            $cronExpression
                ->getNextRunDate($currentTimeInEventTimezone) // Get next due date
                ->setTimezone($timezone), // Set to desired timezone
        );

        // If the event is not repeatable, return the next due date immediately.
        if (! $event->isRepeatable()) {
            return $nextDueDate;
        }

        // Calculate the previous due date (this helps determine if it's time for a repeat).
        $previousDueDate = Carbon::instance(
            $cronExpression
                ->getPreviousRunDate($currentTimeInEventTimezone, allowCurrentDate: true) // Allow current date as previous run date
                ->setTimezone($timezone), // Set to desired timezone
        );

        // If the current time is not equal to the previous due date, return the next due date.
        if (! $currentTimeInEventTimezone->copy()->startOfMinute()->eq($previousDueDate)) {
            return $nextDueDate;
        }

        // If the event is repeatable, calculate the next repeatable run date.
        return $currentTimeInEventTimezone
            ->endOfSecond() // Ensure the time is at the end of the second
            ->ceilSeconds($event->repeatSeconds); // Adjust the time to match the repeat interval
    }

    /**
     * Format the cron expression based on the spacing provided.
     *
     * This method formats the cron expression according to the given spacing array
     * to align the columns for the listing display.
     *
     * @param  string $expression The cron expression to format.
     * @param  array<int, int> $spacing    The array containing the required column widths.
     *
     * @return string The formatted cron expression with proper spacing.
     */
    private function formatCronExpression($expression, $spacing): string
    {
        // Split the cron expression into individual components (e.g., minute, hour, day).
        $expressions = preg_split("/\s+/", $expression);

        // Map the spacing array to pad each cron component and then implode them into a single string.
        return (string)collect($spacing)
            ->map(fn($length, $index) => Str::padRight($expressions[$index], $length))
            ->implode(' ');
    }

    /**
     * Get the file and line number for the event closure.
     *
     * This method retrieves the location of the callback closure for the event, if
     * it is a closure, including the file and line number.
     *
     * @param  CallbackEvent  $event  The event containing the callback.
     *
     * @return string The location of the closure as a string.
     */
    private function getClosureLocation(CallbackEvent $event): string
    {
        // Retrieve the callback function from the event using reflection.
        $callback = Reflection::getProperty($event, 'callback')->getValue($event);

        // If the callback is a closure, get the file and line number where it is defined.
        if ($callback instanceof Closure) {
            $function = Reflection::getFunction($callback);

            return sprintf(
                '%s:%s',
                str_replace(BP . DIRECTORY_SEPARATOR, '', $function->getFileName() ?: ''),
                $function->getStartLine(),
            );
        }

        // If the callback is a string, return it as is.
        if (is_string($callback)) {
            return $callback;
        }

        // If the callback is an array (class method), return the class and method name.
        if (is_array($callback)) {
            $className = is_string($callback[0]) ? $callback[0] : $callback[0]::class;

            return sprintf('%s::%s', $className, $callback[1]);
        }

        // Return the __invoke method if the callback is an invokable class.
        return sprintf('%s::__invoke', $callback::class);
    }
}
