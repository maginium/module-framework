<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Collection;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Maginium\Framework\Elasticsearch\Eloquent\Models;

/**
 * @template TKey of array-key
 * @template TModel of Model
 *
 * This class extends Laravel's Eloquent Collection to handle collections of Elasticsearch models.
 *
 * @extends Collection<int, TModel>
 */
class ElasticCollection extends Collection
{
    // Trait to include additional metadata or functionality for the collection.
    use ElasticCollectionMeta;

    /**
     * Constructor for creating an instance of ElasticCollection.
     *
     * @param  Arrayable<TKey, TModel>|iterable<TKey, TModel>|array<TKey|int, mixed>|null  $items
     *
     * This constructor allows for various types of input for the collection's items, including:
     * - Arrayable objects (Laravel's contracts for objects that can be converted to arrays)
     * - Iterables (such as generators or collections)
     * - Standard arrays or null if no items are provided.
     */
    public function __construct($items = [])
    {
        // Call the parent constructor (Illuminate's Collection class) to initialize the collection with the given items.
        parent::__construct($items);
    }
}
