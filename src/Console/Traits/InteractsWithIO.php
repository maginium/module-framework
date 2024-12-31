<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Traits;

use Closure;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Contracts\Support\Arrayable;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Traversable;

/**
 * Trait InteractsWithIO.
 *
 * Provides helper methods for interacting with command-line arguments and options in a console command.
 * It wraps the retrieval of arguments and options, providing an easy interface to access them.
 * The trait provides functionality to get specific argument/option values or all of them as a DataObject.
 */
trait InteractsWithIO
{
    /**
     * The console components factory.
     *
     *
     * @internal This property is not meant to be used or overwritten outside the framework.
     */
    protected Factory $components;

    /**
     * The input interface implementation.
     */
    protected ?InputInterface $input = null;

    /**
     * The output interface implementation.
     */
    protected ?OutputStyle $output = null;

    /**
     * The default verbosity of output commands.
     */
    protected int $verbosity = OutputInterface::VERBOSITY_NORMAL;

    /**
     * The mapping between human readable verbosity levels and Symfony's OutputInterface.
     */
    protected array $verbosityMap = [
        'v' => OutputInterface::VERBOSITY_VERBOSE,
        'vv' => OutputInterface::VERBOSITY_VERY_VERBOSE,
        'vvv' => OutputInterface::VERBOSITY_DEBUG,
        'quiet' => OutputInterface::VERBOSITY_QUIET,
        'normal' => OutputInterface::VERBOSITY_NORMAL,
    ];

    /**
     * The arguments the command accepts.
     */
    protected array $arguments = [];

    /**
     * Determine if the given argument is present.
     *
     * @param  string|int  $name
     */
    public function hasArgument($name): bool
    {
        return $this->getInput()->hasArgument($name);
    }

    /**
     * Retrieve the value of a specific command argument.
     * If no argument key is provided, returns all arguments as a DataObject.
     *
     * @param  string|null  $key  The argument key (optional)
     *
     * @return array|string|bool|DataObject|null Returns a DataObject containing the argument value(s)
     */
    public function argument($key = null): mixed
    {
        // Fetch the arguments from the command and wrap them in a DataObject
        $arguments = DataObject::make($this->arguments);

        // If no specific key is provided, return all arguments
        if ($key === null) {
            return $arguments;
        }

        // Otherwise, return the specific argument value by its key
        return $arguments->getData($key);
    }

    /**
     * Get all the arguments passed to the command.
     * This is essentially a shorthand for the `argument()` method.
     *
     * @return DataObject Returns all arguments as a DataObject
     */
    public function arguments(): array|bool|DataObject|string|null
    {
        // Return all arguments as a DataObject
        return $this->argument();
    }

    /**
     * Determine if the given option is present.
     *
     * @param  string  $name
     */
    public function hasOption($name): bool
    {
        return $this->getInput()->hasOption($name);
    }

    /**
     * Retrieve the value of a specific command option.
     * If no option key is provided, returns all options as a DataObject.
     *
     * @param  string|null  $key  The option key (optional)
     *
     * @return string|array|bool|DataObject|null Returns a DataObject containing the option value(s)
     */
    public function option($key = null): string|array|bool|DataObject|null
    {
        // Fetch the options from the command and wrap them in a DataObject
        $options = DataObject::make($this->getInput()->getOptions());

        // If no specific key is provided, return all options
        if ($key === null) {
            return $options;
        }

        // Otherwise, return the specific option value by its key
        return $options->getData($key);
    }

    /**
     * Get all the options passed to the command.
     * This is essentially a shorthand for the `option()` method.
     *
     * @return DataObject Returns all options as a DataObject
     */
    public function options(): array|bool|DataObject|string|null
    {
        // Return all options as a DataObject
        return $this->option();
    }

    /**
     * Confirm a question with the user.
     *
     * This method prompts the user with a yes/no question and returns a boolean value based on the user's
     * confirmation. The default value determines what the response should be if the user simply presses Enter
     * without providing an answer.
     *
     * @param  string  $question  The question to ask the user.
     * @param  bool  $default  The default answer if the user presses Enter (true for "yes", false for "no").
     *
     * @return bool Returns true if the user confirms (answers "yes"), false if the user declines (answers "no").
     */
    public function confirm($question, $default = false): bool
    {
        // Call the 'confirm' method on the output object to display the question and return the result.
        return $this->getOutput()->confirm($question, $default);
    }

    /**
     * Prompt the user for input.
     *
     * This method prompts the user with a question and waits for their response. If a default value is provided,
     * it will be used if the user presses Enter without entering anything.
     *
     * @param  string  $question  The question to ask the user.
     * @param  string|null  $default  The default value to return if the user doesn't provide any input.
     *
     * @return mixed Returns the user's input or the default value if no input is provided.
     */
    public function ask($question, $default = null): mixed
    {
        // Call the 'ask' method on the output object to prompt the user and return their input.
        return $this->getOutput()->ask($question, $default);
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * This method extends the 'ask' method by providing auto-completion options for the user's input.
     * The choices can either be an array of possible completions or a callable that dynamically provides
     * completions based on the user's current input. It helps guide the user to make a valid selection from
     * a predefined set of options.
     *
     * @param  string  $question  The question to ask the user.
     * @param  array|callable  $choices  An array of valid choices or a callable that generates choices dynamically.
     * @param  string|null  $default  The default value to return if the user doesn't provide any input.
     *
     * @return mixed Returns the user's input, with auto-completion if applicable, or the default value if no input is provided.
     */
    public function anticipate($question, $choices, $default = null): mixed
    {
        // Call the 'askWithCompletion' method to prompt the user with auto-completion options and return their input.
        return $this->askWithCompletion($question, $choices, $default);
    }

    /**
     * Prompt the user for input with auto-completion.
     *
     * This method allows prompting the user with a question and provides them with an
     * auto-completion feature. It accepts either a list of choices or a callable to
     * dynamically generate the options for auto-completion.
     *
     * @param  string  $question  The question to ask the user.
     * @param  array|callable  $choices  An array of choices or a callable for dynamic choices.
     * @param  string|null  $default  The default value if no answer is given by the user.
     *
     * @return mixed The user's input, which will be auto-completed based on the choices.
     */
    public function askWithCompletion($question, $choices, $default = null): mixed
    {
        // Create a new Question instance with the provided question and default value
        $question = new Question($question, $default);

        // Set the auto-completion options: either a list of values or a callable function
        is_callable($choices)
            ? $question->setAutocompleterCallback($choices)  // Use the callable if provided
            : $question->setAutocompleterValues($choices);  // Use the array of values otherwise

        // Prompt the user and return the response
        return $this->getOutput()->askQuestion($question);
    }

    /**
     * Prompt the user for input but hide the answer from the console.
     *
     * This method prompts the user for input (e.g., a password) and hides the input
     * from the console for security purposes. Optionally, a fallback value can be provided
     * if the user does not enter anything.
     *
     * @param  string  $question  The question to ask the user.
     * @param  bool  $fallback  Whether to provide a fallback value if the user doesn't input anything.
     *
     * @return mixed The user's hidden input.
     */
    public function secret($question, $fallback = true): mixed
    {
        // Create a new Question instance with the provided question
        $question = new Question($question);

        // Hide the input and set the fallback behavior
        $question->setHidden(true)->setHiddenFallback($fallback);

        // Prompt the user and return the response
        return $this->getOutput()->askQuestion($question);
    }

    /**
     * Give the user a single choice from an array of answers.
     *
     * This method prompts the user with a question and a list of choices. The user
     * can select one option from the choices. It allows setting a default answer,
     * the maximum number of attempts, and whether multiple selections are allowed.
     *
     * @param  string  $question  The question to ask the user.
     * @param  array  $choices  An array of possible answers for the user to choose from.
     * @param  string|int|null  $default  The default answer, if the user chooses to skip.
     * @param  mixed|null  $attempts  The maximum number of attempts allowed for the user.
     * @param  bool  $multiple  Whether multiple answers are allowed.
     *
     * @return string|array The selected answer(s) from the choices.
     */
    public function choice($question, array $choices, $default = null, $attempts = null, $multiple = false): mixed
    {
        // Create a new ChoiceQuestion instance with the provided question and choices
        $question = new ChoiceQuestion($question, $choices, $default);

        // Set the maximum number of attempts and whether multiple selections are allowed
        $question->setMaxAttempts($attempts)->setMultiselect($multiple);

        // Prompt the user and return the selected answer(s)
        return $this->getOutput()->askQuestion($question);
    }

    /**
     * Format input to display it as a table in the console.
     *
     * This method formats the provided data (headers and rows) into a textual table
     * and outputs it to the console. The table style can be customized, and column
     * styles can also be applied to specific columns.
     *
     * @param  array  $headers  The headers for the table.
     * @param  Arrayable|array  $rows  The rows of data to display in the table.
     * @param  TableStyle|string  $tableStyle  The style of the table to use (e.g., 'default', 'compact').
     * @param  array  $columnStyles  An array of column styles (e.g., alignments, widths).
     *
     * @return void This method does not return anything; it simply renders the table.
     */
    public function table($headers, $rows, $tableStyle = 'default', array $columnStyles = []): void
    {
        // Create a new Table instance for rendering
        $table = new Table($this->getOutput());

        // If the rows are an Arrayable instance, convert them to a plain array
        if ($rows instanceof Arrayable) {
            $rows = $rows->toArray();
        }

        // Set the table headers, rows, and style
        $table->setHeaders((array)$headers)->setRows($rows)->setStyle($tableStyle);

        // Apply column styles (e.g., alignment) if provided
        foreach ($columnStyles as $columnIndex => $columnStyle) {
            $table->setColumnStyle($columnIndex, $columnStyle);
        }

        // Render the table to the console
        $table->render();
    }

    /**
     * Execute a given callback while advancing a progress bar.
     *
     * This method creates a progress bar and advances it as the callback is executed.
     * It can be used to visually indicate the progress of a task.
     *
     * @param  iterable|int  $totalSteps  The total number of steps or an iterable representing the steps.
     * @param  Closure  $callback  The callback function that will be executed during the progress.
     *
     * @return array|int|Traversable|null The result of the callback execution or null if no result is needed.
     */
    public function withProgressBar($totalSteps, Closure $callback): array|int|Traversable|null
    {
        // Create a progress bar instance with the specified number of steps
        $bar = $this->getOutput()->createProgressBar(
            is_iterable($totalSteps) ? count($totalSteps) : $totalSteps,
        );

        // Start the progress bar
        $bar->start();

        // If totalSteps is iterable, iterate over the steps and advance the bar
        if (is_iterable($totalSteps)) {
            foreach ($totalSteps as $value) {
                $callback($value, $bar);  // Execute the callback for each step
                $bar->advance(); // Advance the progress bar
            }

            // Return the iterable when totalSteps is iterable
            return $totalSteps;
        }
        // If totalSteps is an integer, execute the callback once
        $callback($bar);

        // Finish the progress bar
        $bar->finish();

        // No value returned if the totalSteps is an integer
        return null;
    }

    /**
     * Write a string to the console as an informational message.
     *
     * This method outputs the given string as an informational message, using the
     * 'info' style for formatting. The verbosity level can also be adjusted.
     *
     * @param  string  $string  The message to display.
     * @param  int|string|null  $verbosity  The verbosity level of the message (optional).
     *
     * @return void This method does not return anything; it simply displays the message.
     */
    public function info($string, $verbosity = null): void
    {
        // Output the string with 'info' style and the specified verbosity
        $this->line($string, 'info', $verbosity);
    }

    /**
     * Write a string to the console with an optional style and verbosity.
     *
     * This method writes the given string to the console with optional styling
     * (e.g., color or formatting) and verbosity level.
     *
     * @param  string  $string  The message to display.
     * @param  string|null  $style  The style to apply (e.g., 'info', 'error').
     * @param  int|string|null  $verbosity  The verbosity level of the message (optional).
     *
     * @return void This method does not return anything; it simply displays the message.
     */
    public function line($string, $style = null, $verbosity = null): void
    {
        // Apply the style to the string if a style is provided
        $styled = $style ? "<{$style}>{$string}</{$style}>" : $string;

        // Output the styled string to the console with the specified verbosity
        $this->getOutput()->writeln($styled, $this->parseVerbosity($verbosity));
    }

    /**
     * Write a string as comment output.
     *
     * This method outputs a string as a comment in the console. The output is styled using the 'comment' style,
     * typically used for informational messages or additional notes. The verbosity level controls how much
     * information is shown based on the console's settings.
     *
     * @param  string  $string  The string to output as a comment.
     * @param  int|string|null  $verbosity  The verbosity level, which can adjust the level of output detail.
     */
    public function comment($string, $verbosity = null): void
    {
        // Call the 'line' method with the 'comment' style to output the string as a comment.
        $this->line($string, 'comment', $verbosity);
    }

    /**
     * Write a string as question output.
     *
     * This method outputs a string as a question in the console. The output is styled using the 'question' style,
     * which typically formats the string as a prompt for user input. The verbosity level controls the level of
     * detail based on the console's configuration.
     *
     * @param  string  $string  The string to output as a question.
     * @param  int|string|null  $verbosity  The verbosity level, controlling how much output is displayed.
     */
    public function question($string, $verbosity = null): void
    {
        // Call the 'line' method with the 'question' style to output the string as a question.
        $this->line($string, 'question', $verbosity);
    }

    /**
     * Write a string as error output.
     *
     * This method outputs a string as an error message in the console. The output is styled using the 'error' style,
     * typically used for critical error messages that need attention. The verbosity level adjusts the amount of
     * error details shown, depending on the console's settings.
     *
     * @param  string  $string  The string to output as an error message.
     * @param  int|string|null  $verbosity  The verbosity level, which may influence the amount of error detail.
     */
    public function error($string, $verbosity = null): void
    {
        // Call the 'line' method with the 'error' style to output the string as an error.
        $this->line($string, 'error', $verbosity);
    }

    /**
     * Write a string as warning output.
     *
     * This method writes a string message in a warning style, typically yellow-colored, to the console.
     * If the warning style hasn't been defined before, it sets it to yellow.
     *
     * @param  string  $string  The message to be written as a warning.
     * @param  int|string|null  $verbosity  The verbosity level for the output.
     *                                      Can be used to control the level of detail in the output.
     */
    public function warn($string, $verbosity = null): void
    {
        // Check if the 'warning' style has been defined, if not, define it as yellow.
        if (! $this->getOutput()->getFormatter()->hasStyle('warning')) {
            $style = new OutputFormatterStyle('yellow');
            $this->getOutput()->getFormatter()->setStyle('warning', $style);
        }

        // Output the warning message with the specified verbosity.
        $this->line($string, 'warning', $verbosity);
    }

    /**
     * Write a string in an alert box.
     *
     * This method displays the given message within an alert-style box in the console,
     * which is surrounded by asterisks for emphasis. It calculates the box width
     * based on the message length and adds padding around the text for visual clarity.
     *
     * @param  string  $string  The message to be written inside the alert box.
     * @param  int|string|null  $verbosity  The verbosity level for the output.
     */
    public function alert($string, $verbosity = null): void
    {
        // Calculate the length of the string excluding any HTML tags, adding padding for the alert box.
        $length = Str::length(strip_tags($string)) + 12;

        // Print the top and bottom of the alert box, and the message inside it.
        $this->comment(str_repeat('*', $length), $verbosity);
        $this->comment('*     ' . $string . '     *', $verbosity);
        $this->comment(str_repeat('*', $length), $verbosity);

        // Print a blank line after the alert for visual separation.
        $this->comment('', $verbosity);
    }

    /**
     * Write a blank line.
     *
     * This method writes one or more blank lines to the console to help with formatting
     * and separating outputs visually.
     *
     * @param  int  $count  The number of blank lines to print. Defaults to 1.
     *
     * @return $this The current instance for method chaining.
     */
    public function newLine($count = 1): static
    {
        // Output the specified number of blank lines.
        $this->getOutput()->newLine($count);

        // Return the instance to allow method chaining.
        return $this;
    }

    /**
     * Get the input implementation.
     *
     * This method returns the current input instance, which is an implementation of InputInterface.
     * It can be used to retrieve and manipulate the current input settings for the console command.
     *
     * @return InputInterface The current input interface implementation.
     */
    public function getInput(): InputInterface
    {
        // Return the current input interface.
        return $this->input;
    }

    /**
     * Set the input interface implementation.
     *
     * This method assigns an instance of InputInterface to the input property,
     * allowing access to the command-line input provided to the application.
     * This is typically used for reading arguments and options passed to the console command.
     *
     * @param  InputInterface  $input  The input interface implementation.
     */
    public function setInput(InputInterface $input): void
    {
        // Assign the input interface instance to the $input property.
        $this->input = $input;
    }

    /**
     * Set the output interface implementation.
     *
     * This method assigns an instance of OutputStyle to the output property,
     * which is used for writing styled output to the console.
     * It allows the command to format and control the style of the output (e.g., color, verbosity).
     *
     * @param  OutputStyle  $output  The output interface implementation.
     */
    public function setOutput(OutputStyle $output): void
    {
        // Assign the output interface instance to the $output property.
        $this->output = $output;
    }

    /**
     * Get the output implementation.
     *
     * This method returns the current output instance, which is an implementation of OutputStyle.
     * It can be used to retrieve and manipulate the current output settings for the console command.
     *
     * @return OutputStyle The current output interface implementation.
     */
    public function getOutput(): OutputStyle
    {
        // Return the current output interface.
        return $this->output;
    }

    /**
     * Get the output component factory implementation.
     *
     * This method returns the component factory used to generate output-related components.
     * It provides access to the underlying components used for handling the output in a more modular way.
     *
     * @return Factory The output component factory instance.
     */
    public function outputComponents(): Factory
    {
        // Return the components factory used for output-related tasks.
        return $this->components;
    }

    /**
     * Set the verbosity level.
     *
     * This method sets the verbosity level for the output. It uses the provided level (string, integer, or null)
     * and resolves it into a valid verbosity level by calling `parseVerbosity()`.
     * The verbosity level controls the amount of information output to the console.
     *
     * @param  string|int  $level  The verbosity level. Can be a string (e.g., 'verbose', 'quiet') or an integer.
     */
    protected function setVerbosity($level): void
    {
        // Set the verbosity level using the parseVerbosity method to ensure it's correctly resolved.
        $this->verbosity = $this->parseVerbosity($level);
    }

    /**
     * Get the verbosity level in terms of Symfony's OutputInterface level.
     *
     * This method converts the given verbosity level (either a string, integer, or null)
     * into an integer that corresponds to the verbosity level used by Symfony's OutputInterface.
     * If the level is not explicitly provided, it defaults to the current verbosity level.
     *
     * @param  string|int|null  $level  The verbosity level to be parsed.
     *                                  It can be a string (e.g., 'verbose', 'quiet'), an integer,
     *                                  or null (which defaults to the current verbosity level).
     *
     * @return int The corresponding integer verbosity level.
     */
    protected function parseVerbosity($level = null): mixed
    {
        // If the level is found in the verbosityMap, use the mapped value.
        if (isset($this->verbosityMap[$level])) {
            $level = $this->verbosityMap[$level];
        }
        // If the level is not an integer, fall back to the current verbosity level.
        elseif (! is_int($level)) {
            $level = $this->verbosity;
        }

        // Return the resolved verbosity level as an integer.
        return $level;
    }
}
