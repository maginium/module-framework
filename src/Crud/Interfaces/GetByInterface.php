<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces;

use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Crud\Constants\Criteria;

/**
 * Interface GetByInterface.
 *
 * Interface for retrieving an model by a specific value (e.g., field, value, etc.).
 */
interface GetByInterface
{
    /**
     * Retrieve an model by a given attribute.
     *
     * @param mixed $value The attribute of the model to retrieve.
     * @param string $code The key to use for the attribute (default: 'id').
     *
     * @throws NotFoundException If the model with the given attribute does not exist in the repository.
     * @throws LocalizedException If an error occurs during the retrieval process.
     *
     * @return string[] The retrieved model data.
     */
    public function handle($value, $code = Criteria::DEFAULT_KEY): array;
}
