<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\HasOne as BaseHasOne;

/**
 * Class HasOne.
 *
 * Custom implementation of the `HasOne` relationship for Elasticsearch.
 *
 * This class overrides the default `HasOne` behavior to provide specific Elasticsearch-based query logic
 * for retrieving related models. It modifies how the existence of related models is checked.
 */
class HasOne extends BaseHasOne
{
    /**
     * Get the key for comparing against the parent key in the "has" query.
     *
     * This key is used in Elasticsearch queries to compare the parent model's key with the related model's
     * foreign key when performing a "has" relationship query.
     *
     * @return string The foreign key name for the relationship.
     */
    public function getHasCompareKey(): string
    {
        return $this->getForeignKeyName();
    }

    /**
     * Get the plain foreign key for the relationship.
     *
     * This method returns the foreign key used to reference the related model in the Elasticsearch index.
     * The foreign key is typically defined in the parent model.
     *
     * @return string The foreign key name.
     */
    public function getForeignKeyName(): string
    {
        return $this->foreignKey;
    }

    /**
     * Get the query to check the existence of the related model in the "has" relationship.
     *
     * This method modifies the `has` query to check for the existence of a related model in Elasticsearch.
     * It uses the `exists` query type to confirm if the related record exists.
     *
     * @param Builder $query The query builder instance for the related model.
     * @param Builder $parentQuery The parent query builder instance.
     * @param array|string $columns The columns to select (default is ['*']).
     *
     * @return Builder The query builder instance after applying the necessary conditions for the "has" query.
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $foreignKey = $this->getForeignKeyName();

        // Apply an Elasticsearch exists query on the foreign key
        return $query->select($foreignKey)->where($foreignKey, 'exists', true);
    }

    /**
     * Determine the method used for a "where in" query.
     *
     * This method is used internally to specify the appropriate query method for filtering related models.
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
