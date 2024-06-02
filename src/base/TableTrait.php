<?php
namespace  horm\base;

/**
 * 数据库访问　DB 实体
 *<B>说明：</B>
 *<pre>
 * 不支持分库分表,如需支持,继承ShardDbEntity,ShardDbTableEntity,ShardTableEntity
 *</pre>
 * @method BaseTable executeCommand()
 * @method BaseTable queryCommand()
 */
trait TableTrait
{

    /**
     * 添加数据行总接口
     *<B>说明：</B>
     *<pre>
     *  可以重写，以便实现分表，分库，等功能
     *</pre>
     * @param Query $query 数据
     * @param  string  $queryType 操作方法
     * @return Query
	 *<pre>
	 * 	int:10,添加成功行数
	 * 	boolean:false sql 错误
	 *</pre>
     */
	public function addInternal(Query $query,$queryType = '')
	{

		$query->setBuildParams([$query]);
        $query->asWrite(true);

		if ($query->asQueryStatus()) {
			return $query;
		}

		$affected = $this->executeCommand($query);

        $query->setResult($affected);

        return $query;
	}

    /**
     * 修改记录总接口
     *<B>说明：</B>
     *<pre>
     *  可以重写，以便实现分表，分库，等功能
     *</pre>
     * @param Query $query 数据
     * @param string  $queryType 操作方法
     * @return Query
	 *<pre>
	 * 	int:10,更新成功行数
	 * 	boolean:false sql 错误
	 *</pre>
     */
	public function updateInternal(Query $query,$queryType = '')
	{

		$query->setBuildParams([$query]);
        $query->asWrite(true);
		if ($query->asQueryStatus()) {
			return $query;
		}

		$affected = $this->executeCommand($query);

        $query->setResult($affected);

		return $query;
	}

    /**
     * 删除记录总接口
     *<B>说明：</B>
     *<pre>
     *  可以重写，以便实现分表，分库，等功能
     *</pre>
     * @param Query $query 数据
     * @param  string  $queryType 操作方法
	 * @return Query
	 *<pre>
	 * 	int:10,删除成功行数
	 * 	boolean:false sql 错误
	 *</pre>
     */
	public function deleteInternal(Query $query,$queryType = '')
	{

		$query->setBuildParams([$query]);
        $query->asWrite(true);

		if ($query->asQueryStatus()) {
			return $query;
		}

		$affected = $this->executeCommand($query);

        $query->setResult($affected);

        return $query;
	}

    /**
     * 查询总接口总接口
     *<B>说明：</B>
     *<pre>
     *  可以重写，以便实现分表，分库，等功能
     *</pre>
     * @param Query $query 数据
     * @param  string $queryType 操作方法
     * @return Query
	 *<pre>
	 * 	array:1数据行(二维数组)
	 * 	boolean:false sql 错误
	 *</pre>
     */
    public function queryInternal(Query $query,$queryType = '')
    {
		$query->setBuildParams([$query]);
        $query->asWrite(false);
		if ($query->asQueryStatus()) {
			return $query;
		}

		$queryResult = $this->queryCommand($query);

        $query->setResult($queryResult);

        return $query;
    }

	/**
	 * 查询第一行，第一列总接口
	 *<B>说明：</B>
	 *<pre>
	 *  可以重写，以便实现分表，分库，等功能
	 *</pre>
	 * @param Query $query 数据
	 * @param  string  $method 操作方法 count,min,max,avg
	 * @return Query
	 *<pre>
	 * 	array:数据行(二维数组)
	 * 	boolean:false sql 错误
	 *</pre>
	 */
	public function queryScalarInternal(Query $query,$method = '')
	{
		$query->setBuildParams([$query,$method]);
        $query->asWrite(false);
		if ($query->asQueryStatus()) {
			return $query;
		}

		$queryResult = $this->queryCommand($query);
        $queryResult = $this->_scalarMethod($method,$queryResult);
        $query->setResult($queryResult);

		return $query;
	}

    /**
     * 计算scalar最终结果
     *<B>说明：</B>
     *<pre>
     *  计算分片序号
     *</pre>
     * @param string $method 方法名,比如sum,avg
     * @param array $reuslt db 查询结果
     * @return string|null
     */
    public function _scalarMethod($method, $reuslt)
    {
        // 结果转换
        $handle_method = $method . 'ScalarValue';
        if (method_exists($this, $handle_method)) {
            $reuslt = call_user_func([&$this, $handle_method], $reuslt);
        }

        return $reuslt;
    }

    /**
     * 统计查询结果行数
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $result 二维数组
     * @param string $field 字段名
     * @return string
     */
    public function countScalarValue($result,$field = '__result')
    {

        $count = 0;
        foreach ($result as $column) {
            $count = $count + $column[$field];
        }

        return $count;
    }

    /**
     * 获取查询结果最大值
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $result 二维数组
     * @param string $field 字段名
     * @return string
     */
    public function maxScalarValue($result,$field = '__result')
    {
        $max = 0;
        foreach ($result as $rows) {
            $max = max($max, $rows[$field]);
        }

        return $max;
    }

    /**
     * 获取查询结果最小值
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $result 二维数组
     * @param string $field 字段名
     * @return string
     */
    public function minScalarValue($result,$field = '__result')
    {
        $max = 0;
        foreach ($result as $rows) {
            $max = max($max, $rows[$field]);
        }

        return $max;
    }

    /**
     * 获取查询结果字段累加
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $result 二维数组
     * @param string $field 字段名
     * @return string
     */
    public function sumScalarValue($result,$field = '__result')
    {
        $sum = 0;
        foreach ($result as $rows) {
            $sum = bcadd($sum, $rows[$field]);
        }

        return $sum;
    }

    /**
     * 获取查询结果字段平均值
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $result 二维数组
     * @param string $field 字段名
     * @return string
     */
    public function avgScalarValue($result,$field = '__result')
    {
        $sum = 0;
        foreach ($result as $rows) {
            $sum = bcadd($sum, $rows[$field]);
        }

        $count = count($result);

        return number_format($sum / $count,2);
    }


}

?>
