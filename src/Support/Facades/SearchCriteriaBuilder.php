<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\Api\SearchCriteriaBuilder as SearchCriteriaBuilderManager;
use Maginium\Framework\Support\Facade;

/**
 * Class AdminSession.
 *
 * Facade for interacting with the Admin Session management and encryption services.
 *
 * @method static \Magento\Framework\Api\SearchCriteria create() Builds the SearchCriteria Data Object.
 * @method static self addFilters(array $filter) Create a filter group based on the filter array provided and add to the filter groups.
 * @method static self addFilter(string $field, mixed $value, string $conditionType = 'eq') Add a search filter.
 * @method static self setFilterGroups(array $filterGroups) Set filter groups.
 * @method static self addSortOrder(\Magento\Framework\Api\SortOrder $sortOrder) Add sort order.
 * @method static self setSortOrders(array $sortOrders) Set sort orders.
 * @method static self setPageSize(int $pageSize) Set the page size.
 * @method static self setCurrentPage(int $currentPage) Set the current page.
 * @method static void _resetState() Reset the state of the current session.
 *
 * @see SearchCriteriaBuilderManager
 */
class SearchCriteriaBuilder extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return SearchCriteriaBuilderManager::class;
    }
}
