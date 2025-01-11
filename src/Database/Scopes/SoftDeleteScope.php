<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Scopes;

use Illuminate\Database\Eloquent\Builder as BuilderBase;
use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\SoftDeletingScope as BaseSoftDeletingScope;

/**
 * SoftDeleteScope.
 *
 * A global scope to apply soft delete functionality on models.
 * This scope ensures only non-deleted records are queried by default.
 */
class SoftDeleteScope extends BaseSoftDeletingScope
{
    /**
     * apply the scope to a given Eloquent query builder.
     */
    public function apply(BuilderBase $builder, ModelBase $model)
    {
        if ($model->isSoftDeleteEnabled()) {
            $builder->whereNull($model->getQualifiedDeletedAtColumn());
        }
    }
}
