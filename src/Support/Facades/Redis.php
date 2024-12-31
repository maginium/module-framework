<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Redis\Interfaces\RedisInterface;
use Maginium\Framework\Support\Facade;

/**
 * Redis Facade.
 *
 * @see BaseRedis
 *
 * @method static int copy(string $source, string $destination, int $db = -1, bool $replace = false)
 * @method static int del(string[]|string $keyOrKeys, string ...$keys = null)
 * @method static string|null   dump(string $key)
 * @method static int exists(string $key)
 * @method static int expire(string $key, int $seconds, string $expireOption = '')
 * @method static int expireat(string $key, int $timestamp, string $expireOption = '')
 * @method static int expiretime(string $key)
 * @method static array keys(string $pattern)
 * @method static int move(string $key, int $db)
 * @method static mixed object($subcommand, string $key)
 * @method static int persist(string $key)
 * @method static int pexpire(string $key, int $milliseconds)
 * @method static int pexpireat(string $key, int $timestamp)
 * @method static int pttl(string $key)
 * @method static string|null   randomkey()
 * @method static mixed rename(string $key, string $target)
 * @method static int renamenx(string $key, string $target)
 * @method static array scan($cursor, array $options = null)
 * @method static array sort(string $key, array $options = null)
 * @method static array sort_ro(string $key, ?string $byPattern = null, ?LimitOffsetCount $limit = null, array $getPatterns = [], ?string $sorting = null, bool $alpha = false)
 * @method static int ttl(string $key)
 * @method static mixed type(string $key)
 * @method static int append(string $key, $value)
 * @method static int bfadd(string $key, $item)
 * @method static int bfexists(string $key, $item)
 * @method static array bfinfo(string $key, string $modifier = '')
 * @method static array bfinsert(string $key, int $capacity = -1, float $error = -1, int $expansion = -1, bool $noCreate = false, bool $nonScaling = false, string ...$item)
 * @method static Status bfloadchunk(string $key, int $iterator, $data)
 * @method static array bfmadd(string $key, ...$item)
 * @method static array bfmexists(string $key, ...$item)
 * @method static Status bfreserve(string $key, float $errorRate, int $capacity, int $expansion = -1, bool $nonScaling = false)
 * @method static array bfscandump(string $key, int $iterator)
 * @method static int bitcount(string $key, $start = null, $end = null, string $index = 'byte')
 * @method static int bitop($operation, $destkey, $key)
 * @method static array|null bitfield(string $key, $subcommand, ...$subcommandArg)
 * @method static int bitpos(string $key, $bit, $start = null, $end = null, string $index = 'byte')
 * @method static array blmpop(int $timeout, array $keys, string $modifier = 'left', int $count = 1)
 * @method static array bzpopmax(array $keys, int $timeout)
 * @method static array bzpopmin(array $keys, int $timeout)
 * @method static array bzmpop(int $timeout, array $keys, string $modifier = 'min', int $count = 1)
 * @method static int cfadd(string $key, $item)
 * @method static int cfaddnx(string $key, $item)
 * @method static int cfcount(string $key, $item)
 * @method static int cfdel(string $key, $item)
 * @method static int cfexists(string $key, $item)
 * @method static Status cfloadchunk(string $key, int $iterator, $data)
 * @method static int cfmexists(string $key, ...$item)
 * @method static array cfinfo(string $key)
 * @method static array cfinsert(string $key, int $capacity = -1, bool $noCreate = false, string ...$item)
 * @method static array cfinsertnx(string $key, int $capacity = -1, bool $noCreate = false, string ...$item)
 * @method static Status cfreserve(string $key, int $capacity, int $bucketSize = -1, int $maxIterations = -1, int $expansion = -1)
 * @method static array cfscandump(string $key, int $iterator)
 * @method static array cmsincrby(string $key, string|int...$itemIncrementDictionary)
 * @method static array cmsinfo(string $key)
 * @method static Status cmsinitbydim(string $key, int $width, int $depth)
 * @method static Status cmsinitbyprob(string $key, float $errorRate, float $probability)
 * @method static Status cmsmerge(string $destination, array $sources, array $weights = [])
 * @method static array cmsquery(string $key, string ...$item)
 * @method static int decr(string $key)
 * @method static int decrby(string $key, int $decrement)
 * @method static Status failover(?To $to = null, bool $abort = false, int $timeout = -1)
 * @method static mixed fcall(string $function, array $keys, ...$args)
 * @method static mixed fcall_ro(string $function, array $keys, ...$args)
 * @method static array ftaggregate(string $index, string $query, ?AggregateArguments $arguments = null)
 * @method static Status ftaliasadd(string $alias, string $index)
 * @method static Status ftaliasdel(string $alias)
 * @method static Status ftaliasupdate(string $alias, string $index)
 * @method static Status ftalter(string $index, FieldInterface[] $schema, ?AlterArguments $arguments = null)
 * @method static Status ftcreate(string $index, FieldInterface[] $schema, ?CreateArguments $arguments = null)
 * @method static int ftdictadd(string $dict, ...$term)
 * @method static int ftdictdel(string $dict, ...$term)
 * @method static array ftdictdump(string $dict)
 * @method static Status ftdropindex(string $index, ?DropArguments $arguments = null)
 * @method static string ftexplain(string $index, string $query, ?ExplainArguments $arguments = null)
 * @method static array ftinfo(string $index)
 * @method static array ftprofile(string $index, ProfileArguments $arguments)
 * @method static array ftsearch(string $index, string $query, ?SearchArguments $arguments = null)
 * @method static array ftspellcheck(string $index, string $query, ?SearchArguments $arguments = null)
 * @method static int ftsugadd(string $key, string $string, float $score, ?SugAddArguments $arguments = null)
 * @method static int ftsugdel(string $key, string $string)
 * @method static array ftsugget(string $key, string $prefix, ?SugGetArguments $arguments = null)
 * @method static int ftsuglen(string $key)
 * @method static array ftsyndump(string $index)
 * @method static Status ftsynupdate(string $index, string $synonymGroupId, ?SynUpdateArguments $arguments = null, string ...$terms)
 * @method static array fttagvals(string $index, string $fieldName)
 * @method static string|null   get(string $key)
 * @method static int getbit(string $key, $offset)
 * @method static int|null getex(string $key, $modifier = '', $value = false)
 * @method static string getrange(string $key, $start, $end)
 * @method static string getdel(string $key)
 * @method static string|null   getset(string $key, $value)
 * @method static int incr(string $key)
 * @method static int incrby(string $key, int $increment)
 * @method static string incrbyfloat(string $key, int|float $increment)
 * @method static array mget(string[]|string $keyOrKeys, string ...$keys = null)
 * @method static mixed mset(array $dictionary)
 * @method static int msetnx(array $dictionary)
 * @method static Status psetex(string $key, $milliseconds, $value)
 * @method static Status set(string $key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
 * @method static int setbit(string $key, $offset, $value)
 * @method static Status setex(string $key, $seconds, $value)
 * @method static int setnx(string $key, $value)
 * @method static int setrange(string $key, $offset, $value)
 * @method static int strlen(string $key)
 * @method static int hdel(string $key, array $fields)
 * @method static int hexists(string $key, string $field)
 * @method static string|null   hget(string $key, string $field)
 * @method static array hgetall(string $key)
 * @method static int hincrby(string $key, string $field, int $increment)
 * @method static string hincrbyfloat(string $key, string $field, int|float $increment)
 * @method static array hkeys(string $key)
 * @method static int hlen(string $key)
 * @method static array hmget(string $key, array $fields)
 * @method static mixed hmset(string $key, array $dictionary)
 * @method static array hrandfield(string $key, int $count = 1, bool $withValues = false)
 * @method static array hscan(string $key, $cursor, array $options = null)
 * @method static int hset(string $key, string $field, string $value)
 * @method static int hsetnx(string $key, string $field, string $value)
 * @method static array hvals(string $key)
 * @method static int hstrlen(string $key, string $field)
 * @method static array jsonarrappend(string $key, string $path = '$', ...$value)
 * @method static array jsonarrindex(string $key, string $path, string $value, int $start = 0, int $stop = 0)
 * @method static array jsonarrinsert(string $key, string $path, int $index, string ...$value)
 * @method static array jsonarrlen(string $key, string $path = '$')
 * @method static array jsonarrpop(string $key, string $path = '$', int $index = -1)
 * @method static int jsonclear(string $key, string $path = '$')
 * @method static array jsonarrtrim(string $key, string $path, int $start, int $stop)
 * @method static int jsondel(string $key, string $path = '$')
 * @method static int jsonforget(string $key, string $path = '$')
 * @method static string jsonget(string $key, string $indent = '', string $newline = '', string $space = '', string ...$paths)
 * @method static string jsonnumincrby(string $key, string $path, int $value)
 * @method static Status jsonmerge(string $key, string $path, string $value)
 * @method static array jsonmget(array $keys, string $path)
 * @method static Status jsonmset(string ...$keyPathValue)
 * @method static array jsonobjkeys(string $key, string $path = '$')
 * @method static array jsonobjlen(string $key, string $path = '$')
 * @method static array jsonresp(string $key, string $path = '$')
 * @method static string jsonset(string $key, string $path, string $value, ?string $subcommand = null)
 * @method static array jsonstrappend(string $key, string $path, string $value)
 * @method static array jsonstrlen(string $key, string $path = '$')
 * @method static array jsontoggle(string $key, string $path)
 * @method static array jsontype(string $key, string $path = '$')
 * @method static string blmove(string $source, string $destination, string $where, string $to, int $timeout)
 * @method static array|null blpop(array|string $keys, int|float $timeout)
 * @method static array|null brpop(array|string $keys, int|float $timeout)
 * @method static string|null   brpoplpush(string $source, string $destination, int|float $timeout)
 * @method static mixed lcs(string $key1, string $key2, bool $len = false, bool $idx = false, int $minMatchLen = 0, bool $withMatchLen = false)
 * @method static string|null   lindex(string $key, int $index)
 * @method static int linsert(string $key, $whence, $pivot, $value)
 * @method static int llen(string $key)
 * @method static string lmove(string $source, string $destination, string $where, string $to)
 * @method static array|null lmpop(array $keys, string $modifier = 'left', int $count = 1)
 * @method static string|null   lpop(string $key)
 * @method static int lpush(string $key, array $values)
 * @method static int lpushx(string $key, array $values)
 * @method static string[] lrange(string $key, int $start, int $stop)
 * @method static int lrem(string $key, int $count, string $value)
 * @method static mixed lset(string $key, int $index, string $value)
 * @method static mixed ltrim(string $key, int $start, int $stop)
 * @method static string|null   rpop(string $key)
 * @method static string|null   rpoplpush(string $source, string $destination)
 * @method static int rpush(string $key, array $values)
 * @method static int rpushx(string $key, array $values)
 * @method static int sadd(string $key, array $members)
 * @method static int scard(string $key)
 * @method static string[] sdiff(array|string $keys)
 * @method static int sdiffstore(string $destination, array|string $keys)
 * @method static string[] sinter(array|string $keys)
 * @method static int sintercard(array $keys, int $limit = 0)
 * @method static int sinterstore(string $destination, array|string $keys)
 * @method static int sismember(string $key, string $member)
 * @method static string[] smembers(string $key)
 * @method static array smismember(string $key, string ...$members)
 * @method static int smove(string $source, string $destination, string $member)
 * @method static string|array|null spop(string $key, int $count = null)
 * @method static string|null   srandmember(string $key, int $count = null)
 * @method static int srem(string $key, array|string $member)
 * @method static array sscan(string $key, int $cursor, array $options = null)
 * @method static string[] sunion(array|string $keys)
 * @method static int sunionstore(string $destination, array|string $keys)
 * @method static int touch(string[]|string $keyOrKeys, string ...$keys = null)
 * @method static Status tdigestadd(string $key, float ...$value)
 * @method static array tdigestbyrank(string $key, int ...$rank)
 * @method static array tdigestbyrevrank(string $key, int ...$reverseRank)
 * @method static array tdigestcdf(string $key, int ...$value)
 * @method static Status tdigestcreate(string $key, int $compression = 0)
 * @method static array tdigestinfo(string $key)
 * @method static string tdigestmax(string $key)
 * @method static Status tdigestmerge(string $destinationKey, array $sourceKeys, int $compression = 0, bool $override = false)
 * @method static string[] tdigestquantile(string $key, float ...$quantile)
 * @method static string tdigestmin(string $key)
 * @method static array tdigestrank(string $key, float ...$value)
 * @method static Status tdigestreset(string $key)
 * @method static array tdigestrevrank(string $key, float ...$value)
 * @method static string tdigesttrimmed_mean(string $key, float $lowCutQuantile, float $highCutQuantile)
 * @method static array topkadd(string $key, ...$items)
 * @method static array topkincrby(string $key, ...$itemIncrement)
 * @method static array topkinfo(string $key)
 * @method static array topklist(string $key, bool $withCount = false)
 * @method static array topkquery(string $key, ...$items)
 * @method static Status topkreserve(string $key, int $topK, int $width = 8, int $depth = 7, float $decay = 0.9)
 * @method static int tsadd(string $key, int $timestamp, float $value, ?AddArguments $arguments = null)
 * @method static Status tsalter(string $key, ?TSAlterArguments $arguments = null)
 * @method static Status tscreate(string $key, ?TSCreateArguments $arguments = null)
 * @method static Status tscreaterule(string $sourceKey, string $destKey, string $aggregator, int $bucketDuration, int $alignTimestamp = 0)
 * @method static int tsdecrby(string $key, float $value, ?DecrByArguments $arguments = null)
 * @method static int tsdel(string $key, int $fromTimestamp, int $toTimestamp)
 * @method static Status tsdeleterule(string $sourceKey, string $destKey)
 * @method static array tsget(string $key, GetArguments $arguments = null)
 * @method static int tsincrby(string $key, float $value, ?IncrByArguments $arguments = null)
 * @method static array tsinfo(string $key, ?InfoArguments $arguments = null)
 * @method static array tsmadd(mixed ...$keyTimestampValue)
 * @method static array tsmget(MGetArguments $arguments, string ...$filterExpression)
 * @method static array tsmrange($fromTimestamp, $toTimestamp, MRangeArguments $arguments)
 * @method static array tsmrevrange($fromTimestamp, $toTimestamp, MRangeArguments $arguments)
 * @method static array tsqueryindex(string ...$filterExpression)
 * @method static array tsrange(string $key, $fromTimestamp, $toTimestamp, ?RangeArguments $arguments = null)
 * @method static array tsrevrange(string $key, $fromTimestamp, $toTimestamp, ?RangeArguments $arguments = null)
 * @method static string xadd(string $key, array $dictionary, string $id = '*', array $options = null)
 * @method static int xdel(string $key, string ...$id)
 * @method static int xlen(string $key)
 * @method static array xrevrange(string $key, string $end, string $start, ?int $count = null)
 * @method static array xrange(string $key, string $start, string $end, ?int $count = null)
 * @method static string xtrim(string $key, array|string $strategy, string $threshold, array $options = null)
 * @method static int zadd(string $key, array $membersAndScoresDictionary)
 * @method static int zcard(string $key)
 * @method static string zcount(string $key, int|string $min, int|string $max)
 * @method static array zdiff(array $keys, bool $withScores = false)
 * @method static int zdiffstore(string $destination, array $keys)
 * @method static string zincrby(string $key, int $increment, string $member)
 * @method static int zintercard(array $keys, int $limit = 0)
 * @method static int zinterstore(string $destination, array $keys, int[] $weights = [], string $aggregate = 'sum')
 * @method static array zinter(array $keys, int[] $weights = [], string $aggregate = 'sum', bool $withScores = false)
 * @method static array zmpop(array $keys, string $modifier = 'min', int $count = 1)
 * @method static array zmscore(string $key, string ...$member)
 * @method static array zpopmin(string $key, int $count = 1)
 * @method static array zpopmax(string $key, int $count = 1)
 * @method static mixed zrandmember(string $key, int $count = 1, bool $withScores = false)
 * @method static array zrange(string $key, int|string $start, int|string $stop, array $options = null)
 * @method static array zrangebyscore(string $key, int|string $min, int|string $max, array $options = null)
 * @method static int zrangestore(string $destination, string $source, int|string $min, int|string $max, string|bool $by = false, bool $reversed = false, bool $limit = false, int $offset = 0, int $count = 0)
 * @method static int|null zrank(string $key, string $member)
 * @method static int zrem(string $key, string ...$member)
 * @method static int zremrangebyrank(string $key, int|string $start, int|string $stop)
 * @method static int zremrangebyscore(string $key, int|string $min, int|string $max)
 * @method static array zrevrange(string $key, int|string $start, int|string $stop, array $options = null)
 * @method static array zrevrangebyscore(string $key, int|string $max, int|string $min, array $options = null)
 * @method static int|null zrevrank(string $key, string $member)
 * @method static array zunion(array $keys, int[] $weights = [], string $aggregate = 'sum', bool $withScores = false)
 * @method static int zunionstore(string $destination, array $keys, int[] $weights = [], string $aggregate = 'sum')
 * @method static string|null   zscore(string $key, string $member)
 * @method static array zscan(string $key, int $cursor, array $options = null)
 * @method static array zrangebylex(string $key, string $start, string $stop, array $options = null)
 * @method static array zrevrangebylex(string $key, string $start, string $stop, array $options = null)
 * @method static int zremrangebylex(string $key, string $min, string $max)
 * @method static int zlexcount(string $key, string $min, string $max)
 * @method static int pexpiretime(string $key)
 * @method static int pfadd(string $key, array $elements)
 * @method static mixed pfmerge(string $destinationKey, array|string $sourceKeys)
 * @method static int pfcount(string[]|string $keyOrKeys, string ...$keys = null)
 * @method static mixed pubsub($subcommand, $argument)
 * @method static int publish($channel, $message)
 * @method static mixed discard()
 * @method static array|null exec()
 * @method static mixed multi()
 * @method static mixed unwatch()
 * @method static array waitaof(int $numLocal, int $numReplicas, int $timeout)
 * @method static mixed watch(string $key)
 * @method static mixed eval(string $script, int $numkeys, string ...$keyOrArg = null)
 * @method static mixed eval_ro(string $script, array $keys, ...$argument)
 * @method static mixed evalsha(string $script, int $numkeys, string ...$keyOrArg = null)
 * @method static mixed evalsha_ro(string $sha1, array $keys, ...$argument)
 * @method static mixed script($subcommand, $argument = null)
 * @method static Status shutdown(bool $noSave = null, bool $now = false, bool $force = false, bool $abort = false)
 * @method static mixed auth(string $password)
 * @method static string echo(string $message)
 * @method static mixed ping(string $message = null)
 * @method static mixed select(int $database)
 * @method static mixed bgrewriteaof()
 * @method static mixed bgsave()
 * @method static mixed client($subcommand, $argument = null)
 * @method static mixed config($subcommand, $argument = null)
 * @method static int dbsize()
 * @method static mixed flushall()
 * @method static mixed flushdb()
 * @method static array info($section = null)
 * @method static int lastsave()
 * @method static mixed save()
 * @method static mixed slaveof(string $host, int $port)
 * @method static mixed slowlog($subcommand, $argument = null)
 * @method static array time()
 * @method static array command()
 * @method static int geoadd(string $key, $longitude, $latitude, $member)
 * @method static array geohash(string $key, array $members)
 * @method static array geopos(string $key, array $members)
 * @method static string|null   geodist(string $key, $member1, $member2, $unit = null)
 * @method static array georadius(string $key, $longitude, $latitude, $radius, $unit, array $options = null)
 * @method static array georadiusbymember(string $key, $member, $radius, $unit, array $options = null)
 * @method static array geosearch(string $key, FromInterface $from, ByInterface $by, ?string $sorting = null, int $count = -1, bool $any = false, bool $withCoord = false, bool $withDist = false, bool $withHash = false)
 * @method static int geosearchstore(string $destination, string $source, FromInterface $from, ByInterface $by, ?string $sorting = null, int $count = -1, bool $any = false, bool $storeDist = false)
 *
 * @see RedisInterface
 */
class Redis extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return RedisInterface::class;
    }
}
