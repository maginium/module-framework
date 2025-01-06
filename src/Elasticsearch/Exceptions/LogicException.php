<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Exceptions;

/**
 * Class LogicException.
 *
 * Custom exception for logic errors related to Elasticsearch operations.
 * This exception extends the base `ElasticsearchException` class and is used
 * to handle scenarios where there is a logical error in the processing or
 * execution of Elasticsearch queries, typically when the state of the
 * application or the Elasticsearch environment does not meet expectations.
 */
class LogicException extends ElasticsearchException
{
    // This class currently extends ElasticsearchException without adding extra logic.
    // It may be extended further in the future to customize the behavior of the exception.
}
