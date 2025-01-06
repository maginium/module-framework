<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Abstracts;

use Maginium\Framework\Actions\Concerns\AsController;
use Maginium\Framework\Crud\Interfaces\Services\AbstractServiceInterface;

/**
 * Class AbstractService.
 *
 * Abstract service class for managing models.
 */
abstract class AbstractService implements AbstractServiceInterface
{
    use AsController;
}
