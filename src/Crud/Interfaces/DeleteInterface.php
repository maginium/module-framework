<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces;

use Maginium\Foundation\Exceptions\CouldNotDeleteException;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Foundation\Exceptions\NotFoundException;

/**
 * Interface DeleteInterface.
 *
 * Interface for deleting models.
 */
interface DeleteInterface
{
    /**
     * Handles the delete of an model by its unique identifier.
     *
     * This method retrieves input data from the request, deletes the model through the repository,
     * and constructs a response with the deleted model's data. It ensures consistent exception
     * handling by propagating known exceptions and wrapping unexpected ones in a domain-specific exception.
     *
     * @param int $id The unique identifier of the model to be deleted.
     *
     * @throws NotFoundException If the model with the given ID does not exist in the repository.
     * @throws CouldNotDeleteException If the model could not be saved due to an error.
     * @throws LocalizedException For unexpected errors during the delete process.
     *
     * @return string[] An associative array containing the deleted model's data, HTTP status, and message.
     */
    public function handle(int $id): array;
}
