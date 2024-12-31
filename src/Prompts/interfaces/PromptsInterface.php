<?php

declare(strict_types=1);

namespace Maginium\Framework\Prompts\Interfaces;

use Closure;
use Laravel\Prompts\FormBuilder;
use Laravel\Prompts\Progress;
use Maginium\Framework\Support\Collection;

/**
 * Class Prompts.
 *
 * This class provides static methods to prompt the user for various types of input.
 * It offers flexibility in specifying labels, placeholders, default values,
 * validation rules, and transformation functions for user input.
 *
 * Example usage:
 *
 * $name = Prompts::text('Enter your name', 'Name', '', true);
 */
interface PromptsInterface
{
    /**
     * Allow the user to search for an option.
     *
     * @param  string  $label  The label for the search prompt.
     * @param  Closure  $options  The closure that returns available options based on user input.
     * @param  string  $placeholder  The placeholder text for the search input.
     * @param  int  $scroll  The number of options to scroll through in the search prompt.
     * @param  mixed  $validate  Optional validation rules to be applied to the user input.
     * @param  string  $hint  An optional hint providing additional context for the user.
     * @param  bool|string  $required  Indicates if the search prompt is required to have a valid input.
     * @param  Closure|null  $transform  Optional closure to transform the input value before it's returned.
     *
     * @return int|string The selected option, either as an integer or string, depending on the closure's return value.
     */
    public function search(
        string $label,
        Closure $options,
        string $placeholder = '',
        int $scroll = 5,
        $validate = null,
        string $hint = '',
        $required = true,
        ?Closure $transform = null,
    ): int|string;

    /**
     * Prompt the user for text input.
     *
     * @param  string  $label  The label for the text input prompt.
     * @param  string  $placeholder  The placeholder text for the input.
     * @param  string  $default  The default value pre-filled in the input.
     * @param  bool|string  $required  Indicates if the input is required.
     * @param  mixed  $validate  Optional validation rules to apply to the input.
     * @param  string  $hint  Optional hint that can be displayed alongside the input prompt.
     * @param  Closure|null  $transform  Optional transformation closure to modify the input value before it's returned.
     *
     * @return string The user-provided text input.
     */
    public function text(
        string $label,
        string $placeholder = '',
        string $default = '',
        bool|string $required = false,
        mixed $validate = null,
        string $hint = '',
        ?Closure $transform = null,
    ): string;

    /**
     * Prompt the user for multiline text input.
     *
     * @param  string  $label  The label for the textarea prompt.
     * @param  string  $placeholder  The placeholder text for the textarea.
     * @param  string  $default  The default value pre-filled in the textarea.
     * @param  bool|string  $required  Indicates if the input is required.
     * @param  mixed  $validate  Optional validation rules to apply to the input.
     * @param  string  $hint  Optional hint for the user displayed next to the input.
     * @param  int  $rows  The number of rows for the textarea input.
     * @param  Closure|null  $transform  Optional closure for transforming the input value before it's returned.
     *
     * @return string The user-provided multiline text input.
     */
    public function textarea(
        string $label,
        string $placeholder = '',
        string $default = '',
        bool|string $required = false,
        mixed $validate = null,
        string $hint = '',
        int $rows = 5,
        ?Closure $transform = null,
    ): string;

    /**
     * Prompt the user for password input, hiding the entered value.
     *
     * @param  string  $label  The label for the password prompt.
     * @param  string  $placeholder  The placeholder text for the password input.
     * @param  bool|string  $required  Indicates if the input is required.
     * @param  mixed  $validate  Optional validation rules to apply to the input.
     * @param  string  $hint  An optional hint that can be displayed next to the input.
     * @param  Closure|null  $transform  Optional closure to transform the input before returning it.
     *
     * @return string The user-provided password input.
     */
    public function password(
        string $label,
        string $placeholder = '',
        bool|string $required = false,
        mixed $validate = null,
        string $hint = '',
        ?Closure $transform = null,
    ): string;

    /**
     * Prompt the user to select an option.
     *
     * This method asks the user to select an option from a provided list,
     * with options for default selection, validation, scrolling, and hints.
     *
     * @param  string  $label  The label for the select prompt.
     * @param  array|Collection  $options  The available options for selection.
     * @param  int|string|null  $default  The default selected option.
     * @param  int  $scroll  The number of options to scroll through.
     * @param  mixed  $validate  Optional validation rules.
     * @param  string  $hint  An optional hint for the user.
     * @param  bool|string  $required  Indicates if the selection is required.
     * @param  Closure|null  $transform  Optional transformation for the input value.
     *
     * @return int|string The selected option.
     */
    public function select(
        string $label,
        array|Collection $options,
        int|string|null $default = null,
        int $scroll = 5,
        mixed $validate = null,
        string $hint = '',
        bool|string $required = true,
        ?Closure $transform = null,
    ): int|string;

    /**
     * Prompt the user to select multiple options.
     *
     * @param  string  $label  The label for the multiselect prompt.
     * @param  array|Collection  $options  The available options for selection.
     * @param  array|Collection  $default  The default selected options.
     * @param  int  $scroll  The number of options to scroll through.
     * @param  bool|string  $required  Indicates if the selection is required.
     * @param  mixed  $validate  Optional validation rules.
     * @param  string  $hint  An optional hint for the user.
     * @param  Closure|null  $transform  Optional transformation for the input value.
     *
     * @return array<int|string> The selected options.
     */
    public function multiselect(
        string $label,
        array|Collection $options,
        array|Collection $default = [],
        int $scroll = 5,
        bool|string $required = false,
        mixed $validate = null,
        string $hint = 'Use the space bar to select options.',
        ?Closure $transform = null,
    ): array;

    /**
     * Prompt the user to confirm an action.
     *
     * @param  string  $label  The label for the confirmation prompt.
     * @param  bool  $default  The default response (true for "yes", false for "no").
     * @param  string  $yes  The text for the "yes" option.
     * @param  string  $no  The text for the "no" option.
     * @param  bool|string  $required  Indicates if the confirmation is required.
     * @param  mixed  $validate  Optional validation rules.
     * @param  string  $hint  An optional hint for the user.
     * @param  Closure|null  $transform  Optional transformation for the confirmation input value.
     *
     * @return bool The confirmation response (true for "yes", false for "no").
     */
    public function confirm(
        string $label,
        bool $default = true,
        string $yes = 'Yes',
        string $no = 'No',
        bool|string $required = false,
        mixed $validate = null,
        string $hint = '',
        ?Closure $transform = null,
    ): bool;

    /**
     * Display an alert message using the note factory.
     *
     * @param  string  $message  The alert message to display.
     */
    public function alert(string $message): void;

    /**
     * Display an informational message using the note factory.
     *
     * @param  string  $message  The informational message to display.
     */
    public function info(string $message): void;

    /**
     * Display a success message using the note factory.
     *
     * @param  string  $message  The success message to display.
     */
    public function success(string $message): void;

    /**
     * Display an intro message using the note factory.
     *
     * @param  string  $message  The intro message to display.
     */
    public function intro(string $message): void;

    /**
     * Display a closing message to the user.
     *
     * @param  string  $message  The closing message content to display.
     */
    public function outro(string $message): void;

    /**
     * Display a table to the user.
     *
     * @param  array  $headers  The headers of the table.
     * @param  array|null  $rows  The rows of the table.
     */
    public function table(array $headers = [], ?array $rows = null): void;

    /**
     * Display a progress bar to the user.
     *
     * @param  string  $label  The label of the progress.
     * @param  iterable|int  $steps  The total number of steps.
     * @param  Closure|null  $callback  Optional callback for each step.
     * @param  string  $hint  Optional hint for the progress bar.
     *
     * @return Progress The created Progress object.
     */
    public function progress(
        string $label,
        $steps,
        ?Closure $callback = null,
        string $hint = '',
    ): Progress;

    /**
     * Create a new form builder instance.
     *
     * @return FormBuilder A new instance of the FormBuilder class.
     */
    public function form(): FormBuilder;

    /**
     * Show the spinner while executing the provided callback.
     *
     * This method creates a spinner prompt and executes the provided callback
     * while the spinner is running. The spinner will display the provided
     * message (if any) and will remain active until the callback finishes executing.
     *
     * @param  Closure  $callback  The callback to execute while showing the spinner.
     * @param  string  $message  Optional message to show next to the spinner.
     *
     * @return mixed The result of the callback execution.
     */
    public function spinner(Closure $callback, string $message = ''): mixed;
}
