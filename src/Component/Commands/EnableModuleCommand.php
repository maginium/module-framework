<?php

declare(strict_types=1);

namespace Maginium\Framework\Component\Commands;

use Maginium\Framework\Console\Enums\Commands;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for disabling a module.
 *
 * This command enables a specific module or all modules based on the options provided.
 */
#[AsCommand(Commands::MODULE_ENABLE)]
class EnableModuleCommand extends ModuleCommand
{
    /**
     * A brief description of the enable module command.
     *
     * This property provides a short description of what the command does.
     * It will be shown when running `php bin/magento list` to list available commands.
     */
    protected ?string $description = 'Enable a specific a module or all modules.';

    /**
     * Return the type of action for enabling the module.
     *
     * @return string The action type ('enable').
     */
    public function getType(): string
    {
        return 'enable';
    }
}
