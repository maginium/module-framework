<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Sorts\Strategies;

use Maginium\Framework\Crud\Sorts\AbstractSort;
use Maginium\Framework\Database\Eloquent\Builder;
use Maginium\Framework\Database\Eloquent\Model;

/**
 * Class HasOneSort.
 *
 * A strategy class for applying sorting on "HasOne" relationships in Eloquent models.
 *
 * This class is used to sort results based on a related model in a "has one" relationship.
 * It retrieves the related model and orders the results based on a column in the related model's table.
 * The sorting can be done in either ascending or descending order.
 */
class HasOneSort extends AbstractSort
{
    /**
     * Apply sorting to the query for "HasOne" relationships.
     *
     * This method constructs a query that orders the results of a "has one" relationship
     * based on a specified column in the related model's table. It uses a subquery to select the
     * value from the related model's table and orders the results accordingly.
     *
     * @return Builder The modified query builder with applied sorting.
     */
    public function apply(): Builder
    {
        // Get the related model for the "has one" relationship.
        /** @var Model $relatedModel */
        $relatedModel = $this->model->{$this->relationName}()->getRelated();

        // Get the qualified foreign key name used in the "has one" relationship.
        $foreignKeyKey = $this->model->{$this->relationName}()->getQualifiedForeignKeyName();

        // Get the qualified local key name used in the "has one" relationship (the parent's key).
        $localKey = $this->model->{$this->relationName}()->getQualifiedParentKeyName();

        // Get the related model's table name.
        $relatedTable = $relatedModel->getTable();

        // Build the query to order by the specified column in the related model's table.
        return $this->query->orderBy(
            // Subquery to select the related model's column for sorting.
            $relatedModel::query()
                ->select("{$relatedTable}.{$this->column}") // Select the sorting column from the related table.
                ->whereColumn($localKey, $foreignKeyKey) // Join condition: match the local key to the foreign key.
                ->orderByRaw("{$relatedTable}.{$this->column} {$this->direction}") // Apply ordering to the column.
                ->limit(1), // Limit the result to one value for sorting.
            $this->direction, // The direction of the order (ascending or descending).
        );
    }
}
