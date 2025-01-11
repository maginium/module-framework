<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters;

use Maginium\Framework\Crud\Interfaces\FilterInterface;
use Maginium\Framework\Support\Reflection;

/**
 * Manages a list of filters for CRUD operations.
 *
 * This class dynamically loads default and custom filters, allows
 * retrieval of filters by their operator, and provides utility methods
 * for filtering and extracting filter keys.
 */
class FilterList
{
    /**
     * List of registered filters, keyed by their operators.
     *
     * @var array<string, class-string<FilterInterface>>
     */
    public array $filters;

    /**
     * Initializes the filter list by loading default and custom filters.
     *
     * The constructor can accept an optional array of custom filters. If no
     * filters are provided, it initializes with an empty array.
     *
     * @param array<string, class-string<FilterInterface>> $filters An optional array of filters to register.
     */
    public function __construct(array $filters = [])
    {
        // Initialize the filters array with the given filters
        $this->filters = $filters;
    }

    /**
     * Retrieves a filter class by its operator.
     *
     * @param string $operator The operator used to identify the filter.
     *
     * @return class-string<FilterInterface>|null The filter class, or null if not found.
     */
    public function get(string $operator): ?string
    {
        return $this->filters[$operator] ?? null;
    }

    /**
     * Filters the registered filters to include only the specified ones.
     *
     * @param array<string|class-string<Filter>> $filters List of filter operators or class names.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function only(array $filters): self
    {
        foreach ($filters as $key => $filter) {
            // If the class is a subclass of the base Filter class, replace it with its operator.
            if (Reflection::isSubclassOf($filter, Filter::class)) {
                $filters[$key] = $filter::operator();
            }
        }

        // Retain only the specified filters.
        $this->filters = collect($this->filters)->only($filters)->all();

        return $this;
    }

    /**
     * Retrieves all registered filter operators.
     *
     * @return array<string> List of filter operators.
     */
    public function keys(): array
    {
        return array_keys($this->filters);
    }
}
