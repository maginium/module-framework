<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Interfaces\Services;

use Maginium\Framework\Crud\Interfaces\Services\ServiceInterface as BaseServiceInterface;
use Maginium\Framework\Elasticsearch\Eloquent\Model;

/**
 * Interface ServiceInterface.
 *
 * This interface defines the core contract for CRUD service classes,
 * providing methods for managing entities, performing database operations,
 * and handling pagination. Implementations of this interface ensure
 * standardization across service layers within the application.
 *
 * @method Model $this query()
 * @method Model $this where(array|Closure|Expression|string $column, $operator = null, $value = null, $boolean = 'and')
 * @method Model $this whereIn(string $column, array $values)
 * @method Model $this whereExact(string $column, string $value, $boolean = 'and')
 * @method Model $this wherePhrase(string $column, string $value, $boolean = 'and')
 * @method Model $this wherePhrasePrefix(string $column, string $value, $boolean = 'and')
 * @method Model $this whereDate($column, $operator = null, $value = null, $boolean = 'and')
 * @method Model $this whereTimestamp($column, $operator = null, $value = null, $boolean = 'and')
 * @method Model $this whereRegex(string $column, string $regex)
 * @method Model $this orWhere(array|Closure|Expression|string $column, $operator = null, $value = null)
 * @method Model $this orWhereIn(string $column, array $values)
 * @method Model $this orWhereExact(string $column, string $value)
 * @method Model $this orWherePhrase(string $column, string $value)
 * @method Model $this orWherePhrasePrefix(string $column, string $value)
 * @method Model $this orWhereDate($column, $operator = null, $value = null)
 * @method Model $this orWhereTimestamp($column, $operator = null, $value = null)
 * @method Model $this orWhereRegex(string $column, string $regex)
 * @method Model $this whereNestedObject(string $column, Callable $callback, string $scoreType = 'avg')
 * @method Model $this whereNotNestedObject(string $column, Callable $callback, string $scoreType = 'avg')
 * @method Model $this queryNested(string $column, Callable $callback)
 *
 * Filter and order methods ---------------------------------
 * @method Model $this orderBy(string $column, string $direction = 'asc')
 * @method Model $this orderByDesc(string $column)
 * @method Model $this withSort(string $column, string $key, mixed $value)
 * @method Model $this orderByGeo(string $column, array $pin, $direction = 'asc', $unit = 'km', $mode = null, $type = 'arc')
 * @method Model $this orderByGeoDesc(string $column, array $pin, $unit = 'km', $mode = null, $type = 'arc')
 * @method Model $this orderByNested(string $column, string $direction = 'asc', string $mode = null)
 * @method Model $this filterGeoBox(string $column, array $topLeftCoords, array $bottomRightCoords)
 * @method Model $this filterGeoPoint(string $column, string $distance, array $point)
 * @method Model $this orderByRandom(string $column, int $seed = 1)
 *
 * Full Text Search Methods ---------------------------------
 * @method Model $this searchFor($value, $fields = ['*'], $options = [], $boolean = 'and')
 * @method Model $this searchTerm($term, $fields = ['*'], $options = [], $boolean = 'and')
 * @method Model $this searchTermMost($term, $fields = ['*'], $options = [], $boolean = 'and')
 * @method Model $this searchTermCross($term, $fields = ['*'], $options = [], $boolean = 'and')
 * @method Model $this searchPhrase($phrase, $fields = ['*'], $options = [], $boolean = 'and')
 * @method Model $this searchPhrasePrefix($phrase, $fields = ['*'], $options = [], $boolean = 'and')
 * @method Model $this searchBoolPrefix($phrase, $fields = ['*'], $options = [], $boolean = 'and')
 * @method Model $this orSearchFor($value, $fields = ['*'], $options = [])
 * @method Model $this orSearchTerm($term, $fields = ['*'], $options = [])
 * @method Model $this orSearchTermMost($term, $fields = ['*'], $options = [])
 * @method Model $this orSearchTermCross($term, $fields = ['*'], $options = [])
 * @method Model $this orSearchPhrase($phrase, $fields = ['*'], $options = [])
 * @method Model $this orSearchPhrasePrefix($phrase, $fields = ['*'], $options = [])
 * @method Model $this orSearchBoolPrefix($phrase, $fields = ['*'], $options = [])
 * @method Model $this withHighlights(array $fields = [], string|array $preTag = '<em>', string|array $postTag = '</em>', array $globalOptions = [])
 * @method Model $this asFuzzy(?int $depth = null)
 * @method Model $this setMinShouldMatch(int $value)
 * @method Model $this setBoost(int $value)
 *
 * Query Executors --------------------------------------------
 * @method Model Model|null find($id)
 * @method Model array getModels(array $columns = ['*'])
 * @method Model ElasticCollection get(array $columns = ['*'])
 * @method Model Model|null first(array $columns = ['*'])
 * @method Model Model firstOrCreate(array $attributes, array $values = [])
 * @method Model Model firstOrCreateWithoutRefresh(array $attributes, array $values = [])
 * @method Model int|array sum(array|string $columns)
 * @method Model int|array min(array|string $columns)
 * @method Model int|array max(array|string $columns)
 * @method Model int|array avg(array|string $columns)
 * @method Model mixed agg(array $functions, $column)
 * @method Model LengthAwarePaginatorInterface paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null, ?int $total = null)
 * @method Model SearchAfterPaginator cursorPaginate(int|null $perPage = null, array $columns = [], string $cursorName = 'cursor', ?Cursor $cursor = null)
 * @method Model ElasticCollection insert($values, $returnData = null):
 * @method Model ElasticCollection insertWithoutRefresh($values, $returnData = null)
 * @method Model array toDsl(array $columns = ['*'])
 * @method Model mixed rawDsl(array $bodyParams)
 * @method Model ElasticCollection rawSearch(array $bodyParams)
 * @method Model array rawAggregation(array $bodyParams)
 * @method Model bool chunk(mixed $count, callable $callback, string $keepAlive = '5m')
 * @method Model bool chunkById(mixed $count, callable $callback, $column = '_id', $alias = null, $keepAlive = '5m')
 *
 * Index Methods ---------------------------------
 * @method Model string getQualifiedKeyName()
 * @method Model string getConnection()
 * @method Model void truncate()
 * @method Model bool indexExists()
 * @method Model bool deleteIndexIfExists()
 * @method Model bool deleteIndex()
 * @method Model bool createIndex(array $options = [])
 * @method Model array getIndexMappings()
 * @method Model array getFieldMapping(string|array $field = '*', $raw = false)
 * @method Model array getIndexOptions()
 *
 * Search Methods - Due for sunsetting, keep for now
 * @method Model $this term(string $term, $boostFactor = null)
 * @method Model $this andTerm(string $term, $boostFactor = null)
 * @method Model $this orTerm(string $term, $boostFactor = null)
 * @method Model $this fuzzyTerm(string $term, $boostFactor = null)
 * @method Model $this andFuzzyTerm(string $term, $boostFactor = null)
 * @method Model $this orFuzzyTerm(string $term, $boostFactor = null)
 * @method Model $this regEx(string $term, $boostFactor = null)
 * @method Model $this andRegEx(string $term, $boostFactor = null)
 * @method Model $this orRegEx(string $term, $boostFactor = null)
 * @method Model $this phrase(string $term, $boostFactor = null)
 * @method Model $this andPhrase(string $term, $boostFactor = null)
 * @method Model $this orPhrase(string $term, $boostFactor = null)
 * @method Model $this minShouldMatch(int $value)
 * @method Model $this highlight(array $fields = [], string|array $preTag = '<em>', string|array $postTag = '</em>', $globalOptions = [])
 * @method Model $this minScore(float $value)
 * @method Model $this field(string $field, int $boostFactor = null)
 * @method Model $this fields(array $fields)
 * @method Model array searchModels(array $columns = ['*'])
 * @method Model ElasticCollection search(array $columns = ['*'])
 */
interface ServiceInterface extends BaseServiceInterface
{
}
