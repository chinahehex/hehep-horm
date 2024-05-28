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
        if (!preg_match('/[,\'\"\*\(\)`.\s]/',$column_name)) {
            $column_name = ''.$column_name.'';
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
    public function parseSql(Query $query,$sql)
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
                $this->parseTable($query,$query->getTable()),
                $this->parseDistinct($query,$query->getDistinct()),
                $this->parseField($query,$query,$query->getField()),
                $this->parseAlias($query,$query->getAlias()),
                $this->parseJoin($query,$query->getJoin()),
                $this->parseWhere($query,$query->getWhere()),
                $this->parseGroup($query,$query->getGroup()),
                $this->parseHaving($query,$query->getHaving()),
                $this->parseOrder($query,$query->getOrder()),
                $this->parseUnion($query,$query->getUnion()),
			],$sql);



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
