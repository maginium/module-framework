<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BaseBelongsTo;

/**
 * Class BelongsTo.
 *
 * Extends the `BelongsTo` relationship to provide custom query constraints and eager loading logic for Elasticsearch.
 * This class customizes the default behavior of the `BelongsTo` relationship in Laravel to support Elasticsearch-specific needs.
 */
class BelongsTo extends BaseBelongsTo
{
    /**
     * Get the key used for comparison in the relationship.
     *
     * This method returns the key used to compare the `ownerKey` for the `BelongsTo` relationship.
     *
     * @return string The key used for comparison.
     */
    public function getHasCompareKey(): string
    {
        return $this->ownerKey;
    }

    /**
     * Add constraints to the relationship query.
     *
     * This method adds the necessary constraints to the query for the `BelongsTo` relationship.
     * It ensures that the foreign key is compared to the owner key in the query.
     *
     * @return void
     */
    public function addConstraints(): void
    {
        if (static::$constraints) {
            $this->query->where($this->ownerKey, '=', $this->parent->{$this->foreignKey});
        }
    }

    /**
     * Add eager loading constraints to the relationship query.
     *
     * This method modifies the query for eager loading, ensuring the correct model keys are used for the `BelongsTo` relationship.
     *
     * @param array $models The models to be eager loaded.
     *
     * @return void
     */
    public function addEagerConstraints(array $models): void
    {
        $this->query->whereIn($this->ownerKey, $this->getEagerModelKeys($models));
    }

    /**
     * Get the query for the existence of the relationship.
     *
     * This method returns the query used to check the existence of the relationship, without applying any specific query logic.
     * This could be useful for situations where the relationship's existence needs to be checked directly in the query.
     *
     * @param Builder $query The query builder instance for the related model.
     * @param Builder $parentQuery The query builder instance for the parent model.
     * @param array $columns The columns to select.
     *
     * @return Builder The modified query builder.
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        return $query;
    }

    /**
     * Define the method to use for `whereIn` in the query.
     *
     * This method returns the method name used for applying the `whereIn` condition in the query.
     * It can be overridden to use custom methods when necessary.
     *
     * @param EloquentModel $model The related model.
     * @param mixed $key The key to use in the `whereIn` query.
     *
     * @return string The name of the method to use for the `whereIn` query.
     */
    protected function whereInMethod(EloquentModel $model, $key): string
    {
        return 'whereIn';
    }
}
