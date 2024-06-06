<?php
namespace horm\base;

use horm\Dbsession;

/**
 * 数据库连接基类
 *<B>说明：</B>
 *<pre>
 *  数据库连接基类，所有数据库连接都必须继承此类
 *</pre>
 */
abstract class BaseConnection
{

    /**
     * db 管理器
     *<B>说明：</B>
     *<pre>
     * 略
     *</pre>
     * @var Dbsession
     */
    protected $dbsession = null;

    /**
     * db key
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var string
     */
    public $dbKey = '';

	/**
	 * 数据库连接配置参数
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @var array
	 */
	public $config = [];

    /**
     * 反射连接方法的缓存
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @var array
     */
	protected static $ref_method_cache = [];

    /**
     * 构造方法
     *<B>说明：</B>
     *<pre>
     *  初始化数据库连接
     *</pre>
     * @param array $attrs 数据库连接配置
     */
    public function __construct($attrs = [])
    {
        if (!empty($attrs)) {
            foreach ($attrs as $name=>$value) {
                $this->$name = $value;
            }
        }
    }

    protected static function getMethodParams($dbconn_class,$method,$params = [])
    {

        $ref_params = [];
        if (!isset(static::$ref_method_cache[$method])) {
            $reflectionMethod = new \ReflectionMethod($dbconn_class, $method);
            foreach ($reflectionMethod->getParameters() as $parameter) {
                $name = $parameter->getName();
                $ref_params[$name] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
            }

            static::$ref_method_cache[$method] = $ref_params;
        } else {
            $ref_params = static::$ref_method_cache[$method];
        }

        $method_params = [];
        foreach ($ref_params as $name=>$val) {
            if (isset($params[$name])) {
                $method_params[] = $params[$name];
            } else {
                $method_params[] = $val;
            }
        }

        return $method_params;
    }

    /**
     * 获取表前缀
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->config['prefix'];
    }

    /**
     * 获取配置参数
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @param string|null 返回指定配置项对应的值,null表示返回全部参数
     * @return string
     */
    public function getConfig($name = null)
    {
        if (is_null($name)) {
            return $this->config;
        } else {
            return isset($this->config[$name]) ? $this->config[$name] : '';
        }
    }

    /**
     * 是否开启主从模式
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return boolean
     */
    public function isSlave()
    {
        return isset($this->config['onSlave']) ? $this->config['onSlave'] : false;
    }

    /**
     * 获取从库db key
     *<B>说明：</B>
     *<pre>
     *  略
     *</pre>
     * @return boolean
     */
    public function getSlaveDbkey()
    {
        if (empty($this->_slaveHandler)) {
            $this->_slaveHandler = $this->dbsession->buildSlaveHandler($this->config['slaveHandler']);
            if (empty($this->_slaveHandler)) {
                $this->_slaveHandler = [$this,'randSlave'];
            }
        }

        return call_user_func_array($this->_slaveHandler,[$this]);
    }

    protected function randSlave(BaseConnection $dbconn)
    {
        $slaves = $dbconn->getConfig("slaves");

        return $slaves[mt_rand(0,count($slaves) -1)];
    }

	/**
	 * 执行查询,返回数据行
	 *<B>说明：</B>
	 *<pre>
	 *  只执行查询的命令
	 *</pre>
	 * @param QueryCommand $queryCommand  sql 命令对象
	 * @return array|boolean
	 *<pre>
	 *  略
	 *</pre>
	 */
	abstract public function callQuery(QueryCommand $queryCommand);

	/**
	 * 执行更新sql语句
     *<B>说明：</B>
     *<pre>
     *  只执行更新，删除，插入等sql语句
     *</pre>
	 * @param QueryCommand $queryCommand sql命令对象
	 * @return int
	 */
    abstract public function callExecute(QueryCommand $queryCommand);

    /**
     * 开启事务
     */
    public function beginTransaction()
    {

    }

    /**
     * 提交事务
     */
    public function commitTransaction()
    {

    }

    /**
     * 回滚事务
     */
    public function rollbackTransaction()
    {

    }

    /**
     * 获取最后插入的自增id
     * @param string $sequence
     */
    public function getLastId($sequence = '')
    {

    }

	/**
	 * 获取名构建对象
	 *<B>说明：</B>
	 *<pre>
	 *  略
	 *</pre>
	 * @return BaseQueryBuilder
	 */
	public function getQueryBuilder(){}
}
