<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Relations;

use Illuminate\Database\Eloquent\Relations\MorphOne as BaseMorphOne;

/**
 * Class MorphOne.
 *
 * Custom implementation of the `MorphOne` relationship for Elasticsearch.
 *
 * This class extends the base `MorphOne` relationship to provide specific Elasticsearch-related behavior.
 * You can extend or override methods for Elasticsearch-specific query logic if needed in the future.
 */
class MorphOne extends BaseMorphOne
{
    // Future customizations for Elasticsearch can be added here
}
