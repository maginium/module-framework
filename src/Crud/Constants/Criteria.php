<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Constants;

/**
 * Class Criteria.
 */
class Criteria
{
    /**
     * Default key used for retrieving models.
     */
    public const DEFAULT_KEY = 'id';

    /**
     * Key for sorting criteria.
     */
    public const KEY_SORTS = 'sorts';

    /**
     * Key for filtering criteria.
     */
    public const KEY_FILTERS = 'filters';

    /**
     * Key for the search term criteria.
     */
    public const KEY_SEARCH_TERM = 'search_term';

    /**
     * Key for the search fields criteria.
     */
    public const KEY_SEARCH_FIELDS = 'search_fields';

    /**
     * Key for the items in the search result.
     */
    public const KEY_ITEMS = 'items';

    /**
     * Key for the total number of items in the search result.
     */
    public const KEY_TOTAL = 'total';

    /**
     * Key for the metadata in the search result.
     */
    public const KEY_META = 'meta';

    /**
     * Key for the page number in the metadata.
     */
    public const KEY_PAGE = 'page';

    /**
     * Key for the limit (number of items per page) in the metadata.
     */
    public const KEY_PER_PAGE = 'per_page';

    /**
     * Key for the total number of pages in the metadata.
     */
    public const KEY_TOTAL_PAGES = 'total_pages';

    /**
     * Key for attribute criteria.
     */
    public const KEY_ATTRIBUTE = 'attribute';

    /**
     * Key for the field criteria.
     */
    public const KEY_FIELD = 'field';

    /**
     * Key for the value criteria.
     */
    public const KEY_VALUE = 'value';

    /**
     * Key for the condition criteria.
     */
    public const KEY_CONDITION = 'condition';

    /**
     * Key for the condition direction.
     */
    public const KEY_DIRECTION = 'direction';
}
