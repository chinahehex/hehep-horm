<?php
namespace  horm\base;

use horm\util\DbUtil;
use ReflectionClass;
use horm\Dbsession;
use horm\base\DbConnection;

/**
 * Query
 *<B>说明：</B>
 *<pre>
 * 	略
 *</pre>
 */
class Query
{

	const BUILD_SELECT = 'select';
	const BUILD_INSERT = 'insert';
	const BUILD_INSERTALL = 'insertAll';
	const BUILD_DELETE = 'delete';
	const BUILD_UPDATE = 'update';
	const BUILD_SCALAR = 'queryScalar';


    /**
     * db 管理器
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var Dbsession
     */
	protected $dbsession = null;

	/**
	 * db 名称
	 *<B>说明：</B>
	 *<pre>
	 *  数据库配置key
	 *</pre>
	 * @var string
	 */
	protected $dbkey = '';

	/**
	 * 表名
	 *<B>说明：</B>
	 *<pre>
	 *  不带前缀
	 *</pre>
	 * @var string
	 */
	protected $table = '';

	/**
	 * 读取字段列表
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var array
	 */
	protected $field = '';

	/**
	 * 数据行
	 *<B>说明：</B>
	 *<pre>
	 *  插入或修改的数据
	 *</pre>
	 * @var array
	 */
	protected $data = [];

    /**
     * 结果集处理
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var boolean|string|\
     */
	protected $class = null;

    /**
     * 实体类
     *<B>说明：</B>
     *<pre>
     *  实体类路径
     *</pre>
     * @var BaseEntity
     */
	protected $entity = null;

	/**
	 * 表别名列表
	 *<B>说明：</B>
	 *<pre>
	 *  格式:['表名'=>'别名',...]
	 *</pre>
	 * @var array
	 */
	protected $alias = '';

	/**
	 * 条件
	 *<B>说明：</B>
	 *<pre>
	 *  基本格式:['字段列名'=>['操作符',[1,2,3]],'字段列名'=>‘value’]
	 *  其他格式['or','字段列名'=>1,'字段列名'=>2]
	 *</pre>
	 * @var array
	 */
	protected $where = [];

	/**
	 * 连表
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var array
	 */
	protected $join = [];

    /**
     * 连表查询
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var array
     */
    protected $with = [];

	/**
	 * 分组
	 *<B>说明：</B>
	 *<pre>
	 *  基本格式:['分组列名1','分组列名1']
	 *</pre>
	 * @var array
	 */
	protected $group = [];

	/**
	 * 是否加锁
	 *<B>说明：</B>
	 *<pre>
	 *  查询或更新时是否加锁，行锁或表锁
	 *</pre>
	 * @var boolean
	 */
	protected $lock = false;

    /**
     * 是否返回数组
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var boolean
     */
	protected $isArray = true;

	/**
	 * 是否强制操作主库
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var boolean
	 */
	protected $isMaster = false;

	/**
	 * 排序规则
	 *<B>说明：</B>
	 *<pre>
	 *  基本格式:['排序字段'=>'降序或升序',...]
	 *</pre>
	 * @var array
	 */
	protected $order = [];

    /**
     * 格式化数据
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var array
     */
    protected $formats = [];

	/**
	 * 影响行数或读取条数
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var null|int
	 */
	protected $limit = null;

	/**
	 * 偏移量
	 *<B>说明：</B>
	 *<pre>
	 *  与limit　配合使用
	 *</pre>
	 * @var null|int
	 */
	protected $offset = null;

	/**
	 * 是否取消重复行
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var boolean
	 */
	protected $distinct = false;

	/**
	 * 分组查询条件
	 *<B>说明：</B>
	 *<pre>
	 *  请参考where 格式
	 *</pre>
	 * @var array
	 */
	protected $having = [];


    /**
     * 原始sql
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var array|string
     */
	protected $rawCmd;

	/**
	 * 分区列值
	 *<B>说明：</B>
	 *<pre>
	 *  用于分库分表
	 *</pre>
	 * @var array
	 */
	protected $shard = [];

	/**
	 * 自增序列
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var string
	 */
	protected $sequence = '';

	/**
	 * 联合查询
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var Query[]
	 */
	protected $union = [];

	/**
	 * 是否返回Query
	 *<B>说明：</B>
	 *<pre>
	 *  用于子查询或联合查询
	 *</pre>
	 * @var boolean
	 */
	protected $isQuery = false;

	/**
	 * 是否返回自增id
	 *<B>说明：</B>
	 *<pre>
	 *  用于单条数据插入
	 *</pre>
	 * @var boolean
	 */
	protected $isId = false;

    /**
     * 是否写操作
     *<B>说明：</B>
     *<pre>
     *  用于事务的启用
     *</pre>
     * @var boolean
     */
	protected $isWrite = false;

	/**
	 * build 方法
	 *<B>说明：</B>
	 *<pre>
	 *  生成sql 的方法，比如 QueryBuilder 的 select
	 *</pre>
	 * @var string
	 */
	protected $buildMethod = '';

	/**
	 * build 方法参数
	 *<B>说明：</B>
	 *<pre>
	 *  基本格式['参数1','参数2']
	 *</pre>
	 * @var string
	 */
	protected $buildParams = [];

	/**
	 * 绑定参数
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var array
	 */
	protected $params = [];

    /**
     * 执行sql返回结果
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var mixed
     */
	protected $result = null;

	/**
	 * 构建过程中的产生的临时数据
	 * @var array
	 */
	protected $build_results = [];

	/**
	 * 构造方法
	 *<B>说明：</B>
	 *<pre>
	 * 参数赋值
	 *</pre>
	 * @param array $options 参数
	 */
	public function __construct($options = [])
	{
		foreach ($options as $attr => $value) {
			$this->$attr = $value;
		}

		$this->_init();
	}


    /**
     * 格式化query 参数
     *<B>说明：</B>
     *<pre>
     *  将query 参数　转化标准格式
     *</pre>
     */
    protected function _init()
    {
        // 初始化表名
        list($table,$alias) = DbUtil::splitAlias($this->table);

        $this->table = $table;

        if ($alias !== '') {
            $this->alias = $alias;
        }

    }

	/**
	 * 获取表名
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * 设置表名
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param string $table
	 */
	public function setTable($table)
	{
		$this->table = $table;

		return $this;
	}

	/**
	 * 获取db 名称
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return string
	 */
	public function getDbkey()
	{
		return $this->dbkey;
	}

    /**
     * 设置db key
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param  string $dbkey
     */
	public function setDbKey($dbkey)
    {
        $this->dbkey = $dbkey;

		return $this;
    }

	/**
	 * 设置读取字段列
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param array $field
	 */
	public function setField($field)
	{
		$this->field = $field;

		return $this;
	}

    /**
     * 获取读取字段列
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return array
     */
    public function getField()
    {
        return $this->field;
    }

	public function setEntity(string $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }


	/**
	 * 获取更新数据
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * 添加更新数据
	 *<B>说明：</B>
	 *<pre>
	 * 主要用于插入语句
	 *</pre>
	 * @param array $data
	 */
	public function addData($data)
	{
		$this->data[] = $data;

		return $this;
	}

	public function setData($data)
    {
        $this->data = $data;

		return $this;
    }

	/**
	 * 获取表别名列表
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return array
	 */
	public function getAlias()
	{
		return $this->alias;
	}


	/**
	 * 获取查询条件
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return array
	 */
	public function getWhere()
	{
		return $this->where;
	}

	public function setWhere($where = [])
	{
		if (is_array($where)) {
			$this->where = array_merge($this->where,$where);
		} else {
			$this->where = array_merge([$this->where],$where);
		}

		return $this;
	}

	/**
	 * 获取锁
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return boolean
	 */
	public function getLock()
	{
		return $this->lock;
	}

	/**
	 * 获取排序规则
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return array
	 */
	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * 获取连表规则
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return array
	 */
	public function getJoin()
	{
		return $this->join;
	}

	public function addJoin($join)
    {
        return $this->join[] = $join;
    }

    public function getWith()
    {
        return $this->with;
    }

	/**
	 * 获取分组规则
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return array
	 */
	public function getGroup()
	{
		return $this->group;
	}

	/**
	 * 获取联合查询规则
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return Query[]
	 */
	public function getUnion()
	{
		return $this->union;
	}

	/**
	 * 添加联合查询
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param array
	 */
	public function addUnion($union)
	{
		if (empty($union)) {
			return ;
		}

		if (is_array($union)) {
			$this->union = array_merge($this->union,$union);
		} else {
			$this->union[] = $union;
		}
	}

	public function getSequence()
	{
		return $this->sequence;
	}

	public function asId()
	{
		return $this->isId;
	}

	/**
	 * 获取影响行数
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return int|null
	 */
	public function getLimit()
	{
		return $this->limit;
	}

	/**
	 * 获取偏移量
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return int|null
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * 获取取消重复行标识
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return boolean
	 */
	public function getDistinct()
	{
		return $this->distinct;
	}

	/**
	 * 获取分组条件规则
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return array
	 */
	public function getHaving()
	{
		return $this->having;
	}

	/**
	 * 获取绑定参数
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * 获取分区值
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return string
	 */
	public function getShard()
	{
		return $this->shard;
	}


	/**
	 * 设置构建类方法
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param string $method 构建类对应的方法名
	 * @param array $params　构建类方法参数
	 * @return $this
	 */
	public function setBuild($method,$params)
	{
		$this->buildMethod = $method;
		$this->buildParams = $params;

		return $this;
	}

	/**
	 * 获取构建类参数
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return array ['方法名','方法参数']
	 */
	public function getBuild()
	{
		return [$this->buildMethod,$this->buildParams];
	}

	/**
	 * 设置构建类方法
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param string $method 构建类对应的方法名
	 */
	public function setBuildMethod($method)
	{
		$this->buildMethod =  $method;

		return $this;
	}

	public function getBuildMethod()
    {
        return $this->buildMethod;
    }

	/**
	 * 设置构建类方法参数
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param array $params　QueryBuilder方法参数
	 */
	public function setBuildParams($params)
	{
		$this->buildParams =  $params;

		return $this;
	}

	/**
	 * 获取原始sql
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return string
	 */
	public function getRawCmd()
	{
		return $this->rawCmd;
	}

	/**
	 * 设置是否返回Query
	 *<B>说明：</B>
	 *<pre>
	 * 不执行
	 *</pre>
	 * @return static
	 */
	public function asQuery($isQuery = true):self
	{
		$this->isQuery = $isQuery;

		return $this;
	}

	/**
	 * 返回是否返回Query状态
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return boolean
	 */
	public function asQueryStatus():bool
	{
		return $this->isQuery;
	}

	/**
	 * 设置是否返回数组
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return boolean
	 */
	public function asArray($isArray = true):self
    {
        $this->isArray = $isArray;

		return $this;
    }

	/**
	 * 返回是否返回数组状态
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return boolean
	 */
	public function asArrayStatus():bool
	{
		return $this->isArray;
	}

	/**
	 * 设置是否强制操作主库
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return boolean
	 */
	public function asMaster($isMaster = true):self
	{
		$this->isMaster = $isMaster;

		return $this;
	}

	/**
	 * 返回是否强制操作主库状态
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return boolean
	 */
	public function asMasterStatus():bool
	{
		return $this->isMaster;
	}

	/**
	 * 设置原始命令
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param string $rawCmd 命令语句
	 * @param array $params 绑定参数
	 */
	public function setRawCommand($rawCmd,array $params = [])
	{
		$this->rawCmd = $rawCmd;
		$this->params = $params;

		return $this;
	}

    /**
     * 设置命令执行结果
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param mixed $result
     */
	public function setResult($result)
    {
        $this->result = $result;

		return $this;
    }

    /**
     * 获取命令执行结果
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return mixed
     */
    public function getResult()
    {
        $query_result = $this->getRawResult();
        // 查询
        if ($this->buildMethod === static::BUILD_SELECT) {
        	if (!empty($query_result)) {
				// format 事件
				foreach ($query_result as $key=>$res) {

				}
			}
        }

        return $query_result;
    }

	/**
	 * 获取命令的原始结果(未经过处理的数据)
	 * @return array|mixed|null
	 */
	public function getRawResult()
    {
        if ($this->isArray === true) {
            return $this->result;
        }

        if (!is_null($this->entity) && $this->buildMethod === static::BUILD_SELECT) {
            if (!empty($this->result)) {
                $entitys = [];
                foreach ($this->result as $row) {
					$entitys[] = $this->arrayToObject($row);
                }

                return $entitys;
            }
        }

		return $this->result;
    }

    /**
     * 数组转对象
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $rows
     * @return mixed
     */
    protected function arrayToObject($rows)
    {
		if (is_bool($this->entity)) {
			$stdClass = new \stdClass();
			foreach ($rows as $attr=>$value) {
				$stdClass->$attr = $value;
			}

			return $stdClass;
		} else if (is_string($this->entity)) {
			/** @var BaseEntity $className **/
			$className = $this->entity;
			$rows = $className::makeByColumn($rows);
		}

		return $rows;
	}

	/**
	 * 克隆当前Query
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param array $options
	 * @return Query
	 */
	public function cloneQuery(array $options = []):Query
	{
		$class = new ReflectionClass($this);
		$attrs = [];
		foreach ($class->getProperties() as $property) {
			if (!$property->isStatic()) {
				$attrs[] = $property->getName();
			}
		}

		$values = [];
		foreach ($attrs as $attr) {
			$values[$attr] = $this->$attr;
		}

		$values = array_merge($values,$options);

		return new static($values);
	}

	/**
	 * 设置是否写操作
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param boolean $isWrite
	 * @return static
	 */
	public function asWrite(bool $isWrite = true):self
	{
		$this->isWrite = $isWrite;

		return $this;
	}

	/**
	 * 返回是否写操作状态
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @return boolean
	 */
	public function asWriteStatus():bool
	{
		return $this->isWrite;
	}

    /**
     * 获取当前db连接对象
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return DbConnection
     */
    public function getDb()
    {
        $dbConn = null;
        if (!empty($this->entity) && $this->asWriteStatus() == false && !$this->asMasterStatus()) {
            $dbslave = $this->entity::dbSlave();
            if (!empty($dbslave)) {
                $dbConn = $this->dbsession->getDb($this,$dbslave,true);
            }
        }

        if (empty($dbConn)) {
            $dbConn = $this->dbsession->getDb($this,$this->getDbkey());
        }

        return $dbConn;
    }

    public function getQueryBuilder():BaseQueryBuilder
	{
		return $this->getDb()->getQueryBuilder();
	}

	public function setBuildResult($key,$result)
	{
		$this->build_results[$key][] = $result;
	}

	public function getBuildResult($key = '')
	{
		if (!empty($key)) {
			if (isset($this->build_results[$key])) {
				return $this->build_results[$key];
			} else {
				return null;
			}
		} else {
			return $this->build_results;
		}
	}

	/**
	 * 构建命令
	 *<B>说明：</B>
	 *<pre>
	 *  将query参数转化标准格式
	 *</pre>
	 * @return QueryCommand
	 */
	public function buildQueryCommand():QueryCommand
	{
		$dbConn = $this->getDb();
		$querycmd = $dbConn->getQueryBuilder()->buildQueryCommand($this);

		return $querycmd;
	}

	/**
	 * 构建原始命令
	 *<B>说明：</B>
	 *<pre>
	 *  将query参数转化标准的命令格式
	 *</pre>
	 * @return string
	 */
	public function toRawCommand()
	{
		$dbConn = $this->getDb();
		$querycmd = $dbConn->getQueryBuilder()->buildQueryCommand($this);
		$raw_cmd = $querycmd->toRawCommand();

		return $raw_cmd;
	}

}

?>
