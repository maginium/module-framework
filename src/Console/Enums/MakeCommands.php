<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum representing different console commands.
 *
 * @method static self SEEDER() Represents the command to make a seeder.
 * @method static self MODULE() Represents the command to make a module.
 * @method static self MODEL() Represents the command to make a model.
 * @method static self MIGRATION() Represents the command to make a migration.
 * @method static self COMMAND() Represents the command to make a command.
 * @method static self ACTION() Represents the command to make an action.
 * @method static self SERVICE() Represents the command to make a service.
 * @method static self REPOSITORY() Represents the command to make a repository.
 * @method static self JOB() Represents the command to make a job.
 * @method static self ELASTIC_MODEL() Represents the command to make an elastic model.
 * @method static self QUEUE() Represents the command to make a queue.
 * @method static self CONTROLLER() Represents the command to make a controller.
 * @method static self ADMIN_CONTROLLER() Represents the command to make an admin controller.
 * @method static self MIDDLEWARE() Represents the command to make middleware.
 * @method static self FACTORY() Represents the command to make a factory.
 * @method static self ENUM() Represents the command to make an enum.
 * @method static self DTO() Represents the command to make a DTO.
 * @method static self BLOCK() Represents the command to make a block.
 * @method static self INTERCEPTOR() Represents the command to make a interceptor.
 */
class MakeCommands extends Enum
{
    /**
     * Represents the command to make a seeder.
     */
    #[Label('Make Seeder')]
    #[Description('Represents the command to make a seeder.')]
    public const SEEDER = 'app:make:seeder';

    /**
     * Represents the command to make a module.
     */
    #[Label('Make Module')]
    #[Description('Represents the command to make a module.')]
    public const MODULE = 'app:make:module';

    /**
     * Represents the command to make a model.
     */
    #[Label('Make Model')]
    #[Description('Represents the command to make a model.')]
    public const MODEL = 'app:make:model';

    /**
     * Represents the command to make a migration.
     */
    #[Label('Make Migration')]
    #[Description('Represents the command to make a migration.')]
    public const MIGRATION = 'app:make:migration';

    /**
     * Represents the command to make a command.
     */
    #[Label('Make Command')]
    #[Description('Represents the command to make a command.')]
    public const COMMAND = 'app:make:command';

    /**
     * Represents the command to make an action.
     */
    #[Label('Make Action')]
    #[Description('Represents the command to make an action.')]
    public const ACTION = 'app:make:action';

    /**
     * Represents the command to make a service.
     */
    #[Label('Make Service')]
    #[Description('Represents the command to make a service.')]
    public const SERVICE = 'app:make:service';

    /**
     * Represents the command to make a repository.
     */
    #[Label('Make Repository')]
    #[Description('Represents the command to make a repository.')]
    public const REPOSITORY = 'app:make:repository';

    /**
     * Represents the command to make a job.
     */
    #[Label('Make Job')]
    #[Description('Represents the command to make a job.')]
    public const JOB = 'app:make:job';

    /**
     * Represents the command to make an elastic model.
     */
    #[Label('Make Elastic Model')]
    #[Description('Represents the command to make an elastic model.')]
    public const ELASTIC_MODEL = 'app:make:elastic-model';

    /**
     * Represents the command to make a queue.
     */
    #[Label('Make Queue')]
    #[Description('Represents the command to make a queue.')]
    public const QUEUE = 'app:make:queue';

    /**
     * Represents the command to make a controller.
     */
    #[Label('Make Controller')]
    #[Description('Represents the command to make a controller.')]
    public const CONTROLLER = 'app:make:controller';

    /**
     * Represents the command to make an admin controller.
     */
    #[Label('Make Admin Controller')]
    #[Description('Represents the command to make an admin controller.')]
    public const ADMIN_CONTROLLER = 'app:make:admin-controller';

    /**
     * Represents the command to make middleware.
     */
    #[Label('Make Middleware')]
    #[Description('Represents the command to make middleware.')]
    public const MIDDLEWARE = 'app:make:middleware';

    /**
     * Represents the command to make a factory.
     */
    #[Label('Make Factory')]
    #[Description('Represents the command to make a factory.')]
    public const FACTORY = 'app:make:factory';

    /**
     * Represents the command to make an enum.
     */
    #[Label('Make Enum')]
    #[Description('Represents the command to make an enum.')]
    public const ENUM = 'app:make:enum';

    /**
     * Represents the command to make a DTO.
     */
    #[Label('Make DTO')]
    #[Description('Represents the command to make a DTO.')]
    public const DTO = 'app:make:dto';

    /**
     * Represents the command to make a Block.
     */
    #[Label('Make Block')]
    #[Description('Represents the command to make a Block.')]
    public const BLOCK = 'app:make:block';

    /**
     * Represents the command to make a Block.
     */
    #[Label('Make Interceptor')]
    #[Description('Represents the command to make a Interceptor.')]
    public const INTERCEPTOR = 'app:make:interceptor';
}
