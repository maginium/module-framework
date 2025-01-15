<?php

declare(strict_types=1);

namespace Maginium\Framework\Prompts;

use Closure;
use Laravel\Prompts\ConfirmPromptFactory;
use Laravel\Prompts\FormBuilder;
use Laravel\Prompts\FormBuilderFactory;
use Laravel\Prompts\MultiSearchPromptFactory;
use Laravel\Prompts\MultiSelectPromptFactory;
use Laravel\Prompts\NoteFactory;
use Laravel\Prompts\PasswordPromptFactory;
use Laravel\Prompts\PausePromptFactory;
use Laravel\Prompts\Progress;
use Laravel\Prompts\ProgressFactory;
use Laravel\Prompts\SearchPromptFactory;
use Laravel\Prompts\SelectPromptFactory;
use Laravel\Prompts\SpinnerFactory;
use Laravel\Prompts\SuggestPromptFactory;
use Laravel\Prompts\TableFactory;
use Laravel\Prompts\TextareaPromptFactory;
use Laravel\Prompts\TextPromptFactory;
use Maginium\Framework\Prompts\Interfaces\PromptsInterface;
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
class PromptsManager implements PromptsInterface
{
    /**
     * Factory for creating confirm prompts.
     *
     * @var ConfirmPromptFactory
     */
    protected ConfirmPromptFactory $confirmPromptFactory;

    /**
     * Factory for creating form builders.
     *
     * @var FormBuilderFactory
     */
    protected FormBuilderFactory $formBuilderFactory;

    /**
     * Factory for creating multi-search prompts.
     *
     * @var MultiSearchPromptFactory
     */
    protected MultiSearchPromptFactory $multiSearchPromptFactory;

    /**
     * Factory for creating multi-select prompts.
     *
     * @var MultiSelectPromptFactory
     */
    protected MultiSelectPromptFactory $multiSelectPromptFactory;

    /**
     * Factory for creating note components.
     *
     * @var NoteFactory
     */
    protected NoteFactory $noteFactory;

    /**
     * Factory for creating password prompts.
     *
     * @var PasswordPromptFactory
     */
    protected PasswordPromptFactory $passwordPromptFactory;

    /**
     * Factory for creating pause prompts.
     *
     * @var PausePromptFactory
     */
    protected PausePromptFactory $pausePromptFactory;

    /**
     * Factory for creating progress indicators.
     *
     * @var ProgressFactory
     */
    protected ProgressFactory $progressFactory;

    /**
     * Factory for creating search prompts.
     *
     * @var SearchPromptFactory
     */
    protected SearchPromptFactory $searchPromptFactory;

    /**
     * Factory for creating select prompts.
     *
     * @var SelectPromptFactory
     */
    protected SelectPromptFactory $selectPromptFactory;

    /**
     * Factory for creating spinner components.
     *
     * @var SpinnerFactory
     */
    protected SpinnerFactory $spinnerFactory;

    /**
     * Factory for creating suggest prompts.
     *
     * @var SuggestPromptFactory
     */
    protected SuggestPromptFactory $suggestPromptFactory;

    /**
     * Factory for creating table components.
     *
     * @var TableFactory
     */
    protected TableFactory $tableFactory;

    /**
     * Factory for creating textarea prompts.
     *
     * @var TextareaPromptFactory
     */
    protected TextareaPromptFactory $textareaPromptFactory;

    /**
     * Factory for creating text prompts.
     *
     * @var TextPromptFactory
     */
    protected TextPromptFactory $textPromptFactory;

    /**
     * Prompts constructor.
     */
    public function __construct(
        NoteFactory $noteFactory,
        TableFactory $tableFactory,
        SpinnerFactory $spinnerFactory,
        ProgressFactory $progressFactory,
        TextPromptFactory $textPromptFactory,
        PausePromptFactory $pausePromptFactory,
        FormBuilderFactory $formBuilderFactory,
        SearchPromptFactory $searchPromptFactory,
        SelectPromptFactory $selectPromptFactory,
        ConfirmPromptFactory $confirmPromptFactory,
        SuggestPromptFactory $suggestPromptFactory,
        TextareaPromptFactory $textareaPromptFactory,
        PasswordPromptFactory $passwordPromptFactory,
        MultiSearchPromptFactory $multiSearchPromptFactory,
        MultiSelectPromptFactory $multiSelectPromptFactory,
    ) {
        $this->noteFactory = $noteFactory;
        $this->tableFactory = $tableFactory;
        $this->spinnerFactory = $spinnerFactory;
        $this->progressFactory = $progressFactory;
        $this->textPromptFactory = $textPromptFactory;
        $this->formBuilderFactory = $formBuilderFactory;
        $this->pausePromptFactory = $pausePromptFactory;
        $this->searchPromptFactory = $searchPromptFactory;
        $this->selectPromptFactory = $selectPromptFactory;
        $this->suggestPromptFactory = $suggestPromptFactory;
        $this->confirmPromptFactory = $confirmPromptFactory;
        $this->passwordPromptFactory = $passwordPromptFactory;
        $this->textareaPromptFactory = $textareaPromptFactory;
        $this->multiSearchPromptFactory = $multiSearchPromptFactory;
        $this->multiSelectPromptFactory = $multiSelectPromptFactory;
    }

    /**
     * Allow the user to search for an option.
     *
     * This method prompts the user to select an option from a dynamic list generated by a closure
     * that returns available options based on user input. It also provides additional parameters
     * for customizing the user prompt.
     *
     * @param  string  $label  The label for the search prompt.
     * @param  Closure  $options  The closure that returns available options based on the user's input.
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
    ): int|string {
        // Create a search prompt using the searchPromptFactory and pass the parameters for customization
        $searchPrompt = $this->searchPromptFactory->create([
            'label' => $label, // The label displayed for the search prompt
            'options' => $options, // Closure to fetch available options
            'placeholder' => $placeholder, // Placeholder for the search input field
            'scroll' => $scroll, // Number of options to scroll at a time
            'validate' => $validate, // Optional validation rules for the input
            'hint' => $hint, // Optional hint to assist the user
            'required' => $required, // Whether the search input is required
            'transform' => $transform, // Optional function to transform the input value
        ]);

        // Return the result from the search prompt, which is the selected option
        return $searchPrompt->prompt();
    }

    /**
     * Prompt the user for text input.
     *
     * This method displays a prompt to the user asking for a single line of text input,
     * with optional parameters for validation, default values, and input transformation.
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
    ): string {
        // Create a text input prompt using the textPromptFactory and pass the parameters for customization
        $textPrompt = $this->textPromptFactory->create([
            'label' => $label, // The label for the text input prompt
            'placeholder' => $placeholder, // Placeholder for the text input field
            'default' => $default, // Default value for the input
            'required' => $required, // Whether the input is required
            'validate' => $validate, // Optional validation rules
            'hint' => $hint, // Optional hint for the user
            'transform' => $transform, // Optional function to transform the value before returning it
        ]);

        // Return the result from the text prompt, which is the user input
        return $textPrompt->prompt();
    }

    /**
     * Prompt the user for multiline text input.
     *
     * This method prompts the user for a multiline input (i.e., a textarea),
     * with several options for customization, including the number of rows in the textarea.
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
    ): string {
        // Create a textarea input prompt using the textareaPromptFactory and pass the parameters for customization
        $textareaPrompt = $this->textareaPromptFactory->create([
            'label' => $label, // The label for the textarea prompt
            'placeholder' => $placeholder, // Placeholder for the textarea input
            'default' => $default, // Default value for the textarea
            'required' => $required, // Whether the input is required
            'validate' => $validate, // Optional validation rules
            'hint' => $hint, // Optional hint for the user
            'rows' => $rows, // Number of rows for the textarea input
            'transform' => $transform, // Optional function to transform the value before returning it
        ]);

        // Return the result from the textarea prompt, which is the user input
        return $textareaPrompt->prompt();
    }

    /**
     * Prompt the user for password input, hiding the entered value.
     *
     * This method asks the user to input a password with the text input hidden,
     * while allowing customization through parameters like validation and transformation.
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
    ): string {
        // Create a password input prompt using the passwordPromptFactory, passing in all necessary parameters
        $passwordPrompt = $this->passwordPromptFactory->create([
            'label' => $label, // The label for the password prompt
            'placeholder' => $placeholder, // Placeholder for the password input
            'required' => $required, // Whether the input is required
            'validate' => $validate, // Optional validation rules for the input
            'hint' => $hint, // Optional hint for the user
            'transform' => $transform, // Optional transformation for the input value
        ]);

        // Return the result from the password prompt, which is the user input
        return $passwordPrompt->prompt();
    }

    /**
     * Prompt the user to select an option.
     *
     * This method asks the user to select an option from a provided list,
     * with options for default selection, validation, scrolling, and hints.
     *
     * @param  string  $label  The label for the select prompt.
     * @param  array|Collection  $options  The available options for selection.
     * @param  int|string|null  $default  The default selected option.
     * @param  int  $scrollThe  number of options to scroll through.
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
    ): int|string {
        // Create a select prompt using the SelectPromptFactory with the provided parameters
        $selectPrompt = $this->selectPromptFactory->create([
            'label' => $label, // The label displayed for the select prompt
            'options' => $options, // The list of available options for selection
            'default' => $default, // The default selected option (if any)
            'scroll' => $scroll, // Number of options to scroll at a time
            'validate' => $validate, // Optional validation rules for the selection
            'hint' => $hint, // Optional hint to assist the user
            'required' => $required, // Whether the selection is mandatory
            'transform' => $transform, // Optional transformation function for the selected value
        ]);

        // Return the result from the select prompt, which is the selected option
        return $selectPrompt->prompt();
    }

    /**
     * Prompt the user to select multiple options.
     *
     * This method asks the user to select multiple options from a provided list,
     * with options for default selection, validation, scrolling, and hints.
     *
     * @param  string  $label  The label for the multiselect prompt.
     * @param  array|Collection  $options  The available options for selection.
     * @param  array|Collection  $default  The default selected options.
     * @param  int  $scrollThe  number of options to scroll through.
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
    ): array {
        // Create a multiselect prompt using the MultiSelectPromptFactory with the provided parameters
        $multiSelectPrompt = $this->multiSelectPromptFactory->create([
            'label' => $label, // The label displayed for the multiselect prompt
            'options' => $options, // The list of available options for selection
            'default' => $default, // The default selected options (if any)
            'scroll' => $scroll, // Number of options to scroll at a time
            'required' => $required, // Whether the selection is mandatory
            'validate' => $validate, // Optional validation rules for the selection
            'hint' => $hint, // Optional hint to assist the user
            'transform' => $transform, // Optional transformation function for the selected values
        ]);

        // Return the result from the multiselect prompt, which is the selected options
        return $multiSelectPrompt->prompt();
    }

    /**
     * Prompt the user to confirm an action.
     *
     * This method displays a confirmation prompt to the user, allowing them to
     * choose between a 'yes' or 'no' response. It supports customizing the
     * label, default choice, option labels, and validation rules. Optionally,
     * a transformation can be applied to the confirmation input.
     *
     * @param  string  $label  The label for the confirmation prompt.
     * @param  bool  $default  The default response (true for "yes", false for "no").
     * @param  string  $yesThe  text for the "yes" option.
     * @param  string  $no  The text for the "no" option.
     * @param  bool|string  $required  Indicates if the confirmation is required.
     * @param  mixed  $validate  Optional validation rules.
     * @param  string  $hint  An optional hint for the user.
     * @param  Closure|null  $transform  Optional transformation for the input value.
     *
     * @return bool The user's confirmation (true for "yes", false for "no").
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
    ): bool {
        // Create a confirmation prompt using the ConfirmPromptFactory with the provided parameters
        $confirmPrompt = $this->confirmPromptFactory->create([
            'label' => $label, // The label displayed for the confirmation prompt
            'default' => $default, // The default confirmation (yes/no)
            'yes' => $yes, // The text displayed for the "yes" option
            'no' => $no, // The text displayed for the "no" option
            'required' => $required, // Whether the confirmation is mandatory
            'validate' => $validate, // Optional validation rules for the confirmation
            'hint' => $hint, // Optional hint to assist the user
            'transform' => $transform, // Optional transformation function for the confirmation result
        ]);

        // Return the result from the confirmation prompt (true for "yes", false for "no")
        return $confirmPrompt->prompt();
    }

    /**
     * Pause and prompt the user to continue after a pause.
     *
     * This method displays a message and waits for the user to press enter
     * to continue. It can be used to pause the flow of execution, allowing
     * the user to read important information before proceeding.
     *
     * @param  string  $message  The message to display during the pause.
     *
     * @return bool True if the user chooses to continue (presses enter), false otherwise.
     */
    public function pause(string $message = 'Press enter to continue...'): bool
    {
        // Create a pause prompt using the PausePromptFactory, passing in the message to display
        $pausePrompt = $this->pausePromptFactory->create(['message' => $message]);

        // Return the result from the pause prompt (true if user presses enter to continue)
        return $pausePrompt->prompt();
    }

    /**
     * Prompt the user for text input with auto-completion options.
     *
     * This method displays a text input prompt to the user, offering auto-completion
     * options as the user types. It can be used to suggest options based on input
     * and is useful for prompting users to select from a predefined list of options.
     *
     * @param  string  $label  The label for the suggest prompt.
     * @param  array|Collection|Closure  $options  The options for auto-completion.
     * @param  string  $placeholder  The placeholder text for the input.
     * @param  string  $default  The default value for the input.
     * @param  int  $scrollThe  number of options to scroll through.
     * @param  bool|string  $required  Indicates if the input is required.
     * @param  mixed  $validate  Optional validation rules.
     * @param  string  $hint  An optional hint for the user.
     * @param  Closure|null  $transform  Optional transformation for the input value.
     *
     * @return string The user's selected input.
     */
    public function suggest(
        string $label,
        array|Collection|Closure $options,
        string $placeholder = '',
        string $default = '',
        int $scroll = 5,
        bool|string $required = false,
        mixed $validate = null,
        string $hint = '',
        ?Closure $transform = null,
    ): string {
        // Create the suggest prompt using the injected factory and pass the parameters for customization
        $suggestPrompt = $this->suggestPromptFactory->create([
            'label' => $label,
            'options' => $options,
            'placeholder' => $placeholder,
            'default' => $default,
            'scroll' => $scroll,
            'required' => $required,
            'validate' => $validate,
            'hint' => $hint,
            'transform' => $transform,
        ]);

        // Return the result of the suggest prompt, which is the selected input
        return $suggestPrompt->prompt();
    }

    /**
     * Prompt the user to search for multiple options dynamically.
     *
     * This method allows the user to search and select multiple options from a
     * list of dynamically loaded items. The list of available options is determined
     * by the closure provided, which can be based on user input. It is ideal for
     * scenarios where the user needs to choose multiple options from a large dataset.
     *
     * @param  string  $label  The label for the multisearch prompt.
     * @param  Closure  $options  A closure that returns an array of available options based on user input.
     * @param  string  $placeholder  The placeholder text for the multisearch input.
     * @param  int  $scrollThe  number of options to scroll through.
     * @param  bool|string  $required  Indicates if the selection is required.
     * @param  mixed  $validate  Optional validation rules.
     * @param  string  $hint  An optional hint for the user.
     * @param  Closure|null  $transform  Optional transformation for the input value.
     *
     * @return array<int|string> The selected options as an array of strings or integers.
     */
    public function multisearch(
        string $label,
        Closure $options,
        string $placeholder = '',
        int $scroll = 5,
        bool|string $required = false,
        mixed $validate = null,
        string $hint = 'Use the space bar to select options.',
        ?Closure $transform = null,
    ): array {
        // Create the multisearch prompt using the injected factory and pass the parameters for customization
        $multiSearchPrompt = $this->multiSearchPromptFactory->create([
            'label' => $label,
            'options' => $options,
            'placeholder' => $placeholder,
            'scroll' => $scroll,
            'required' => $required,
            'validate' => $validate,
            'hint' => $hint,
            'transform' => $transform,
        ]);

        // Return the result of the multisearch prompt, which is an array of selected options
        return $multiSearchPrompt->prompt();
    }

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
    public function spinner(Closure $callback, string $message = ''): mixed
    {
        // Create a spinner instance using the factory and pass the optional message
        $spinnerPrompt = $this->spinnerFactory->create(['message' => $message]);

        // Execute the callback while the spinner is running, and return the result
        return $spinnerPrompt->spin($callback);
    }

    /**
     * Display a note message.
     *
     * This method creates a note using the provided message and optional type
     * (such as success, error, or custom). It then displays the note to the user.
     *
     * @param  string  $message  The message content to display.
     * @param  string|null  $type  The type of note (e.g., success, error).
     */
    public function note(string $message, ?string $type = null): void
    {
        // Create a note instance using the factory and display it with the provided type
        $notePrompt = $this->noteFactory->create(['message' => $message, 'type' => $type]);

        // Display the note message
        $notePrompt->display();
    }

    /**
     * Display an error message using the note factory.
     *
     * This method creates and displays a note of type 'error' with the provided
     * message, allowing the user to see an error alert.
     *
     * @param  string  $message  The error message to display.
     */
    public function error(string $message): void
    {
        // Create and display an error note using the note factory
        $errorPrompt = $this->noteFactory->create(['message' => $message, 'type' => 'error']);

        // Display the note message
        $errorPrompt->display();
    }

    /**
     * Display a warning message using the note factory.
     *
     * This method creates and displays a note of type 'warning' with the provided
     * message, alerting the user of a potential issue.
     *
     * @param  string  $message  The warning message to display.
     */
    public function warning(string $message): void
    {
        // Create and display a warning note using the note factory
        $warningPrompt = $this->noteFactory->create(['message' => $message, 'type' => 'warning']);

        // Display the note message
        $warningPrompt->display();
    }

    /**
     * Display an alert message using the note factory.
     *
     * This method creates and displays a note of type 'alert' with the provided
     * message, notifying the user of an important event.
     *
     * @param  string  $message  The alert message to display.
     */
    public function alert(string $message): void
    {
        // Create and display an alert note using the note factory
        $alertPrompt = $this->noteFactory->create(['message' => $message, 'type' => 'alert']);

        // Display the note message
        $alertPrompt->display();
    }

    /**
     * Display an informational message using the note factory.
     *
     * This method creates and displays a note of type 'info' with the provided
     * message, offering helpful information to the user.
     *
     * @param  string  $message  The informational message to display.
     */
    public function info(string $message): void
    {
        // Create and display an info note using the note factory
        $infoPrompt = $this->noteFactory->create(['message' => $message, 'type' => 'info']);

        // Display the note message
        $infoPrompt->display();
    }

    /**
     * Display a success message using the note factory.
     *
     * This method creates and displays a note of type 'success' with the provided
     * message, indicating that an action was successful.
     *
     * @param  string  $message  The success message to display.
     */
    public function success(string $message): void
    {
        // Create and display a success note using the note factory
        $successPrompt = $this->noteFactory->create(['message' => $message, 'type' => 'success']);

        // Display the note message
        $successPrompt->display();
    }

    /**
     * Display an intro message using the note factory.
     *
     * This method creates and displays a note of type 'intro' with the provided
     * message, introducing the user to a specific context or section.
     *
     * @param  string  $message  The intro message to display.
     */
    public function intro(string $message): void
    {
        // Create a note instance with the type 'intro' using the note factory and display the intro message
        $introNote = $this->noteFactory->create(['message' => $message, 'type' => 'intro']);

        // Display the note message
        $introNote->display();
    }

    /**
     * Display a closing message to the user.
     *
     * This method creates and displays a note of type 'outro' with the provided
     * message, signaling the end of a section or process.
     *
     * @param  string  $message  The closing message content to display.
     */
    public function outro(string $message): void
    {
        // Create a note instance with the type 'outro' using the note factory
        $outroNote = $this->noteFactory->create([
            'message' => $message,
            'type' => 'outro',
        ]);

        // Display the note message
        $outroNote->display();
    }

    /**
     * Display a table to the user.
     *
     * This method creates and displays a table with headers and optional rows.
     * If no rows are provided, an empty table will be displayed.
     *
     * @param  array  $headers  The headers of the table, either as an array or a collection.
     * @param  array|null  $rows  The rows of the table, either as an array or a collection. Can be null if no rows are provided.
     */
    public function table(array $headers = [], ?array $rows = null): void
    {
        // Create a table instance with provided headers and rows using the table factory
        $table = $this->tableFactory->create([
            'headers' => $headers,
            'rows' => $rows,
        ]);

        // Display the note message
        $table->display();
    }

    /**
     * Display a progress bar to the user.
     *
     * This method creates and displays a progress bar with the provided label
     * and steps. It can optionally accept a callback to execute for each step,
     * and an optional hint to provide additional context.
     *
     * @param  string  $label  The label of the progress, displayed beside the progress bar.
     * @param  iterable|int  $steps  The total number of steps to complete or an iterable providing the steps.
     * @param  Closure|null  $callback  Optional callback function to be executed for each step.
     * @param  string  $hint  Optional hint to provide additional context about the progress bar.
     *
     * @template TReturn
     *
     * @return Progress The created Progress object.
     */
    public function progress(string $label, $steps, ?Closure $callback = null, string $hint = ''): Progress
    {
        // Create a progress instance with the provided label, steps, and optional hint using the progress factory
        $progress = $this->progressFactory->create([
            'label' => $label,
            'steps' => $steps,
            'hint' => $hint,
        ]);

        // If a callback is provided, apply it, ensuring that the result is still a Progress instance
        if ($callback !== null) {
            $mappedProgress = $progress->map($callback);

            // Check if `map()` returns a Progress or wrap it if necessary
            if ($mappedProgress instanceof Progress) {
                return $mappedProgress;
            }

            // If map returns something else (e.g., an array), ensure it is wrapped in a Progress
            // Depending on what `map()` returns, this might need adjusting.
            return $progress; // Or create a new Progress instance based on mapped results
        }

        // Return the progress instance for further use
        return $progress;
    }

    /**
     * Create a new form builder instance.
     *
     * This method creates a new instance of the FormBuilder class using the
     * form builder factory. It can be used to create and configure forms.
     *
     * @return FormBuilder A new instance of the FormBuilder class.
     */
    public function form(): FormBuilder
    {
        // Create and return a new FormBuilder instance using the form builder factory
        return $this->formBuilderFactory->create();
    }
}
