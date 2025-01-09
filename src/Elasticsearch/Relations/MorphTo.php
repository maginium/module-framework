<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Relations;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\MorphTo as BaseMorphTo;
use Maginium\Framework\Database\Eloquent\Collection;

/**
 * Class MorphTo.
 *
 * Custom implementation of the `MorphTo` relationship for Elasticsearch.
 *
 * This class extends the base `MorphTo` relationship to add Elasticsearch-specific behavior.
 * It overrides methods to customize the relationship logic, such as adding constraints and retrieving related results.
 */
class MorphTo extends BaseMorphTo
{
    /**
     * Add necessary constraints to the query.
     *
     * This method adds constraints for the `MorphTo` relationship by matching the owner key with
     * the foreign key from the parent model.
     *
     * {@inheritdoc}
     */
    public function addConstraints(): void
    {
        if (static::$constraints) {
            $this->query->where($this->ownerKey, '=', $this->getForeignKeyFrom($this->parent));
        }
    }

    /**
     * Get the related results based on the type.
     *
     * This method fetches the related models by their type and key, performing a `whereIn` query
     * to retrieve the related results.
     *
     * {@inheritdoc}
     *
     * @param string $type The type of the related model to query for.
     *
     * @return Collection The collection of related models.
     */
    protected function getResultsByType($type): Collection
    {
        $instance = $this->createModelByType($type);

        $key = $instance->getKeyName();

        $query = $instance->newQuery();

        return $query->whereIn($key, $this->gatherKeysByType($type, $instance->getKeyType()))->get();
    }

    /**
     * Define the method to perform `whereIn` on the query.
     *
     * This method overrides the default `whereIn` method to apply the necessary logic for the `MorphTo`
     * relationship in Elasticsearch.
     *
     * @param EloquentModel $model The model instance.
     * @param mixed $key The key for the `whereIn` condition.
     *
     * @return string The method to use for the `whereIn` query.
     */
    protected function whereInMethod(EloquentModel $model, $key): string
    {
        return 'whereIn';
    }
}
