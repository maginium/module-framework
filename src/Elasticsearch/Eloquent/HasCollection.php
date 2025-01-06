<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Eloquent;

use Maginium\Framework\Elasticsearch\Collection\ElasticCollection;
use Maginium\Framework\Support\Facades\Container;

/**
 * Trait HasCollection.
 *
 * This trait provides a method for creating a new custom Elasticsearch collection instance.
 * It wraps the given models into an `ElasticCollection`, enabling additional operations
 * specific to Elasticsearch results, such as filtering, mapping, and iterating over models.
 *
 * This trait can be used in Eloquent models to extend the functionality of collections
 * with Elasticsearch-specific logic and behavior.
 */
trait HasCollection
{
    /**
     * Create a new Eloquent Collection instance.
     *
     * This method creates an instance of the custom `ElasticCollection` class, which is used to
     * wrap multiple models in the context of Elasticsearch results. This collection can then
     * be manipulated with collection operations like filtering, mapping, and iterating.
     *
     * By using this custom collection, we can leverage any additional functionality specific
     * to Elasticsearch models that would not be available in the default Eloquent collection.
     *
     * @param  array<array-key, Model>  $models An array of models to include in the collection.
     *
     * @return ElasticCollection<array-key, Model>
     *         A new instance of `ElasticCollection` containing the provided models.
     */
    public function newCollection(array $models = []): ElasticCollection
    {
        // Return a new instance of ElasticCollection, passing the provided models array
        return Container::make(ElasticCollection::class, ['items' => $models]);
    }
}
