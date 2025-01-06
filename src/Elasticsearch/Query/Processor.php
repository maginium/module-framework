<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Query;

use Illuminate\Database\Query\Processors\Processor as BaseProcessor;

/**
 * Class Processor.
 *
 * Custom processor for handling the results of Elasticsearch queries. Extends the base Processor class
 * from Laravel's database query builder, allowing for potential interception or custom processing of query
 * results that are returned from Elasticsearch. This class is designed to be extended or modified as needed
 * to integrate Elasticsearch-specific result handling.
 */
class Processor extends BaseProcessor
{
    // In case we need to intercept something at some point
}
