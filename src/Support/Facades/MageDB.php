<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\App\ResourceConnection;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Database adapter service.
 *
 * @method static object load(object $model, string $identifier, array $arguments = [])
 * @method static void beginTransaction()
 * @method static void commit()
 * @method static void rollBack()
 * @method static \Zend_Db_Statement_Interface query($sql, $bind = [])
 * @method static array fetchAll($sql, $bind = [], $fetchMode = null)
 * @method static array fetchRow($sql, $bind = [], $fetchMode = null)
 * @method static array fetchAssoc($sql, $bind = [])
 * @method static array fetchCol($sql, $bind = [])
 * @method static array fetchPairs($sql, $bind = [])
 * @method static string fetchOne($sql, $bind = [])
 * @method static string quote($value, $type = null)
 * @method static string quoteInto($text, $value, $type = null, $count = null)
 * @method static string quoteIdentifier($ident, $auto = false)
 * @method static string quoteColumnAs($ident, $alias, $auto = false)
 * @method static string quoteTableAs($ident, $alias = null, $auto = false)
 * @method static string formatDate($date, $includeTime = true)
 * @method static void startSetup()
 * @method static void endSetup()
 * @method static void setCacheAdapter(\Magento\Framework\Cache\FrontendInterface $cacheAdapter)
 * @method static void allowDdlCache()
 * @method static void disallowDdlCache()
 * @method static void resetDdlCache($tableName = null, $schemaName = null)
 * @method static void saveDdlCache($tableCacheKey, $ddlType, $data)
 * @method static array loadDdlCache($tableCacheKey, $ddlType)
 * @method static string prepareSqlCondition($fieldName, $condition)
 * @method static mixed prepareColumnValue(array $column, $value)
 * @method static string getCheckSql($condition, $true, $false)
 * @method static string getIfNullSql($expression, $value = 0)
 * @method static string getConcatSql(array $data, $separator = null)
 * @method static string getLengthSql($string)
 * @method static string getLeastSql(array $data)
 * @method static string getGreatestSql(array $data)
 * @method static string getDateAddSql($date, $interval, $unit)
 * @method static string getDateSubSql($date, $interval, $unit)
 * @method static string getDateFormatSql($date, $format)
 * @method static string getDatePartSql($date)
 * @method static string getSubstringSql($stringExpression, $pos, $len = null)
 * @method static string getStandardDeviationSql($expressionField)
 * @method static string getDateExtractSql($date, $unit)
 * @method static string getTableName($tableName)
 * @method static string getTriggerName($tableName, $time, $event)
 * @method static string getIndexName($tableName, $fields, $indexType = '')
 * @method static string getForeignKeyName($priTableName, $priColumnName, $refTableName, $refColumnName)
 * @method static void disableTableKeys($tableName, $schemaName = null)
 * @method static void enableTableKeys($tableName, $schemaName = null)
 * @method static void insertFromSelect(\Magento\Framework\DB\Select $select, $table, array $fields = [], $mode = false)
 * @method static array selectsByRange($rangeField, \Magento\Framework\DB\Select $select, $stepCount = 100)
 * @method static void updateFromSelect(\Magento\Framework\DB\Select $select, $table)
 * @method static void deleteFromSelect(\Magento\Framework\DB\Select $select, $table)
 * @method static array getTablesChecksum($tableNames, $schemaName = null)
 * @method static bool supportStraightJoin()
 * @method static void orderRand(\Magento\Framework\DB\Select $select, $field = null)
 * @method static void forUpdate($sql)
 * @method static string getPrimaryKeyName($tableName, $schemaName = null)
 * @method static string decodeVarbinary($value)
 * @method static int getTransactionLevel()
 * @method static array getTables($likeCondition = null)
 * @method static string getCaseSql($valueName, $casesResults, $defaultValue = null)
 * @method static string getAutoIncrementField($tableName, $schemaName = null)
 * @method static void addColumn(string $tableName, string $columnName, array|string $definition, string $schemaName = null)
 *
 * @see AdapterInterface
 */
class MageDB extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    public static function getIndexPrfix(): string
    {
        return 'IDX';
    }

    /**
     * Get the columns for a given table.
     *
     * @param  string  $table
     *
     * @return array
     */
    public static function getColumns($table)
    {
        $tableName = static::getFacadeRoot()->getConnection()->getTable($table);

        return static::getFacadeRoot()->getConnection()->describeTable($tableName);
    }

    /**
     * Get the indexes for a given table.
     *
     * @param  string  $table
     *
     * @return array
     */
    public static function getIndexes($table)
    {
        $tableName = static::getFacadeRoot()->getConnection()->getTable($table);

        return static::getFacadeRoot()->getConnection()->getIndexList($tableName);
    }

    /**
     * Get the foreign keys for a given table.
     *
     * @param  string  $table
     *
     * @return array
     */
    public static function getForeignKeys($table)
    {
        $tableName = static::getFacadeRoot()->getConnection()->getTable($table);

        return static::getFacadeRoot()->getConnection()->getForeignKeys($tableName);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return ResourceConnection::class;
    }

    /**
     * Proxy method calls to the database connection.
     *
     * @param string $method The method name being called.
     * @param string[] $args The arguments passed to the method.
     *
     * @return mixed The result of the method call.
     */
    public static function __callStatic($method, $args)
    {
        // Resolve the database connection instance
        $connection = static::getFacadeRoot()->getConnection();

        // Call the method on the connection instance
        return call_user_func_array([$connection, $method], $args);
    }
}
