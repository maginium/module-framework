<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Exceptions;

use Maginium\Foundation\Exceptions\Exception;

/**
 * Class ElasticsearchException.
 *
 * Base exception class for handling Elasticsearch-related errors.
 * This exception serves as the foundation for other, more specific
 * exceptions that may be thrown during Elasticsearch operations, such as
 * query errors, connection issues, or logic-related issues.
 * It extends the built-in `Exception` class and can be further customized
 * to capture specific error details or context related to Elasticsearch.
 */
class ElasticsearchException extends Exception
{
    // This class extends the built-in Exception class without adding any extra functionality for now.
    // It acts as a base class for all Elasticsearch-related exceptions, providing a foundation for future customizations.
}
