<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Eloquent\Docs;

use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Maginium\Framework\Database\Query\Builder;

/**
 * Query Builder Methods ---------------------------------.
 *
 * @method \Maginium\Framework\Database\Eloquent\Model $this query()
 * @method \Maginium\Framework\Database\Eloquent\Model $this where(array|\Closure|\Illuminate\Contracts\Database\Query\Expression|string $column, $operator = null, $value = null, $boolean = 'and')
 * @method \Maginium\Framework\Database\Eloquent\Model $this whereIn(string $column, array $values)
 * @method \Maginium\Framework\Database\Eloquent\Model $this whereExact(string $column, string $value, $boolean = 'and')
 * @method \Maginium\Framework\Database\Eloquent\Model $this wherePhrase(string $column, string $value, $boolean = 'and')
 * @method \Maginium\Framework\Database\Eloquent\Model $this wherePhrasePrefix(string $column, string $value, $boolean = 'and')
 * @method \Maginium\Framework\Database\Eloquent\Model $this whereDate($column, $operator = null, $value = null, $boolean = 'and')
 * @method \Maginium\Framework\Database\Eloquent\Model $this whereTimestamp($column, $operator = null, $value = null, $boolean = 'and')
 * @method \Maginium\Framework\Database\Eloquent\Model $this whereRegex(string $column, string $regex)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orWhere(array|\Closure|\Illuminate\Contracts\Database\Query\Expression|string $column, $operator = null, $value = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orWhereIn(string $column, array $values)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orWhereExact(string $column, string $value)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orWherePhrase(string $column, string $value)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orWherePhrasePrefix(string $column, string $value)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orWhereDate($column, $operator = null, $value = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orWhereTimestamp($column, $operator = null, $value = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orWhereRegex(string $column, string $regex)
 * @method \Maginium\Framework\Database\Eloquent\Model $this whereNestedObject(string $column, Callable $callback, string $scoreType = 'avg')
 * @method \Maginium\Framework\Database\Eloquent\Model $this whereNotNestedObject(string $column, Callable $callback, string $scoreType = 'avg')
 * @method \Maginium\Framework\Database\Eloquent\Model $this queryNested(string $column, Callable $callback)
 *
 * Filter and order methods ---------------------------------
 * @method \Maginium\Framework\Database\Eloquent\Model $this orderBy(string $column, string $direction = 'asc')
 * @method \Maginium\Framework\Database\Eloquent\Model $this orderByDesc(string $column)
 * @method \Maginium\Framework\Database\Eloquent\Model $this withSort(string $column, string $key, mixed $value)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orderByGeo(string $column, array $pin, $direction = 'asc', $unit = 'km', $mode = null, $type = 'arc')
 * @method \Maginium\Framework\Database\Eloquent\Model $this orderByGeoDesc(string $column, array $pin, $unit = 'km', $mode = null, $type = 'arc')
 * @method \Maginium\Framework\Database\Eloquent\Model $this orderByNested(string $column, string $direction = 'asc', string $mode = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this filterGeoBox(string $column, array $topLeftCoords, array $bottomRightCoords)
 * @method \Maginium\Framework\Database\Eloquent\Model $this filterGeoPoint(string $column, string $distance, array $point)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orderByRandom(string $column, int $seed = 1)
 *
 * Full Text Search Methods ---------------------------------
 * @method \Maginium\Framework\Database\Eloquent\Model $this searchFor($value, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Database\Eloquent\Model $this searchTerm($term, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Database\Eloquent\Model $this searchTermMost($term, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Database\Eloquent\Model $this searchTermCross($term, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Database\Eloquent\Model $this searchPhrase($phrase, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Database\Eloquent\Model $this searchPhrasePrefix($phrase, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Database\Eloquent\Model $this searchBoolPrefix($phrase, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Database\Eloquent\Model $this orSearchFor($value, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Database\Eloquent\Model $this orSearchTerm($term, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Database\Eloquent\Model $this orSearchTermMost($term, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Database\Eloquent\Model $this orSearchTermCross($term, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Database\Eloquent\Model $this orSearchPhrase($phrase, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Database\Eloquent\Model $this orSearchPhrasePrefix($phrase, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Database\Eloquent\Model $this orSearchBoolPrefix($phrase, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Database\Eloquent\Model $this withHighlights(array $fields = [], string|array $preTag = '<em>', string|array $postTag = '</em>', array $globalOptions = [])
 * @method \Maginium\Framework\Database\Eloquent\Model $this asFuzzy(?int $depth = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this setMinShouldMatch(int $value)
 * @method \Maginium\Framework\Database\Eloquent\Model $this setBoost(int $value)
 *
 * Query Executors --------------------------------------------
 * @method \Maginium\Framework\Database\Eloquent\Model \Maginium\Framework\Database\Eloquent\Model|null find($id)
 * @method \Maginium\Framework\Database\Eloquent\Model array get\Maginium\Framework\Database\Eloquent\Model(array $columns = ['*'])
 * @method \Maginium\Framework\Database\Eloquent\Model \Maginium\Framework\Database\Eloquent\Collection get(array $columns = ['*'])
 * @method \Maginium\Framework\Database\Eloquent\Model \Maginium\Framework\Database\Eloquent\Model|null first(array $columns = ['*'])
 * @method \Maginium\Framework\Database\Eloquent\Model \Maginium\Framework\Database\Eloquent\Model firstOrCreate(array $attributes, array $values = [])
 * @method \Maginium\Framework\Database\Eloquent\Model \Maginium\Framework\Database\Eloquent\Model firstOrCreateWithoutRefresh(array $attributes, array $values = [])
 * @method \Maginium\Framework\Database\Eloquent\Model int|array sum(array|string $columns)
 * @method \Maginium\Framework\Database\Eloquent\Model int|array min(array|string $columns)
 * @method \Maginium\Framework\Database\Eloquent\Model int|array max(array|string $columns)
 * @method \Maginium\Framework\Database\Eloquent\Model int|array avg(array|string $columns)
 * @method \Maginium\Framework\Database\Eloquent\Model mixed agg(array $functions, $column)
 * @method \Maginium\Framework\Database\Eloquent\Model \Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null, ?int $total = null)
 * @method \Maginium\Framework\Database\Eloquent\Model \Maginium\Framework\Database\Eloquent\Collection insert($values, $returnData = null):
 * @method \Maginium\Framework\Database\Eloquent\Model \Maginium\Framework\Database\Eloquent\Collection insertWithoutRefresh($values, $returnData = null)
 * @method \Maginium\Framework\Database\Eloquent\Model array toDsl(array $columns = ['*'])
 * @method \Maginium\Framework\Database\Eloquent\Model mixed rawDsl(array $bodyParams)
 * @method \Maginium\Framework\Database\Eloquent\Model \Maginium\Framework\Database\Eloquent\Collection rawSearch(array $bodyParams)
 * @method \Maginium\Framework\Database\Eloquent\Model array rawAggregation(array $bodyParams)
 * @method \Maginium\Framework\Database\Eloquent\Model bool chunk(mixed $count, callable $callback, string $keepAlive = '5m')
 * @method \Maginium\Framework\Database\Eloquent\Model bool chunkById(mixed $count, callable $callback, $column = '_id', $alias = null, $keepAlive = '5m')
 *
 * Index Methods ---------------------------------
 * @method \Maginium\Framework\Database\Eloquent\Model string getQualifiedKeyName()
 * @method \Maginium\Framework\Database\Eloquent\Model string getConnection()
 * @method \Maginium\Framework\Database\Eloquent\Model void truncate()
 * @method \Maginium\Framework\Database\Eloquent\Model bool indexExists()
 * @method \Maginium\Framework\Database\Eloquent\Model bool deleteIndexIfExists()
 * @method \Maginium\Framework\Database\Eloquent\Model bool deleteIndex()
 * @method \Maginium\Framework\Database\Eloquent\Model bool createIndex(array $options = [])
 * @method \Maginium\Framework\Database\Eloquent\Model array getIndexMappings()
 * @method \Maginium\Framework\Database\Eloquent\Model array getFieldMapping(string|array $field = '*', $raw = false)
 * @method \Maginium\Framework\Database\Eloquent\Model array getIndexOptions()
 *
 * Search Methods - Due for sunsetting, keep for now
 * @method \Maginium\Framework\Database\Eloquent\Model $this term(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this andTerm(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orTerm(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this fuzzyTerm(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this andFuzzyTerm(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orFuzzyTerm(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this regEx(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this andRegEx(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orRegEx(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this phrase(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this andPhrase(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this orPhrase(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this minShouldMatch(int $value)
 * @method \Maginium\Framework\Database\Eloquent\Model $this highlight(array $fields = [], string|array $preTag = '<em>', string|array $postTag = '</em>', $globalOptions = [])
 * @method \Maginium\Framework\Database\Eloquent\Model $this minScore(float $value)
 * @method \Maginium\Framework\Database\Eloquent\Model $this field(string $field, int $boostFactor = null)
 * @method \Maginium\Framework\Database\Eloquent\Model $this fields(array $fields)
 * @method \Maginium\Framework\Database\Eloquent\Model array search\Maginium\Framework\Database\Eloquent\Model(array $columns = ['*'])
 * @method \Maginium\Framework\Database\Eloquent\Model \Maginium\Framework\Database\Eloquent\Collection search(array $columns = ['*'])
 *
 * @property object $search_highlights
 * @property object $with_highlights
 * @property array $search_highlights_as_array
 *
 * @mixin Builder
 * @mixin HasAttributes
 */
trait ModelDocs
{
}
