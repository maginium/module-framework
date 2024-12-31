<?php

declare(strict_types=1);

namespace Maginium\Framework\Pagination\Constants;

/**
 * Class holding constant values for pagination-related keys and labels.
 *
 * These constants are used for consistent access to pagination-related keys and labels across the application.
 */
class Paginator
{
    /**
     * Minimum limit for the number of items to retrieve.
     *
     * @var int
     */
    public const MIN_PER_PAGE = 1;

    /**
     * Maximum limit for the number of items to retrieve.
     *
     * @var int
     */
    public const MAX_PER_PAGE = 100;

    /**
     * Default page number.
     *
     * @var int
     */
    public const DEFAULT_PAGE = 1;

    /**
     * Default page size.
     *
     * @var int
     */
    public const DEFAULT_PER_PAGE = 50;

    /**
     * Default page name.
     *
     * @var string
     */
    public const DEFAULT_PAGE_NAME = 'page';

    /**
     * Represents an ellipsis ('...') used in pagination.
     *
     * @var string
     */
    public const ELLIPSIS = '...';

    /**
     * Label for the "Previous" page link.
     *
     * @var string
     */
    public const PREVIOUS_LABEL = 'Previous';

    /**
     * Label for the "Next" page link.
     *
     * @var string
     */
    public const NEXT_LABEL = 'Next';

    /**
     * Key used to refer to the URL in pagination data.
     *
     * @var string
     */
    public const URL = 'url';

    /**
     * Key used to refer to the label in pagination data.
     *
     * @var string
     */
    public const LABEL = 'label';

    /**
     * Key used to indicate if a page is active in pagination data.
     *
     * @var string
     */
    public const ACTIVE = 'active';

    /**
     * Key for the current page number in pagination data.
     *
     * @var string
     */
    public const CURRENT_PAGE = 'current_page';

    /**
     * Key for the data array of items on the current page in pagination.
     *
     * @var string
     */
    public const DATA = 'data';

    /**
     * Key for the first page URL in pagination data.
     *
     * @var string
     */
    public const FIRST_PAGE_URL = 'first_page_url';

    /**
     * Key for the index of the first item on the current page.
     *
     * @var string
     */
    public const FROM = 'from';

    /**
     * Key for the last page number in pagination data.
     *
     * @var string
     */
    public const LAST_PAGE = 'last_page';

    /**
     * Key for the last page URL in pagination data.
     *
     * @var string
     */
    public const LAST_PAGE_URL = 'last_page_url';

    /**
     * Key for the array of pagination links in pagination data.
     *
     * @var string
     */
    public const LINKS = 'links';

    /**
     * Key for the next page URL in pagination data.
     *
     * @var string
     */
    public const NEXT_PAGE_URL = 'next_page_url';

    /**
     * Key for the base path for pagination URLs.
     *
     * @var string
     */
    public const PATH = 'path';

    /**
     * Key for the pageName for pagination.
     *
     * @var string
     */
    public const PAGE_NAME = 'pageName';

    /**
     * Key for the number of items page in pagination data.
     *
     * @var string
     */
    public const PAGE = 'page';

    /**
     * Key for the number of items per page in pagination data.
     *
     * @var string
     */
    public const PER_PAGE = 'per_page';

    /**
     * Key for the previous page URL in pagination data.
     *
     * @var string
     */
    public const PREV_PAGE_URL = 'prev_page_url';

    /**
     * Key for the index of the last item on the current page.
     *
     * @var string
     */
    public const TO = 'to';

    /**
     * Key for the total number of items in pagination data.
     *
     * @var string
     */
    public const TOTAL = 'total';

    /**
     * Key for the first page element in pagination data.
     *
     * @var string
     */
    public const FIRST = 'first';

    /**
     * Key for the slider (middle pages) in pagination data.
     *
     * @var string
     */
    public const SLIDER = 'slider';

    /**
     * Key for the last page element in pagination data.
     *
     * @var string
     */
    public const LAST = 'last';

    /**
     * Key for the next cursor in pagination data.
     *
     * @var string
     */
    public const NEXT_CURSOR = 'next_cursor';

    /**
     * Key for the previous cursor in pagination data.
     *
     * @var string
     */
    public const PREV_CURSOR = 'prev_cursor';

    /**
     * Key for the items in pagination data.
     *
     * @var string
     */
    public const ITEMS = 'items';

    /**
     * Key for the options in pagination data.
     *
     * @var string
     */
    public const OPTIONS = 'options';
}
