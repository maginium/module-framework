<?php

declare(strict_types=1);

namespace Maginium\Framework\Component\Commands;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Component\ComponentRegistrar;
use Maginium\Framework\Component\Module;
use Maginium\Framework\Console\Command;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Facades\Prompts;
use Maginium\Framework\Support\Path;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Abstract class for handling module-related commands.
 *
 * This abstract command provides common functionality for interacting with
 * modules, including listing, enabling, and disabling modules.
 */
abstract class ModuleCommand extends Command
{
    /**
     * Key for the input option specifying the module name.
     *
     * This constant is used to define the input key for identifying the module
     * for which the command should be executed. It ensures consistency across
     * commands and improves code readability.
     */
    public const INPUT_KEY_MODULE = 'module';

    /**
     * The signature of the enable/disable module command.
     *
     * This property defines the command-line arguments and options for the command.
     * It includes:
     * - `--m` or `--module`: A required option to specify the module to enable or disable.
     * - `--a` or `--all`: A flag to enable or disable all modules.
     * - `--force`: A flag to force execution of the command, bypassing any preconditions.
     */
    protected ?string $signature = '{--m|module= : The name of the module to enable/disable.}
                                    {--a|all : Enable/disable all modules.}
                                    {--force : Force the execution of the command, bypassing any preconditions.}';

    /**
     * Get the action type (either 'enable' or 'disable') for the module.
     *
     * This method must be implemented by subclasses to define the desired action.
     *
     * @return string The action type ('enable' or 'disable').
     */
    abstract public function getType(): string;

    /**
     * Main method to handle the execution of the module management command.
     *
     * This method is responsible for managing modules based on user input and
     * executing the enable/disable actions.
     *
     * @return int The exit code of the command execution (self::SUCCESS or self::FAILURE).
     */
    public function handle(): int
    {
        try {
            // Step 1: Simulate loading state with spinner
            $modules = $this->fetchModules();

            if ($modules === self::FAILURE) {
                // Return failure if fetching modules fails
                return self::FAILURE;
            }

            // Step 2: Retrieve action type (enable or disable)
            $actionType = $this->getType();

            if (! $actionType) {
                ConsoleOutput::error('Action type is not provided.');

                // Return failure if action type is invalid
                return self::FAILURE;
            }

            // Step 3: Check if the '--force' option is provided
            $forceOption = $this->options()->getForce();

            // Step 4: Check if the '--all' option is selected
            if ($this->options()->getAll()) {
                $result = $this->handleAllModules($modules, $actionType, $forceOption);

                if ($result === self::FAILURE) {
                    // Return failure if handling all modules fails
                    return self::FAILURE;
                }
            }

            // Step 5: Handle a specific module if '--module' option is selected
            if ($moduleName = $this->options()->getModule()) {
                $result = $this->handleSingleModule($moduleName, $actionType, $forceOption);

                if ($result === self::FAILURE) {
                    // Return failure if handling single module fails
                    return self::FAILURE;
                }
            }

            // Step 6: If neither '--all' nor '--module' option is selected, prompt the user to select a module
            if (! $this->options()->getAll() && ! $this->options()->getModule()) {
                $result = $this->handleUserSelection($modules, $actionType, $forceOption);

                if ($result === self::FAILURE) {
                    // Return failure if user selection handling fails
                    return self::FAILURE;
                }
            }

            // Step 7: Execute list module command
            $result = $this->listModules($moduleName);

            if ($result === self::FAILURE) {
                // Return failure if listing modules fails
                return self::FAILURE;
            }

            // If all steps are successful, return success
            return self::SUCCESS;
        } catch (Exception $e) {
            // Handle any exception that occurs during the process
            ConsoleOutput::error('Error: ' . $e->getMessage());

            // Return failure exit code
            return self::FAILURE;
        }
    }

    /**
     * Get all modules defined in `app/etc/config.php`.
     *
     * This method reads the `config.php` file located in `app/etc/` and
     * returns a collection of modules with their names, enabled/disabled
     * status, and file paths.
     *
     * @return int The exit code of the operation (self::SUCCESS or self::FAILURE).
     */
    protected function listModules($moduleName): int
    {
        // Prepare the arguments for the 'app:modules:list' command
        $arguments = [];

        // Check if a module name is provided and pass it as an argument
        if ($moduleName) {
            $arguments['module'] = $moduleName;
        }

        // Call the 'app:modules:list' command with the provided arguments
        $exitCode = $this->call('app:modules:list', $arguments);

        // Check if the command executed successfully
        if ($exitCode === self::SUCCESS) {
            // Return success if the command was successful
            return self::SUCCESS;
        }

        // Return failure code if the command failed
        return self::FAILURE;
    }

    /**
     * Retrieve and process module data from `getRawModules()`.
     *
     * This method processes the raw module data by including additional metadata,
     * such as the module's file path (via `ComponentRegistrar`), priority, and
     * colored status. If a `$module` parameter is provided, only the data for
     * that specific module is returned. Otherwise, all modules are returned.
     *
     * The processed collection of modules includes the following structure:
     * - 'name' => The module's name (e.g., 'Magento_Store')
     * - 'enabled' => A boolean indicating whether the module is enabled (true) or disabled (false)
     * - 'path' => The full path to the module on the filesystem
     * - 'priority' => The position of the module in the list
     * - 'colored_status' => A colored status string representing the module's enabled/disabled state
     *
     * If there is an error or no raw module data is found, an empty collection is returned.
     *
     * @param  string|null  $module  The name of a specific module to retrieve. If null, all modules are returned.
     *
     * @return Collection A collection of modules, each with its name, enabled status, file path, and additional metadata.
     */
    protected function getModules(?string $module = null): Collection
    {
        // Retrieve the raw module data
        $rawModules = $this->getRawModules();

        // If no raw modules data is found, return an empty collection
        if (Validator::isEmpty($rawModules)) {
            return Collection::make();
        }

        // Initialize an array to hold the processed module data
        $modules = [];

        // Process each raw module
        foreach ($rawModules as $moduleName => $isEnabled) {
            // Skip modules that shouldn't be returned
            if ($module && $moduleName !== $module) {
                continue;
            }

            // Get the file path of the module using the ComponentRegistrar
            $absolutePath = Module::getPath($moduleName);

            // If $absolutePath is provided, remove the base path (BP) to get the relative module path.
            $modulePath = $absolutePath ? Str::replace(BP, '', $absolutePath) : '';

            // Get the array of keys (module names) from the raw modules
            $priority = Arr::keys($rawModules);

            // Find the index of the current module name in the array
            $moduleIndex = Arr::search($moduleName, $priority);

            // Append the module details to the $modules array
            $modules[] = DataObject::make([
                'name' => $moduleName,  // Module name
                'path' => $modulePath,  // The module's path on the filesystem
                'enabled' => $isEnabled ? true : false,  // Whether the module is enabled or not
                'priority' => $moduleIndex,  // Module priority
                'colored_status' => $isEnabled ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>',  // Colored status
            ]);
        }

        // Make modules array as collection
        $modules = Collection::make($modules);

        // Exclude Maginium_Framework and Maginium_Foundation
        $filteredModules = $modules->filter(fn($module) => ! in_array($module->getName(), ['Maginium_Framework', 'Maginium_Foundation']));

        // Return the array of processed modules with their respective details
        return Collection::make($filteredModules);
    }

    /**
     * Custom method to filter modules by a 'like' pattern.
     *
     * @param  Collection  $modules  The collection of modules.
     * @param  string  $value  The search query.
     *
     * @return Collection The filtered collection of modules.
     */
    protected function filterModulesByLike(Collection $modules, string $value): Collection
    {
        // Case-insensitive 'like' search
        return $modules->filter(fn($module) => mb_stripos($module->getName(), $value) !== false);
    }

    /**
     * Simulate fetching modules with a loading spinner.
     *
     * This method simulates the process of fetching a list of modules by introducing a 1-second delay,
     * followed by returning the actual list of modules. If a specific `$module` name is provided,
     * only that module's data will be fetched; otherwise, all modules will be returned.
     *
     * The returned collection includes the following structure:
     * - 'name' => The module's name (e.g., 'Magento_Store')
     * - 'enabled' => A boolean indicating whether the module is enabled (true) or disabled (false)
     * - 'path' => The full path to the module on the filesystem
     *
     * If there is an error during fetching, an empty collection will be returned.
     *
     * @param  string|null  $module  The name of a specific module to fetch. If null, all modules are fetched.
     *
     * @return Collection A collection of modules with their name, enabled status, and file path.
     */
    protected function fetchModules(?string $module = null): Collection
    {
        // Show a spinner while fetching modules
        Prompts::spinner(
            message: 'Fetching modules...',  // Message shown during the spinner
            callback: function() {
                // Simulate a 1-second delay to represent processing time
                sleep(1);
            },
        );

        // Step 1: Retrieve the available modules from the system
        return $this->getModules($module);  // This method should return a collection of modules
    }

    /**
     * Retrieve raw module data from `app/etc/config.php`.
     *
     * This method reads the `config.php` file located in the `app/etc/` directory, which
     * contains the list of enabled and disabled modules in Magento 2. It returns the raw
     * module data in the form of an array without any processing or filtering.
     *
     * The raw data includes:
     * - 'modules' => An array of module names and their enabled/disabled status
     *
     * If the `config.php` file cannot be found or an error occurs during processing,
     * an empty array is returned.
     *
     * @return array The raw module data, including module names and enabled/disabled status.
     */
    private function getRawModules(): array
    {
        try {
            // Path to the config.php file that holds the list of enabled/disabled modules
            $configFilePath = Path::join(BP, 'app/etc/config.php');

            // Check if the config file exists before attempting to include it
            if (! Filesystem::exists($configFilePath)) {
                // Output an error message if the file does not exist
                ConsoleOutput::error("Config file does not exist: {$configFilePath}");

                // Return an empty array if the file is missing
                return [];
            }

            // Include the config.php file, which returns an associative array
            // of module names and their enabled/disabled status
            $config = include $configFilePath;

            // Return the raw module data
            return $config['modules'] ?? [];
        } catch (Exception $e) {
            // If an error occurs during the execution, output the error message
            ConsoleOutput::error('Error: ' . $e->getMessage());

            // Return an empty array in case of failure
            return [];
        }
    }

    /**
     * Handle user selection for module management.
     *
     * This method prompts the user to select a module from the available list and then manages that selected module
     * by enabling or disabling it based on the action type ('enable' or 'disable').
     *
     * @param  Collection  $modules  The collection of available modules.
     * @param  string  $actionType  The action type ('enable' or 'disable').
     * @param  bool|null  $forceOption  The force option to bypass preconditions.
     *
     * @return int The exit code of the operation (self::SUCCESS or self::FAILURE).
     */
    private function handleUserSelection(Collection $modules, string $actionType, ?bool $forceOption): int
    {
        // Prompt the user to select a module from the available modules
        $moduleName = Prompts::select(
            label: 'Please select a module to manage:',  // Label for the selection prompt
            options: $modules->pluck('name')->all(),  // List of module names for the user to choose from
        );

        // Use a spinner while managing the selected module (enable/disable)
        $result = Prompts::spinner(
            callback: function() use ($moduleName, $actionType, $forceOption) {
                // Simulate a 1-second delay to represent processing time
                sleep(1);

                // Execute the module management command ('module:enable' or 'module:disable') silently
                return $this->callSilently('module:' . $actionType, [
                    self::INPUT_KEY_MODULE => [$moduleName],  // Pass the selected module name
                    '--force' => $forceOption,  // Pass the force option if available
                ]);
            },
            message: "Managing selected module '{$moduleName}'...",  // Message shown during the spinner
        );

        // Check if the result indicates failure and return FAILURE if so
        if ($result !== self::SUCCESS) {
            ConsoleOutput::error("Failed to {$actionType} module '{$moduleName}'.");

            return self::FAILURE;
        }

        // Output a success message once the module is managed
        ConsoleOutput::success("Module '{$moduleName}' has been {$actionType}d.");

        // Return success exit code
        return self::SUCCESS;
    }

    /**
     * Handle enabling/disabling all modules.
     *
     * This method processes all modules in batch, excluding specific system modules (e.g., Magento_ prefixed modules),
     * and either enables or disables them based on the action type.
     *
     * @param  Collection  $modules  The collection of all available modules.
     * @param  string  $actionType  The action type ('enable' or 'disable').
     * @param  bool|null  $forceOption  The force option to bypass preconditions.
     *
     * @return int The exit code of the operation (self::SUCCESS or self::FAILURE).
     */
    private function handleAllModules(Collection $modules, string $actionType, ?bool $forceOption): int
    {
        // Filter out system modules (e.g., Magento_ prefix modules) from the list
        $filteredModules = $modules->filter(fn($module) => ! str_starts_with($module->getName(), 'Magento_'));

        // Get the list of module names based on the action type (enable or disable)
        $moduleNames = $this->getType() === 'enable' ? $modules->pluck('name')->all() : $filteredModules->pluck('name')->all();

        // Use a spinner while managing the modules (enable/disable)
        $result = Prompts::spinner(
            callback: function() use ($moduleNames, $actionType, $forceOption) {
                // Simulate a 1-second delay to represent processing time
                sleep(1);

                // Execute the module management command for all modules silently
                return $this->callSilently('module:' . $actionType, [
                    self::INPUT_KEY_MODULE => $moduleNames,  // Pass the list of module names
                    '--force' => $forceOption,  // Pass the force option if available
                ]);
            },
            message: 'Managing all modules...',  // Message shown during the spinner
        );

        // Check if the result indicates failure and return FAILURE if so
        if ($result !== self::SUCCESS) {
            ConsoleOutput::error("Failed to {$actionType} all modules.");

            return self::FAILURE;
        }

        // Output a success message once all modules are managed
        ConsoleOutput::success("All modules have been {$actionType}d.");

        // Return success exit code
        return self::SUCCESS;
    }

    /**
     * Handle enabling/disabling a specific module.
     *
     * This method processes a single module based on the provided module name and action type ('enable' or 'disable').
     *
     * @param  string  $moduleName  The name of the module to manage.
     * @param  string  $actionType  The action type ('enable' or 'disable').
     * @param  bool|null  $forceOption  The force option to bypass preconditions.
     *
     * @return int The exit code of the operation (self::SUCCESS or self::FAILURE).
     */
    private function handleSingleModule(string $moduleName, string $actionType, ?bool $forceOption): int
    {
        // Use a spinner while managing the specific module (enable/disable)
        $result = Prompts::spinner(
            callback: function() use ($moduleName, $actionType, $forceOption) {
                // Simulate a 1-second delay to represent processing time
                sleep(1);

                // Execute the module management command for the specific module silently
                return $this->callSilently('module:' . $actionType, [
                    self::INPUT_KEY_MODULE => [$moduleName],  // Pass the specific module name
                    '--force' => $forceOption,  // Pass the force option if available
                ]);
            },
            message: "Managing module '{$moduleName}'...",  // Message shown during the spinner
        );

        // Check if the result indicates failure and return FAILURE if so
        if ($result !== self::SUCCESS) {
            ConsoleOutput::error("Failed to {$actionType} module '{$moduleName}'.");

            return self::FAILURE;
        }

        // Output a success message once the specific module is managed
        ConsoleOutput::success("Module '{$moduleName}' has been {$actionType}d.");

        // Return success exit code
        return self::SUCCESS;
    }
}
