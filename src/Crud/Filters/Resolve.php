<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters;

use Closure;
use Exception;
use Maginium\Framework\Crud\Exceptions\FieldNotSupported;
use Maginium\Framework\Crud\Exceptions\NoOperatorMatch;
use Maginium\Framework\Crud\Exceptions\OperatorNotSupported;
use Maginium\Framework\Crud\Interfaces\FilterInterface;
use Maginium\Framework\Database\Eloquent\Builder;
use Maginium\Framework\Database\Eloquent\Model;
use Maginium\Framework\Support\Facades\Container;

/**
 * Class Resolve.
 *
 * Resolves and applies filters to Eloquent queries by dynamically validating,
 * resolving relations, and applying operators. Supports nested relations and
 * flexible filtering strategies.
 */
class Resolve
{
    /**
     * List of relation chains and the target column.
     *
     * @var array<string>
     */
    private array $fields = [];

    /**
     * List of available filters for filtering operations.
     *
     * @var FilterList
     */
    private FilterList $filterList;

    /**
     * Current Eloquent model instance.
     *
     * @var Model
     */
    private Model $model;

    /**
     * Stack of previous models for nested relations.
     *
     * @var array<Model>
     */
    private array $previousModels = [];

    /**
     * Resolve constructor.
     *
     * @param FilterList $filterList Instance of FilterList containing available filters.
     * @param Model      $model      Current model to be used for filtering.
     */
    public function __construct(FilterList $filterList, Model $model)
    {
        $this->filterList = $filterList;
        $this->model = $model;
    }

    /**
     * Applies the filter to the Eloquent query.
     *
     * @param Builder      $query Query builder instance.
     * @param string       $field Target field for filtering.
     * @param array|string $values Values or operators to be applied.
     *
     * @throws Exception If validation or filter application fails.
     */
    public function apply(Builder $query, string $field, array|string $values): void
    {
        if (! $this->safe(fn() => $this->validate([$field => $values]))) {
            return;
        }

        $this->filter($query, $field, $values);
    }

    /**
     * Safely executes a function, suppressing exceptions if configured.
     *
     * @param Closure $closure Function to be executed.
     *
     * @throws Exception If an exception occurs and silent mode is disabled.
     *
     * @return bool True if successful, false if exceptions are suppressed.
     */
    private function safe(Closure $closure): bool
    {
        try {
            $closure();

            return true;
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Validates the provided filter values.
     *
     * @param array|string $values Filter values or operators to validate.
     *
     * @throws NoOperatorMatch If no matching operator is found.
     */
    private function validate(array|string $values = []): void
    {
        if (empty($values) || Validator::isString($values)) {
            throw NoOperatorMatch::make($this->filterList->keys());
        }

        if (! in_array(key($values), $this->filterList->keys())) {
            $this->validate(array_values($values)[0]);
        }
    }

    /**
     * Filters the query using the specified field and filters.
     *
     * @param Builder           $query   Query builder instance.
     * @param string            $field   Target field for filtering.
     * @param array|string|null $filters Filter values or operators.
     *
     * @throws Exception If the filter cannot be applied.
     */
    private function filter(Builder $query, string $field, array|string|null $filters): void
    {
        $filters = Validator::isArray($filters) ? $filters : [$filters];

        if ($this->filterList->get($field) !== null) {
            $this->safe(fn() => $this->applyFilterStrategy($query, $field, $filters));
        } else {
            $this->safe(fn() => $this->applyRelationFilter($query, $field, $filters));
        }
    }

    /**
     * Applies a filter using a specific operator.
     *
     * @param Builder $query    Query builder instance.
     * @param string  $operator Filter operator.
     * @param array   $filters  Filter values.
     */
    private function applyFilterStrategy(Builder $query, string $operator, array $filters): void
    {
        /** @var class-string<FilterInterface> $filter */
        $filter = $this->filterList->get($operator);
        $field = end($this->fields);

        $callback = Container::resolve($filter)->apply(['query' => $query, 'column' => $field, 'values' => $filters]);
        $this->filterRelations($query, $callback);
    }

    /**
     * Filters nested relations, if applicable.
     *
     * @param Builder $query    Query builder instance.
     * @param Closure $callback Callback to apply filtering.
     */
    private function filterRelations(Builder $query, Closure $callback): void
    {
        array_pop($this->fields);
        $this->applyRelations($query, $callback);
    }

    /**
     * Applies filters to nested relations.
     *
     * @param Builder $query    Query builder instance.
     * @param Closure $callback Callback to apply filtering.
     */
    private function applyRelations(Builder $query, Closure $callback): void
    {
        if (empty($this->fields)) {
            $callback($query);
        } else {
            $this->relation($query, $callback);
        }
    }

    /**
     * Resolves and applies nested relation filters.
     *
     * @param Builder $query    Query builder instance.
     * @param Closure $callback Callback for nested filtering.
     */
    private function relation(Builder $query, Closure $callback): void
    {
        $field = array_shift($this->fields);
        $query->whereHas($field, fn($subQuery) => $this->applyRelations($subQuery, $callback));
    }

    /**
     * Applies a relation filter to the query.
     *
     * @param Builder $query   Query builder instance.
     * @param string  $field   Target relation field.
     * @param array   $filters Filters to apply.
     *
     * @throws Exception If validation fails.
     */
    private function applyRelationFilter(Builder $query, string $field, array $filters): void
    {
        foreach ($filters as $subField => $subFilter) {
            $this->prepareModelForRelation($field);
            $this->validateField($field);
            $this->validateOperator($field, $subField);

            $this->fields[] = $this->model->getField($field);
            $this->filter($query, $subField, $subFilter);
        }

        $this->restorePreviousModel();
    }

    /**
     * Prepares the model for a nested relation.
     *
     * @param string $field Target relation field.
     */
    private function prepareModelForRelation(string $field): void
    {
        $relation = end($this->fields);

        if ($relation !== false) {
            $this->previousModels[] = $this->model;
            $this->model = $this->model->{$relation}()->getRelated();
        }
    }

    /**
     * Restores the previous model after processing a nested relation.
     */
    private function restorePreviousModel(): void
    {
        array_pop($this->fields);

        if (! empty($this->previousModels)) {
            $this->model = array_pop($this->previousModels);
        }
    }

    /**
     * Validates the target field for filtering.
     *
     * @param string $field Field to validate.
     *
     * @throws FieldNotSupported If the field is not supported by the model.
     */
    private function validateField(string $field): void
    {
        $availableFields = $this->model->availableFields();

        if (! in_array($field, $availableFields)) {
            throw FieldNotSupported::make($field, $this->model::class, $availableFields);
        }
    }

    /**
     * Validates the operator for a specific field.
     *
     * @param string $field    Target field.
     * @param string $operator Operator to validate.
     *
     * @throws OperatorNotSupported If the operator is not supported for the field.
     */
    private function validateOperator(string $field, string $operator): void
    {
        $availableFilters = $this->model->getAvailableFiltersFor($field);

        if (! $availableFilters || in_array($operator, $availableFilters)) {
            return;
        }

        throw OperatorNotSupported::make($field, $operator, $availableFilters);
    }
}
