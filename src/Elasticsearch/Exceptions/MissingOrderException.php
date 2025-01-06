<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Exceptions;

use Exception;

/**
 * Class MissingOrderException.
 *
 * Custom exception to handle cases where the "order" parameter is missing
 * during pagination using the `search_after` method in Elasticsearch queries.
 * This exception is thrown when the required `order` parameter is not provided
 * while performing a paginated search.
 */
class MissingOrderException extends ElasticsearchException
{
    /**
     * The exception message.
     *
     * @var string
     */
    protected $message = 'Order parameter is required for pagination using search_after.';
}
