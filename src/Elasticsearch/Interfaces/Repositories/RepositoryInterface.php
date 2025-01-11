<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Interfaces\Repositories;

use Maginium\Framework\Crud\Interfaces\Repositories\RepositoryInterface as BaseRepositoryInterface;

/**
 * Interface RepositoryInterface.
 *
 * This interface defines the core contract for CRUD repository classes,
 * providing methods for managing entities, performing database operations,
 * and handling pagination. Implementations of this interface ensure
 * standardization across repository layers within the application.
 */
interface RepositoryInterface extends BaseRepositoryInterface
{
}
