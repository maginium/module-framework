<?php

declare(strict_types=1);

namespace Maginium\Framework\Application;

use Illuminate\Support\ProcessUtils;
use Magento\Framework\App\MaintenanceMode;
use Maginium\Foundation\Enums\ExecutableTypes;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Application\Interfaces\ApplicationInterface;
use Maginium\Framework\Application\Traits\Binaryable;
use Maginium\Framework\Container\ContainerManager;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Str;

/**
 * A bootstrap of Magento application.
 *
 * Performs basic initialization root function: injects init parameters and creates object manager
 * Can create/run applications
 */
class Application extends ContainerManager implements ApplicationInterface
{
    use Binaryable;

    /**
     * The default path to the TS binary.
     */
    public const DEFAULT_TS_BINARY = 'tsx';

    /**
     * The default path to the PHP binary.
     */
    public const DEFAULT_PHP_BINARY = 'php';

    /**
     * The default path to the Magento binary.
     */
    public const DEFAULT_MAGENTO_BINARY = 'bin/magento';

    /**
     * The base path for the Laravel installation.
     *
     * @var string
     */
    protected $basePath = BP;

    /**
     * The custom environment path defined by the developer.
     *
     * @var string
     */
    protected $environmentPath;

    /**
     * The environment file to load during bootstrapping.
     *
     * @var string
     */
    protected $environmentFile = '.env';

    /**
     * Format the given command as a fully-qualified executable command.
     *
     * @param  string|array  $string The command string to format (e.g., the command to run).
     * @param  string  $executionType The type of executable (node, tsx, yarn, npm, php).
     *
     * @throws InvalidArgumentException if the execution type is not supported.
     *
     * @return string The formatted command string.
     */
    public static function formatExecutableCommandString(string|array $string, string $executionType): string
    {
        // If the command is an array, format it into a string
        // If the command is an array, wrap each part in single quotes and join into a string
        $commandString = is_array($string)
        ? implode(' ', Arr::map($string, fn($part) => ProcessUtils::escapeArgument($part)))
        : ProcessUtils::escapeArgument($string);

        // Determine the executable based on the provided execution type using match
        $executable = match ($executionType) {
            ExecutableTypes::TSX => static::tsxBinary(),
            ExecutableTypes::YARN => static::yarnBinary(),
            ExecutableTypes::NODE => static::nodeBinary(),
            ExecutableTypes::PHP => static::formatCommandString($commandString),
            ExecutableTypes::NPM => Str::format('%s %s, %s',  static::npmBinary(), 'run', '--'),
            default => throw new InvalidArgumentException("Unsupported execution type: {$executionType}"),
        };

        // Format and return the final command string
        return Str::format('%s %s', $executable, $commandString);
    }

    /**
     * Format the given command as a fully-qualified executable command.
     *
     * @param  string  $string
     *
     * @return string
     */
    public static function formatCommandString($string): string
    {
        return Str::format('%s %s %s', static::phpBinary(), static::magentoBinary(), $string);
    }

    /**
     * Get the path to the environment file directory.
     *
     * @return string
     */
    public function environmentPath(): string
    {
        return $this->environmentPath ?: $this->basePath;
    }

    /**
     * Get the environment file the application is using.
     *
     * @return string
     */
    public function environmentFile(): string
    {
        return $this->environmentFile ?: '.env';
    }

    /**
     * Get the fully qualified path to the environment file.
     *
     * @return string
     */
    public function environmentFilePath(): string
    {
        return $this->environmentPath() . DIRECTORY_SEPARATOR . $this->environmentFile();
    }

    /**
     * Get an instance of the maintenance mode manager implementation.
     *
     * @return MaintenanceMode
     */
    public function maintenanceMode(): MaintenanceMode
    {
        return $this->make(MaintenanceMode::class);
    }

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance(): bool
    {
        return $this->maintenanceMode()->isOn();
    }

    /**
     * Get or check the current application environment.
     *
     * @param  string|array  ...$environments
     *
     * @return string|bool
     */
    public function environment(...$environments): string|bool
    {
        if (count($environments) > 0) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            return Str::is($patterns, env('app.env'));
        }

        return env('app.env');
    }

    /**
     * Determine if the application is in the local environment.
     *
     * @return bool
     */
    public function isLocal(): bool
    {
        return env('app.env') === 'local';
    }

    /**
     * Determine if the application is in the production environment.
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return env('app.env') === 'production';
    }
}
