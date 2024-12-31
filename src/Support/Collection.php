<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Illuminate\Support\Collection as BaseCollection;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Pagination\Constants\Paginator as PaginatorConstants;
use Maginium\Framework\Pagination\Facades\LengthAwarePaginator;
use Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface;

/**
 * Class Collection.
 *
 * This class extends the functionality of the base collection, providing additional
 * methods for convenient array manipulation, translations, and pagination.
 *
 * @method static LengthAwarePaginatorInterface paginate(?int $page = null, ?int $perPage = null, array $columns = ['*'], string $pageName = PaginatorConstants::DEFAULT_PAGE_NAME, ?int $total = null) Paginate the collection of models.
 * @method static array lists(string $value, ?string $key = null) Retrieve an array of values for the given key.
 * @method static array<TKey, TValue> all()
 * @method static \Illuminate\Support\LazyCollection<TKey, TValue> lazy()
 * @method static float|int|null avg((callable(TValue): float|int)|string|null $callback = null)
 * @method static float|int|null median(string|array<array-key, string>|null $key = null)
 * @method static array<int, float|int>|null mode(string|array<array-key, string>|null $key = null)
 * @method static<int, mixed> collapse()
 * @method static bool contains((callable(TValue, TKey): bool)|TValue|string $key, mixed $operator = null, mixed $value = null)
 * @method static bool containsStrict((callable(TValue): bool)|TValue|array-key $key, TValue|null $value = null)
 * @method static bool doesntContain(mixed $key, mixed $operator = null, mixed $value = null)
 * @method static<int, array<int, TValue|TCrossJoinValue>> crossJoin(\Illuminate\Contracts\Support\Arrayable<TCrossJoinKey, TCrossJoinValue>|iterable<TCrossJoinKey, TCrossJoinValue> ...$lists)
 * @method static diff(\Illuminate\Contracts\Support\Arrayable<array-key, TValue>|iterable<array-key, TValue> $items)
 * @method static diffUsing(\Illuminate\Contracts\Support\Arrayable<array-key, TValue>|iterable<array-key, TValue> $items, callable(TValue, TValue): int $callback)
 * @method static diffAssoc(\Illuminate\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items)
 * @method static diffAssocUsing(\Illuminate\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items, callable(TKey, TKey): int $callback)
 * @method static diffKeys(\Illuminate\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items)
 * @method static diffKeysUsing(\Illuminate\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items, callable(TKey, TKey): int $callback)
 * @method static duplicates((callable(TValue): bool)|string|null $callback = null, bool $strict = false)
 * @method static duplicatesStrict((callable(TValue): bool)|string|null $callback = null)
 * @method static except(\Illuminate\Support\Enumerable<array-key, TKey>|array<array-key, TKey>|string $keys)
 * @method static filter(callable $callback = null)
 * @method static TValue|TFirstDefault first(callable $callback = null, TFirstDefault|(\Closure(): TFirstDefault) $default = null)
 * @method static flatten(int $depth = INF)
 * @method static flip()
 * @method static forget(\Illuminate\Contracts\Support\Arrayable<array-key, TValue>|iterable<array-key, TKey>|TKey $keys)
 * @method static get(TKey $key, TGetDefault|(\Closure(): TGetDefault) $default = null)
 * @method static getOrPut(mixed $key, mixed $value) Get an item from the collection by key or add it to collection if it does not exist.
 * @method static groupBy(callable|string|array $groupBy, bool $preserveKeys = false) Group an associative array by a field or using a callback.
 * @method static keyBy(callable|string|array $keyBy) Key an associative array by a field or using a callback.
 * @method bool has(mixed $key) Determine if an item exists in the collection by key.
 * @method static hasAny(mixed $key) Determine if any of the keys exist in the collection.
 * @method static implode(callable|string $value, string $glue = null) Concatenate values of a given key as a string.
 * @method static intersect(iterable $items) Intersect the collection with the given items.
 * @method static intersectUsing(iterable $items, callable $callback) Intersect the collection with the given items, using the callback.
 * @method static intersectAssoc(iterable $items) Intersect the collection with the given items with additional index check.
 * @method static intersectAssocUsing(iterable $items, callable $callback) Intersect the collection with the given items with additional index check, using the callback.
 * @method static intersectByKeys(iterable $items) Intersect the collection with the given items by key.
 * @method static isEmpty() Determine if the collection is empty or not.
 * @method static containsOneItem() Determine if the collection contains a single item.
 * @method static join(string $glue, string $finalGlue = '') Join all items from the collection using a string. The final items can use a separate glue string.
 * @method static keys() Get the keys of the collection items.
 * @method static last(callable|null $callback = null, mixed $default = null) Get the last item from the collection.
 * @method static pluck(string|int|array $value, string|null $key = null) Get the values of a given key.
 * @method static map(callable $callback) Run a map over each of the items.
 * @method static mapToDictionary(callable $callback) Run a dictionary map over the items.
 * @method static mapWithKeys(callable $callback) Run an associative map over each of the items.
 * @method static $this merge(iterable<TKey, TValue> $items) Merge the collection with the given items.
 * @method static $this mergeRecursive(iterable<TKey, TMergeRecursiveValue> $items) Recursively merge the collection with the given items.
 * @method static $this combine(iterable<array-key, TCombineValue> $values) Create a collection by using this collection for keys and another for its values.
 * @method static $this union(iterable<TKey, TValue> $items) Union the collection with the given items.
 * @method static $this nth(int $step, int $offset = 0) Create a new collection consisting of every n-th element.
 * @method static $this only(array<array-key, TKey>|string|null $keys) Get the items with the specified keys.
 * @method static $this select(array<array-key, TKey>|string|null $keys) Select specific values from the items within the collection.
 * @method static static<int, TValue>|TValue|null pop(int $count = 1) Get and remove the last N items from the collection.
 * @method static $this prepend(TValue $value, TKey $key = null) Push an item onto the beginning of the collection.
 * @method static $this push(...$values) Push one or more items onto the end of the collection.
 * @method static static<TKey|TConcatKey, TValue|TConcatValue> concat(iterable<TConcatKey, TConcatValue> $source) Push all of the given items onto the collection.
 * @method static TValue|TPullDefault pull(TKey $key, TPullDefault|(\Closure(): TPullDefault) $default = null) Get and remove an item from the collection.
 * @method static $this put(TKey $key, TValue $value) Put an item in the collection by key.
 * @method static TValue|static<int, TValue> random(int $number = null, bool $preserveKeys = false) Get one or a specified number of items randomly from the collection.
 * @method static $this replace(iterable<TKey, TValue> $items) Replace the collection items with the given items.
 * @method static $this replaceRecursive(iterable<TKey, TValue> $items) Recursively replace the collection items with the given items.
 * @method static $this reverse() Reverse items order.
 * @method static TKey|false search(TValue|(callable(TValue, TKey): bool) $value, bool $strict = false) Search the collection for a given value and return the corresponding key if successful.
 * @method static static<int, TValue>|TValue|null shift(int $count = 1) Get and remove the first N items from the collection.
 * @method static $this shuffle(int|null $seed = null) Shuffle the items in the collection.
 * @method static static<int, static> sliding(int $size = 2, int $step = 1) Create chunks representing a "sliding window" view of the items in the collection.
 * @method static $this skip(int $count) Skip the first {$count} items.
 * @method static $this skipUntil(TValue|callable(TValue, TKey): bool $value) Skip items in the collection until the given condition is met.
 * @method static $this skipWhile(TValue|callable(TValue, TKey): bool $value) Skip items in the collection while the given condition is met.
 * @method static $this slice(int $offset, int|null $length = null) Slice the underlying collection array.
 * @method static static<int, static> split(int $numberOfGroups) Split a collection into a certain number of groups.
 * @method static static<int, static> splitIn(int $numberOfGroups) Split a collection into a certain number of groups, and fill the first groups completely.
 * @method static TValue sole(string|callable(TValue, TKey): bool $key = null, mixed $operator = null, mixed $value = null) Get the first item in the collection, but only if exactly one item exists. Otherwise, throw an exception.
 * @method static firstOrFail(callable|string $key = null, mixed $operator = null, mixed $value = null) Get the first item in the collection but throw an exception if no matching items exist.
 * @method static chunk(int $size) Chunk the collection into chunks of the given size.
 * @method static chunkWhile(callable $callback) Chunk the collection into chunks with a callback.
 * @method static sort(callable|null|int $callback = null) Sort through each item with a callback.
 * @method static sortDesc(int $options = SORT_REGULAR) Sort items in descending order.
 * @method static sortBy(callable|string $callback, int $options = SORT_REGULAR, bool $descending = false) Sort the collection using the given callback.
 * @method static sortByDesc(callable|string $callback, int $options = SORT_REGULAR) Sort the collection in descending order using the given callback.
 * @method static sortKeys(int $options = SORT_REGULAR, bool $descending = false) Sort the collection keys.
 * @method static sortKeysDesc(int $options = SORT_REGULAR) Sort the collection keys in descending order.
 * @method static sortKeysUsing(callable $callback) Sort the collection keys using a callback.
 * @method static splice(int $offset, int|null $length = null, array $replacement = []) Splice a portion of the underlying collection array.
 * @method static take(int $limit) Take the first or last {$limit} items.
 * @method static takeUntil(mixed $value) Take items in the collection until the given condition is met.
 * @method static takeWhile(mixed $value) Take items in the collection while the given condition is met.
 * @method static transform(callable $callback) Transform each item in the collection using a callback.
 * @method static dot() Flatten a multi-dimensional associative array with dots.
 * @method static undot() Convert a flatten "dot" notation array into an expanded array.
 * @method static unique(callable|string|null $key = null, bool $strict = false) Return only unique items from the collection array.
 * @method static values() Reset the keys on the underlying array.
 * @method static zip(iterable ...$items) Zip the collection together with one or more arrays.
 * @method static pad(int $size, mixed $value) Pad collection to the specified length with a value.
 * @method static getIterator() Get an iterator for the items.
 * @method static count() Count the number of items in the collection.
 * @method static countBy(callable|string|null $countBy = null) Count the number of items in the collection by a field or using a callback.
 * @method static add(mixed $item) Add an item to the collection.
 * @method static toBase() Get a base Support collection instance from this collection.
 * @method static offsetExists(mixed $key) Determine if an item exists at an offset.
 * @method static offsetGet(mixed $key) Get an item at a given offset.
 * @method static offsetSet(mixed $key, mixed $value) Set the item at a given offset.
 * @method static offsetUnset(mixed $key) Unset the item at a given offset.
 * @method static start() Begin the operation (starting from this method).
 */
class Collection extends BaseCollection
{
    /**
     * Paginate the collection by slicing it into a smaller collection.
     *
     * @param int $page The current page number.
     * @param int $perPage The number of items per page.
     *
     * @throws InvalidArgumentException If $page or $perPage is not a positive integer.
     *
     * @return LengthAwarePaginatorInterface Paginated results.
     */
    public function paginate(int $page, int $perPage): LengthAwarePaginatorInterface
    {
        // Validate input
        if ($page <= 0 || $perPage <= 0) {
            throw InvalidArgumentException::make(__('Page and perPage values must be positive integers.'));
        }

        // Paginate the collection
        $items = $this->slice(($page - 1) * $perPage, $perPage);

        return LengthAwarePaginator::make([
            PaginatorConstants::ITEMS => $items, // Default value if no items are passed
            PaginatorConstants::TOTAL => $this->count(), // Default total value
            PaginatorConstants::PER_PAGE => $perPage, // Default perPage value
            PaginatorConstants::CURRENT_PAGE => $page, // Default currentPage value
            PaginatorConstants::OPTIONS => ['path' => LengthAwarePaginator::resolveCurrentPath()], // Default options value
        ]);
    }

    /**
     * Get an array with the values of a given key.
     *
     * This method retrieves the values associated with the specified key from the
     * collection, optionally indexing them by another key. If the key doesn't exist,
     * an empty array will be returned.
     *
     * @param string $value The key to retrieve values for.
     * @param string|null $key The key to index the values by, if needed.
     *
     * @return array An array containing the values for the specified key.
     */
    public function lists(string $value, ?string $key = null): array
    {
        // Ensure the key exists before attempting to retrieve it
        return $this->pluck($value, $key)->all();
    }
}
