<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Sorts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Maginium\Framework\Crud\Exceptions\FieldNotSupported;
use Maginium\Framework\Crud\Exceptions\RelationshipNotSupport;
use Maginium\Framework\Crud\Helpers\Column;
use Maginium\Framework\Crud\Sorts\Strategies\BelongsToManySortFactory;
use Maginium\Framework\Crud\Sorts\Strategies\BelongsToSortFactory;
use Maginium\Framework\Crud\Sorts\Strategies\HasManySortFactory;
use Maginium\Framework\Crud\Sorts\Strategies\HasOneSortFactory;
use Maginium\Framework\Crud\Sorts\Strategies\NullSortFactory;
use Maginium\Framework\Database\Eloquent\Builder;
use Maginium\Framework\Database\Eloquent\Model;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use ReflectionMethod;

/**
 * Class Sort.
 *
 * Handles sorting of database queries, supporting both direct column sorting
 * and relationship-based sorting strategies. Uses factory pattern to dynamically
 * create sorting strategies based on the relationship type.
 */
class Sort
{
    /**
     * The direction of sorting (e.g., ascending or descending).
     *
     * @var string
     */
    protected string $direction;

    /**
     * The related model for the relationship being sorted, if applicable.
     *
     * @var Model|null
     */
    protected ?Model $relation = null;

    /**
     * The name of the relationship for sorting, if applicable.
     *
     * @var string
     */
    protected string $relationName;

    /**
     * The name of the column to be sorted.
     *
     * @var string
     */
    protected string $column;

    /**
     * The field associated with the column to determine sorting criteria.
     *
     * @var string
     */
    protected string $field;

    /**
     * The query builder instance used for constructing the database query.
     *
     * @var Builder
     */
    protected Builder $query;

    /**
     * The model instance that represents the main entity being sorted.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Factory for creating instances of the HasOne sort strategy.
     *
     * @var HasOneSortFactory
     */
    private HasOneSortFactory $hasOneSortFactory;

    /**
     * Factory for creating instances of the HasMany sort strategy.
     *
     * @var HasManySortFactory
     */
    private HasManySortFactory $hasManySortFactory;

    /**
     * Factory for creating instances of the BelongsTo sort strategy.
     *
     * @var BelongsToSortFactory
     */
    private BelongsToSortFactory $belongsToSortFactory;

    /**
     * Factory for creating instances of the BelongsToMany sort strategy.
     *
     * @var BelongsToManySortFactory
     */
    private BelongsToManySortFactory $belongsToManySortFactory;

    /**
     * Factory for creating instances of the Null sort strategy.
     *
     * @var NullSortFactory
     */
    private NullSortFactory $nullSortFactory;

    /**
     * Sort constructor.
     *
     * @param string $column The column name to sort by.
     * @param string $field The field name associated with sorting.
     * @param Builder $query The query builder instance.
     * @param Model $model The model instance to perform the sort on.
     * @param HasOneSortFactory $hasOneSortFactory Factory for creating HasOne sort strategy.
     * @param HasManySortFactory $hasManySortFactory Factory for creating HasMany sort strategy.
     * @param BelongsToSortFactory $belongsToSortFactory Factory for creating BelongsTo sort strategy.
     * @param BelongsToManySortFactory $belongsToManySortFactory Factory for creating BelongsToMany sort strategy.
     * @param NullSortFactory $nullSortFactory Factory for creating Null sort strategy.
     */
    public function __construct(
        Model $model,
        string $field,
        string $column,
        Builder $query,
        NullSortFactory $nullSortFactory,
        HasOneSortFactory $hasOneSortFactory,
        HasManySortFactory $hasManySortFactory,
        BelongsToSortFactory $belongsToSortFactory,
        BelongsToManySortFactory $belongsToManySortFactory,
    ) {
        $this->model = $model;
        $this->field = $field;
        $this->column = $column;
        $this->query = $query;
        $this->nullSortFactory = $nullSortFactory;
        $this->hasOneSortFactory = $hasOneSortFactory;
        $this->hasManySortFactory = $hasManySortFactory;
        $this->belongsToSortFactory = $belongsToSortFactory;
        $this->belongsToManySortFactory = $belongsToManySortFactory;

        // Determine the relationship name, if any
        $this->setRelationName();

        // Set the related model
        $this->setRelation();

        // Set the sorting direction
        $this->setDirection();

        // Validate and set the sorting field
        $this->setField();
    }

    /**
     * Applies sorting to the query.
     *
     * Determines whether to sort by column or by relationship based on the
     * presence of a related model.
     *
     * @return Builder The query builder with the sorting applied.
     */
    public function __invoke(): Builder
    {
        // Check if sorting involves a relationship or a direct column
        return $this->relation ? $this->sortByRelation() : $this->sortByColumn();
    }

    /**
     * Sorts the query by a specified column.
     *
     * Uses the NullSort strategy to handle null values during sorting.
     *
     * @return Builder The query builder with column sorting applied.
     */
    public function sortByColumn(): Builder
    {
        // Create a NullSort strategy instance
        $nullSort = $this->nullSortFactory->create([
            'query' => $this->query,
            'column' => $this->column,
            'direction' => $this->direction,
        ]);

        // Apply the NullSort strategy
        return $nullSort->apply();
    }

    /**
     * Sorts the query based on a related model's relationship type.
     *
     * Dynamically determines the relationship type and delegates the sorting
     * to the appropriate strategy factory.
     *
     * @throws RelationshipNotSupport If the relationship type is unsupported.
     *
     * @return Builder The query builder with relationship-based sorting applied.
     */
    public function sortByRelation(): Builder
    {
        // Reflect the method to retrieve relationship metadata
        $method = new ReflectionMethod($this->model, $this->relationName);

        // Get the relationship type
        $type = $method->getReturnType()?->getName();

        // Ensure null values are ordered appropriately
        $this->query->orderByRaw("{$this->field} is null");

        // Delegate sorting to the appropriate strategy factory based on relationship type
        return match ($type) {
            HasOne::class => $this->hasOneSortFactory->create([
                'field' => $this->field,
                'query' => $this->query,
                'model' => $this->model,
                'direction' => $this->direction,
                'relationName' => $this->relationName,
            ])->apply(),

            HasMany::class => $this->hasManySortFactory->create([
                'field' => $this->field,
                'query' => $this->query,
                'model' => $this->model,
                'direction' => $this->direction,
                'relationName' => $this->relationName,
            ])->apply(),

            BelongsTo::class => $this->belongsToSortFactory->create([
                'field' => $this->field,
                'query' => $this->query,
                'model' => $this->model,
                'direction' => $this->direction,
                'relationName' => $this->relationName,
            ])->apply(),

            BelongsToMany::class => $this->belongsToManySortFactory->create([
                'field' => $this->field,
                'query' => $this->query,
                'model' => $this->model,
                'direction' => $this->direction,
                'relationName' => $this->relationName,
            ])->apply(),

            // Throw exception for unsupported relationships
            default => throw RelationshipNotSupport::make(),
        };
    }

    /**
     * Set the relation name for sorting.
     *
     * This method checks if the field has a relationship defined. If it does, it sets
     * the relation name by extracting the part before the first dot.
     */
    private function setRelationName(): void
    {
        // Check if the field has a relationship and set the relation name.
        if ($this->checkFieldHasRelationship()) {
            $this->relationName = Str::before($this->field, '.');
        }
    }

    /**
     * Set the relation model for sorting.
     *
     * If the field contains a relationship, this method sets the related model by
     * invoking the relationship method on the model.
     */
    private function setRelation(): void
    {
        // Check if the field has a relationship and set the relation model.
        if ($this->checkFieldHasRelationship()) {
            $relationName = Str::before($this->field, '.');
            $this->relation = $this->model->{$relationName}()->getRelated();
        }
    }

    /**
     * Set the sorting direction based on the field.
     *
     * The direction is determined by checking if the field ends with ':desc'.
     * If it does, the direction is set to 'desc', otherwise, it's set to 'asc'.
     */
    private function setDirection(): void
    {
        // Set the direction based on the field (either 'asc' or 'desc').
        $this->direction = Str::of($this->field)->lower()->endsWith(':desc') ? 'desc' : 'asc';
    }

    /**
     * Set the field for sorting.
     *
     * If the field contains a relationship, this method verifies that the field is
     * valid for sorting by checking against the available fields in the related model.
     *
     * @throws FieldNotSupported If the field is not valid for sorting.
     */
    private function setField(): void
    {
        // Check if the field has a relationship.
        if ($this->checkFieldHasRelationship()) {
            // Get the available columns for sorting in the related model.
            $validFields = Column::getAvailableSortColumns($this->relation);
            // Extract the field name between the dots and the colon.
            $field = Str::between($this->field, '.', ':');

            // Ensure the field is valid for sorting, otherwise throw an exception.
            throw_unless(in_array($field, $validFields), FieldNotSupported::make($field, class_basename($this->model), $validFields));

            // Set the real field name if necessary.
            $this->field = $this->realName($validFields, $field);
        }
    }

    /**
     * Get the real name of the field from the list of valid fields.
     *
     * If the field name exists in the list of valid fields, returns the real name.
     * Otherwise, returns the field as it is.
     *
     * @param array $fields The valid fields.
     * @param string $field The field name to get the real name for.
     *
     * @return string The real name of the field.
     */
    private function realName(array $fields, string $field): string
    {
        // Search for the field in the list of valid fields and return the real name.
        $real = Arr::search($field, $fields, true);

        // If the field is found, return it; otherwise, return the original field.
        return Validator::isInt($real) ? $field : $real;
    }

    /**
     * Check if the field contains a relationship.
     *
     * Determines if the field contains a dot (indicating a relationship) to check if
     * sorting should be applied to a related model.
     *
     * @return bool True if the field contains a relationship; otherwise, false.
     */
    private function checkFieldHasRelationship(): bool
    {
        // Return true if the field contains a dot (indicating a relationship).
        return Str::contains($this->field, '.');
    }
}
