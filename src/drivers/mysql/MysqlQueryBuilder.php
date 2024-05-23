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
			$key = '`'.$key.'`';
		}

		return $key;
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
                    $value[] = $this->callExpressionMethod($operator,$columnName,$columnValue[1]);
                } else {
                    $value[] = $this->buildColumnValue($columnName,$columnValue);
                }
            }

            $values[]    = '('.implode(',', $value).')';
        }

        $replace = $query->getReplace() ? true : false;

        $sql   =  ($replace?'REPLACE':'INSERT').' INTO ' . $this->parseTable($query->getTable())
            . ' ('.implode(',', $fields).') VALUES '.implode(',',$values);

        return $sql;
    }
}