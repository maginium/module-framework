<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces;

use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Pagination\Constants\Paginator as PaginatorConstants;

/**
 * Interface SearchInterface.
 *
 * Interface for searching models based on a search term with optional filters, sorting, and pagination.
 */
interface SearchInterface
{
    /**
     * Search for models based on a search term with optional filters, sorting, and pagination.
     *
     * @param string $searchTerm The term to search for.
     * @param int $page The page number for pagination (defaults to the first page).
     * @param int $perPage The number of items per page (defaults to 10).
     * @param array $filters Additional filters for refining the search.
     * @param array $sorts The sorting order ('ASC' or 'DESC').
     *
     * @throws NotFoundException If the model with the given search term does not exist in the repository.
     * @throws LocalizedException If no models are found or if an error occurs during the search process.
     *
     * @return string[] The search results with metadata and model data.
     */
    public function handle(
        string $searchTerm,
        int $page = PaginatorConstants::DEFAULT_PAGE,
        int $perPage = PaginatorConstants::DEFAULT_PER_PAGE,
        array $filters = [],
        array $sorts = [],
    ): array;
}
