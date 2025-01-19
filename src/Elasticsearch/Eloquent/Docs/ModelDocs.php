<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Eloquent\Docs;

/**
 * Query Builder Methods ---------------------------------.
 *
 * @method \Maginium\Framework\Database\Eloquent\Builder query()
 * @method \Maginium\Framework\Elasticsearch\Query\Builder where(array|\Closure|\Illuminate\Contracts\Database\Query\Expression|string $column, $operator = null, $value = null, $boolean = 'and')
 * @method \Maginium\Framework\Elasticsearch\Query\Builder whereIn(string $column, array $values)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder whereExact(string $column, string $value, $boolean = 'and')
 * @method \Maginium\Framework\Elasticsearch\Query\Builder wherePhrase(string $column, string $value, $boolean = 'and')
 * @method \Maginium\Framework\Elasticsearch\Query\Builder wherePhrasePrefix(string $column, string $value, $boolean = 'and')
 * @method \Maginium\Framework\Elasticsearch\Query\Builder whereDate($column, $operator = null, $value = null, $boolean = 'and')
 * @method \Maginium\Framework\Elasticsearch\Query\Builder whereTimestamp($column, $operator = null, $value = null, $boolean = 'and')
 * @method \Maginium\Framework\Elasticsearch\Query\Builder whereRegex(string $column, string $regex)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orWhere(array|\Closure|\Illuminate\Contracts\Database\Query\Expression|string $column, $operator = null, $value = null)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orWhereIn(string $column, array $values)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orWhereExact(string $column, string $value)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orWherePhrase(string $column, string $value)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orWherePhrasePrefix(string $column, string $value)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orWhereDate($column, $operator = null, $value = null)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orWhereTimestamp($column, $operator = null, $value = null)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orWhereRegex(string $column, string $regex)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder whereNestedObject(string $column, Callable $callback, string $scoreType = 'avg')
 * @method \Maginium\Framework\Elasticsearch\Query\Builder whereNotNestedObject(string $column, Callable $callback, string $scoreType = 'avg')
 * @method \Maginium\Framework\Elasticsearch\Query\Builder queryNested(string $column, Callable $callback)
 *
 * Filter and order methods ---------------------------------
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orderBy(string $column, string $direction = 'asc')
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orderByDesc(string $column)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder withSort(string $column, string $key, mixed $value)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orderByGeo(string $column, array $pin, $direction = 'asc', $unit = 'km', $mode = null, $type = 'arc')
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orderByGeoDesc(string $column, array $pin, $unit = 'km', $mode = null, $type = 'arc')
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orderByNested(string $column, string $direction = 'asc', string $mode = null)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder filterGeoBox(string $column, array $topLeftCoords, array $bottomRightCoords)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder filterGeoPoint(string $column, string $distance, array $point)
 * @method \Maginium\Framework\Elasticsearch\Query\Builder orderByRandom(string $column, int $seed = 1)
 *
 * Full Text Search Methods ---------------------------------
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model searchFor($value, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model searchTerm($term, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model searchTermMost($term, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model searchTermCross($term, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model searchPhrase($phrase, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model searchPhrasePrefix($phrase, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model searchBoolPrefix($phrase, $fields = ['*'], $options = [], $boolean = 'and')
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model orSearchFor($value, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model orSearchTerm($term, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model orSearchTermMost($term, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model orSearchTermCross($term, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model orSearchPhrase($phrase, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model orSearchPhrasePrefix($phrase, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model orSearchBoolPrefix($phrase, $fields = ['*'], $options = [])
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model withHighlights(array $fields = [], string|array $preTag = '<em>', string|array $postTag = '</em>', array $globalOptions = [])
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model asFuzzy(?int $depth = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model setMinShouldMatch(int $value)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model setBoost(int $value)
 *
 * Query Executors --------------------------------------------
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model|null find($id)
 * @method array getModels(array $columns = ['*'])
 * @method \Maginium\Framework\Elasticsearch\Collection\ElasticCollection get(array $columns = ['*'])
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model|null first(array $columns = ['*'])
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model firstOrCreate(array $attributes, array $values = [])
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model firstOrCreateWithoutRefresh(array $attributes, array $values = [])
 * @method int|array sum(array|string $columns)
 * @method int|array min(array|string $columns)
 * @method int|array max(array|string $columns)
 * @method int|array avg(array|string $columns)
 * @method mixed agg(array $functions, $column)
 * @method \Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null, ?int $total = null)
 * @method \Maginium\Framework\Elasticsearch\Pagination\SearchAfterPaginator cursorPaginate(int|null $perPage = null, array $columns = [], string $cursorName = 'cursor', ?\Maginium\Framework\Pagination\Interfaces\CursorInterface $cursor = null)
 * @method \Maginium\Framework\Elasticsearch\Collection\ElasticCollection insert($values, $returnData = null):
 * @method \Maginium\Framework\Elasticsearch\Collection\ElasticCollection insertWithoutRefresh($values, $returnData = null)
 * @method array toDsl(array $columns = ['*'])
 * @method mixed rawDsl(array $bodyParams)
 * @method \Maginium\Framework\Elasticsearch\Collection\ElasticCollection rawSearch(array $bodyParams)
 * @method array rawAggregation(array $bodyParams)
 * @method bool chunk(mixed $count, callable $callback, string $keepAlive = '5m')
 * @method bool chunkById(mixed $count, callable $callback, $column = '_id', $alias = null, $keepAlive = '5m')
 *
 * Index Methods ---------------------------------
 * @method string getQualifiedKeyName()
 * @method string getConnection()
 * @method void truncate()
 * @method bool indexExists()
 * @method bool deleteIndexIfExists()
 * @method bool deleteIndex()
 * @method bool createIndex(array $options = [])
 * @method array getIndexMappings()
 * @method array getFieldMapping(string|array $field = '*', $raw = false)
 * @method array getIndexOptions()
 *
 * Search Methods - Due for sunsetting, keep for now
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model term(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model andTerm(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model orTerm(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model fuzzyTerm(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model andFuzzyTerm(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model orFuzzyTerm(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model regEx(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model andRegEx(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model orRegEx(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model phrase(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model andPhrase(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model orPhrase(string $term, $boostFactor = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model minShouldMatch(int $value)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model highlight(array $fields = [], string|array $preTag = '<em>', string|array $postTag = '</em>', $globalOptions = [])
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model minScore(float $value)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model field(string $field, int $boostFactor = null)
 * @method \Maginium\Framework\Elasticsearch\Eloquent\Model fields(array $fields)
 * @method array searchModels(array $columns = ['*'])
 * @method \Maginium\Framework\Elasticsearch\Collection\ElasticCollection search(array $columns = ['*'])
 *
 * @property object $search_highlights
 * @property object $with_highlights
 * @property array $search_highlights_as_array
 *
 * @mixin \Maginium\Framework\Database\Eloquent\Builder
 */
trait ModelDocs
{
}
