<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Collection;

use Maginium\Framework\Support\LazyCollection;

/**
 * LazyElasticCollection is an extension of Laravel's LazyCollection that also includes
 * Elasticsearch-specific metadata functionality via the ElasticCollectionMeta trait.
 *
 * @template TKey of array-key The type of the collection's keys (array keys).
 * @template TValue The type of the collection's values.
 *
 * @extends \Illuminate\Support\LazyCollection<TKey, TValue> The class extends Laravel's LazyCollection
 * and inherits all its features, like lazy loading, filtering, mapping, etc.
 */
class LazyElasticCollection extends LazyCollection
{
    // Use the ElasticCollectionMeta trait to add Elasticsearch query metadata functionality.
    use ElasticCollectionMeta;
}
