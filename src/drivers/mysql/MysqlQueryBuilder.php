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

}
