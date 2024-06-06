<?php
namespace horm\drivers\sqlsrv;

use horm\base\Query;
use horm\builders\SqlQueryBuilder;

/**
 * mysql 生成SQL类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class SqlsrvQueryBuilder extends SqlQueryBuilder
{
    /**
     * 查询sql模板
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
    protected $selectopSql  = 'SELECT[DISTINCT] [LIMIT][FIELD] FROM [TABLE][ALIAS][JOIN][WHERE][GROUP][HAVING][UNION][ORDER]';

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

    public function formatColumnName(Query $query,$column_name = '')
    {
        //todo 判断表别名
        $column_name = trim($column_name);
        if (!preg_match('/[,\'\"\*\(\)`.\s]/',$column_name)) {
            $column_name = '"'.$column_name.'"';
        }

        return $column_name;
    }


    protected function parseLock(Query $query,bool $lock = false)
    {
        if (!$lock) {
            return '';
        }

        return '';
    }

    protected function parseLimit(Query $query,$length = null,$offset = null)
    {
        $limitSql = '';

        if (isset($length) || isset($offset)) {
            if (isset($offset)) {
                $limitSql = ' OFFSET '.$offset.' ROWS FETCH NEXT ' . $length . ' ROWS ONLY';
            } else {
                $limitSql = ' OFFSET 0 ROWS FETCH NEXT ' . $length . ' ROWS ONLY';
            }
        }

        return $limitSql;
    }

    /**
     * 替换SQL语句中表达式
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param Query $query Query 对象
     * @param string $sql_tpl sql模板
     * @return string
     */
    public function parseSql(Query $query,$sql_tpl)
    {

        $select_parse_params =  [
            '[TABLE]'=>$this->parseTable($query,$query->getTable()),
            '[DISTINCT]'=>$this->parseDistinct($query,$query->getDistinct()),
            '[FIELD]'=>$this->parseField($query,$query->getField()),
            '[ALIAS]'=>$this->parseAlias($query,$query->getAlias()),
            '[JOIN]'=>$this->parseJoin($query,$query->getJoin()),
            '[WHERE]'=>$this->parseWhere($query,$query->getWhere()),
            '[GROUP]'=>$this->parseGroup($query,$query->getGroup()),
            '[HAVING]'=>$this->parseHaving($query,$query->getHaving()),
            '[UNION]'=>$this->parseUnion($query,$query->getUnion()),
            '[ORDER]'=>$this->parseOrder($query,$query->getOrder()),
            '[LIMIT]'=>$this->parseLimit($query,$query->getLimit(),$query->getOffset()),
        ];

        if (!is_null($query->getLimit()) && !is_null($query->getOffset())) {
            $sql = str_replace(array_keys($select_parse_params), array_values($select_parse_params), $this->selectSql);
        } else if (!is_null($query->getLimit())) {
            $select_parse_params['[LIMIT]'] = 'top ' . $query->getLimit() . ' ';
            $sql = str_replace(array_keys($select_parse_params), array_values($select_parse_params), $this->selectopSql);
        } else {
            $sql = str_replace(array_keys($select_parse_params), array_values($select_parse_params), $this->selectSql);
        }

        return $sql;
    }


    public function update(Query $query)
    {

        $sql = str_replace(
            ['[TABLE]','[ALIAS]' ,'[SET]', '[JOIN]', '[WHERE]', '[ORDER]', '[LOCK]'],
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
            ['[TABLE]', '[ALIAS]', '[JOIN]', '[WHERE]', '[ORDER]','[LOCK]'],
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
