<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Relations;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\MorphMany as BaseMorphMany;

/**
 * Class MorphMany.
 *
 * Custom implementation of the `MorphMany` relationship for Elasticsearch.
 *
 * This class overrides the default `MorphMany` behavior to provide specific Elasticsearch-based query logic,
 * particularly for filtering related models using a "whereIn" condition.
 */
class MorphMany extends BaseMorphMany
{
    /**
     * Determine the method used for a "where in" query.
     *
     * This method is used to specify the appropriate query method for filtering related models in the
     * Elasticsearch context. The "whereIn" method is used to filter models based on a list of related keys.
     *
     * @param EloquentModel $model The model instance for the parent model.
     * @param string $key The key to be used in the "where in" condition.
     *
     * @return string The query method to use, in this case 'whereIn'.
     */
    protected function whereInMethod(EloquentModel $model, $key): string
    {
        return 'whereIn';
    }
}
