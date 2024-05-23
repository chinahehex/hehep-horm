<?php
namespace horm\drivers\pgsql;

use horm\base\Query;
use horm\builders\SqlQueryBuilder;

/**
 * Pgsql 生成SQL类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class PgsqlQueryBuilder extends SqlQueryBuilder
{
    protected function parseLock($lock = false)
    {
        if (!$lock) {
            return '';
        }

        return '';
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
            ['%TABLE%', '%SET%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%'],
            [
                $this->parseTable($query->getTable()),
                $this->parseAlias($query->getAlias()),
                $this->parseSet($query->getData()),
                $this->parseWhere($query->getWhere()),
                $this->parseOrder($query->getOrder()),
                $this->parseLock($query->getLock()),
            ], $this->updateSql);

        return $sql;
    }

	/**
	 * 字段和表名处理
	 * @access protected
	 * @param string $key
	 * @return string
	 */
	protected function parseColumnName($key  = '')
    {
        //todo 判断表别名
		$key = trim($key);
		if (!preg_match('/[,\'\"\*\(\)`.\s]/',$key)) {
			$key = ''.$key.'';
		}

		return $key;
	}
}