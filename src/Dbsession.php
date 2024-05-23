<?php
namespace horm;

use horm\base\BaseEntity;
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
 */
class Dbsession
{
    /**
     * 线程安全
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
	public $defaultDbkey = 'hehe';

	/**
	 * 全部数据库配置
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var array
	 */
	public $dbconf = [];

	/**
	 * 数据库连接池
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var DbConnection[] 数据库连接对象列表
	 */
	protected $dbConnections = [];

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
		'db_class_undefined'=>"未定义DB驱动类:{dbclass}"
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
     * @param boolean $isWrite 是否写
     * @param string $dbkey 数据库连接key
     * @param bool $force 是否强制连接
     * @return DbConnection
     */
    public function getDb(Query $query,bool $isWrite = true,string $dbkey = '',$force = false):DbConnection
    {
    	// 主库连接
        $dbconn = $this->getDbConnection($dbkey);

        # 读写分离
		if ($force === false && $isWrite == false && $dbconn->isSlave() && !$query->asMaster()) {
			$dbconnkey = $dbconn->getSlaveDbkey();
			$dbconn = $this->getDbConnection($dbconnkey);
		}

        // 如果是更新操作，则将当前的连接加入事务池
        if ($isWrite === true) {
            $this->addTransDb($dbkey);
        }

        $this->dbconn = $dbconn;

        return $dbconn;
    }

	/**
	 * 获取一个数据库连接
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param string $dbKey 数据库连接key
	 * @return DbConnection
	 */
	public function getDbConnection(string $dbKey = ''):DbConnection
	{

        $connectionPool = $this->getConnectionPool($dbKey);

        return $connectionPool->getConnection();
	}

	protected function getConnectionPool(string $dbKey):ConnectionPool
	{
		if (isset($this->dbConnectionPools[$dbKey])) {
            return $this->dbConnectionPools[$dbKey];
		}

		if (!isset($this->dbconf[$dbKey])) {
			throw new Exception($this->formatMessage('db_non_existent',['dbkey'=>$dbKey]));
		}

        $dbconf = $this->dbconf[$dbKey];
        $this->dbConnectionPools[$dbKey] = $this->makeConnectionPool($dbKey,$dbconf);

        return $this->dbConnectionPools[$dbKey];
	}

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
     * 获取事务
     *<B>说明：</B>
     *<pre>
     * 	1、开启事务之前，自动回提交之前的事务
     * 	2、只作用与实体类当前连接的数据库事务
     *</pre>
	 * @param string $dbKey
     * @return Transaction
     */
	public function getTransaction(string $dbKey):Transaction
	{
        $db = $this->getDbConnection($dbKey);

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
	 * @param string $dbConnKey 数据库连接配置键名
	 * @return void
	 */
	public function addTransDb(string $dbConnKey = ''):void
	{
		if ($this->transStatus === true) {//如果事务状态已经开启，则自动开启db 事务
			if (!$this->getTransactionGroup()->hasTransaction($dbConnKey)) {
                $this->getTransactionGroup()->addTransaction($dbConnKey,$this->getTransaction($dbConnKey));
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
        $result = $this->getTransactionGroup()->commit();
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
        $result = $this->getTransactionGroup()->rollback();
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
	private function clearTransaction():void
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
	 * @param QueryCommand $command
	 * @return void
	 */
	public function addQueryCommand(QueryCommand $command)
	{
		$this->lastcommand = $this->replaceSqlValue($command->buildCommand(),$command->getParams());
	}

	/**
	 * 构建sql
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param Query $query sql 语句
	 * @return string
	 */
	public function buildSql(Query $query):string
	{
        $command = $query->buildParamsCommand();

		return $this->replaceSqlValue($command->buildCommand(),$command->getParams());
	}

	/**
	 * 构建sql
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @param QueryCommand $command
	 * @return string
	 */
	public function buildSqlByCommand(QueryCommand $command):string
	{
		return $this->replaceSqlValue($command->buildCommand(),$command->getParams());
	}

	/**
	 * 替换sql 参数
	 *<B>说明：</B>
	 *<pre>
	 *  	1、替换sql 语句中预处理标识
	 *</pre>
	 * @param string $query sql 指令
	 * @param array $params 绑定参数
	 * @return string sql 语句
	 */
	private  function replaceSqlValue(string $query = '', array $params = []):string
	{
		$keys = array();
		$values = array();

		# build a regular expression for each parameter
		foreach ($params as $key=>$value)
		{
			if (is_string($key)) {
				if (0 !== strpos($key,':')) {
					$keys[] = '/:'.$key.'/';
				} else {
					$keys[] = '/'.$key.'/';
				}

			} else {
				$keys[] = '/[?]/';
			}

			if (is_numeric($value)) {
				$values[] = intval($value);
			} else if (is_array($value)) {
				if ($value[1] == \PDO::PARAM_INT) {
					$values[] = $value[0] ;
				} else if ($value[1] == \PDO::PARAM_STR) {
					$values[] = '"'.$value[0] .'"';
				} else {
					$values[] = '"'.$value[0] .'"';
				}
			} else {
				$values[] = '"'.$value .'"';
			}
		}

		$sql2 = preg_replace($keys, $values, $query, 1, $count);

		return $query . "\n" . $sql2;
	}

    /**
     * 获取最后插入sql的自增id
     *<B>说明：</B>
     *<pre>
     * 如果自增id
     * 如果开启自动生成序号，则返回最后产生的序号
     *</pre>
     * @return string 最后插入sql自增id
     */
    public function getLastId()
    {
    	if ($this->dbconn === null) {
    		return null;
		}

        return $this->dbconn->getLastInsertID();
    }

	/**
	 * 获取最后一条sql
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @return string sql 语句
	 */
	public function getLastCommand()
	{
		return $this->lastcommand;
	}

	public function getLastSql()
	{
		return $this->lastcommand;
	}

    /**
     * 获取数据库类实例
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @param string $dbKey
     * @param array $dbconf;
     * @return DbConnection 返回数据库驱动类
     * @throws Exception
     */
    public function makeDbConnection(string $dbKey,array $dbconf):DbConnection
    {

        $dbClass = $this->buildConnectionClass($dbconf);

        // 检查驱动类
        if (!class_exists($dbClass)) {
            throw new Exception($this->formatMessage('db_class_undefined',['dbclass'=>$dbClass]));
        }

        $dbconn = new $dbClass(['dbKey'=>$dbKey,'config'=>$dbconf,'dbsession'=>$this]);

        return $dbconn;
    }

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
	 * @param string $dbKey db key
     * @param array $dbconf db 配置
     * @return ConnectionPool
     */
    public function makeConnectionPool(string $dbKey,array $dbconf):ConnectionPool
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
		$poolconf['dbKey'] = $dbKey;

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

    public function __get($name)
    {
		return $this->_saleLocal->getAttribute($name);
    }

    public function __set($name, $value)
    {
        $this->_saleLocal->setAttribute($name,$value);
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
	public function query(string $dbkey = '',string $queryTableClass = QueryTable::class):QueryTable
	{
		/** @var QueryTable $queryTable**/
        $queryTable = new $queryTableClass();

        if (empty($dbkey)) {
            $dbkey = $this->defaultDbkey;
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
	 * @param string $queryTableClass 表
	 * @return QueryTable
	 */
	public function select(string $dbkey = '',string $queryTableClass = QueryTable::class):QueryTable
	{
		/** @var QueryTable $queryTable**/
		$queryTable = new $queryTableClass();

		if (empty($dbkey)) {
			$dbkey = $this->defaultDbkey;
		}

		$queryTable->setDbkey($dbkey);
		$queryTable->setDbsession($this);

		return $queryTable;
	}

	/**
	 * 添加db配置
	 * @param string $db_key
	 * @param array $db_conf
	 * @return $this
	 */
	public function addDbconf(string $db_key = '',array $db_conf = []):self
	{
		$this->dbconf[$db_key] = $db_conf;

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

}

?>
