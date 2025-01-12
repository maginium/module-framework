<?php

declare(strict_types=1);

namespace Illuminate\Console;

use Illuminate\Console\CommandMutex as CommandMutexInterface;
use Illuminate\Console\Concerns\CallsCommands;
use Illuminate\Console\Concerns\HasParameters;
use Illuminate\Console\Concerns\InteractsWithSignals;
use Illuminate\Console\Concerns\PromptsForMissingInput;
use Illuminate\Console\View\Components\FactoryFactory as ComponentsFactory;
use Illuminate\Support\Traits\Macroable;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Console\Exceptions\InvalidModeException;
use Maginium\Framework\Console\Interfaces\Isolatable;
use Maginium\Framework\Console\Traits\ConfiguresPrompts;
use Maginium\Framework\Console\Traits\InteractsWithIO;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use Override;
use ReflectionFunctionFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputOptionFactory;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Class AbstractCommand.
 *
 * This abstract class serves as a base for console commands, providing common functionality
 * and ensuring that child commands implement the execute method.
 */
abstract class Command extends SymfonyCommand
{
    // Manages command parameters.
    use CallsCommands;
    // Configures prompt settings and behavior.
    use ConfiguresPrompts;
    // Manages command parameters.
    use HasParameters;
    // Facilitates input and output interactions.
    use InteractsWithIO;
    // Manages system signals.
    use InteractsWithSignals;
    // Allows extending functionality via macros.
    use Macroable;
    // Prompts for any missing required input.
    use PromptsForMissingInput;

    /**
     * Indicates that the parameter or argument is required.
     */
    public const REQUIRED = 'required';

    /**
     * Indicates that the parameter or argument is optional.=.
     */
    public const OPTIONAL = 'optional';

    /**
     * The default path to the PHP binary.
     */
    public const DEFAULT_PHP_BINARY = 'php';

    /**
     * The default path to the Magento binary.
     */
    public const DEFAULT_MAGENTO_BINARY = 'bin/magento';

    /**
     * The name of the command.
     */
    protected $name;

    /**
     * A brief description of the command.
     */
    protected $description;

    /**
     * The signature of the console command.
     */
    protected $signature;

    /**
     * The console command help text.
     */
    protected $help = null;

    /**
     * The console command name aliases.
     */
    protected array $aliases;

    /**
     * Indicates whether only one instance of the command can run at any given time.
     */
    protected bool $isolated = false;

    /**
     * The default exit code for isolated commands.
     */
    protected int $isolatedExitCode = self::SUCCESS;

    /**
     * The application's state object.
     */
    protected State $state;

    /**
     * Factory to create OutputStyle instances for console output formatting.
     */
    protected OutputStyleFactory $outputStyleFactory;

    /**
     * Factory to create InputOption instances for command-line options.
     */
    protected InputOptionFactory $inputOptionFactory;

    /**
     *  Factory to create ReflectionFunction instances.
     */
    protected ReflectionFunctionFactory $reflectionFunctionFactory;

    /**
     *  Factory to create ComponentsFactory instances.
     */
    protected ComponentsFactory $componentsFactory;

    /**
     *  Instance for CommandMutexInterface.
     */
    protected CommandMutexInterface $commandMutex;

    /**
     * Constructor for the Command class.
     *
     * Initializes the command with dependencies required for managing the application's state,
     * handling output, input options, and PHP executable discovery.
     *
     * @param  State  $state  The application's state object.
     * @param  CommandMutexInterface $commandMutex Instance for CommandMutexInterface.
     * @param  OutputStyleFactory  $outputStyleFactory  Factory to create OutputStyle instances.
     * @param  InputOptionFactory  $inputOptionFactory  Factory to create InputOption instances.
     * @param  ComponentsFactory $componentsFactory Factory to create ComponentsFactory instances.
     * @param  ReflectionFunctionFactory  $reflectionFunctionFactory  Factory to create ReflectionFunction instances.
     */
    public function __construct(
        State $state,
        CommandMutexInterface $commandMutex,
        ComponentsFactory $componentsFactory,
        OutputStyleFactory $outputStyleFactory,
        InputOptionFactory $inputOptionFactory,
        ReflectionFunctionFactory $reflectionFunctionFactory,
    ) {
        // Assign dependencies to class properties for later use
        $this->state = $state;
        $this->commandMutex = $commandMutex;
        $this->componentsFactory = $componentsFactory;
        $this->outputStyleFactory = $outputStyleFactory;
        $this->inputOptionFactory = $inputOptionFactory;
        $this->reflectionFunctionFactory = $reflectionFunctionFactory;

        // Set the area code for the current context.
        // TODO: FIX HERE
        // $this->setAreaCode();

        // Check if a command signature is defined for the command
        // This helps to automatically set up command properties like name, description, and parameters.
        if (isset($this->signature)) {
            // Call a method to configure the command's name and description using a fluent definition approach.
            $this->configureUsingFluentDefinition();
        } else {
            parent::__construct($this->getName());
        }

        // Set the command's description. If a description hasn't been explicitly defined,
        // call the static method to get a default description.
        if (! isset($this->description)) {
            $this->setDescription((string)static::getDefaultDescription());
        } else {
            // If a description is provided, cast it to a string and set it as the command's description.
            $this->setDescription((string)$this->getDescription());
        }

        // Set help text for the command, which provides additional information for users.
        $this->setHelp((string)$this->help);

        // Determine if the command should be hidden from the console output.
        // This may be useful for internal commands that should not be visible to end users.
        $this->setHidden($this->isHidden());

        // If aliases are defined for the command, set them.
        // Aliases allow the command to be called by alternative names.
        if (isset($this->aliases)) {
            $this->setAliases((array)$this->aliases);
        }

        // If a signature is not defined, specify the parameters for the command.
        // This can help automate the command's parameter handling.
        if (! isset($this->signature)) {
            $this->specifyParameters();
        }

        // Check if the command implements the Isolatable interface.
        // This interface indicates that the command can run in an isolated mode.
        if ($this instanceof Isolatable) {
            // Call a method to configure the command for isolated execution.
            $this->configureIsolation();
        }
    }

    /**
     * Format the given command as a fully-qualified executable command.
     *
     * @param  string  $string
     *
     * @return string
     */
    public static function formatCommandString($string)
    {
        return Str::format('%s %s %s', static::phpBinary(), static::magentoBinary(), $string);
    }

    /**
     * Retrieves the default name of the command.
     *
     * This method first checks for the `AsCommand` attribute to determine
     * the command name. If the attribute is not present, it falls back to
     * checking the deprecated static `$defaultName` property.
     *
     * @return string|null The default name of the command, or null if not set.
     */
    #[Override]
    public static function getDefaultName(): ?string
    {
        $class = Reflection::getClassName(static::class);

        // Check if the `AsCommand` attribute is present and retrieve the command name.
        if ($attribute = Reflection::getAttributes($class, AsCommand::class)) {
            return $attribute[0]->newInstance()->name;
        }

        // Reflect on the `$defaultName` property of the current class.
        $r = Reflection::getProperty($class, 'defaultName');

        // Ensure the `$defaultName` property is defined in the current class
        // and has a non-null value.
        if ($class !== $r->class || static::$defaultName === null) {
            return null;
        }

        // Trigger a deprecation notice if relying on `$defaultName`.
        trigger_deprecation(
            'symfony/console',
            '6.1',
            'Relying on the static property "$defaultName" for setting a command name is deprecated. Add the "%s" attribute to the "%s" class instead.',
            AsCommand::class,
            static::class,
        );

        return static::$defaultName;
    }

    /**
     * Retrieves the default description of the command.
     *
     * This method first checks for the `AsCommand` attribute to determine
     * the command description. If the attribute is not present, it falls back
     * to checking the deprecated static `$defaultDescription` property.
     *
     * @return string|null The default description of the command, or null if not set.
     */
    #[Override]
    public static function getDefaultDescription(): ?string
    {
        $class = static::class;

        // Check if the `AsCommand` attribute is present and retrieve the command description.
        if ($attribute = Reflection::getAttributes($class, AsCommand::class)) {
            return $attribute[0]->newInstance()->description;
        }

        // Reflect on the `$defaultDescription` property of the current class.
        $r = Reflection::getProperty($class, 'defaultDescription');

        // Ensure the `$defaultDescription` property is defined in the current class
        // and has a non-null value.
        if ($class !== $r->class || static::$defaultDescription === null) {
            return null;
        }

        // Trigger a deprecation notice if relying on `$defaultDescription`.
        trigger_deprecation(
            'symfony/console',
            '6.1',
            'Relying on the static property "$defaultDescription" for setting a command description is deprecated. Add the "%s" attribute to the "%s" class instead.',
            AsCommand::class,
            static::class,
        );

        return static::$defaultDescription;
    }

    /**
     * Get the PHP binary path.
     *
     * This method utilizes the PhpExecutableFinder to locate the PHP binary
     * available in the system. If not found, it defaults to the constant `DEFAULT_PHP_BINARY`.
     *
     * @return string The path to the PHP binary.
     */
    protected static function phpBinary(): string
    {
        // Use PhpExecutableFinder to locate the PHP binary or default to the constant value.
        return Container::resolve(PhpExecutableFinder::class)->find(false) ?: self::DEFAULT_PHP_BINARY;
    }

    /**
     * Get the Magento binary path.
     *
     * This method checks if the `MAGENTO_BINARY` constant is defined. If not, it defaults to `DEFAULT_MAGENTO_BINARY`.
     *
     * @return string The path to the Magento binary.
     */
    protected static function magentoBinary(): string
    {
        // Return the defined MAGENTO_BINARY constant or default to the constant value.
        return defined('MAGENTO_BINARY') ? MAGENTO_BINARY : self::DEFAULT_MAGENTO_BINARY;
    }

    /**
     * Executes the console command.
     *
     * This method orchestrates the execution of a console command by:
     * 1. Running any necessary pre-execution logic via `beforeExecute`.
     * 2. Checking if command isolation is needed and handling it.
     * 3. Trying to invoke the command's executable method (`handle` or `__invoke`).
     * 4. Handling any errors that may occur during execution.
     * 5. Running any post-execution logic via `afterExecute`.
     *
     * If an error occurs or no executable method is found, an appropriate exit code is returned.
     *
     * @param  InputInterface  $input  The input interface containing the command-line arguments.
     * @param  OutputInterface  $output  The output interface for writing messages to the console.
     *
     * @return int The exit code indicating the success or failure of the command.
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Run pre-execution logic (e.g., setup or checks)
        $this->beforeExecute();

        // Default exit code indicating failure
        $exitCode = self::FAILURE;

        // Check if command is isolated
        $isIsolated = $this->options()->getIsolated();
        $isInstanceOfIsolated = $this instanceof Isolatable;

        // Handle command isolation if applicable
        if ($isInstanceOfIsolated && $isIsolated !== false) {
            // Prevent running multiple instances of the same command concurrently
            if (! $this->commandIsolationMutex()->create($this)) {
                $this->comment(Str::format('The [%s] command is already running.', $this->getName()));

                // Return the exit code for isolated execution (use the option or a default exit code)
                return (int)(is_numeric($isIsolated)
                            ? $isIsolated
                            : $this->getIsolatedExitCode());
            }
        }

        $method = Reflection::methodExists($this, 'handle') ? 'handle' : '__invoke';

        try {
            return (int)Container::call([$this, $method]);
        } catch (ExceptionInterface $exception) {
            // Handle any exceptions that occur during execution
            $this->handleError($exception);
        } finally {
            // Ensure that isolation is removed after execution if applicable
            if ($isInstanceOfIsolated && $isIsolated !== false) {
                $this->commandIsolationMutex()->forget($this);
            }
        }

        // Run any post-execution logic (e.g., cleanup)
        $this->afterExecute();

        // Return the determined exit code
        return $exitCode;
    }

    /**
     * Runs the command.
     *
     * This method executes the command. The execution code can be defined
     * either directly using the setCode() method or by overriding the execute()
     * method in a derived class. It also manages the prompt configuration for the command.
     *
     * @throws ExceptionInterface When input binding fails. Users can bypass this
     *                            error by calling {@link ignoreValidationErrors()}.
     *
     * @return int The command exit code, which indicates the success or failure
     *             of the command execution.
     *
     * @see setCode() To set the command's execution logic directly.
     * @see execute() To override the command's execution logic in a subclass.
     */
    #[Override]
    public function run(InputInterface $input, OutputInterface $output): int
    {
        // Create an output style if not already provided
        $this->setInput($input);

        // Create an output style if not already provided
        $this->output = $output instanceof OutputStyle
        ? $output
        : $this->outputStyleFactory->create(['input' => $input, 'output' => $output]);

        // Set an components instance
        $this->components = $this->componentsFactory->create(['output' => $this->output]);

        try {
            // Configure prompts and pass input
            $this->configurePrompts($input);

            // Call the parent run method to execute the command and return its exit code
            return parent::run($input, $output);
        } finally {
            // Ensure that resources are released and any necessary cleanup is done
            $this->untrap();
        }
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback
     * @param  array<string, mixed>  $parameters
     * @param  string|null  $defaultMethod
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function callClosure($callback, array $parameters = [], $defaultMethod = null)
    {
        // Use the ObjectManager to create the instance if necessary
        $instance = null;

        if (Validator::isString($callback) && str_contains($callback, '@')) {
            // Split into class and method
            [$class, $method] = explode('@', $callback);

            // Create the instance
            $instance = Container::make($class);

            // Rebuild the callback with instance and method
            $callback = [$instance, $method];
        }

        // Invoke the callback with parameters
        $result = $this->invokeCallback($callback, $parameters, $defaultMethod);

        // Return the result of the callback invocation
        return $result;
    }

    /**
     * Configure the console command for isolation.
     *
     * This method sets up the 'isolated' option for the console command,
     * allowing it to run in isolation if another instance of the command
     * is already executing. The option is defined as optional.
     */
    public function configureIsolation(): void
    {
        // Create a new InputOption instance with the required parameters.
        $options = $this->inputOptionFactory->create([
            'name' => 'isolated', // The name of the option.
            'shortcut' => null, // No shortcut defined for this option.
            'default' => $this->isolated, // Default value set from the instance property.
            'mode' => InputOption::VALUE_OPTIONAL, // The option is optional.
            'description' => $this->getDescription(), // Description of what the option does.
        ]);

        // Add the created option to the command's definition.
        $this->getDefinition()->addOption($options);
    }

    /**
     * Returns the command name.
     */
    public function getName(): ?string
    {
        return $this->name ?? $this->getDefaultName();
    }

    /**
     * Returns the signature for the command.
     */
    public function getSignture(): string
    {
        return $this->signature;
    }

    /**
     * Get a command isolation mutex instance for the command.
     *
     * @return CommandMutexInterface
     */
    protected function commandIsolationMutex(): CommandMutexInterface
    {
        return $this->commandMutex;
    }

    /**
     * Invoke the callback with the given parameters.
     *
     * @param  callable  $callback
     * @param  string|null  $defaultMethod
     *
     * @return mixed
     */
    protected function invokeCallback($callback, array $parameters, $defaultMethod)
    {
        // Call the callback using Reflection or directly if callable
        if (is_callable($callback)) {
            return call_user_func_array($callback, $parameters);
        }

        // Handle the case of a default method if applicable
        if ($defaultMethod && Validator::isString($callback)) {
            return Container::make($callback)->{$defaultMethod}(...$parameters);
        }

        throw InvalidArgumentException::make('The provided callback is not callable.');
    }

    /**
     * Abstract method `handle`.
     *
     * This method must be implemented by child classes and will contain
     * the core logic for executing the console command.
     *
     * @return int The exit code of the command execution.
     */
    // abstract protected function handle(): int;

    /**
     * Hook for logic to execute before the command runs.
     *
     * This method can be overridden in subclasses to implement any setup,
     * checks, or initializations required before the main command execution.
     */
    protected function beforeExecute(): void
    {
        // Logic to run before command execution
    }

    /**
     * Hook for logic to execute after the command has completed.
     *
     * This method can be overridden in subclasses to perform any cleanup,
     * logging, or additional actions required after the main command execution.
     */
    protected function afterExecute(): void
    {
        // Logic to run after command execution
    }

    /**
     * Configure the console command using a fluent definition.
     *
     * This method parses the command signature into its components: name, arguments,
     * and options. It then sets these components on the command instance, ensuring
     * they are properly registered with Symfony's input handling system.
     */
    protected function configureUsingFluentDefinition(): void
    {
        // Parse the command signature into its components: name, arguments, and options.
        [$name, $arguments, $options] = Parser::parse($this->getSignture());

        // Set the command name if it's valid (not empty, doesn't start with '{', and doesn't contain '?')
        if (empty($name) || $name[0] === '{' || str_contains($name, '?')) {
            parent::__construct(); // Use default constructor if invalid
        } else {
            parent::__construct($name); // Set the valid command name
        }

        // Register arguments and options if they are provided.
        if (! empty($arguments)) {
            $this->getDefinition()->addArguments($arguments);
        }

        if (! empty($options)) {
            $this->getDefinition()->addOptions($options);
        }
    }

    /**
     * Configure the command's name, description, and options.
     *
     * This method sets the command's name and description for the console output,
     * and defines any options that the command may have.
     */
    protected function configure(): void
    {
        // Set the command name using the value from the class property `$name`.
        $this->setName($this->getName());

        // Set the command description using the value from the class property `$description`.
        $this->setDescription($this->getDescription());

        // Set the command options definition if available.
        if ($this->getOptions()) {
            // Set the command options definition if available.
            foreach ($this->options()->toArray() as $option) {
                $this->addOption(...$option);
            }
        }

        // Set the command arguments if available.
        if ($this->arguments()->hasData()) {
            foreach ($this->arguments()->getData() as $name => $argument) {
                $this->addArgument($name, $this->mode($argument), $argument['description']);
            }
        }
    }

    /**
     * Get the class name for the given callback, if one can be determined.
     *
     * @param  callable|string  $callback
     *
     * @return string|false
     */
    protected function getClassForCallable($callback)
    {
        // Using Container::make to instantiate the ReflectionFunction
        $reflector = $this->reflectionFunctionFactory->create(['callback' => $callback]);

        if (is_callable($callback) && ! $reflector->isAnonymous()) {
            return $reflector->getClosureScopeClass()->name ?? false;
        }

        return false;
    }

    /**
     * Resolve the console command instance for the given command.
     *
     * This method checks if the provided command is a string or an instance of SymfonyCommand.
     * It either retrieves the command instance from the application or finds it by class name.
     *
     * @param  SymfonyCommand|string  $command  The command to resolve, either as a class name or an instance.
     *
     * @return SymfonyCommand The resolved command instance.
     */
    protected function resolveCommand($command)
    {
        // Check if the command is provided as a string
        if (Validator::isString($command)) {
            // If the class does not exist, try to find the command by its name
            if (! Php::isClassExists($command)) {
                return $this->getApplication()->find($command);
            }

            // If the class exists, retrieve the command instance from the application
            $command = $this->getApplication()->find($command);
        }

        // If the command is an instance of SymfonyCommand, set its application context
        if ($command instanceof SymfonyCommand) {
            $command->setApplication($this->getApplication());
        }

        // Return the resolved command instance
        return $command;
    }

    /**
     * Get the command argument mode.
     *
     *
     * @throws InvalidModeException
     *
     * @return int
     */
    private function mode($argument)
    {
        // Check if the mode of the argument is set to 'required'.
        if ($argument['mode'] === self::REQUIRED) {
            // Return the constant representing a required argument.
            return InputArgument::REQUIRED;
        }

        // Check if the mode of the argument is set to 'optional'.
        if ($argument['mode'] === self::OPTIONAL) {
            // Return the constant representing an optional argument.
            return InputArgument::OPTIONAL;
        }

        // If the mode does not match 'required' or 'optional', throw an exception.
        // This ensures only valid modes are processed.
        throw InvalidModeException::make();
    }

    /**
     * Set the area code for the command.
     *
     * This method attempts to set the area code for the current request. If the area
     * code has not been set yet, it tries to set it to the frontend area. If an error
     * occurs during the process, the exception is logged for debugging purposes.
     *
     * @throws LocalizedException If the area code cannot be set.
     */
    private function setAreaCode(): void
    {
        if (! $this->state->getAreaCode()) {
            try {
                // Attempt to set the area code to the frontend area
                $this->state->setAreaCode(Area::AREA_FRONTEND);
            } catch (LocalizedException $exception) {
            }
        }
    }
}
