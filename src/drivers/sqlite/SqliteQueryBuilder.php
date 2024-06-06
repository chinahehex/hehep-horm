<?php
namespace horm\drivers\sqlite;

use horm\base\Query;
use horm\builders\SqlQueryBuilder;

/**
 * sqlite3 生成SQL类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class SqliteQueryBuilder extends SqlQueryBuilder
{
    /**
     * 更新sql模板
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
    protected $updateSql = 'UPDATE [TABLE][ALIAS] SET [SET][JOIN][WHERE][ORDER][LOCK]';

    /**
     * 删除sql模板
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
    protected $deleteSql = 'DELETE FROM [TABLE][ALIAS] [JOIN][WHERE][ORDER][LOCK]';

	/**
	 * 字段和表名处理
	 * @access protected
	 * @param string $column_name
	 * @return string
	 */
    public function formatColumnName(Query $query,$column_name = '')
    {
        //todo 判断表别名
        $column_name = trim($column_name);
		if (!preg_match('/[,\'\"\*\(\)`.\s]/',$column_name)) {
            $column_name = '`'.$column_name.'`';
		}

		return $column_name;
	}

    /**
     * 生成 update sql
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query 命令对象
     * @return string
     */
    public function update(Query $query)
    {
        $sql = str_replace(
            ['[TABLE]','[ALIAS]', '[SET]', '[JOIN]', '[WHERE]','[ORDER]','[LOCK]'],
            [
                $this->parseTable($query,$query->getTable()),
                $this->parseAlias($query,$query->getAlias()),
                $this->parseSet($query,$query->getData()),
                $this->parseJoin($query,$query->getJoin()),
                $this->parseWhere($query,$query->getWhere()),
                $this->parseOrder($query,$query->getOrder()),
                $this->parseLock($query,$query->getLock()),
            ], $this->updateSql);

        return $sql;
    }

    public function delete(Query $query)
    {
        $sql = str_replace(
            ['[TABLE]','[ALIAS]','[JOIN]', '[WHERE]', '[ORDER]', '[LOCK]'],
            [
                $this->parseTable($query,$query->getTable()),
                $this->parseAlias($query,$query->getAlias()),
                $this->parseJoin($query,$query->getJoin()),
                $this->parseWhere($query,$query->getWhere()),
                $this->parseOrder($query,$query->getOrder()),
                $this->parseLock($query,$query->getLock()),
            ], $this->deleteSql);

        return $sql;
    }
}
