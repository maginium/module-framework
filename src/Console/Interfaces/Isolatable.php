<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Interfaces;

/**
 * Interface for isolating console commands.
 *
 * This interface defines the structure for classes that require isolation when executed.
 * It provides a method to configure isolation, ensuring that multiple instances
 * of the same command do not run concurrently.
 */
interface Isolatable
{
    /**
     * Configure the console command for isolation.
     *
     * This method sets up the 'isolated' option for the console command,
     * allowing it to run in isolation if another instance of the command
     * is already executing. The option is defined as optional.
     *
     * The implementation of this method should handle the logic for isolating
     * the command, such as checking for any active instances of the command
     * and preventing further execution if necessary.
     */
    public function configureIsolation(): void;
}
