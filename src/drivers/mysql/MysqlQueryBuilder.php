<?php
namespace horm\drivers\mysql;

use horm\base\Query;
use horm\builders\SqlQueryBuilder;

/**
 * mysql 生成SQL类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class MysqlQueryBuilder extends SqlQueryBuilder
{

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
     * 构建 between where 条件 sql
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $operator 操作符
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidBetweenWhere(Query $query,$operator,$condition = [])
    {
        list($columnName, $values) = $condition;
        $betweenValues = [];
        foreach ($values as $value) {
            $betweenValues[] = $this->buildColumnValue($query,$columnName,$value);
        };

        $columnName = $this->parseColumnName($query,$columnName);
        $buildSql = " {$columnName} BETWEEN {$betweenValues[0]} AND {$betweenValues[1]}";

        return $buildSql;
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
}
