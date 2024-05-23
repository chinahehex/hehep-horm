<?php
namespace  horm\shard\base;

use horm\base\BaseTable;
use horm\base\Query;

/**
 * 分库实体基类
 *<B>说明：</B>
 *<pre>
 * 	只分库,不分表
 *</pre>
 * @method BaseTable executeCommand()
 * @method BaseTable queryCommand()
 */
trait ShardDbTrait
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
	public function addInternal($query,$queryType = '')
	{
        $affected = 0;
        $addResult = true;//更新状态
		$queryList = $this->dataShardByDb($query);

		foreach ($queryList as $cmdQuery) {
            $cmdQuery->toUpdate(true);
			$cmdQuery->setBuildParams([$cmdQuery]);
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
	 *  1、可以重写，以便实现分表，分库，等功能
	 *</pre>
	 * @param Query $query 数据
	 * @param  string  $queryType 操作方法
	 * @return Query
	 *<pre>
	 *  	int:更新成功行数
	 * 		boolean:sql 错误
	 *</pre>
	 */
	public function updateInternal($query,$queryType = '')
	{
        $updateResult = true;//更新状态
	    $affected = 0;//影响行数

		$queryList = $this->whereShardByDb($query);

		foreach ($queryList as $cmdQuery) {
            $cmdQuery->toUpdate(true);
			$cmdQuery->setBuildParams([$cmdQuery]);
			$result = $this->executeCommand($cmdQuery);
			if ($result === false) {
                $updateResult = false;
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
	public function deleteInternal($query,$queryType = '')
	{
        $deleteResult = true;//更新状态
        $affected = 0;
		$queryList = $this->whereShardByDb($query);

		foreach ($queryList as $cmdQuery) {
            $cmdQuery->toUpdate(true);
			$cmdQuery->setBuildParams([$cmdQuery]);
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
	public function queryInternal($query,$queryType = '')
	{
		$queryList = $this->whereShardByDb($query);
		$queryResult = [];

		foreach ($queryList as $cmdQuery) {
            $cmdQuery->toUpdate(false);
			$cmdQuery->setBuildParams([$cmdQuery]);
			$data = $this->queryCommand($cmdQuery);
			if (!empty($data)) {
				$queryResult = array_merge($queryResult,$data);
			}
		}

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
	public function queryScalarInternal($query ,$method = '')
	{
		$queryList = $this->whereShardByDb($query);
		$queryResult = [];

		foreach ($queryList as $cmdQuery) {
            $cmdQuery->toUpdate(false);
			$cmdQuery->setBuildParams([$cmdQuery,$method]);
			$data = $this->queryCommand($cmdQuery);
			if (!empty($data)) {
				$queryResult = array_merge($queryResult,$data);
			}
		}

		$queryResult = $this->_scalarMethod($method,$queryResult);
        $query->setResult($queryResult);

		return $query;
	}
}

?>