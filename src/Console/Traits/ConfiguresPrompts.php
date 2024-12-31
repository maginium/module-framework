<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Traits;

use Closure;
use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\MultiSearchPrompt;
use Laravel\Prompts\MultiSelectPrompt;
use Laravel\Prompts\PasswordPrompt;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\SearchPrompt;
use Laravel\Prompts\SelectPrompt;
use Laravel\Prompts\SuggestPrompt;
use Laravel\Prompts\TextPrompt;
use Maginium\Framework\Support\Arr;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Trait ConfiguresPrompts.
 *
 * This trait provides methods for configuring interactive prompts in console commands.
 * It sets up fallback mechanisms for various prompt types, ensuring that user input
 * is collected and validated properly during command execution.
 *
 * Prompts include text, password, confirm, select, multi-select, suggest, search,
 * and multi-search prompts, with customizable validation rules and required checks.
 */
trait ConfiguresPrompts
{
    /**
     * Configure the fallback behavior for different prompt types.
     *
     * This method sets the output for prompts, enables interactive mode, and configures
     * the fallback behavior for various prompt types such as text, password, confirm,
     * select, multi-select, suggest, search, and multi-search prompts.
     *
     * @param  InputInterface  $input  The input interface instance.
     */
    protected function configurePrompts(InputInterface $input): void
    {
        // Set the output for prompts to the current command's output.
        Prompt::setOutput($this->output);

        // Enable interactive mode if the input is interactive.
        Prompt::interactive($input->isInteractive() && defined('STDIN') && stream_isatty(STDIN));

        // Set a fallback mechanism for Windows operating systems.
        Prompt::fallbackWhen(windows_os());

        // Configure fallback behavior for all prompt types.

        // Configure fallback for text prompts.
        $this->configureTextPromptFallback();

        // Configure fallback for password prompts.
        $this->configurePasswordPromptFallback();

        // Configure fallback for confirm prompts.
        $this->configureConfirmPromptFallback();

        // Configure fallback for select prompts.
        $this->configureSelectPromptFallback();

        // Configure fallback for multi-select prompts.
        $this->configureMultiSelectPromptFallback();

        // Configure fallback for suggest prompts.
        $this->configureSuggestPromptFallback();

        // Configure fallback for search prompts.
        $this->configureSearchPromptFallback();

        // Configure fallback for multi-search prompts.
        $this->configureMultiSearchPromptFallback();
    }

    /**
     * Configure the fallback behavior for text prompts.
     *
     * This method sets up how the application should handle text prompts when
     * the normal prompt behavior cannot be used, using a fallback mechanism
     * that repeatedly asks for input until valid input is provided.
     */
    protected function configureTextPromptFallback(): void
    {
        // Define how to handle text prompts using a fallback mechanism.
        TextPrompt::fallbackUsing(fn(TextPrompt $prompt) => $this->promptUntilValid(
            // Callback function to ask for user input.
            fn() => $this->components->ask($prompt->label, $prompt->default ?: null) ?? '',
            $prompt->required, // Indicates if input is required.
            $prompt->validate, // Validation callback for the input.
        ));
    }

    /**
     * Configure the fallback behavior for password prompts.
     *
     * Similar to text prompts, but specifically for sensitive information
     * that should be masked during input.
     */
    protected function configurePasswordPromptFallback(): void
    {
        // Define how to handle password prompts using a fallback mechanism.
        PasswordPrompt::fallbackUsing(fn(PasswordPrompt $prompt) => $this->promptUntilValid(
            // Callback function to ask for secret input.
            fn() => $this->components->secret($prompt->label) ?? '',
            $prompt->required, // Indicates if input is required.
            $prompt->validate, // Validation callback for the input.
        ));
    }

    /**
     * Configure the fallback behavior for confirm prompts.
     *
     * This handles yes/no confirmation prompts, validating the user's response
     * according to the specified requirements.
     */
    protected function configureConfirmPromptFallback(): void
    {
        // Define how to handle confirm prompts using a fallback mechanism.
        ConfirmPrompt::fallbackUsing(fn(ConfirmPrompt $prompt) => $this->promptUntilValid(
            // Callback function to confirm a yes/no answer.
            fn() => $this->components->confirm($prompt->label, $prompt->default),
            $prompt->required, // Indicates if input is required.
            $prompt->validate, // Validation callback for the input.
        ));
    }

    /**
     * Configure the fallback behavior for select prompts.
     *
     * This manages prompts that require the user to select one option
     * from a list of predefined choices.
     */
    protected function configureSelectPromptFallback(): void
    {
        // Define how to handle select prompts using a fallback mechanism.
        SelectPrompt::fallbackUsing(fn(SelectPrompt $prompt) => $this->promptUntilValid(
            // Callback function to present a choice from options.
            fn() => $this->components->choice($prompt->label, $prompt->options, $prompt->default),
            false, // Selection is not required.
            $prompt->validate, // Validation callback for the input.
        ));
    }

    /**
     * Configure the fallback behavior for multi-select prompts.
     *
     * This handles prompts where the user can select multiple options from a list.
     */
    protected function configureMultiSelectPromptFallback(): void
    {
        // Define how to handle multi-select prompts using a fallback mechanism.
        MultiSelectPrompt::fallbackUsing(function(MultiSelectPrompt $prompt) {
            // If default options are provided, handle selection.
            if ($prompt->default !== []) {
                return $this->promptUntilValid(
                    // Callback to allow multiple choices to be made.
                    fn() => $this->components->choice($prompt->label, $prompt->options, implode(',', $prompt->default), multiple: true),
                    $prompt->required, // Indicates if input is required.
                    $prompt->validate, // Validation callback for the input.
                );
            }

            // If no defaults, allow selection from options or "None".
            return $this->promptUntilValid(
                fn() => collect($this->components->choice($prompt->label, ['None', ...$prompt->options], 'None', multiple: true))
                    ->reject('') // Filter out empty selections.
                    ->all(), // Return all selected options.
                $prompt->required, // Indicates if input is required.
                $prompt->validate, // Validation callback for the input.
            );
        });
    }

    /**
     * Configure the fallback behavior for suggest prompts.
     *
     * Suggest prompts allow users to input data with suggestions based on previous input.
     */
    protected function configureSuggestPromptFallback(): void
    {
        // Define how to handle suggest prompts using a fallback mechanism.
        SuggestPrompt::fallbackUsing(fn(SuggestPrompt $prompt) => $this->promptUntilValid(
            // Callback to ask for input with suggestions.
            fn() => $this->components->askWithCompletion($prompt->label, $prompt->options, $prompt->default ?: null) ?? '',
            $prompt->required, // Indicates if input is required.
            $prompt->validate, // Validation callback for the input.
        ));
    }

    /**
     * Configure the fallback behavior for search prompts.
     *
     * This allows the user to search through a set of options based on their input.
     */
    protected function configureSearchPromptFallback(): void
    {
        // Define how to handle search prompts using a fallback mechanism.
        SearchPrompt::fallbackUsing(fn(SearchPrompt $prompt) => $this->promptUntilValid(
            function() use ($prompt) {
                // Prompt user for search input.
                $query = $this->components->ask($prompt->label);

                // Fetch options based on user query.
                $options = ($prompt->options)($query);

                // Present options to the user.
                return $this->components->choice($prompt->label, $options);
            },
            false, // Selection is not required.
            $prompt->validate, // Validation callback for the input.
        ));
    }

    /**
     * Configure the fallback behavior for multi-search prompts.
     *
     * Multi-search prompts allow users to search and select multiple options.
     */
    protected function configureMultiSearchPromptFallback(): void
    {
        // Define how to handle multi-search prompts using a fallback mechanism.
        MultiSearchPrompt::fallbackUsing(fn(MultiSearchPrompt $prompt) => $this->promptUntilValid(
            function() use ($prompt) {
                // Prompt user for search input.
                $query = $this->components->ask($prompt->label);

                // Get options based on the search query.
                $options = ($prompt->options)($query);

                // Handle optional selection; if no default, provide a way to select none.
                if ($prompt->required === false) {
                    if (Arr::isList($options)) {
                        return collect($this->components->choice($prompt->label, ['None', ...$options], 'None', multiple: true))
                            ->reject('None') // Filter out "None" option.
                            ->all(); // Return all selected options.
                    }

                    // Present options without the "None" selection.
                    return $this->components->choice($prompt->label, $options, null, multiple: true);
                }

                // Present options when required.
                return $this->components->choice($prompt->label, $options, null, multiple: true);
            },
            $prompt->required, // Indicates if input is required.
            $prompt->validate, // Validation callback for the input.
        ));
    }

    /**
     * Prompt the user repeatedly until valid input is provided.
     *
     * This method loops until the user provides input that satisfies the
     * specified validation rules, allowing for required checks and returning
     * the validated input.
     *
     * @param  callable  $callback  The callback function that collects input.
     * @param  bool  $required  Indicates if input is required.
     * @param  Closure|null  $validate  Optional validation callback.
     */
    protected function promptUntilValid(callable $callback, bool $required = false, ?Closure $validate = null): mixed
    {
        do {
            // Call the provided function to get user input.
            $input = $callback();

            // If input is required and empty, prompt the user again.
            if ($required && trim($input) === '') {
                // Warn the user.
                $this->components->warn('This field is required. Please provide a value.');

                // Restart the loop to prompt again.
                continue;
            }

            // If a validation callback is provided, execute it and check for errors.
            if ($validate && ! $validate($input)) {
                // Warn the user about invalid input.
                $this->components->warn('Invalid input. Please try again.');

                // Restart the loop to prompt again.
                continue;
            }

            // Break the loop if the input is valid.
            break;
            // Continue prompting until valid input is received.
        } while (true);

        // Return the validated input.
        return $input;
    }

    /**
     * Restore the prompts output.
     */
    protected function restorePrompts(): void
    {
        Prompt::setOutput($this->output);
    }
}
