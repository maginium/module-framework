<?php

declare(strict_types=1);

namespace Maginium\Framework\Component\Commands;

use Maginium\Framework\Console\Enums\Commands;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for disabling a module.
 *
 * This command disables a specific module or all modules based on the options provided.
 */
#[AsCommand(Commands::MODULE_DISABLE)]
class DisableModuleCommand extends ModuleCommand
{
    /**
     * A brief description of the disable module command.
     *
     * This property provides a short description of what the command does.
     * It will be shown when running `php bin/magento list` to list available commands.
     */
    protected ?string $description = 'Disable a specific a module or all modules.';

    /**
     * Return the type of action for disabling the module.
     *
     * @return string The action type ('disable').
     */
    public function getType(): string
    {
        return 'disable';
    }
}
