<?php

declare(strict_types=1);

namespace Maginium\Framework\Application\Traits;

use Illuminate\Support\ProcessUtils;

/**
 * Provides methods to retrieve paths for common binaries used in the application.
 *
 * This trait includes methods for retrieving the executable paths for common binaries,
 * including PHP, Magento, Node.js, NPM, Yarn, and TSX. The paths are determined using
 * respective helper methods or fallback defaults where applicable.
 *
 * @method string phpBinary(): Retrieves the PHP binary path.
 * @method string magentoBinary(): Retrieves the Magento binary path.
 * @method string nodeBinary(): Retrieves the Node.js binary path.
 * @method string npmBinary(): Retrieves the NPM binary path.
 * @method string yarnBinary(): Retrieves the Yarn binary path.
 * @method string tsxBinary(): Retrieves the TSX binary path.
 */
trait Binaryable
{
    /**
     * Get the PHP binary path.
     *
     * This method utilizes the PhpExecutableFinder to locate the PHP binary
     * available in the system.
     *
     * @return string The path to the PHP binary.
     */
    public static function phpBinary(): string
    {
        // Use PhpExecutableFinder to locate the PHP binary or default to the constant value.
        return ProcessUtils::escapeArgument(php_binary());
    }

    /**
     * Get the Magento binary path.
     *
     * This method checks if the `MAGENTO_BINARY` constant is defined. If not, it defaults to `DEFAULT_MAGENTO_BINARY`.
     *
     * @return string The path to the Magento binary.
     */
    public static function magentoBinary(): string
    {
        // Use ExecutableFinder to locate the Magento binary or default to the constant value.
        return ProcessUtils::escapeArgument(magento_binary());
    }

    /**
     * Get the Node binary path.
     *
     * This method utilizes the ExecutableFinder to locate the Node binary
     * available in the system.
     *
     * @return string The path to the Node binary.
     */
    public static function nodeBinary(): string
    {
        // Use ExecutableFinder to locate the Node binary or default to the constant value.
        return ProcessUtils::escapeArgument(node_binary());
    }

    /**
     * Get the NPM binary path.
     *
     * This method checks the system for the `npm` binary and returns its path.
     *
     * @return string The path to the NPM binary.
     */
    public static function npmBinary(): string
    {
        // Use ExecutableFinder to locate the NPM binary or default to the constant value.
        return ProcessUtils::escapeArgument(npm_binary());
    }

    /**
     * Get the Yarn binary path.
     *
     * This method checks the system for the `yarn` binary and returns its path.
     *
     * @return string The path to the Yarn binary.
     */
    public static function yarnBinary(): string
    {
        // Use ExecutableFinder to locate the Yarn binary or default to the constant value.
        return ProcessUtils::escapeArgument(yarn_binary());
    }

    /**
     * Get the TSX binary path.
     *
     * This method checks the system for the `tsx` binary and returns its path.
     *
     * @return string The path to the TSX binary.
     */
    public static function tsxBinary(): string
    {
        // Use ExecutableFinder to locate the TSX binary or default to the constant value.
        return ProcessUtils::escapeArgument(tsx_binary());
    }
}
