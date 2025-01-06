<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces;

use Maginium\Foundation\Exceptions\LocalizedException;

/**
 * Interface GetListInterface.
 *
 * Interface for retrieving a list of models with pagination.
 */
interface GetListInterface
{
    /**
     * Retrieve a paginated list of models.
     *
     * @throws NotFoundException If no models exist in the repository.
     * @throws LocalizedException If an error occurs during the retrieval process.
     *
     * @return string[] The paginated list of models, including metadata and data.
     */
    public function handle(): array;
}
