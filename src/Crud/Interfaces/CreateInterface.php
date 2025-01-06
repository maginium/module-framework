<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces;

use Maginium\Foundation\Exceptions\CouldNotSaveException;

/**
 * Interface CreateInterface.
 *
 * Interface for creating and saving models.
 */
interface CreateInterface
{
    /**
     * Handles the save of an model.
     *
     * This method retrieves input data from the request, saves the model through the repository,
     * and constructs a response with the saved model's data. It ensures consistent exception
     * handling by propagating known exceptions and wrapping unexpected ones in a domain-specific exception.
     *
     * @throws NotFoundException If the model with the given ID does not exist in the repository.
     * @throws CouldNotSaveException If the model could not be saved due to an error.
     * @throws LocalizedException For unexpected errors during the save process.
     *
     * @return string[] An associative array containing the saved model's data, HTTP status, and message.
     */
    public function handle(): array;
}
