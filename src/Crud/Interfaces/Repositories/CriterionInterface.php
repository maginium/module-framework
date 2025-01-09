<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces\Repositories;

/**
 * Interface CriterionInterface
 * Defines a contract for applying criteria to a query within a repository.
 */
interface CriterionInterface
{
    /**
     * Apply the current criterion to the provided query and return the modified query.
     *
     * This method modifies the query based on the logic of the specific criterion implementation.
     *
     * @param mixed $query The query object or structure to which the criterion will be applied.
     *                     Typically, this might be an ORM query builder instance.
     * @param RepositoryInterface $repository The repository instance managing the query.
     *                                         Provides context for applying the criterion.
     *
     * @return mixed The modified query after applying the criterion.
     */
    public function apply(mixed $query, RepositoryInterface $repository): mixed;
}
