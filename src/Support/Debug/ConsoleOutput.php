<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Debug;

use Laravel\Prompts\Output\ConsoleOutput as BaseConsoleOutput;
use Maginium\Framework\Support\Facades\Prompts as Console;

/**
 * Class ConsoleOutput.
 *
 * This class provides methods for displaying customized messages in the console with optional emoji prefixes
 * for better visual feedback. It supports different message types such as notes, information, success, warnings,
 * alerts, errors, intros, and outros.
 *
 * Each method in this class includes an option to prepend an emoji to the message for enhanced clarity and
 * visual appeal. By default, emojis are included, but this can be disabled by passing a boolean parameter.
 */
class ConsoleOutput extends BaseConsoleOutput
{
    /**
     * Display a note message to the user.
     *
     * This method will prepend a note emoji ("📝") to the message unless disabled via the $addEmoji flag.
     * Notes are typically used for helpful information or additional context that does not fall under errors or warnings.
     *
     * @param string $message The note content to display.
     * @param string|null $type Optional type of the note for further customization (e.g., specific formatting).
     * @param bool $addEmoji Whether to prepend an emoji to the message. Default is true (emoji added).
     */
    public static function note(string $message, ?string $type = null, bool $addEmoji = true): void
    {
        // Determine the emoji to prepend based on $addEmoji
        $emoji = $addEmoji ? '📝 ' : '';

        // Display the note message with optional type and emoji
        Console::note("{$emoji}{$message}", $type);
    }

    /**
     * Display an informational message to the user.
     *
     * This method will prepend an information emoji ("ℹ️") to the message unless disabled via the $addEmoji flag.
     * Use this for general informational messages that don't indicate any success or failure.
     *
     * @param string $message The informational message content to display.
     * @param bool $addEmoji Whether to prepend an emoji to the message. Default is true (emoji added).
     */
    public static function info(string $message, bool $addEmoji = true): void
    {
        // Emoji to use for information messages
        $emoji = $addEmoji ? 'ℹ️ ' : '';

        // Display the info message with optional emoji
        Console::info("{$emoji}{$message}");
    }

    /**
     * Display a success message to the user.
     *
     * This method will prepend a success emoji ("✅") to the message unless disabled via the $addEmoji flag.
     * Success messages indicate the successful completion of an action or task.
     *
     * @param string $message The success message content to display.
     * @param bool $addEmoji Whether to prepend an emoji to the message. Default is true (emoji added).
     */
    public static function success(string $message, bool $addEmoji = true): void
    {
        // Emoji for success messages
        $emoji = $addEmoji ? '✅ ' : '';

        // Display the success message with optional emoji
        Console::success("{$emoji}{$message}");
    }

    /**
     * Display a warning message to the user.
     *
     * This method will prepend a warning emoji ("⚠️") to the message unless disabled via the $addEmoji flag.
     * Use warnings to indicate potential issues or areas where user attention is needed.
     *
     * @param string $message The warning message content to display.
     * @param bool $addEmoji Whether to prepend an emoji to the message. Default is true (emoji added).
     */
    public static function warning(string $message, bool $addEmoji = true): void
    {
        // Emoji for warning messages
        $emoji = $addEmoji ? '⚠️ ' : '';

        // Display the warning message with optional emoji
        Console::warning("{$emoji}{$message}");
    }

    /**
     * Display an alert message to the user.
     *
     * This method will prepend an alert emoji ("🚨") to the message unless disabled via the $addEmoji flag.
     * Alerts are typically used for critical issues or items requiring immediate attention.
     *
     * @param string $message The alert message content to display.
     * @param bool $addEmoji Whether to prepend an emoji to the message. Default is true (emoji added).
     */
    public static function alert(string $message, bool $addEmoji = true): void
    {
        // Emoji for alert messages
        $emoji = $addEmoji ? '🚨 ' : '';

        // Display the alert message with optional emoji
        Console::alert("{$emoji}{$message}");
    }

    /**
     * Display an error message to the user.
     *
     * This method will prepend an error emoji ("❌") to the message unless disabled via the $addEmoji flag.
     * Use this for error messages indicating that something went wrong or failed.
     *
     * @param string $message The error message content to display.
     * @param bool $addEmoji Whether to prepend an emoji to the message. Default is true (emoji added).
     */
    public static function error(string $message, bool $addEmoji = true): void
    {
        // Emoji for error messages
        $emoji = $addEmoji ? '❌ ' : '';

        // Display the error message with optional emoji
        Console::error("{$emoji}{$message}");
    }

    /**
     * Display an introduction message to the user.
     *
     * This method will prepend a greeting emoji ("👋") to the message unless disabled via the $addEmoji flag.
     * Use introduction messages to provide a warm welcome or introduce a process or workflow.
     *
     * @param string $message The introduction message content to display.
     * @param bool $addEmoji Whether to prepend an emoji to the message. Default is true (emoji added).
     */
    public static function intro(string $message, bool $addEmoji = false): void
    {
        // Emoji for introduction messages
        $emoji = $addEmoji ? '👋 ' : '';

        // Display the intro message with optional emoji
        Console::intro("{$emoji}{$message}");
    }

    /**
     * Display a closing message to the user.
     *
     * This method will prepend a celebration emoji ("🎉") to the message unless disabled via the $addEmoji flag.
     * Use closing messages to wrap up or mark the end of a process, event, or task.
     *
     * @param string $message The closing message content to display.
     * @param bool $addEmoji Whether to prepend an emoji to the message. Default is true (emoji added).
     */
    public static function outro(string $message, bool $addEmoji = false): void
    {
        // Emoji for closing messages
        $emoji = $addEmoji ? '🎉 ' : '';

        // Display the outro message with optional emoji
        Console::outro("{$emoji}{$message}");
    }
}
