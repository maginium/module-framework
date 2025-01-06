<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces;

use Maginium\Foundation\Exceptions\LocalizedException;

/**
 * Interface GetByIdInterface.
 *
 * Interface for retrieving an model by its ID.
 */
interface GetByIdInterface
{
    /**
     * Retrieve model by ID.
     *
     * @param int $id The ID of the model to retrieve.
     *
     * @throws NotFoundException If the model with the given ID does not exist in the repository.
     * @throws LocalizedException If an error occurs during the retrieval process.
     *
     * @return string[] The retrieved model data.
     */
    public function handle(int $id): array;
}
