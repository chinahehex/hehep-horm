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
     * 查询sql模板
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
    protected $selectSql  = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%ALIAS%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER% %UNION%%COMMENT%';

    /**
     * 插入sql模板
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
    protected $insertSql = '%INSERT% INTO %TABLE% (%FIELD%) VALUES (%DATA%)';

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

	protected function parseLock($lock = false)
    {
        if (!$lock) {
            return '';
        }

        return '';
    }
	
	    /**
     * 替换SQL语句中表达式
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string $sql sql语句
     * @param Query $query sql参数
     * @return string
     */
    public function parseSql($sql,$query)
    {
		$offset = $query->getOffset();
		$limit = $query->getLimit();
		if (isset($offset)) {
			$limitWhere = [
				'rownum'=>['between',[$offset,$limit + $offset]]
			];
		} else {
			$limitWhere = [
				'rownum'=>['between',[1,$limit]]
			];
		}

		if (isset($limit) || isset($offset)) {
			$query->setWhere($limitWhere);
		}
		

        $sql   = str_replace(
            ['%TABLE%','%DISTINCT%','%FIELD%','%ALIAS%','%JOIN%','%WHERE%','%GROUP%','%HAVING%','%ORDER%','%UNION%','%COMMENT%'],
            [
                $this->parseTable($query->getTable()),
                $this->parseDistinct($query->getDistinct()),
                $this->parseField($query,$query->getField()),
                $this->parseAlias($query->getAlias()),
                $this->parseJoin($query->getJoin(),$query->getSeq()),
                $this->parseWhere($query->getWhere()),
                $this->parseGroup($query->getGroup()),
                $this->parseHaving($query->getHaving()),
                $this->parseOrder($query->getOrder()),
                $this->parseUnion($query->getUnion()),
			],$sql);
			
			

        return $sql;
	}


	public function delete($query)
    {
        $sql = str_replace(
            ['%TABLE%', '%USING%', '%JOIN%', '%WHERE%', '%ORDER%','%LOCK%'],
            [
                $this->parseTable($query->getTable()),
                $this->parseAlias($query->getAlias()),
                $this->parseWhere($query->getWhere()),
                $this->parseOrder($query->getOrder()),
                $this->parseLock($query->getLock()),
            ], $this->deleteSql);

        return $sql;
	}
	
	public function update($query)
    {

        $sql = str_replace(
            ['%TABLE%', '%SET%', '%JOIN%', '%WHERE%', '%ORDER%', '%LOCK%'],
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
     * 构建 between where 条件 sql
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $operator 操作符
     * @param array $condition 字段名，字段值 [$column,$values]
     * @return string
     */
    protected function bulidBetweenWhere($operator,$condition = [])
    {
        list($columnName, $values) = $condition;
        $betweenValues = [];
        foreach ($values as $value) {
            $betweenValues[] = $this->buildColumnValue($columnName,$value);
        }

        $columnName = $this->parseColumnName($columnName);
		$buildSql = " {$columnName} >= {$betweenValues[0]} AND {$columnName} <= {$betweenValues[1]}";
		
        return $buildSql;
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