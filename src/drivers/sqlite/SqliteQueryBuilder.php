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
     * 批量插入记录
     * @param Query $query 数据
     * true 启用,false 禁用
     * @return bool|int|void
     */
    public function insertAll(Query $query)
    {
        //批量插入数据，第一个参数必须是数组
        $datas = $query->getData();

        if (!is_array($datas[0])) {
            return false;
        }

        //读取字段名数组
        $fields = array_keys($datas[0]);
        //格式化字段名，每个$fields 元素都调用parseKey 方法
        //array_walk($fields, array($this, 'parseKey'));
        $values  =  array();
        foreach ($datas as $data) {
            $value   =  [];
            foreach ($data as $columnName=>$columnValue){
                if (is_array($columnValue)) {
                    $operator = $columnValue[0];
                    $value[] = $this->callExpressionMethod($query,$operator,$columnName,$columnValue[1]);
                } else {
                    $value[] = $this->buildColumnValue($query,$columnName,$columnValue);
                }
            }

            $values[]    = '('.implode(',', $value).')';
        }

        $replace = $query->getReplace() ? true : false;

        $sql   =  ($replace?'REPLACE':'INSERT').' INTO ' . $this->parseTable($query,$query->getTable())
            . ' ('.implode(',', array_map(function($field)use($query){return $this->parseColumnName($query,$field);},$fields)).') VALUES '.implode(',',$values);

        return $sql;
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
                $this->parseTable($query,$query->getTable()),
                $this->parseAlias($query,$query->getAlias()),
                $this->parseSet($query,$query->getData()),
                $this->parseWhere($query,$query->getWhere()),
                $this->parseOrder($query,$query->getOrder()),
                //$this->parseLimit($query->getLimit()),
                $this->parseLock($query,$query->getLock()),
            ], $this->updateSql);

        return $sql;
    }

    public function delete(Query $query)
    {
        $sql = str_replace(
            ['%TABLE%', '%USING%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%'],
            [
                $this->parseTable($query,$query->getTable()),
                $this->parseAlias($query,$query->getAlias()),
                $this->parseWhere($query,$query->getWhere()),
                $this->parseOrder($query,$query->getOrder()),
                //$this->parseLimit($query->getLimit()),
                $this->parseLock($query,$query->getLock()),
            ], $this->deleteSql);

        return $sql;
    }
}
