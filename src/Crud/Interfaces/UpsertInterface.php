<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces;

use Maginium\Foundation\Exceptions\CouldNotSaveException;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Foundation\Exceptions\NotFoundException;

/**
 * Interface UpsertInterface.
 *
 * Interface for upserting models.
 */
interface UpsertInterface
{
    /**
     * The key for the 'data' array in the request, which will be inserted or updated.
     *
     * @var string
     */
    public const DATA = 'data';

    /**
     * The key for the 'update' array in the request, which contains the fields to update if a matching record is found.
     *
     * @var string
     */
    public const UPDATE = 'update';

    /**
     * The key for the 'unique_by' array in the request, which contains the columns that will be used to identify
     * unique records for performing the upsert operation.
     *
     * @var string
     */
    public const UNIQUE_BY = 'unique_by';

    /**
     * Handles the upsert operation for an model by its unique identifier.
     *
     * This method processes the input data from the request, performs the upsert operation through the repository,
     * and returns a response with the upserted model's data. It ensures that known exceptions are propagated,
     * while unexpected ones are wrapped in a domain-specific exception to provide better error context.
     *
     * @throws NotFoundException If the model with the given ID cannot be found in the repository.
     * @throws CouldNotSaveException If there is an issue saving the model to the repository.
     * @throws LocalizedException For unexpected errors during the upsert process.
     *
     * @return string[] An associative array containing the upserted model's data, HTTP status, and a success message.
     */
    public function handle(): array;
}
