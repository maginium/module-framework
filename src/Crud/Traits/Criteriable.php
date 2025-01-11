<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Traits;

use Closure;
use Maginium\Framework\Crud\Exceptions\CriterionException;
use Maginium\Framework\Crud\Exceptions\RepositoryException;
use Maginium\Framework\Crud\Interfaces\Repositories\CriterionInterface;
use Maginium\Framework\Crud\Interfaces\RepositoryInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;

trait Criteriable
{
    /**
     * List of repository criteria.
     *
     * @var array
     */
    protected array $criteria = [];

    /**
     * List of default repository criteria.
     *
     * @var array
     */
    protected array $defaultCriteria = [];

    /**
     * Skip criteria flag.
     * If setted to true criteria will not be apply to the query.
     *
     * @var bool
     */
    protected bool $skipCriteria = false;

    /**
     * Skip default criteria flag.
     * If setted to true default criteria will not be added to the criteria list.
     *
     * @var bool
     */
    protected bool $skipDefaultCriteria = false;

    /**
     * Return the name of the criterion.
     * If the parameter is a string, assume it is the criterion's class name.
     * If the parameter is a closure, use its unique object hash as the name.
     *
     * @param CriterionInterface|Closure|string $criteria The criterion to retrieve the name for.
     *
     * @return string The name of the criterion.
     */
    public function getCriterionName($criteria): string
    {
        if ($criteria instanceof Closure) {
            // Generate a unique hash for the closure.
            return spl_object_hash($criteria);
        }

        return Validator::isObject($criteria) ? get_class($criteria) : $criteria; // Return class name if object, else the string itself.
    }

    /**
     * Add a single criterion to the criteria list.
     *
     * @param CriterionInterface|Closure|array|string $criterion The criterion to add.
     *
     * @return $this
     */
    public function pushCriterion($criterion)
    {
        // Add criterion to the criteria list.
        $this->addCriterion($criterion, 'criteria');

        return $this;
    }

    /**
     * Remove a specific criterion from the criteria list.
     *
     * @param CriterionInterface|Closure|string $criterion The criterion to remove.
     *
     * @return $this
     */
    public function removeCriterion($criterion)
    {
        // Remove the criterion by its name.
        unset($this->criteria[$this->getCriterionName($criterion)]);

        return $this;
    }

    /**
     * Remove multiple criteria from the criteria list.
     *
     * @param array $criteria List of criteria to remove.
     *
     * @return RepositoryInterface
     */
    public function removeCriteria(array $criteria)
    {
        Arr::walk($criteria, function($criterion): void {
            // Remove each criterion in the array.
            $this->removeCriterion($criterion);
        });

        return $this;
    }

    /**
     * Add multiple criteria to the criteria list.
     *
     * @param array $criteria List of criteria to add.
     *
     * @return $this
     */
    public function pushCriteria(array $criteria)
    {
        // Add all criteria to the list.
        $this->addCriteria($criteria, 'criteria');

        return $this;
    }

    /**
     * Clear the criteria list.
     * Clears the list only if criteria are not skipped.
     *
     * @return $this
     */
    public function flushCriteria()
    {
        if (! $this->skipCriteria) {
            $this->criteria = []; // Reset the criteria list to an empty array.
        }

        return $this;
    }

    /**
     * Set the default criteria list.
     *
     * @param array $criteria List of default criteria.
     *
     * @return $this
     */
    public function setDefaultCriteria(array $criteria)
    {
        // Add criteria to the default criteria list.
        $this->addCriteria($criteria, 'defaultCriteria');

        return $this;
    }

    /**
     * Retrieve the default criteria list.
     *
     * @return array The default criteria list.
     */
    public function getDefaultCriteria(): array
    {
        return $this->defaultCriteria; // Return the default criteria.
    }

    /**
     * Retrieve the current list of criteria.
     *
     * @return array The active criteria list.
     */
    public function getCriteria(): array
    {
        if ($this->skipCriteria) {
            return []; // Return an empty list if criteria are skipped.
        }

        // Merge default criteria with current criteria if skipDefaultCriteria is false.
        return $this->skipDefaultCriteria ? $this->criteria : Arr::merge($this->getDefaultCriteria(), $this->criteria);
    }

    /**
     * Toggle the skipCriteria flag.
     * When true, criteria will not be applied.
     *
     * @param bool|true $flag The value to set the flag.
     *
     * @return $this
     */
    public function skipCriteria($flag = true)
    {
        $this->skipCriteria = $flag; // Set the skipCriteria flag.

        return $this;
    }

    /**
     * Toggle the skipDefaultCriteria flag.
     * When true, default criteria will not be included.
     *
     * @param bool|true $flag The value to set the flag.
     *
     * @return $this
     */
    public function skipDefaultCriteria($flag = true)
    {
        $this->skipDefaultCriteria = $flag; // Set the skipDefaultCriteria flag.

        return $this;
    }

    /**
     * Check if a given criterion exists in the criteria list.
     *
     * @param CriterionInterface|Closure|string $criterion The criterion to check for.
     *
     * @return bool True if the criterion exists, false otherwise.
     */
    public function hasCriterion($criterion): bool
    {
        // Check if the criterion exists.
        return isset($this->getCriteria()[$this->getCriterionName($criterion)]);
    }

    /**
     * Return a criterion object or closure from the criteria list by its name.
     *
     * @param mixed $criterion The name or identifier of the criterion to retrieve.
     *
     * @return CriterionInterface|Closure|null The criterion object or closure if found, or `null` if it doesn't exist.
     */
    public function getCriterion($criterion)
    {
        // Check if the specified criterion exists in the criteria list.
        if ($this->hasCriterion($criterion)) {
            // Retrieve the criterion object or closure from the criteria list by its normalized name.
            return $this->getCriteria()[$this->getCriterionName($criterion)];
        }
    }

    /**
     * Apply the criteria list to the provided query object.
     *
     * @param mixed $query      The query object to which the criteria will be applied.
     * @param mixed $repository The repository instance associated with the query.
     *
     * @return mixed The modified query object after applying all criteria.
     */
    public function applyCriteria($query, $repository)
    {
        // Iterate through each criterion in the criteria list.
        foreach ($this->getCriteria() as $criterion) {
            // If the criterion implements CriterionInterface, apply it to the query.
            if ($criterion instanceof CriterionInterface) {
                $query = $criterion->apply($query, $repository);
            }
            // If the criterion is a closure, invoke it with the query and repository.
            elseif ($criterion instanceof Closure) {
                $query = $criterion($query, $repository);
            }
        }

        // Return the modified query object.
        return $query;
    }

    /**
     * Attempt to instantiate a criterion class with the provided arguments.
     *
     * @param string $class      The fully qualified class name of the criterion.
     * @param array  $arguments  The arguments to pass to the criterion's constructor.
     *
     * @throws CriterionException If the class does not implement CriterionInterface.
     *
     * @return mixed The instantiated criterion object.
     */
    protected function instantiateCriterion($class, $arguments)
    {
        // Use reflection to inspect the class.
        $reflection = Reflection::getClass($class);

        // Ensure the class implements the CriterionInterface interface.
        if (! $reflection->implementsInterface(CriterionInterface::class)) {
            throw CriterionException::classNotImplementContract($class);
        }

        // If arguments are associative, reorder them to match constructor parameter order.
        if (Arr::isAssoc($arguments)) {
            $parameters = Arr::column($reflection->getConstructor()->getParameters(), 'name');

            $arguments = Arr::filter(Arr::map($parameters, fn($parameter) => $arguments[$parameter] ?? null));
        }

        // Instantiate the class with the provided arguments.
        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * Extract the class name and arguments from a criterion represented as an array.
     *
     * @param array $criterion The criterion represented as an array.
     *
     * @throws CriterionException If the array structure is invalid.
     *
     * @return array An array containing the class name and its arguments.
     */
    protected function extractCriterionClassAndArgs(array $criterion): array
    {
        // Validate the structure of the criterion array.
        if (count($criterion) > 2 || empty($criterion)) {
            throw CriterionException::wrongArraySignature($criterion);
        }

        // Handle associative arrays where keys represent the class name.
        if (Arr::isAssoc($criterion)) {
            $criterion = [Arr::keys($criterion)[0], Arr::values($criterion)[0]];
        } elseif (count($criterion) === 1) {
            // If a single element is provided, assume it represents a class without arguments.
            $criterion[] = [];
        }

        return $criterion;
    }

    /**
     * Add a single criterion to a specific list.
     *
     * @param Closure|CriterionInterface|array|string $criterion The criterion to add (can be a class name, object, closure, or array).
     * @param string                                 $list      The list where the criterion should be added.
     *
     * @throws CriterionException If the criterion type is invalid or instantiation fails.
     * @throws RepositoryException If the specified list does not exist.
     *
     * @return $this Self instance for method chaining.
     */
    protected function addCriterion($criterion, $list)
    {
        // Ensure the specified list exists as a property of the class.
        if (! property_exists($this, $list)) {
            throw RepositoryException::listNotFound($list, $this);
        }

        // Validate the criterion type.
        if (! ($criterion instanceof Closure ||
              $criterion instanceof CriterionInterface ||
              Validator::isString($criterion) ||
              Validator::isArray($criterion))) {
            throw CriterionException::wrongCriterionType($criterion);
        }

        // Normalize string criteria as a class name with no arguments.
        if (Validator::isString($criterion)) {
            $criterion = [$criterion, []];
        }

        // If the criterion is an array, instantiate it as a class with arguments.
        if (Validator::isArray($criterion)) {
            $criterion = call_user_func_array([$this, 'instantiateCriterion'], $this->extractCriterionClassAndArgs($criterion));
        }

        // Add the criterion to the specified list using its normalized name as the key.
        $this->{$list}[$this->getCriterionName($criterion)] = $criterion;

        return $this;
    }

    /**
     * Add multiple criteria to a specific list.
     *
     * @param array  $criteria The array of criteria to add.
     * @param string $list     The list where the criteria should be added.
     *
     * @return void
     */
    protected function addCriteria(array $criteria, $list): void
    {
        // Iterate over each criterion and add it to the specified list.
        Arr::walk($criteria, function($value, $key) use ($list): void {
            // Normalize each criterion, handling both associative and non-associative cases.
            $criterion = Validator::isString($key) ? [$key, $value] : $value;
            $this->addCriterion($criterion, $list);
        });
    }
}
