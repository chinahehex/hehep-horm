<?php
namespace horm\drivers\oci;

use horm\base\Query;
use horm\builders\SqlQueryBuilder;

/**
 * oracle 生成SQL类
 *<B>说明：</B>
 *<pre>
 *  略
 *</pre>
 */
class OciQueryBuilder extends SqlQueryBuilder
{

    /**
     * 更新sql模板
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
    protected $updateSql = 'UPDATE %TABLE% SET %SET% %JOIN% %WHERE% %ORDER% %LOCK%';

    /**
     * 删除sql模板
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
	protected $deleteSql = 'DELETE FROM %TABLE% %USING% %JOIN% %WHERE% %ORDER% %LOCK%';

    /**
     * 字段和表名处理
     * @access protected
     * @param string $key
     * @return string
     */
    public function formatColumnName(Query $query,$column_name = '')
    {
        //todo 判断表别名
        $column_name = trim($column_name);
        if ($column_name === 'rownum'){
            return $column_name;
        }

        if (!preg_match('/[,\'\"\*\(\)`.\s]/',$column_name)) {
            $column_name = '"'.$column_name.'"';
        }

        return $column_name;
    }

	protected function parseLock(Query $query,$lock = false)
    {
        if (!$lock) {
            return '';
        }

        return '';
    }

    protected function parseAlias(Query $query,$alias = '')
    {
        $buildSql = '';
        if (!empty($alias)) {
            $buildSql = ' ' . $this->parseColumnName($query,$alias);
        }

        return $buildSql;
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
        $sql_fields = implode(',', array_map(function($field)use($query){return $this->parseColumnName($query,$field);},$fields));
        $insert_sqls = [];
        foreach ($datas as $data) {
            $value = [];
            foreach ($data as $columnName=>$columnValue){
                if (is_array($columnValue)) {
                    $operator = $columnValue[0];
                    $value[] = $this->callExpressionMethod($query,$operator,$columnName,$columnValue[1]);
                } else {
                    $value[] = $this->buildColumnValue($query,$columnName,$columnValue);
                }
            }

            $sql_values = '('.implode(',', $value).')';

            $sql   =  ' INTO ' . $this->parseTable($query,$query->getTable())
                . ' ('.$sql_fields.') VALUES '.$sql_values;
            $insert_sqls[] = $sql;
        }

        // INSERT ALL
        $sql = 'INSERT ALL ' . implode(' ',$insert_sqls) . " select 1 from dual";

        return $sql;
    }


	public function delete(Query $query)
    {
        $sql = str_replace(
            ['%TABLE%', '%USING%', '%JOIN%', '%WHERE%', '%ORDER%','%LOCK%'],
            [
                $this->parseTable($query,$query->getTable()),
                $this->parseAlias($query,$query->getAlias()),
                $this->parseWhere($query,$query->getWhere()),
                $this->parseOrder($query,$query->getOrder()),
                $this->parseLock($query,$query->getLock()),
            ], $this->deleteSql);

        return $sql;
	}

	public function update(Query $query)
    {

        $sql = str_replace(
            ['%TABLE%', '%SET%', '%JOIN%', '%WHERE%', '%ORDER%', '%LOCK%'],
            [
                $this->parseTable($query,$query->getTable()),
                $this->parseAlias($query,$query->getAlias()),
                $this->parseSet($query,$query->getData()),
                $this->parseWhere($query,$query->getWhere()),
                $this->parseOrder($query,$query->getOrder()),
                $this->parseLock($query,$query->getLock()),
            ], $this->updateSql);

        return $sql;
    }






}
