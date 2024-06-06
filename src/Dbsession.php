<?php
namespace horm;

use horm\base\BaseConnection;
use horm\base\QueryCommand;
use horm\base\DbConnection;
use horm\base\Query;
use horm\base\SaleLocal;
use horm\base\Transaction;
use horm\base\TransactionGroup;
use Exception;
use horm\pools\ConnectionPool;

/**
 * db 管理器
 *<B>说明：</B>
 *<pre>
 *	略
 *</pre>
 * @property bool $transStatus 事务状态
 * @property TransactionGroup $transactionGroup 事务状态
 * @property DbConnection $dbconn 最后执行sql 的db 连接
 * @property string $lastcommand 最后执行命令语句
 *
 * @method QueryTable setScope($scope,...$args)
 * @method QueryTable setShard($shard_columns = [])
 * @method QueryTable setField($fields = [])
 * @method QueryTable setWhere($where = [], $params = [])
 * @method QueryTable setTable($table = '')
 * @method QueryTable setAlias($alias = '')
 * @method QueryTable setJoin($table, $on, $joinType = '')
 * @method QueryTable setLeftJoin($table, $on)
 * @method QueryTable setInnerJoin($table, $on)
 * @method QueryTable setWith($with,$join = false,$load = true)
 * @method QueryTable setLeftWith($with,$load = true)
 * @method QueryTable setInnerWith($with,$load = true)
 * @method QueryTable setOrder($order = [])
 * @method QueryTable setGroup($group = [])
 * @method QueryTable setLimit($length = null)
 * @method QueryTable setOffset($offset = null)
 * @method QueryTable setParam($params = null)
 * @method QueryTable asArray($asArray = true)
 * @method QueryTable asQuery($asQuery = true)
 * @method QueryTable asMaster($asMaster = true)
 * @method QueryTable asId($asId = true)
 * @method QueryTable queryCmd($queryCommand, $params = [])
 * @method QueryTable execCmd($queryCommand, $params = [])
 * @method QueryTable addParams($params = [])
 * @method QueryTable count($field = null,$where = [])
 * @method QueryTable queryMax($field = null, $where = [])
 * @method QueryTable queryMin($field = null, $where = [])
 * @method QueryTable querySum($field = null, $where = [])
 * @method QueryTable queryAvg($field = null, $where = [])
 *
 */
class Dbsession
{
    /**
     * 线程安全类
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var SaleLocal
     */
	protected $saleLocal = '';

	protected $_saleLocal = null;

    /**
     * 默认db key
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
	public $dbkey = 'hehe';

	/**
	 * 数据库配置
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var array
	 */
	public $dblist = [];

    /**
     * 数据库连接池
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var ConnectionPool[]
     */
	protected $dbConnectionPools = [];

	/**
	 * 错误消息列表定义
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var array
	 */
	public $messages = [
		'db_non_existent'=>'{dbkey}数据库配置不存在',
		'db_class_undefined'=>"未定义DB驱动类:{dbclass}",
		'query_where_empty'=>'删除操作情况下，删除条件不能为空,必须设置',
	];

	/**
	 * 构造方法
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param array $attrs 配置参数
	 */
	public function __construct($attrs = [])
	{
		if (!empty($attrs)) {
			foreach ($attrs as $attr=>$value) {
				$this->$attr = $value;
			}
		}

        $this->_saleLocal = $this->createSaleLocal();
	}

    /**
     * 获取当前数据库连接对象
     *<B>说明：</B>
     *<pre>
     * 每次数据库操作之前都必须调用此方法选择对应的数据库db
     *</pre>
     * @param Query $query query 对象
     * @param string $dbkey 数据库连接key
     * @param bool $force 是否强制使用指定的dbkey连接
     * @return DbConnection
     */
    public function getDb(Query $query,string $dbkey = '',$force = false):BaseConnection
    {
    	// 主库连接
        $dbconn = $this->getDbConnection($dbkey);

        # 读写分离
		if ($force === false && $query->asWriteStatus() == false && $dbconn->isSlave() && !$query->asMasterStatus()) {
			$dbconnkey = $dbconn->getSlaveDbkey();
			$dbconn = $this->getDbConnection($dbconnkey);
		}

        // 如果是更新操作，则将当前的连接加入事务池
        if ($query->asWriteStatus() === true) {
            $this->addTransDb($dbkey);
        }

        // 记录最后操作的db连接
        $this->dbconn = $dbconn;

        return $dbconn;
    }

	/**
	 * 获取一个数据库连接
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param string $dbkey 数据库连接key
	 * @return DbConnection
	 */
	public function getDbConnection(string $dbkey = ''):BaseConnection
	{

        $connectionPool = $this->getConnectionPool($dbkey);
		$dbConnection = $connectionPool->getConnection();
		if (is_null($dbConnection)) {
			throw new Exception($this->formatMessage('db_non_existent',['dbkey'=>$dbkey]));
		}

        return $dbConnection;
	}

	/**
	 * 获取一个数据库的连接池
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param string $dbkey 数据库连接key
	 * @return ConnectionPool
	 */
	protected function getConnectionPool(string $dbkey):ConnectionPool
	{
		if (isset($this->dbConnectionPools[$dbkey])) {
            return $this->dbConnectionPools[$dbkey];
		}

		if (!isset($this->dblist[$dbkey])) {
			throw new Exception($this->formatMessage('db_non_existent',['dbkey'=>$dbkey]));
		}

        $dbconf = $this->dblist[$dbkey];
        $this->dbConnectionPools[$dbkey] = $this->makeConnectionPool($dbkey,$dbconf);

        return $this->dbConnectionPools[$dbkey];
	}

	/**
	 * 获取一个数据库的事务组对象
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @return TransactionGroup
	 */
	protected function getTransactionGroup():TransactionGroup
	{
		$transactionGroup = $this->transactionGroup;
		if (!empty($transactionGroup)) {
            return $transactionGroup;
		}

        $this->transactionGroup = new TransactionGroup();

		return $this->transactionGroup;
	}

    /**
     * 获取指定db连接的事务对象
     *<B>说明：</B>
     *<pre>
     * 	1、开启事务之前，自动回提交之前的事务
     * 	2、只作用与实体类当前连接的数据库事务
     *</pre>
	 * @param string $dbkey 为指定db key,则读取默认数据库key
     * @return Transaction
     */
	public function getTransaction(string $dbkey = ''):Transaction
	{
		if ($dbkey == '') {
			$dbkey = $this->dbkey;
		}

        $db = $this->getDbConnection($dbkey);

        return new Transaction($db);
	}


	/**
	 * 启动事务
	 *<B>说明：</B>
	 *<pre>
	 * 	1、开启事务之前，自动回提交之前的事务
	 * 	2、只作用与实体类当前连接的数据库事务
	 *</pre>
	 * @return boolean
	 */
	public function beginTransaction():bool
	{
        $this->getTransactionGroup()->beginTransaction();
		$this->transStatus = true;

		return true;
	}

	/**
	 * 添加事务db
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param string $dbkey 数据库db key
	 * @return void
	 */
	protected function addTransDb(string $dbkey = ''):void
	{
		if ($this->transStatus === true) {//如果事务状态已经开启，则自动开启db 事务
			if (!$this->getTransactionGroup()->hasTransaction($dbkey)) {
                $this->getTransactionGroup()->addTransaction($dbkey,$this->getTransaction($dbkey));
			}
		}
	}


	/**
	 * 提交事务
	 *<B>说明：</B>
	 *<pre>
	 *  事务提交，清空当前事务相关数据，状态，事务db
	 *</pre>
	 * @return boolean false 只要其中一个事务提交失败,true 全部数据库事务提交成功
	 */
	public function commitTransaction():bool
	{
        $result = $this->getTransactionGroup()->commitTransaction();
        // 清空事务db
        $this->clearTransaction();

        return $result;
	}

	/**
	 * 回滚事务
	 *<B>说明：</B>
	 *<pre>
	 *  事务回滚，清空当前事务相关数据，状态，事务db
	 *</pre>
	 * @return boolean false:只要其中一个事务提交失败,true:全部数据库事务提交成功
	 */
	public function rollbackTransaction():bool
	{
        $result = $this->getTransactionGroup()->rollbackTransaction();
		// 清空事务db
		$this->clearTransaction();

		return $result;
	}

	/**
	 * 清空事务相关参数
	 *<B>说明：</B>
	 *<pre>
	 *  每次提交，回滚事务，都必须调用此方法清空
	 *</pre>
	 * @return void
	 */
	protected function clearTransaction():void
	{
		$this->transStatus = null;
		$this->transactionGroup = null;
	}

	/**
	 * 检测是否开启事务
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @return boolean
	 */
	public function hasBeginTransaction():bool
	{
		if (!empty($this->transStatus)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 添加sql
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param QueryCommand $queryCommand
	 * @return void
	 */
	public function addQueryCommand(QueryCommand $queryCommand)
	{
		$this->lastcommand = $queryCommand->toRawCommand();
	}

    /**
     * 获取最后插入sql的自增id
     *<B>说明：</B>
     *<pre>
     * 如果自增id
     * 如果开启自动生成序号，则返回最后产生的序号
     *</pre>
	 * @param string $sequence 序列
     * @return mixed 最后插入sql自增id
     */
    public function getLastId($sequence = '')
    {
    	if ($this->dbconn === null) {
    		return null;
		}

        return $this->dbconn->getLastId($sequence);
    }

	/**
	 * 获取最后一条命令
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @return string 命令语句
	 */
	public function getLastCmd()
	{
		return $this->lastcommand;
	}

    /**
     * 创建数据库连接实例
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $dbkey
     * @param array $dbconf;
     * @return DbConnection
     * @throws Exception
     */
    public function makeDbConnection(string $dbkey,array $dbconf):BaseConnection
    {

        $dbClass = $this->buildConnectionClass($dbconf);

        // 检查驱动类
        if (!class_exists($dbClass)) {
            throw new Exception($this->formatMessage('db_class_undefined',['dbclass'=>$dbClass]));
        }

        $dbconn = new $dbClass(['dbKey'=>$dbkey,'config'=>$dbconf,'dbsession'=>$this]);

        return $dbconn;
    }

	/**
	 * 根据配置生成数据库连接类路径
	 *<B>说明：</B>
	 *<pre>
	 * 略
	 *</pre>
	 * @param array $dbconf;
	 * @return DbConnection
	 * @throws Exception
	 */
    protected function buildConnectionClass(array $dbconf = []):string
    {

        $dbType = $dbconf['driver'];

        if (strpos($dbType,'\\') !== false) {
            $db_class =  $dbType;
        } else {
			$dbname = $dbType;
        	if (isset($dbconf['type'])) {
                $dbname = $dbconf['type'];
			}

			$db_class =  __NAMESPACE__ . '\\drivers\\' . $dbType .'\\' .ucfirst($dbname). 'DbConnection';
        }

        return $db_class;
    }

    /**
     * 创建数据库连接池
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
	 * @param string $dbkey db key
     * @param array $dbconf db 配置
     * @return ConnectionPool
     */
    protected function makeConnectionPool(string $dbkey,array $dbconf):ConnectionPool
    {
        if (isset($dbconf['pool'])) {
            $poolconf = $dbconf['pool'];
        } else {
            $poolconf = [];
        }

        $poolType = 'process';
        if (isset($poolconf['class'])) {
            $poolType = $poolconf['class'];
            unset($poolconf['class']);
        }

        if (strpos($poolType,'\\') !== false) {
            $poolClass =  $poolType;
        } else {
            $poolClass =  __NAMESPACE__ . '\\pools\\' .ucfirst($poolType). 'ConnectionPool';
        }

		$poolconf['dbsession'] = $this;
		$poolconf['dbconf'] = $dbconf;
		$poolconf['dbKey'] = $dbkey;

        return new $poolClass($poolconf);
    }

	/**
	 * 解析模板消息
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param string $errorCode 消息模板id
	 * @param array $data 替换数据
	 * @return string
	 */
	public function formatMessage(string $errorCode = '',array $data = [])
	{

		if (!isset($this->messages[$errorCode])) {
			return '';
		}

		$message = $this->messages[$errorCode];
		if (!empty($data)) {
			$find = array_map(function($value){
				return '{' . $value . '}';
			},array_keys($data));

			$replace = array_values($data);
			$message = str_replace($find,$replace,$message);
		}

		return $message;
	}

    public function createSaleLocal()
	{
        if (!empty($this->saleLocal)) {
        	$saleLocalClazz = $this->saleLocal;
            return new $saleLocalClazz();
        } else {
            return new SaleLocal();
        }
	}

	public function buildSlaveHandler($handler)
	{

		if (is_array($handler) || $handler instanceof \Closure) {
			return $handler;
		} else if (is_string($handler)) {
			if (strpos($handler,"@@") !== false) {
				list($handleClass,$handleMethod) = explode("@@",$handler);
				return [$handleClass,$handleMethod];
			} else if (strpos($handler,"@") !== false) {
				list($handleClass,$handleMethod) = explode("@",$handler);
				return [$handleClass(),$handleMethod];
			}
		}

		return null;
	}

    /**
     * 创建表query
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
	 * @param string $dbkey db key
     * @param string $queryTableClass 表
     * @return QueryTable
     */
	protected function getQueryTable(string $dbkey = '',string $queryTableClass = QueryTable::class):QueryTable
	{
		/** @var QueryTable $queryTable**/
        $queryTable = new $queryTableClass();

        if (empty($dbkey)) {
            $dbkey = $this->dbkey;
		}

        $queryTable->setDbkey($dbkey);
        $queryTable->setDbsession($this);

        return $queryTable;
	}

	/**
	 * 切换数据库
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param string $dbkey 数据库key
	 * @return QueryTable
	 */
	public function query(string $dbkey = ''):QueryTable
	{
		return $this->getQueryTable($dbkey);
	}

	/**
	 * 添加数据库连接配置
	 * @param string $dbkey
	 * @param array $db_conf
	 * @return static
	 */
	public function addDb(string $dbkey = '',array $db_conf = []):self
	{
		$this->dblist[$dbkey] = $db_conf;

		return $this;
	}

	/**
	 * 设置默认db key
	 * @param $dbkey
	 * @return static
	 */
	public function setDb(string $dbkey):self
	{
		$this->dbkey = $dbkey;

		return $this;
	}

	/**
	 * 常用单元测试
	 * @return void
	 */
	public function cleanAll():void
	{
		foreach ($this->dbConnectionPools as $connectionPool) {
			$connection = $connectionPool->getConnection();
			$connection->close();
			unset($connection);
		}
	}

	public function __get($name)
	{
		return $this->_saleLocal->getAttribute($name);
	}

	public function __set($name, $value)
	{
		$this->_saleLocal->setAttribute($name,$value);
	}

	public function __call($method,$args)
	{
		return call_user_func_array([$this->getQueryTable(), $method], $args);
	}

}

?>
