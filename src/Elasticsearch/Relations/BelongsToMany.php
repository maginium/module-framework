<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as BaseBelongsToMany;
use Maginium\Framework\Elasticsearch\Eloquent\Model;
use RuntimeException;

/**
 * Class BelongsToMany.
 *
 * Custom implementation of the `BelongsToMany` relationship for Elasticsearch.
 *
 * This class overrides the default `BelongsToMany` behavior to throw an exception, as `BelongsToMany` relationships
 * are not supported in the current package. Instead, it suggests using models as pivot tables and `HasMany` relations
 * for better compatibility with Elasticsearch.
 */
class BelongsToMany extends BaseBelongsToMany
{
    /**
     * BelongsToMany constructor.
     *
     * @param Builder $query The query builder instance for the related model.
     * @param Model $parent The parent model instance.
     * @param string $table The pivot table name.
     * @param string $foreignPivotKey The foreign key of the parent model in the pivot table.
     * @param string $relatedPivotKey The foreign key of the related model in the pivot table.
     * @param string $parentKey The parent model key.
     * @param string $relatedKey The related model key.
     * @param string|null $relationName The name of the relationship, if any.
     *
     * @throws RuntimeException This exception is thrown because `BelongsToMany` is not supported in this package.
     */
    public function __construct(
        Builder $query,
        Model $parent,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null,
    ) {
        parent::__construct($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);

        // Throw an exception as BelongsToMany is not supported
        throw new RuntimeException('BelongsToMany relation is currently not supported for this package. You can create a model as a pivot table and use HasMany relations to that instead.');
    }
}
