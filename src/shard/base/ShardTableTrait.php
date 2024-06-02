<?php
namespace  horm\shard\base;

use horm\base\BaseTable;
use horm\base\Query;

/**
 * 分表实体基类
 *<B>说明：</B>
 *<pre>
 * 	只支持分表，不分库
 *</pre>
 * @method BaseTable executeCommand()
 * @method BaseTable queryCommand()
 */
trait ShardTableTrait
{

	use Partition;

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
		if ($query->asQueryStatus()) {
			$query->asWrite(true)->setBuildParams([$query]);
			return $query;
		}

        $addResult = true;//更新状态
        $affected = 0;
		$queryList = $this->dataShardByTable($query);

		foreach ($queryList as $cmdQuery) {
			$cmdQuery->setBuildParams([$cmdQuery]);
            $cmdQuery->asWrite(true);
			$result = $this->executeCommand($cmdQuery);
			if ($result === false) {
                $addResult = false;
				break;
			}

            $affected += $result;
		}

        if ($addResult === false) {
            $query->setResult(false);
        } else {
            $query->setResult($affected);
        }

		return $query;
	}

	/**
	 * 更新数据行总接口
	 *<B>说明：</B>
	 *<pre>
	 *  	1、可以重写，以便实现分表，分库，等功能
	 *</pre>
	 * @param Query $query 数据
	 * @param  string  $queryType 操作方法
	 * @return Query
	 *<pre>
	 *  	int:更新成功行数
	 * 		boolean:sql 错误
	 *</pre>
	 */
	public function updateInternal(Query $query,$queryType = '')
	{
		if ($query->asQueryStatus()) {
			$query->asWrite(true)->setBuildParams([$query]);
			return $query;
		}

        $affected = 0;//影响行数
        $updateResult = true;
		$queryList = $this->whereShardByTable($query);

		foreach ($queryList as $cmdQuery) {
			$cmdQuery->setBuildParams([$cmdQuery]);
            $cmdQuery->asWrite(true);
			$result = $this->executeCommand($cmdQuery);
			if ($result === false) {
				break;
			}

            $affected += $result;
		}

        if ($updateResult === false) {
            $query->setResult(false);
        } else {
            $query->setResult($affected);
        }

		return $query;
	}

	/**
	 * 删除数据行总接口
	 *<B>说明：</B>
	 *<pre>
	 *  可以重写，以便实现分表，分库，等功能
	 *</pre>
	 * @param Query $query 数据
	 * @param  string  $queryType 操作方法
	 * @return Query
	 *<pre>
	 *  	int:删除成功行数
	 * 		boolean:sql 错误
	 *</pre>
	 */
	public function deleteInternal(Query $query,$queryType = '')
	{
		if ($query->asQueryStatus()) {
			$query->asWrite(true)->setBuildParams([$query]);
			return $query;
		}

        $affected = 0;
        $deleteResult = true;//更新状态
		$queryList = $this->whereShardByTable($query);

		foreach ($queryList as $cmdQuery) {
			$cmdQuery->setBuildParams([$cmdQuery]);
            $cmdQuery->asWrite(true);
			$result = $this->executeCommand($cmdQuery);
			if ($result === false) {
                $deleteResult = false;
				break;
			}

            $affected += $result;
		}

        if ($deleteResult === false) {
            $query->setResult(false);
        } else {
            $query->setResult($affected);
        }

		return $query;
	}


	/**
	 * 查询数据行总接口
	 *<B>说明：</B>
	 *<pre>
	 *  可以重写，以便实现分表，分库，等功能
	 *</pre>
	 * @param Query $query 数据
	 * @param  string  $queryType 操作方法
	 * @return Query
	 *<pre>
	 *  	array:数据行(二维数组)
	 * 		boolean:sql 错误
	 *</pre>
	 */
	public function queryInternal(Query $query,$queryType = '')
	{
		if ($query->asQueryStatus()) {
			$query->asWrite(false)->setBuildParams([$query]);
			return $query;
		}

		$queryList = $this->whereShardByTable($query);
		foreach ($queryList as $cmdQuery) {
			$cmdQuery->setBuildParams([$cmdQuery]);
		}

		/** @var Query $mainQuery*/
		$mainQuery = array_shift($queryList);
		$mainQuery->addUnion($queryList);
        $mainQuery->asWrite(false);

		$queryResult = $this->queryCommand($mainQuery);

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
	 *  	array:数据行(二维数组)
	 * 		boolean:sql 错误
	 *</pre>
	 */
	public function queryScalarInternal(Query $query ,$method = '')
	{
		if ($query->asQueryStatus()) {
			$query->asWrite(false)->setBuildParams([$query]);
			return $query;
		}

		$queryList = $this->whereShardByTable($query);

		foreach ($queryList as $cmdQuery) {
			$cmdQuery->setBuildParams([$cmdQuery,$method]);
		}

		/** @var Query $mainQuery*/
		$mainQuery = array_shift($queryList);
		$mainQuery->addUnion($queryList);
        $mainQuery->asWrite(false);

		$queryResult = $this->queryCommand($mainQuery);
		$queryResult = $this->_scalarMethod($method,$queryResult);

        $query->setResult($queryResult);

		return $query;
	}

}

?>
