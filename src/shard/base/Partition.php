<?php
namespace  horm\shard\base;

use horm\base\BaseQueryBuilder;
use horm\base\Query;
use horm\base\RawComparison;
use horm\builders\SqlQueryBuilder;

/**
 * 分区相关方法trait
 *<B>说明：</B>
 *<pre>
 * 	分区公共方法
 *</pre>
 */
trait  Partition
{
    /**
     * 分库规则对象
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var ShardRule
     */
    protected $dbShardRule;

    /**
     * 分表规则对象
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var ShardRule
     */
    protected $tbShardRule;


    public function setDbRule($dbShardRule)
    {
        $this->dbShardRule = $dbShardRule;
        return $this;
    }

    public function setTbRule($tbShardRule)
    {
        $this->tbShardRule = $tbShardRule;
        return $this;
    }

	/**
	 * 按库名分离(归类)添加数据
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param Query $query 命令对象
	 * @return Query[]
	 */
	public function dataShardByDb($query)
	{
		/** @var Query[] $queryList */
		$queryList = [];

		if (empty($this->dbShardRule)) {
			$queryList[$query->getDbkey()] = $query;
		} else {
			$shard_columns = $query->getShard();
			if (!empty($shard_columns) && isset($shard_columns[$this->dbShardRule->getFields()])) {
				$shard_value = $shard_columns[$this->dbShardRule->getFields()];
				$shardId = $this->dbShardRule->getShardId($this, $shard_value);
				$shardDb = $this->buildShardname($query->getDbkey(),$shardId);
				$queryList[$shardDb] = $query->cloneQuery(['dbkey'=>$shardDb]);
			} else {
				$shardColumnName = $this->dbShardRule->getFields();
				$addData = [];
				if ($query->getBuildMethod() == Query::BUILD_INSERT) {
					$addData[] = $query->getData();
				} else {
					$addData = $query->getData();
				}

				foreach ($addData as $field => $data) {
					if (isset($data[$shardColumnName])) {//判断分表字段是否存在
						$shardColumnValue = $data[$shardColumnName];
						$shardList = $this->countShardSequence([$shardColumnName, $shardColumnValue], $this->dbShardRule);
						// 获取所有分片id
						$shardIds = array_keys($shardList);
						// 读取第一个分片id
						$shardId = $shardIds[0];
						$shardDb = $this->buildShardname($query->getDbkey(),$shardId);
						if (!isset($queryList[$shardDb])) {
							$queryList[$shardDb] = $query->cloneQuery(['dbkey'=>$shardDb,'data' => []]);
						}

						if ($query->getBuildMethod() == Query::BUILD_INSERT) {
							$queryList[$shardDb]->setData($data);
						} else {
							$queryList[$shardDb]->addData($data);
						}
					}
				}
			}
		}

		return $queryList;
	}


	/**
	 * 按表名分离(归类)添加数据
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param Query $query 命令对象
	 * @return Query[]
	 */
	public function dataShardByTable($query)
	{
		/** @var Query[] $queryList */
		$queryList = [];
		if (empty($this->tbShardRule)) {
			$queryList[$query->getTable()] = $query;
		} else {
			$shard_columns = $query->getShard();
			if (!empty($shard_columns) && isset($shard_columns[$this->tbShardRule->getFields()])) {
				$shard_value = $shard_columns[$this->tbShardRule->getFields()];
				$shardId = $this->tbShardRule->getShardId($this, $shard_value);
				$shardTable = $this->buildShardname($query->getTable(),$shardId);
				$queryList[$shardTable] = $query->cloneQuery(['table' => $shardTable]);
			} else {
				$shardColumnName = $this->tbShardRule->getFields();
				if ($query->getBuildMethod() == Query::BUILD_INSERT) {
					$addData[] = $query->getData();
				} else {
					$addData = $query->getData();
				}

				foreach ($addData as $field => $data) {
					if (isset($data[$shardColumnName])) {//判断分表字段是否存在
						$shardColumnValue = $data[$shardColumnName];
						$shardList = $this->countShardSequence([$shardColumnName, $shardColumnValue], $this->tbShardRule);
						// 获取所有分片id
						$shardIds = array_keys($shardList);
						// 读取第一个分片id
						$shardId = $shardIds[0];
						$shardTable = $this->buildShardname($query->getTable(),$shardId);
						if (!isset($queryList[$shardTable])) {
							$queryList[$shardTable] = $query->cloneQuery(['table' => $shardTable, 'data' => []]);
						}

						if ($query->getBuildMethod() == Query::BUILD_INSERT) {
							$queryList[$shardTable]->setData($data);
						} else {
							$queryList[$shardTable]->addData($data);
						}
					}
				}
			}
		}

		return $queryList;
	}

	/**
	 * 从where 条件中按分表归类
	 *<B>说明：</B>
	 *<pre>
	 *  分表字段从where 查找
	 *</pre>
	 * @param Query $query 命令对象
	 * @return Query[]
	 */
	public function whereShardByTable($query)
	{
		if (empty($this->tbShardRule)) {
			return [$query];
		}

		$queryList = [];
		$shard_columns = $query->getShard();
		if (!empty($shard_columns) && isset($shard_columns[$this->tbShardRule->getFields()])) {
			$shard_value = $shard_columns[$this->tbShardRule->getFields()];
			$shardId = $this->tbShardRule->getShardId($this, $shard_value);
			$shardTable = $this->buildShardname($query->getTable(),$shardId);
			$queryList[$shardTable] = $query->cloneQuery(['table' => $shardTable]);
		} else {
			$shardColumn = $this->getShardColumnByWhere($query, $this->tbShardRule);
			$shardList = $this->countShardSequence($shardColumn, $this->tbShardRule);
			foreach ($shardList as $shardId => $shardColumnWhere) {
				list($columnName, $columnWhere) = $shardColumnWhere;
				$shardTable = $this->buildShardname($query->getTable(),$shardId);
				$where = $query->getWhere();
				$where[$columnName] = $columnWhere;
				$queryList[$shardTable] = $query->cloneQuery(['table' => $shardTable, 'where' => $where]);
			}
		}

		return $queryList;
	}

	/**
	 * 从where 条件中按db归类归类
	 *<B>说明：</B>
	 *<pre>
	 *  分区字段从where 查找
	 *</pre>
	 * @param Query $query 命令对象
	 * @return Query[]
	 */
	public function whereShardByDb($query)
	{

		if (empty($this->dbShardRule)) {
			return [$query->getDbkey()=>$query];
		}

		$queryList = [];
		$shard_columns = $query->getShard();
		if (!empty($shard_columns) && isset($shard_columns[$this->dbShardRule->getFields()])) {
			$shard_value = $shard_columns[$this->dbShardRule->getFields()];
			$shardId = $this->dbShardRule->getShardId($this, $shard_value);
			$shardDb = $this->buildShardname($query->getDbkey(),$shardId);
			$queryList[$shardDb] = $query->cloneQuery(['dbkey'=>$shardDb]);
		} else {
			$shardColumn = $this->getShardColumnByWhere($query, $this->dbShardRule);
			$shardList = $this->countShardSequence($shardColumn, $this->dbShardRule);
			foreach ($shardList as $shardId => $shardColumnWhere) {
				list($columnName, $columnWhere) = $shardColumnWhere;
				$shardDb = $this->buildShardname($query->getDbkey(),$shardId);
				$where = $query->getWhere();
				$where[$columnName] = $columnWhere;
				$queryList[$shardDb] = $query->cloneQuery(['dbkey'=>$shardDb,'where' => $where]);
			}
		}

		return $queryList;
	}

	/**
	 * 从where 找出分区字段
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param Query $query 命令对象
	 * @param ShardRule $shardRule 分片规则
	 * @return array ['分区字段名','分区字段值']
	 */
	public function getShardColumnByWhere($query, $shardRule = null)
	{

		$whereList = $query->getWhere();
		$aliasList = $query->getAlias();
		$table = $query->getTable();

		if (isset($aliasList[$table])) {
			$tableAlias = $aliasList[$table];
		} else {
			$tableAlias = '';
		}

		$shardColumn = $shardRule->getFields();
		$columnName = '';
		$columnValue = '';

		if (!empty($tableAlias)) {
			foreach ($whereList as $whereColumn => $value) {
				if (strpos($whereColumn, '.') !== false) {
					// 判断是否有表别名引用
					list($columnTableAlias, $column) = explode('.', $whereColumn);
					// 表别名与字段名同时相同才能确认为分区字段
					if ($tableAlias == $columnTableAlias && $shardColumn == $column) {
						$columnValue = $value;
						$columnName = $whereColumn;
					}
				}
			}
		} else {
			if (isset($whereList[$shardColumn])) {
				$columnName = $shardColumn;
				$columnValue = $whereList[$shardColumn];
			}
		}

		return [$columnName, $columnValue];
	}


	/**
	 * 计算分片的序号
	 *<B>说明：</B>
	 *<pre>
	 *  可以分离出多张表,多个库
	 *</pre>
	 * @param array $column 字段 ['分片字段名','分片字段值']
	 * @param ShardRule $shardRule 分片规则
	 * @return array e.g [分片序号=>数据] 数据包括 字段名,字段值，比如[1=[name=>2323],2=[name=>2355]]
	 */
	protected function countShardSequence($column, $shardRule = null)
	{
		// 分片列表
		$shardList = [];
		list($columnName, $columnValue) = $column;

		if (is_array($columnValue)) {
			// 格式['in',[1,2,3,4]]
			//　操作符
			$operator = $columnValue[0];
			if (!is_array($operator)) {
				if (is_string($operator) && in_array($operator,[SqlQueryBuilder::EXP_OR,SqlQueryBuilder::EXP_AND])) {
					throw new \Exception('In the case of partitioning, the query criteria cannot be within a range');
				} else {
					if ($operator instanceof RawComparison) {
						throw new \Exception('In the case of partitioning, the query criteria cannot be within a RawComparison');
					} else if (isset(BaseQueryBuilder::$comparison[$operator])) {
						// （ps:['userId'=>['in',[1,2,3,4]]]),ps:['userId'=>['eq','1'])
						if (preg_match('/IN/i', $operator)) {
							$inShardList = [];
							// in 操作
							foreach ($columnValue[1] as $val) {
								$shardId = $shardRule->getShardId($this, $val);
								$inShardList[$shardId][] = $val;
							}
							foreach ($inShardList as $shardId => $value) {
								$shardList[$shardId] = [$columnName, ['in', $value]];
							}
						} else {
							// 按原来操作符
							$shardId = $shardRule->getShardId($this,  $columnValue[1]);
							$shardList[$shardId] = [$columnName, [$operator,  $columnValue[1]]];
						}
					} else {
						$inShardList = [];
						// in 操作
						foreach ($columnValue as $val) {
							$shardId = $shardRule->getShardId($this, $val);
							$inShardList[$shardId][] = $val;
						}
						foreach ($inShardList as $shardId => $value) {
							$shardList[$shardId] = [$columnName, ['in', $value]];
						}
					}
				}

			} else {
				// （ps:['age'=>[['gt',4],['lt','9']]])
				throw new \Exception('In the case of partitioning, the query criteria cannot be within a range');
			}
		} else {
			// 字符串等于操作
			$shardId = $shardRule->getShardId($this, $columnValue);
			$shardList[$shardId] = [$columnName, $columnValue];
		}

		return $shardList;
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

	// 分区序号
	protected function buildShardname($shardname,$shardIndex)
    {
        if (strpos($shardname,':shard') !== false) {
            $newshardname = str_replace(':shard', $shardIndex, $shardname);
        } else {
            // 未找到
            $newshardname = $shardname . $shardIndex;
        }

        return $newshardname;
    }

}

?>
