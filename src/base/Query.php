<?php
namespace  horm\base;

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
	 * 是否强制从主库读取
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
     *  []
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
     * @var array
     */
	protected $rawSql;

	/**
	 * 指定分区值
	 *<B>说明：</B>
	 *<pre>
	 *  用于分库分表
	 *</pre>
	 * @var array
	 */
	protected $shard = [];

	/**
	 * 是否使用replace方式插入sql
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var boolean
	 */
	protected $replace = false;

	/**
	 * 自增序列
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var array
	 */
	protected $seq = '';

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
        list($table,$alias) = $this->_separateTableName($this->table);

        $this->setTable($table);
        if ($alias !== '') {
            $this->addAlias([$table=>$alias]);
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
	}

	public function setData($data)
    {
        $this->data = $data;
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
	 * 添加表别名
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param array $alias
	 */
	public function addAlias($alias)
	{
		$this->alias = array_merge($this->alias,$alias);
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

	public function getSeq()
	{
		return $this->seq;
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
	 * 获取是否使用replace方式插入sql 标识
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return boolean
	 */
	public function getReplace()
	{
		return $this->replace;
	}


	/**
	 * 设置构建sql 方法
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param string $method build 方法名
	 * @param array $params　build 方法参数
	 * @return $this
	 */
	public function setBuild($method,$params)
	{
		$this->buildMethod = $method;
		$this->buildParams = $params;

		return $this;
	}

	/**
	 * 获取构建sql 方法
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
	 * 设置构建sql 方法
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param string $method build 方法名
	 */
	public function setBuildMethod($method)
	{
		$this->buildMethod =  $method;
	}

	public function getBuildMethod()
    {
        return $this->buildMethod;
    }

	/**
	 * 设置构建sql 方法参数
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param array $params　build 方法参数
	 */
	public function setBuildParams($params)
	{
		$this->buildParams =  $params;
	}

    /**
     * 获取原始sql
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return string
     */
	public function getRawSql()
    {
        return $this->rawSql;
    }

	/**
	 * 判断where　是否为空
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @return boolean true 表示where 为空
	 */
	public function isEmptyWhere()
	{
		if (empty($this->where) === true) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 是否生成Query
	 *<B>说明：</B>
	 *<pre>
	 * 不执行
	 *</pre>
	 * @return boolean
	 */
	public function isQuery()
	{
		return $this->isQuery;
	}

	public function asArray()
    {
        return $this->isArray;
    }

    public function asMaster()
	{
		return $this->isMaster;
	}

	/**
	 * 设置原始命令
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param string $rawSql sql语句
	 * @param array $params 绑定参数
	 */
	public function setCommand($rawSql,$params)
	{
		$this->rawSql = $rawSql;
		$this->params = $params;
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
    }


    public function getStdClass($data)
    {
        $stdClass = new \stdClass();
        foreach ($data as $attr=>$value) {
            $stdClass->$attr = $value;
        }

        return $stdClass;
    }

    /**
     * 获取命令执行结果
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @return  mixed
     */
    public function getResult()
    {
        $result = $this->getRawResult();
        // 查询
        if ($this->buildMethod === static::BUILD_SELECT) {
            // format 事件
            foreach ($result as $key=>$res) {

            }
        }

        return $result;
    }

    protected function getRawResult()
    {
        if ($this->isArray === true) {
            return $this->result;
        }

        if (!is_null($this->entity) && $this->buildMethod === static::BUILD_SELECT) {
            if (!empty($this->result)) {
                $objs = [];
                foreach ($this->result as $row) {
                    $objs[] = $this->columnToAttr($row);
                }

                return $objs;
            }
        }

        return $this->result;
    }

    /**
     * 格式化查询数据
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param array $row
     * @return  mixed
     */
    protected function columnToAttr($row)
    {
        if (is_bool($this->entity)) {
            $row = $this->getStdClass($row);
        } else if (is_string($this->entity)) {
            /** @var BaseEntity $className **/
            $className = $this->entity;
            $row = $className::makeByColumn($row);
        }

        return $row;
    }

	/**
	 * 克隆当前　Query
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param array $options
	 * @return Query
	 */
	public function cloneQuery($options)
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
	 * 分离带as的表名
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param string $tablename 表名 比如user as name
	 * @return array
	 * array('表名','表别名')
	 */
	protected function _separateTableName($tablename = '')
	{
		$preg =  '/^(\w+)\s+AS\s+(\w+)\s*.*/i';
		preg_match ($preg, $tablename,$table);
		if (empty($table)) {
			return [$tablename,''];
		} else {
			return [$table[1],$table[2]];
		}
	}


    /**
     * 是否写操作
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param boolean $toUpdate
     */
    public function toUpdate($toUpdate = true)
    {
        $this->isWrite = $toUpdate;
    }



    /**
     *  获取当前db 连接 对象
     *<B>说明：</B>
     *<pre>
     *  将query 参数　转化标准格式
     *</pre>
     * @return DbConnection
     */
    public function getDb()
    {
        $dbConn = null;
        if (!empty($this->entity) && $this->isWrite == false && !$this->asMaster()) {
            $dbslave = $this->entity::dbSlave();
            if (!empty($dbslave)) {
                $dbConn = $this->dbsession->getDb($this,$this->isWrite,$dbslave,true);
            }
        }

        if (empty($dbConn)) {
            $dbConn = $this->dbsession->getDb($this,$this->isWrite,$this->getDbkey());
        }

        return $dbConn;
    }

    /**
     * 构建命令
     *<B>说明：</B>
     *<pre>
     *  将query 参数　转化标准格式
     *</pre>
     * @return QueryCommand
     */
	public function buildParamsCommand()
    {
        $dbConn = $this->getDb();

        return $dbConn->getQueryBuilder()->buildParamsCommand($this);
    }

    /**
     * 构建原始命令
     *<B>说明：</B>
     *<pre>
     *  直接执行sql语句
     *</pre>
     * @return QueryCommand
     */
    public function buildRawCommand()
    {
        $dbConn = $this->getDb();

        return $dbConn->getQueryBuilder()->buildRawCommand($this);
    }

    public function getQueryBuilder():BaseQueryBuilder
	{
		return $this->getDb()->getQueryBuilder();
	}

	/**
	 * 构建自增序列名
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @return string
	 */
    public function buildLastIdSequence()
	{
		$sequence = '';
		if (is_string($this->seq) && !empty($this->seq)) {

		} else {
			$sequence = '';
		}
	}

	/**
	 * 分离带as表名
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param string $tablename 表名 比如user as name 、user
	 * @return array  表分离后的数组
	 *<pre>
	 *   $out = ['user','u'] user表名,u 表别名
	 *</pre>
	 */
	protected function splitTableName($tablename = '')
	{
		$preg =  '/^(.+)\s+AS\s+(.+)\s*.*/i';
		preg_match ($preg, $tablename,$out);

		if (empty($out)) {
			return [$tablename,''];
		}

		return [$out[1],$out[2]];
	}



}

?>
