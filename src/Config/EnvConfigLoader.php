<?php

declare(strict_types=1);

namespace Maginium\Framework\Config;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Illuminate\Support\Env;
use Maginium\Framework\Application\Interfaces\ApplicationInterface;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Crypt;

/**
 * Class EnvConfigLoader.
 *
 * This class is responsible for managing the loading and setting of environment variables
 * prior to application launch. It intercepts the bootstrapping process to ensure all necessary
 * environment configurations are properly loaded.
 */
class EnvConfigLoader
{
    /**
     * Load the .env file from the project directory or root.
     *
     * This method searches for the .env file starting from the current directory
     * and traversing upwards until it finds it.
     *
     * @param  string  $directory  The directory to start the search from.
     */
    public static function load(ApplicationInterface $app, string $rootDirectory = BP): void
    {
        // Traverse up the directory tree to find the .env file
        while ($rootDirectory !== SP) { // SP represents the root directory constant.
            // Build the path to the .env file
            $envFilePath = $rootDirectory . '/.env';

            // Check if the .env file exists in the current directory.
            if (file_exists($envFilePath)) {
                // Load environment variables from the .env file safely.
                try {
                    static::createDotenv($app)->safeLoad(); // Safely load the environment variables.
                } catch (InvalidFileException $e) {
                    // If there's an error with the .env file, handle it and exit.
                    static::handleError($e);
                }

                // Create a Dotenv instance for the current root directory and load the environment variables.
                $dotenv = Dotenv::create(Env::getRepository(), $rootDirectory);
                $dotenv->load(); // Load the environment variables.

                // Ensure that essential environment variables are set and available.
                $dotenv->required([
                    'APP_ENV',      // Application environment (e.g., 'development', 'production').
                    'DB_DATABASE',  // Database name.
                    'DB_USERNAME',  // Database username.
                    'DB_PASSWORD',  // Database password.
                    'APP_KEY',      // Application key for encryption.
                ]);

                // Load additional environment-specific variables.
                static::applyEnvironmentVariables($rootDirectory);

                break; // Break out of the loop once the .env file is found and loaded.
            }

            // Move up to the parent directory if the .env file is not found.
            $rootDirectory = dirname($rootDirectory);
        }
    }

    /**
     * Apply environment-specific variables based on APP_ENV.
     *
     * This method loads additional environment variables from a file specific to APP_ENV
     * if available, and sets default values for missing keys.
     *
     * @param  string  $rootDirectory  The root directory path.
     */
    protected static function applyEnvironmentVariables(string $rootDirectory): void
    {
        // Get the current application environment, defaulting to 'development'.
        $appEnv = $_ENV['APP_ENV'] ?? 'development';

        // Get the current application key, or generate one if missing.
        $appKey = $_ENV['APP_KEY'] ?? static::generateAppKey();

        // Set the application environment and key in the environment variables.
        Env::getRepository()->set('APP_ENV', $appEnv);
        Env::getRepository()->set('APP_KEY', $appKey);

        // Set the Mage mode based on the APP_ENV (either 'developer' or 'production').
        Env::getRepository()->set('MAGE_MODE', $appEnv !== 'production' ? 'developer' : 'production');

        // Paths for the default and environment-specific .env files.
        $defaultEnvPath = $rootDirectory . '/.env';
        $envSpecificFilePath = $rootDirectory . '/.env.' . $appEnv;

        // Load the environment-specific .env file if it exists.
        if (file_exists($envSpecificFilePath)) {
            $dotenv = Dotenv::createMutable($rootDirectory, '.env.' . $appEnv);
            $dotenv->load(); // Load the specific environment file.
        } elseif (file_exists($defaultEnvPath)) {
            // If no environment-specific file is found, load the default .env file.
            $dotenv = Dotenv::createMutable($rootDirectory, '.env');
            $dotenv->load();
        }
    }

    /**
     * Generate a random application key.
     *
     * @return string The generated application key.
     */
    protected static function generateAppKey(): string
    {
        // Get the cipher used for encryption (defaults to 'AES-256-CBC').
        $appCipher = $_ENV['APP_CIPHER'] ?? 'AES-256-CBC';

        // Generate a secure random key using the specified cipher and return it as a base64-encoded string.
        return 'base64:' . base64_encode(Crypt::generateKey($appCipher));
    }

    /**
     * Initialize and return a Dotenv instance.
     *
     * @return Dotenv The Dotenv instance.
     */
    protected static function createDotenv(ApplicationInterface $app): Dotenv
    {
        // Create and return a Dotenv instance for loading environment variables.
        return Dotenv::create(
            Env::getRepository(),
            $app->environmentPath(), // Directory path where the environment file is located.
            $app->environmentFile(), // The name of the environment file to load.
        );
    }

    /**
     * Handle errors when loading the .env file.
     *
     * This method writes the error details to the console and stops the execution.
     *
     * @param  InvalidFileException  $e  The exception thrown when the .env file is invalid.
     */
    protected static function handleError(InvalidFileException $e): never
    {
        // Get the console output for error messages.
        $output = Container::resolve(ConsoleOutput::class)->getErrorOutput();

        // Display error messages to the user.
        $output->writeln('Invalid environment file!');
        $output->writeln($e->getMessage());

        // Send an HTTP 500 response code indicating a server error.
        http_response_code(500);

        // Exit the script with a non-zero exit code to indicate failure.
        exit(1);
    }
}
